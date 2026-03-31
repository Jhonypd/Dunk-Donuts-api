<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;

final class CorsListener
{
    /**
     * Origens permitidas para CORS
     *
     * @var array<string>
     */
    private array $allowedOrigins = [];

    /**
     * @param array<string> $allowedOrigins
     */
    public function __construct(array $allowedOrigins = [])
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    public function __invoke(MvcEvent $e): void
    {
        $request = $e->getRequest();
        $response = $e->getResponse();

        if (! $response instanceof Response || ! $request instanceof HttpRequest) {
            return;
        }

        // Obtém a origem da requisição
        $originHeader = $request->getHeaders()->get('Origin');
        $origin = $originHeader ? $originHeader->getFieldValue() : null;

        // Verifica se a origem é permitida
        if (! $origin || ! in_array($origin, $this->allowedOrigins, true)) {
            return;
        }

        // Define headers CORS
        $response->getHeaders()
            ->addHeaderLine('Access-Control-Allow-Origin', $origin)
            ->addHeaderLine('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->addHeaderLine('Access-Control-Allow-Headers', '*')
            ->addHeaderLine('Access-Control-Max-Age', '86400')
            ->addHeaderLine('Access-Control-Allow-Credentials', 'true');

        // Se é um preflight request (OPTIONS), define status 200 e retorna
        if ($request instanceof HttpRequest && $request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
            $e->setResult($response);
        }
    }
}
