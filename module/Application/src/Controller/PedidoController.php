<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\DTO\PedidoAtualizarDTO;
use Application\DTO\PedidoCancelarDTO;
use Application\DTO\PedidoDTO;
use Application\DTO\PedidoStatusDTO;
use Application\Service\PedidoService;

class PedidoController extends BaseController
{
    public function __construct(private PedidoService $service) {}

    public function listarAction(): array
    {
        $request = $this->validateRequest('GET');
        $status = $this->getQueryParam($request, 'status', false);

        $pedidos = $this->service->listar($status);

        return [
            'success' => true,
            'codigoHttp' => 200,
            'resultado' => [
                'pedidos' => $pedidos,
            ],
        ];
    }

    public function incluirAction(): array
    {
        $request = $this->validateRequest('POST');
        $dados = $this->getJsonBody($request);

        $dto = PedidoDTO::fromArray($dados);
        $resultado = $this->service->incluir($dto);

        return [
            'success' => true,
            'mensagem' => 'Pedido criado com sucesso.',
            'codigoHttp' => 201,
            'resultado' => $resultado,
        ];
    }

    public function editarAction(): array
    {
        $request = $this->validateRequest('PUT');
        $dados = $this->getJsonBody($request);

        $dto = PedidoAtualizarDTO::fromArray($dados);
        $this->service->editar($dto);

        return [
            'success' => true,
            'mensagem' => 'Pedido atualizado com sucesso.',
            'codigoHttp' => 200,
            'resultado' => null,
        ];
    }

    public function cancelarAction(): array
    {
        $request = $this->validateRequest('POST');
        $dados = $this->getJsonBody($request);

        $dto = PedidoCancelarDTO::fromArray($dados);
        $this->service->cancelar($dto);

        return [
            'success' => true,
            'mensagem' => 'Pedido cancelado com sucesso.',
            'codigoHttp' => 200,
            'resultado' => null,
        ];
    }

    public function alterarStatusAction(): array
    {
        $request = $this->validateRequest('POST');
        $dados = $this->getJsonBody($request);

        $dto = PedidoStatusDTO::fromArray($dados);
        $this->service->alterarStatus($dto);

        return [
            'success' => true,
            'mensagem' => 'Status do pedido atualizado com sucesso.',
            'codigoHttp' => 200,
            'resultado' => null,
        ];
    }
}
