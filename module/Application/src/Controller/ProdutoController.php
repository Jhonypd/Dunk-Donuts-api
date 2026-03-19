<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Application\Service\ProdutoService;

class ProdutoController extends AbstractActionController
{
    private $service;

    public function __construct(ProdutoService $service)
    {
        $this->service = $service;
    }

    public function listarAction()
    {
        return new JsonModel($this->service->listar());
    }
}