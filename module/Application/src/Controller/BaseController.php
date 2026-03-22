<?php

namespace Application\Controller;

use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;

abstract class BaseController extends AbstractActionController
{
    /**
     * Valida se a requisição HTTP é válida e corresponde ao método esperado.
     *
     * @param string $method Método esperado (GET, POST, PUT, DELETE)
     * @return Request
     *
     * @throws \RuntimeException Se a requisição não for HTTP válida
     * @throws \DomainException Se o método não for permitido
     */
    protected function validateRequest(string $method): Request
    {
        $request = $this->getRequest();

        if (! $request instanceof Request) {
            throw new \RuntimeException('Requisição HTTP inválida.');
        }

        $valid = match ($method) {
            'POST' => $request->isPost(),
            'PUT' => $request->isPut(),
            'DELETE' => $request->isDelete(),
            'GET' => $request->isGet(),
            default => false,
        };

        if (! $valid) {
            throw new \DomainException('Método não permitido.');
        }

        return $request;
    }

    /**
     * Retorna o corpo da requisição como array a partir de JSON.
     *
     * @param Request $request
     * @return array
     *
     * @throws \InvalidArgumentException Se o body estiver vazio ou JSON inválido
     */
    protected function getJsonBody(Request $request): array
    {
        $raw = (string) $request->getContent();

        if ($raw === '') {
            throw new \InvalidArgumentException('Corpo da requisição vazio.');
        }

        $data = json_decode($raw, true);

        if ($data === null) {
            throw new \InvalidArgumentException('JSON inválido.');
        }

        return $data;
    }

    /**
     * Retorna dados enviados via multipart/form-data (POST + FILES).
     *
     * @param Request $request
     * @return array
     */
    protected function getFormData(Request $request): array
    {
        return array_merge(
            $request->getPost()->toArray(),
            $request->getFiles()->toArray()
        );
    }


    /**
     * Retorna o valor de um header específico.
     *
     * @param Request $request
     * @param string $name Nome do header
     * @param bool $required Se o header é obrigatório
     * @return string|null
     *
     * @throws \InvalidArgumentException Se obrigatório e não informado
     */
    protected function getHeader(Request $request, string $name, bool $required = false): ?string
    {
        $header = $request->getHeaders()->get($name);

        if (! $header) {
            if ($required) {
                throw new \InvalidArgumentException("{$name} não informado.");
            }
            return null;
        }

        return $header->getFieldValue();
    }

    /**
     * Extrai uma lista de IDs a partir de um header (ex: "1,2,3").
     *
     * @param Request $request
     * @param string $headerName
     * @return int[]
     */
    protected function getIdsFromHeader(Request $request, string $headerName = 'Ids'): array
    {
        $value = $this->getHeader($request, $headerName, true);

        return array_map(
            'intval',
            array_filter(explode(',', $value))
        );
    }

    /**
     * Retorna um parâmetro da query string (?id=1).
     *
     * @param Request $request
     * @param string $name Nome do parâmetro
     * @param bool $required Se é obrigatório
     * @param mixed $default Valor padrão
     * @return string|null
     *
     * @throws \InvalidArgumentException Se obrigatório e não informado
     */

    protected function getQueryParam(
        Request $request,
        string $name,
        bool $required = false,
        mixed $default = null
    ): mixed {
        $value = $request->getQuery($name, $default);

        if ($required && ($value === null || $value === '')) {
            throw new \InvalidArgumentException("Parâmetro '{$name}' não informado.");
        }

        return $value;
    }
}
