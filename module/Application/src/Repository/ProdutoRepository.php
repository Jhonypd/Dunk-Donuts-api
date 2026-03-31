<?php

namespace Application\Repository;

use Application\DTO\ProdutoAlterarDTO;
use Application\DTO\ProdutoDeleteDTO;
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
            "INSERT INTO produtos (nome, categoria, preco, descricao, referencia, inativo)
         VALUES (:nome, :categoria, :preco, :descricao, :referencia, :inativo)",
            [
                'nome' => $produto->nome,
                'categoria' => $produto->categoria->value,
                'preco' => $produto->preco,
                'descricao' => $produto->descricao,
                'referencia' => $produto->referencia,
                'inativo' => $produto->inativo,
            ]
        );
    }

    public function alterar(ProdutoAlterarDTO $produto): void
    {
        $campos = [];
        $params = ['id' => $produto->id];

        if ($produto->nome !== null) {
            $campos[] = 'nome = :nome';
            $params['nome'] = trim($produto->nome);
        }

        if ($produto->categoria !== null) {
            $campos[] = 'categoria = :categoria';
            $params['categoria'] = $produto->categoria->value;
        }

        if ($produto->preco !== null) {
            $campos[] = 'preco = :preco';
            $params['preco'] = $produto->preco;
        }

        if ($produto->descricao !== null) {
            $campos[] = 'descricao = :descricao';
            $params['descricao'] = trim($produto->descricao);
        }

        if ($produto->referencia !== null) {
            $campos[] = 'referencia = :referencia';
            $params['referencia'] = trim($produto->referencia);
        }

        if ($produto->inativo !== null) {
            $campos[] = 'inativo = :inativo';
            $params['inativo'] = (int) $produto->inativo;
        }

        if (empty($campos)) {
            throw new \InvalidArgumentException('Nenhum campo foi informado para alteração.');
        }

        $sql = sprintf(
            'UPDATE produtos SET %s WHERE id = :id',
            implode(', ', $campos)
        );

        $this->db->query($sql, $params);
    }

    public function deletar(ProdutoDeleteDTO $ids): void
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
