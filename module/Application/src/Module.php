<?php

declare(strict_types=1);

namespace Application;

use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Uri\Http as HttpUri;
use Application\Listener\ApiMethodGuardListener;
use Application\Listener\ApiResponseListener;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();

        // Prioridade alta para garantir JSON em erros/404 antes do View renderizar templates.
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'renderApiError'], 1000);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'renderApiError'], 1000);

        // Bloqueia verbos HTTP não permitidos por rota (ex.: POST em rota GET).
        // Roda antes do DispatchListener (prioridade 1) para impedir a execução do controller.
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, new ApiMethodGuardListener(), 1000);

        // Normaliza as respostas dos controllers para um envelope JSON padrão.
        // Precisa rodar antes do CreateViewModelListener (prioridade -80) e do InjectTemplateListener (-90),
        // para evitar que arrays virem ViewModel e tentem renderizar templates.
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, new ApiResponseListener(), -70);
    }

    public function renderApiError(MvcEvent $e): void
    {
        $response = $e->getResponse();
        if (! $response instanceof Response) {
            $response = new Response();
            $e->setResponse($response);
        }

        $error = (string) $e->getError();
        $exception = $e->getParam('exception');
        $request = $e->getRequest();
        $isDebug = $this->shouldExposeErrorDetails($e);

        $statusCode = 500;
        if (
            $error === 'error-router-no-match'
            || $error === 'error-controller-not-found'
            || $error === 'error-controller-invalid'
            || $error === 'error-controller-cannot-dispatch'
        ) {
            $statusCode = 404;
        }

        if ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400;
        }

        $path = null;
        $method = null;
        if ($request instanceof HttpRequest) {
            $method = $request->getMethod();
            $uri = $request->getUri();
            if ($uri instanceof HttpUri) {
                $path = $uri->getPath();
            }
        }

        $mensagem = $statusCode === 404
            ? 'Recurso não encontrado.'
            : 'Erro ao processar operação';

        if ($exception instanceof \InvalidArgumentException) {
            $mensagem = $exception->getMessage();
        } elseif ($isDebug && $exception instanceof \Throwable) {
            $mensagem = $exception->getMessage();
        }

        $payload = [
            'success' => false,
            'resultado' => null,
            'mensagem' => $mensagem,
            'codigoHttp' => $statusCode,
        ];

        // Mantém os campos opcionais somente quando existirem.
        if ($path !== null) {
            $payload['path'] = $path;
        }

        if ($method !== null) {
            $payload['method'] = $method;
        }

        $response->setStatusCode($statusCode);

        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json; charset=utf-8');

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $json = json_encode([
                'success' => false,
                'status' => 500,
                'error' => 'internal-error',
                'message' => 'Erro ao serializar JSON.',
            ], JSON_UNESCAPED_UNICODE);
        }

        $response->setContent($json);

        $e->setResult($response);
        $e->stopPropagation(true);
    }

    private function shouldExposeErrorDetails(MvcEvent $e): bool
    {
        try {
            $sm = $e->getApplication()->getServiceManager();
            if (! $sm->has('config')) {
                return false;
            }

            $config = $sm->get('config');
            if (! is_array($config)) {
                return false;
            }

            return (bool) ($config['view_manager']['display_exceptions'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }
}
