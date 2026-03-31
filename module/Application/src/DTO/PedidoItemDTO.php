<?php

declare(strict_types=1);

namespace Application\DTO;

final class PedidoItemDTO
{
    public function __construct(
        public readonly int $produtoId,
        public readonly int $quantidade,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            produtoId: isset($data['produtoId']) ? (int) $data['produtoId'] : 0,
            quantidade: isset($data['quantidade']) ? (int) $data['quantidade'] : 0,
        );
    }
}
