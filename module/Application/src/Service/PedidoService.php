<?php

declare(strict_types=1);

namespace Application\Service;

use Application\DTO\PedidoAtualizarDTO;
use Application\DTO\PedidoCancelarDTO;
use Application\DTO\PedidoDTO;
use Application\DTO\PedidoItemDTO;
use Application\DTO\PedidoStatusDTO;
use Application\Enum\FormaPagamento;
use Application\Enum\StatusPedido;
use Application\Enum\TipoEntrega;
use Application\Repository\PedidoRepository;
use Application\Repository\ProdutoRepository;

class PedidoService
{
    public function __construct(
        private PedidoRepository $pedidoRepository,
        private ProdutoRepository $produtoRepository,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listar(int|string|null $status = null): array
    {
        $statusFiltro = null;
        if ($status !== null && trim((string) $status) !== '') {
            $statusFiltro = $this->resolveStatus($status)->value;
        }

        $pedidos = $this->pedidoRepository->listar($statusFiltro);
        if (empty($pedidos)) {
            return [];
        }

        $ids = array_values(array_map(
            static fn(array $pedido): int => (int) ($pedido['id'] ?? 0),
            $pedidos
        ));

        $itens = $this->pedidoRepository->buscarItensPorPedidos($ids);
        $itensPorPedido = [];

        foreach ($itens as $item) {
            $pedidoId = (int) ($item['pedido_id'] ?? 0);
            if ($pedidoId <= 0) {
                continue;
            }

            $itensPorPedido[$pedidoId][] = [
                'id' => (int) ($item['id'] ?? 0),
                'produtoId' => (int) ($item['produto_id'] ?? 0),
                'produtoNome' => (string) ($item['produto_nome'] ?? ''),
                'quantidade' => (int) ($item['quantidade'] ?? 0),
                'valorUnitario' => round((float) ($item['valor_unitario'] ?? 0), 2),
            ];
        }

        return array_map(function (array $pedido) use ($itensPorPedido): array {
            $pedidoId = (int) ($pedido['id'] ?? 0);

            return [
                'id' => $pedidoId,
                'clienteNome' => (string) ($pedido['cliente_nome'] ?? ''),
                'clienteWhatsapp' => (string) ($pedido['cliente_whatsapp'] ?? ''),
                'dataEntrega' => (string) ($pedido['data_entrega'] ?? ''),
                'horaEntrega' => (string) ($pedido['hora_entrega'] ?? ''),
                'tipoEntrega' => (int) ($pedido['tipo_entrega'] ?? TipoEntrega::RETIRADA->value),
                'endereco' => $pedido['endereco'] ?? null,
                'formaPagamento' => (int) ($pedido['forma_pagamento'] ?? FormaPagamento::PIX->value),
                'status' => (int) ($pedido['status'] ?? StatusPedido::PENDENTE->value),
                'observacao' => $pedido['observacao'] ?? null,
                'valorTotal' => round((float) ($pedido['valor_total'] ?? 0), 2),
                'createdAt' => (string) ($pedido['created_at'] ?? ''),
                'updatedAt' => (string) ($pedido['updated_at'] ?? ''),
                'itens' => $itensPorPedido[$pedidoId] ?? [],
            ];
        }, $pedidos);
    }

    public function incluir(PedidoDTO $dto): array
    {
        $clienteNome = $this->sanitizeRequired($dto->clienteNome, 'Nome do cliente é obrigatório.');
        $whatsapp = $this->sanitizeRequired($dto->clienteWhatsapp, 'Whatsapp do cliente é obrigatório.');
        $dataEntrega = $this->formatDate($dto->dataEntrega);
        $horaEntrega = $this->formatTime($dto->horaEntrega);
        $tipoEntrega = $this->resolveTipoEntrega($dto->tipoEntrega);
        $formaPagamento = $this->resolveFormaPagamento($dto->formaPagamento);
        $observacao = $this->sanitizeOptional($dto->observacao);

        $endereco = $this->sanitizeOptional($dto->endereco);
        if ($tipoEntrega === TipoEntrega::ENTREGA && $endereco === null) {
            throw new \InvalidArgumentException('Endereço é obrigatório para pedidos com entrega.');
        }

        [$itens, $valorTotal] = $this->montarItens($dto->itens);

        $pedidoId = $this->pedidoRepository->inserir(
            [
                'cliente_nome' => $clienteNome,
                'cliente_whatsapp' => $whatsapp,
                'data_entrega' => $dataEntrega,
                'hora_entrega' => $horaEntrega,
                'tipo_entrega' => $tipoEntrega->value,
                'endereco' => $endereco,
                'forma_pagamento' => $formaPagamento->value,
                'status' => StatusPedido::PENDENTE->value,
                'observacao' => $observacao,
                'valor_total' => $valorTotal,
            ],
            $itens
        );

        return [
            'pedidoId' => $pedidoId,
        ];
    }

    public function editar(PedidoAtualizarDTO $dto): void
    {
        $id = $dto->id;
        if ($id <= 0) {
            throw new \InvalidArgumentException('Id do pedido é obrigatório.');
        }

        $pedidoAtual = $this->pedidoRepository->buscarPorId($id);
        if (! $pedidoAtual) {
            throw new \InvalidArgumentException('Pedido não encontrado.');
        }

        $dados = [];

        if ($dto->clienteNome !== null) {
            $dados['cliente_nome'] = $this->sanitizeRequired($dto->clienteNome, 'Nome do cliente não pode ser vazio.');
        }

        if ($dto->clienteWhatsapp !== null) {
            $dados['cliente_whatsapp'] = $this->sanitizeRequired($dto->clienteWhatsapp, 'Whatsapp do cliente não pode ser vazio.');
        }

        if ($dto->dataEntrega !== null) {
            $dados['data_entrega'] = $this->formatDate($dto->dataEntrega);
        }

        if ($dto->horaEntrega !== null) {
            $dados['hora_entrega'] = $this->formatTime($dto->horaEntrega);
        }

        $tipoEntregaAtual = $pedidoAtual['tipo_entrega'] ?? TipoEntrega::RETIRADA->value;
        $tipoEntregaFinal = $this->resolveTipoEntrega($tipoEntregaAtual);
        if ($dto->tipoEntrega !== null) {
            $tipoEntregaFinal = $this->resolveTipoEntrega($dto->tipoEntrega);
            $dados['tipo_entrega'] = $tipoEntregaFinal->value;
        }

        $enderecoFinal = $pedidoAtual['endereco'] ?? null;
        if ($dto->endereco !== null) {
            $enderecoFinal = $this->sanitizeOptional($dto->endereco);
            $dados['endereco'] = $enderecoFinal;
        }

        if ($tipoEntregaFinal === TipoEntrega::ENTREGA && ($enderecoFinal === null || $enderecoFinal === '')) {
            throw new \InvalidArgumentException('Endereço é obrigatório para pedidos com entrega.');
        }

        if ($dto->formaPagamento !== null) {
            $dados['forma_pagamento'] = $this->resolveFormaPagamento($dto->formaPagamento)->value;
        }

        if ($dto->observacao !== null) {
            $dados['observacao'] = $this->sanitizeOptional($dto->observacao);
        }

        $itens = null;
        if ($dto->itens !== null) {
            [$itens, $valorTotal] = $this->montarItens($dto->itens);
            $dados['valor_total'] = $valorTotal;
        }

        if (empty($dados) && $itens === null) {
            throw new \InvalidArgumentException('Nenhum dado foi informado para alteração.');
        }

        $this->pedidoRepository->atualizar($id, $dados, $itens);
    }

    public function cancelar(PedidoCancelarDTO $dto): void
    {
        $pedido = $this->buscarPedidoOuErro($dto->id);
        $statusAtual = isset($pedido['status'])
            ? $this->resolveStatus($pedido['status'])
            : StatusPedido::PENDENTE;

        if (StatusPedido::isFinal($statusAtual) && $statusAtual === StatusPedido::CANCELADO) {
            throw new \InvalidArgumentException('Pedido já está cancelado.');
        }

        if (StatusPedido::isFinal($statusAtual)) {
            throw new \InvalidArgumentException('Pedido não pode ser cancelado pois já foi finalizado.');
        }

        $motivo = $this->sanitizeOptional($dto->motivo);

        $this->pedidoRepository->atualizarStatus($dto->id, StatusPedido::CANCELADO->value, $motivo);
    }

    public function alterarStatus(PedidoStatusDTO $dto): void
    {
        $novoStatus = $this->resolveStatus($dto->status);
        $pedido = $this->buscarPedidoOuErro($dto->id);

        $statusAtual = isset($pedido['status'])
            ? $this->resolveStatus($pedido['status'])
            : StatusPedido::PENDENTE;

        if ($statusAtual === $novoStatus) {
            return;
        }

        if (StatusPedido::isFinal($statusAtual)) {
            throw new \InvalidArgumentException('Não é possível alterar o status de um pedido finalizado.');
        }

        $this->pedidoRepository->atualizarStatus($dto->id, $novoStatus->value);
    }

    private function sanitizeRequired(string $valor, string $mensagem): string
    {
        $limpo = trim($valor);

        if ($limpo === '') {
            throw new \InvalidArgumentException($mensagem);
        }

        return $limpo;
    }

    private function sanitizeOptional(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpo = trim($valor);

        return $limpo === '' ? null : $limpo;
    }

    private function formatDate(string $data): string
    {
        $data = trim($data);
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data);

        if (! $date) {
            throw new \InvalidArgumentException('Data de entrega inválida. Use o formato YYYY-MM-DD.');
        }

        return $date->format('Y-m-d');
    }

    private function formatTime(string $hora): string
    {
        $hora = trim($hora);
        $formats = ['H:i', 'H:i:s'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $hora);
            if ($date) {
                return $date->format('H:i:s');
            }
        }

        throw new \InvalidArgumentException('Hora de entrega inválida. Use o formato HH:MM.');
    }

    private function resolveTipoEntrega(TipoEntrega|int|string|null $tipo): TipoEntrega
    {
        if ($tipo instanceof TipoEntrega) {
            return $tipo;
        }

        if (is_int($tipo)) {
            $resultado = TipoEntrega::tryFrom($tipo);
            if ($resultado) {
                return $resultado;
            }
        }

        if (is_string($tipo)) {
            $normalizado = trim($tipo);

            if ($normalizado !== '' && ctype_digit($normalizado)) {
                $resultado = TipoEntrega::tryFrom((int) $normalizado);
                if ($resultado) {
                    return $resultado;
                }
            }

            $valor = strtolower($normalizado);
            return match ($valor) {
                'retirada' => TipoEntrega::RETIRADA,
                'entrega' => TipoEntrega::ENTREGA,
                default => throw new \InvalidArgumentException('Tipo de entrega inválido.'),
            };
        }

        throw new \InvalidArgumentException('Tipo de entrega inválido.');
    }

    private function resolveFormaPagamento(FormaPagamento|int|string|null $forma): FormaPagamento
    {
        if ($forma instanceof FormaPagamento) {
            return $forma;
        }

        if (is_int($forma)) {
            $resultado = FormaPagamento::tryFrom($forma);
            if ($resultado) {
                return $resultado;
            }
        }

        if (is_string($forma)) {
            $normalizado = trim($forma);

            if ($normalizado !== '' && ctype_digit($normalizado)) {
                $resultado = FormaPagamento::tryFrom((int) $normalizado);
                if ($resultado) {
                    return $resultado;
                }
            }

            $valor = strtolower(str_replace(['-', '_'], ' ', $normalizado));
            return match ($valor) {
                'dinheiro' => FormaPagamento::DINHEIRO,
                'pix' => FormaPagamento::PIX,
                'cartao credito', 'cartao de credito' => FormaPagamento::CARTAO_CREDITO,
                'cartao debito', 'cartao de debito' => FormaPagamento::CARTAO_DEBITO,
                default => throw new \InvalidArgumentException('Forma de pagamento inválida.'),
            };
        }

        throw new \InvalidArgumentException('Forma de pagamento inválida.');
    }

    private function resolveStatus(StatusPedido|int|string|null $status): StatusPedido
    {
        if ($status instanceof StatusPedido) {
            return $status;
        }

        if (is_int($status)) {
            $resultado = StatusPedido::tryFrom($status);
            if ($resultado) {
                return $resultado;
            }
        }

        if (is_string($status)) {
            $normalizado = trim($status);

            if ($normalizado !== '' && ctype_digit($normalizado)) {
                $resultado = StatusPedido::tryFrom((int) $normalizado);
                if ($resultado) {
                    return $resultado;
                }
            }

            $valor = strtolower(str_replace(['-', '_'], ' ', $normalizado));
            return match ($valor) {
                'pendente' => StatusPedido::PENDENTE,
                'em preparacao' => StatusPedido::EM_PREPARACAO,
                'pronto' => StatusPedido::PRONTO,
                'entregue' => StatusPedido::ENTREGUE,
                'cancelado' => StatusPedido::CANCELADO,
                default => throw new \InvalidArgumentException('Status do pedido inválido.'),
            };
        }

        throw new \InvalidArgumentException('Status do pedido inválido.');
    }

    /**
     * @param PedidoItemDTO[] $itensDto
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function montarItens(array $itensDto): array
    {
        if (empty($itensDto)) {
            throw new \InvalidArgumentException('Itens do pedido são obrigatórios.');
        }

        $itens = [];
        $valorTotal = 0.0;

        foreach ($itensDto as $item) {
            if (! $item instanceof PedidoItemDTO) {
                throw new \InvalidArgumentException('Formato dos itens inválido.');
            }

            if ($item->produtoId <= 0) {
                throw new \InvalidArgumentException('Produto inválido informado no pedido.');
            }

            if ($item->quantidade <= 0) {
                throw new \InvalidArgumentException('Quantidade do item deve ser maior que zero.');
            }

            $produto = $this->produtoRepository->buscarPorId($item->produtoId);
            if (! $produto) {
                throw new \InvalidArgumentException('Produto informado no pedido não existe.');
            }

            $inativo = (bool) ($produto['inativo'] ?? false);
            if ($inativo) {
                throw new \InvalidArgumentException('Produto informado no pedido está inativo.');
            }

            $valorUnitario = (float) ($produto['preco'] ?? 0);
            if ($valorUnitario <= 0) {
                throw new \InvalidArgumentException('Produto informado no pedido não possui preço válido.');
            }

            $itens[] = [
                'produto_id' => $item->produtoId,
                'quantidade' => $item->quantidade,
                'valor_unitario' => $valorUnitario,
            ];

            $valorTotal += $valorUnitario * $item->quantidade;
        }

        return [$itens, round($valorTotal, 2)];
    }

    private function buscarPedidoOuErro(int $id): array
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Id do pedido é obrigatório.');
        }

        $pedido = $this->pedidoRepository->buscarPorId($id);
        if (! $pedido) {
            throw new \InvalidArgumentException('Pedido não encontrado.');
        }

        return $pedido;
    }
}
