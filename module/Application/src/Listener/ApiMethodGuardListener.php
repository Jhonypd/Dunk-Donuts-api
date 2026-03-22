<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\MvcEvent;

final class ApiMethodGuardListener
{
    public function __invoke(MvcEvent $e): void
    {
        if ($e->getResult() !== null) {
            return;
        }

        $routeMatch = $e->getRouteMatch();
        if (! $routeMatch) {
            return;
        }

        $allowed = $routeMatch->getParam('allowed_methods');
        if ($allowed === null) {
            return;
        }

        $allowedMethods = is_array($allowed) ? $allowed : [$allowed];
        $allowedMethods = array_map('strtoupper', array_map('trim', $allowedMethods));

        $request = $e->getRequest();
        if (! $request instanceof HttpRequest) {
            return;
        }

        $method = strtoupper($request->getMethod());
        if (in_array($method, $allowedMethods, true)) {
            return;
        }

        $e->setResult([
            'success' => false,
            'resultado' => null,
            'mensagem' => 'Método não permitido.',
            'codigoHttp' => 405,
        ]);
    }
}
