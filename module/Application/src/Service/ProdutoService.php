<?php

namespace Application\Service;

use Application\Repository\ProdutoRepository;

class ProdutoService
{
    private $repository;

    public function __construct(ProdutoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listar()
    {
        return [
            'produtos' => $this->repository->buscarTodos()
        ];
    }
}