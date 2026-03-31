<?php

declare(strict_types=1);

namespace Application\DTO;

use Application\Enum\FormaPagamento;
use Application\Enum\TipoEntrega;

final class PedidoAtualizarDTO
{
    /**
     * @param PedidoItemDTO[]|null $itens
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $clienteNome,
        public readonly ?string $clienteWhatsapp,
        public readonly ?string $dataEntrega,
        public readonly ?string $horaEntrega,
        public readonly TipoEntrega|int|string|null $tipoEntrega,
        public readonly ?string $endereco,
        public readonly FormaPagamento|int|string|null $formaPagamento,
        public readonly ?string $observacao,
        public readonly ?array $itens,
    ) {}

    public static function fromArray(array $data): self
    {
        $itens = null;
        if (array_key_exists('itens', $data)) {
            $itensData = is_array($data['itens']) ? $data['itens'] : [];
            $itens = array_map(static fn(array $item) => PedidoItemDTO::fromArray($item), $itensData);
        }

        return new self(
            id: isset($data['id']) ? (int) $data['id'] : 0,
            clienteNome: array_key_exists('clienteNome', $data) ? (string) $data['clienteNome'] : null,
            clienteWhatsapp: array_key_exists('clienteWhatsapp', $data) ? (string) $data['clienteWhatsapp'] : null,
            dataEntrega: array_key_exists('dataEntrega', $data) ? (string) $data['dataEntrega'] : null,
            horaEntrega: array_key_exists('horaEntrega', $data) ? (string) $data['horaEntrega'] : null,
            tipoEntrega: array_key_exists('tipoEntrega', $data) ? $data['tipoEntrega'] : null,
            endereco: array_key_exists('endereco', $data) ? (string) $data['endereco'] : null,
            formaPagamento: array_key_exists('formaPagamento', $data) ? $data['formaPagamento'] : null,
            observacao: array_key_exists('observacao', $data) ? (string) $data['observacao'] : null,
            itens: $itens,
        );
    }
}
