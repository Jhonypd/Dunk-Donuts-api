<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\DTO\ProdutoDTO;
use Application\DTO\ProdutoAlterarDTO;
use Application\DTO\ProdutoDeletarDTO;
use Application\Service\ProdutoService;
use Application\Controller\BaseController;
use Laminas\Http\Request;


class ProdutoController extends BaseController
{
    private $service;

    public function __construct(ProdutoService $service)
    {
        $this->service = $service;
    }

    public function listarAction()
    {
        $request = $this->validateRequest('GET');
        $dados = $this->getHeader($request, required: true, name: 'incluirInativos');


        return [
            'success' => true,
            'resultado' => $this->service->listar(incluirInativos: filter_var($dados['incluirInativos'] ?? false, FILTER_VALIDATE_BOOLEAN)),
        ];
    }
    public function criarAction()
    {
        $request = $this->validateRequest('POST');

        if (! $request instanceof Request) {
            return [
                'success' => false,
                'mensagem' => 'Requisição HTTP inválida.',
                'codigoHttp' => 400,
            ];
        }

        if (! $request->isPost()) {
            return [
                'success' => false,
                'mensagem' => 'Método não permitido.',
                'codigoHttp' => 405,
            ];
        }

        $dados = $request->getPost()->toArray();

        $headers = $request->getHeaders();
        $contentType = $headers->has('Content-Type')
            ? strtolower((string) $headers->get('Content-Type')->getFieldValue())
            : '';

        if (str_starts_with($contentType, 'application/json')) {
            $rawBody = (string) $request->getContent();
            $decoded = $rawBody !== '' ? json_decode($rawBody, true) : [];

            if ($decoded === null && $rawBody !== '') {
                return [
                    'success' => false,
                    'resultado' => null,
                    'mensagem' => 'JSON inválido no corpo da requisição.',
                    'codigoHttp' => 400,
                ];
            }

            if (is_array($decoded)) {
                $dados = array_merge($dados, $decoded);
            }
        }

        $produtoDto = ProdutoDTO::fromArray($dados);
        $this->service->criar($produtoDto);

        $nome = $produtoDto->nome;
        return [
            'success' => true,
            'mensagem' => 'Produto ' . trim($nome) . ' criado com sucesso.',
            'codigoHttp' => 201,
            'resultado' => null,
        ];
    }

    public function alterarAction()
    {
        $request = $this->validateRequest('PUT');
        $dados = $this->getJsonBody($request);


        $dto = ProdutoAlterarDTO::fromArray($dados);
        $this->service->alterar($dto);

        $nome = $dto->nome;
        return [
            'success' => true,
            'mensagem' => 'Produto ' . trim($nome) . ' alterado com sucesso.',
            'codigoHttp' => 200,
            'resultado' => null,
        ];
    }

    public function deletarAction()
    {
        $request = $this->validateRequest('DELETE');
        $dados = $this->getIdsFromHeader($request);

        $dto = ProdutoDeletarDTO::fromArray($dados);
        $this->service->deletar($dto);

        return [
            'success' => true,
            'mensagem' => 'Produtos deletados com sucesso.',
            'codigoHttp' => 200,
            'resultado' => null,
        ];
    }
}
