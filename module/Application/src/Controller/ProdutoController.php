<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\DTO\ProdutoDTO;
use Application\DTO\ProdutoAlterarDTO;
use Application\DTO\ProdutoDeleteDTO;
use Application\Service\ProdutoService;


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
        $header = $this->getHeader($request, 'incluirInativos', true);
        $incluirInativos = (bool) $header;

        $produtos = $this->service->listar($incluirInativos);

        return [
            'success' => true,
            'resultado' => $produtos,
        ];
    }
    public function criarAction()
    {
        $request = $this->validateRequest('POST');
        $dados = $this->getJsonBody($request);

        $produtoDto = ProdutoDTO::fromArray($dados);
        $this->service->criar($produtoDto);

        return [
            'success' => true,
            'mensagem' => 'Produto ' . trim($produtoDto->nome) . ' criado com sucesso.',
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
        $ids = $this->getIdsFromHeader($request, "Ids");

        $dto = ProdutoDeleteDTO::fromArray(['Ids' => $ids]);
        $resultado = $this->service->deletar($dto);

        return [
            'success' => true,
            'mensagem' => $resultado['mensagem'],
            'codigoHttp' => 200,
            'resultado' => null,
        ];
    }
}
