<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core;

use Safi\Core\Contracts\DatabaseDriverInterface;
use Safi\Core\Contracts\ModelInterface;
use Safi\Core\Contracts\ViewEngineInterface;
use Safi\Core\Exception\ValidationException;
use Safi\Core\Http\Request;
use Safi\Core\Http\Response;
use Safi\Core\Services\SecurityService;

abstract class AbstractController
{
    public function __construct(
        protected ViewEngineInterface $view,
        protected Request $request,
        protected SecurityService $security,
        protected DatabaseDriverInterface $db,
    ) {}

    protected function validateCsrf(): void
    {
        $token = $this->request->post('csrf_token');
        $tokenString = is_string($token) ? $token : null;

        if (!$this->security->validateCsrfToken($tokenString)) {
            throw new ValidationException("CSRF token validation failed.");
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = []): Response
    {
        return new Response(
            $this->view->render($template, $data),
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function jsonResponse(array $data, int $status = 200): Response
    {
        return new Response(
            json_encode($data, JSON_THROW_ON_ERROR),
            $status,
            [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        );
    }

    /**
     * Resolves a domain model by ID or throws a ValidationException (µADR-025).
     *
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @return T
     */
    protected function findModelOrFail(string $modelClass, int $id): ModelInterface
    {
        $model = $this->db->loadModel($modelClass, $id);
        if ($model->getId() === 0) {
            throw new ValidationException("Entity resource '{$modelClass}' matching ID '{$id}' was not found.");
        }

        return $model;
    }

    protected function redirect(string $url): Response
    {
        return new Response('', 302, ['Location' => $url]);
    }
}
