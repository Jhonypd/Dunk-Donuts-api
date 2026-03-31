<?php

declare(strict_types=1);

use Laminas\Router\Http\Literal;

return [
    'pedidos-listar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/pedidos/listar',
            'defaults' => [
                'controller' => \Application\Controller\PedidoController::class,
                'action' => 'listar',
                'allowed_methods' => ['GET'],
            ],
        ],
    ],
    'pedidos-incluir' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/pedidos/incluir',
            'defaults' => [
                'controller' => \Application\Controller\PedidoController::class,
                'action' => 'incluir',
                'allowed_methods' => ['POST'],
            ],
        ],
    ],
    'pedidos-editar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/pedidos/editar',
            'defaults' => [
                'controller' => \Application\Controller\PedidoController::class,
                'action' => 'editar',
                'allowed_methods' => ['PUT'],
            ],
        ],
    ],
    'pedidos-cancelar' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/pedidos/cancelar',
            'defaults' => [
                'controller' => \Application\Controller\PedidoController::class,
                'action' => 'cancelar',
                'allowed_methods' => ['POST'],
            ],
        ],
    ],
    'pedidos-alterar-status' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/pedidos/alterar-status',
            'defaults' => [
                'controller' => \Application\Controller\PedidoController::class,
                'action' => 'alterarStatus',
                'allowed_methods' => ['POST'],
            ],
        ],
    ],
];
