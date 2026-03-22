<?php

declare(strict_types=1);

use Laminas\Router\Http\Literal;

return [
    'produtos-listar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/produtos/listar',
            'defaults' => [
                'controller' => \Application\Controller\ProdutoController::class,
                'action' => 'listar',
                'allowed_methods' => ['GET'],
            ],
        ],
    ],
    'produtos-criar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/produtos/criar',
            'defaults' => [
                'controller' => \Application\Controller\ProdutoController::class,
                'action' => 'criar',
                'allowed_methods' => ['POST'],
            ],
        ],
    ],
    'produtos-alterar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/produtos/alterar',
            'defaults' => [
                'controller' => \Application\Controller\ProdutoController::class,
                'action' => 'alterar',
                'allowed_methods' => ['PUT'],
            ],
        ],
    ],
    'produtos-deletar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/produtos/deletar',
            'defaults' => [
                'controller' => \Application\Controller\ProdutoController::class,
                'action' => 'deletar',
                'allowed_methods' => ['DELETE'],
            ],
        ],
    ],
];
