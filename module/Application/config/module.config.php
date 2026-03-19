<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Db\Adapter\Adapter;

return [
    'router' => [
        'routes' => [
            'produtos' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/produtos',
                    'defaults' => [
                        'controller' => Controller\ProdutoController::class,
                        'action'     => 'listar',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ProdutoController::class => function($container) {
                return new Controller\ProdutoController(
                    $container->get(Service\ProdutoService::class)
                );
            },
        ],
    ],
    'service_manager' => [
        'factories' => [
            

            Adapter::class => function($container) {
            return new Adapter($container->get('config')['db']);
        },
        Application\Repository\ProdutoRepository::class => function($container) {
            return new Application\Repository\ProdutoRepository(
                $container->get(Adapter::class)
            );
        },
        'Application\Service\ProdutoService' => function($container) {
            return new Service\ProdutoService(
                $container->get(Application\Repository\ProdutoRepository::class)
            );
        },
        ],
    ],
    'view_manager' => [
    ],
];