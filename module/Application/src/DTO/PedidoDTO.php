<?php

declare(strict_types=1);

namespace Application\DTO;

use Application\Enum\FormaPagamento;
use Application\Enum\TipoEntrega;

final class PedidoDTO
{
    /**
     * @param PedidoItemDTO[] $itens
     */
    public function __construct(
        public readonly string $clienteNome,
        public readonly string $clienteWhatsapp,
        public readonly string $dataEntrega,
        public readonly string $horaEntrega,
        public readonly TipoEntrega|int|string|null $tipoEntrega,
        public readonly ?string $endereco,
        public readonly FormaPagamento|int|string|null $formaPagamento,
        public readonly ?string $observacao,
        public readonly array $itens,
    ) {}

    public static function fromArray(array $data): self
    {
        $itensData = is_array($data['itens'] ?? null) ? $data['itens'] : [];
        $itens = array_map(static fn(array $item) => PedidoItemDTO::fromArray($item), $itensData);

        return new self(
            clienteNome: (string) ($data['clienteNome'] ?? ''),
            clienteWhatsapp: (string) ($data['clienteWhatsapp'] ?? ''),
            dataEntrega: (string) ($data['dataEntrega'] ?? ''),
            horaEntrega: (string) ($data['horaEntrega'] ?? ''),
            tipoEntrega: array_key_exists('tipoEntrega', $data) ? $data['tipoEntrega'] : null,
            endereco: isset($data['endereco']) ? (string) $data['endereco'] : null,
            formaPagamento: array_key_exists('formaPagamento', $data) ? $data['formaPagamento'] : null,
            observacao: isset($data['observacao']) ? (string) $data['observacao'] : null,
            itens: $itens,
        );
    }
}
