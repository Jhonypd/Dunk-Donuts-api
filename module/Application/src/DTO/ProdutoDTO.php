<?php

declare(strict_types=1);

namespace Application\DTO;

final class ProdutoDTO
{
    public function __construct(
        public readonly string $nome,
        public readonly float $preco,
        public readonly ?string $descricao,
        public readonly string $referencia,
        public readonly bool $inativo,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            nome: (string) ($data['nome'] ?? ''),
            preco: (float) ($data['preco'] ?? 0),
            descricao: isset($data['descricao']) ? (string) $data['descricao'] : null,
            referencia: (string) ($data['referencia'] ?? ''),
            inativo: (bool) ($data['inativo'] ?? false),
        );
    }
}
