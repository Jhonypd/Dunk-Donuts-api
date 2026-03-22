<?php

namespace Application\Repository;

use Application\DTO\ProdutoAlterarDTO;
use Application\DTO\ProdutoDeletarDTO;
use Application\DTO\ProdutoDTO;
use Laminas\Db\Adapter\Adapter;

class ProdutoRepository
{
    private $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function buscarTodos(bool $incluirInativos): array
    {
        $sql = $incluirInativos
            ? "SELECT * FROM produtos"
            : "SELECT * FROM produtos WHERE inativo = 0";

        $result = $this->db->query($sql, []);

        $data = [];
        foreach ($result as $row) {
            $data[] = (array) $row;
        }

        return $data;
    }

    public function inserir(ProdutoDTO $produto): void
    {
        $this->db->query(
            "INSERT INTO produtos (nome, preco, descricao, referencia, inativo)
         VALUES (:nome, :preco, :descricao, :referencia, :inativo)",
            [
                'nome' => $produto->nome,
                'preco' => $produto->preco,
                'descricao' => $produto->descricao,
                'referencia' => $produto->referencia,
                'inativo' => $produto->inativo,
            ]
        );
    }

    public function alterar(ProdutoAlterarDTO $produto): void
    {
        $this->db->query(
            "UPDATE produtos 
         SET nome = :nome, preco = :preco, descricao = :descricao, referencia = :referencia, inativo = :inativo 
         WHERE id = :id",
            [
                'nome' => $produto->nome,
                'preco' => $produto->preco,
                'descricao' => $produto->descricao,
                'referencia' => $produto->referencia,
                'inativo' => $produto->inativo,
                'id' => $produto->id,
            ]
        );
    }

    public function deletar(ProdutoDeletarDTO $ids): void
    {
        if (empty($ids->ids)) {
            return;
        }

        $listaDeletar = implode(',', array_fill(0, count($ids->ids), '?'));

        $this->db->query(
            "DELETE FROM produtos WHERE id IN ($listaDeletar)",
            $ids->ids
        );
    }

    public function buscarPorId(int $id): ?array
    {
        $result = $this->db->query("SELECT * FROM produtos WHERE id = ?", [$id]);
        $produto = $result->current();

        return $produto ? (array) $produto : null;
    }
}
