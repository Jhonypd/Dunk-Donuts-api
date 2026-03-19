<?php

namespace Application\Repository;

use Laminas\Db\Adapter\Adapter;

class ProdutoRepository
{
    private $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function buscarTodos()
    {
        $result = $this->db->query("SELECT * FROM produtos", []);
        return $result->toArray();
    }
}