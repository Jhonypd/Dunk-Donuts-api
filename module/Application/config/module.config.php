<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Db\Adapter\Adapter;

$routes = [];
$routesDir = __DIR__ . '/routes';

if (is_dir($routesDir)) {
    foreach (glob($routesDir . '/*.php') ?: [] as $routeFile) {
        $loadedRoutes = require $routeFile;
        if (is_array($loadedRoutes)) {
            $routes = array_replace($routes, $loadedRoutes);
        }
    }
}

return [
    'router' => [
        'routes' => [
            ...$routes,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ProdutoController::class => function ($container) {
                return new Controller\ProdutoController(
                    $container->get(Service\ProdutoService::class)
                );
            },
        ],
    ],
    'service_manager' => [
        'factories' => [
            Adapter::class => function ($container) {
                return new Adapter($container->get('config')['db']);
            },
            Repository\ProdutoRepository::class => function ($container) {
                return new Repository\ProdutoRepository(
                    $container->get(Adapter::class)
                );
            },
            Service\ProdutoService::class => function ($container) {
                return new Service\ProdutoService(
                    $container->get(Repository\ProdutoRepository::class)
                );
            },
        ],
    ],
    'view_manager' => [
    ],
];
