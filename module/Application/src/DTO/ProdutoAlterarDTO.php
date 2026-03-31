<?php

declare(strict_types=1);

namespace Application\DTO;

use Application\Enum\CategoriaProdutos;

final class ProdutoAlterarDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $nome,
        public readonly ?float $preco,
        public readonly CategoriaProdutos|int|null $categoria,
        public readonly ?string $descricao,
        public readonly ?string $referencia,
        public readonly ?bool $inativo,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : 0,
            nome: array_key_exists('nome', $data) ? $data['nome'] : null,
            preco: array_key_exists('preco', $data) ? (float) $data['preco'] : null,
            categoria: array_key_exists('categoria', $data) ? (int) $data['categoria'] : null,
            descricao: array_key_exists('descricao', $data) ? $data['descricao'] : null,
            referencia: array_key_exists('referencia', $data) ? $data['referencia'] : null,
            inativo: array_key_exists('inativo', $data) ? (bool) $data['inativo'] : null,
        );
    }
}
