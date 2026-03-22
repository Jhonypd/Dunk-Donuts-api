<?php

declare(strict_types=1);

namespace Application\DTO;

final class ProdutoDeleteDTO
{
    /**
     * @param int[] $ids
     */
    public function __construct(
        public readonly array $ids,

    ) {}

    public static function fromArray(array $data): self
    {
        $ids = $data['Ids'] ?? [];

        if (!is_array($ids)) {
            throw new \InvalidArgumentException('O campo "Ids" deve ser uma lista de números inteiros.');
        }

        $ids = array_map(function ($id) {
            if (!is_numeric($id)) {
                throw new \InvalidArgumentException("Id inválido: {$id}");
            }

            return (int) $id;
        }, $ids);

        return new self(
            ids: $ids,
        );
    }
}
