<?php

declare(strict_types=1);

namespace Application\DTO;

final class PedidoCancelarDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $motivo,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : 0,
            motivo: array_key_exists('motivo', $data) ? (string) $data['motivo'] : null,
        );
    }
}
