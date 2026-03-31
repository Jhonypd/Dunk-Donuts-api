<?php

declare(strict_types=1);

namespace Application\DTO;

use Application\Enum\StatusPedido;

final class PedidoStatusDTO
{
    public function __construct(
        public readonly int $id,
        public readonly StatusPedido|int|string $status,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : 0,
            status: $data['status'] ?? '',
        );
    }
}
