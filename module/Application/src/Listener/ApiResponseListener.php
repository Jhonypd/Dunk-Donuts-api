<?php

declare(strict_types=1);

namespace Application\Listener;

use ArrayObject;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ModelInterface;
use Traversable;

final class ApiResponseListener
{
    public function __invoke(MvcEvent $e): void
    {
        $result = $e->getResult();

        if ($result instanceof Response) {
            return;
        }

        $data = null;
        if (is_array($result)) {
            $data = $result;
        } elseif ($result instanceof ArrayObject) {
            $data = $result->getArrayCopy();
        } elseif ($result instanceof ModelInterface) {
            $variables = $result->getVariables();
            if ($variables instanceof ArrayObject) {
                $data = $variables->getArrayCopy();
            } elseif (is_array($variables)) {
                $data = $variables;
            } elseif ($variables instanceof Traversable) {
                $data = iterator_to_array($variables);
            }
        } elseif ($result instanceof Traversable) {
            $data = iterator_to_array($result);
        }

        if (! is_array($data)) {
            return;
        }

        $payload = $this->normalize($data);

        $statusCode = (int) ($payload['codigoHttp'] ?? ($payload['success'] ? 200 : 404));
        $payload['codigoHttp'] = $statusCode;

        $response = $e->getResponse();
        if (! $response instanceof Response) {
            $response = new Response();
            $e->setResponse($response);
        }

        $response->setStatusCode($statusCode);
        $response->getHeaders()->addHeaderLine(
            'Content-Type',
            'application/json; charset=utf-8'
        );

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $json = json_encode([
                'success' => false,
                'resultado' => null,
                'mensagem' => 'Erro ao serializar JSON.',
                'codigoHttp' => 500,
            ], JSON_UNESCAPED_UNICODE);

            $response->setStatusCode(500);
        }

        $response->setContent($json);
        $e->setResult($response);
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function normalize(array $data): array
    {
        $looksLikeEnvelope = array_key_exists('success', $data)
            || array_key_exists('resultado', $data)
            || array_key_exists('mensagem', $data)
            || array_key_exists('codigoHttp', $data)
            || array_key_exists('message', $data);

        if (! $looksLikeEnvelope) {
            return [
                'success' => true,
                'resultado' => $data,
                'mensagem' => 'Operação realizada com sucesso.',
                'codigoHttp' => 200,
            ];
        }

        $payload = $data;

        $success = (bool) ($payload['success'] ?? true);
        $payload['success'] = $success;

        if (! array_key_exists('mensagem', $payload) && array_key_exists('message', $payload)) {
            $payload['mensagem'] = $payload['message'];
        }

        if (! array_key_exists('mensagem', $payload)) {
            $payload['mensagem'] = $success
                ? 'Operação realizada com sucesso.'
                : 'Erro ao processar operação';
        }

        if (! array_key_exists('resultado', $payload)) {
            $payload['resultado'] = $success ? [] : null;
        }

        if (! array_key_exists('codigoHttp', $payload)) {
            $payload['codigoHttp'] = $success ? 200 : 404;
        }

        return $payload;
    }
}
