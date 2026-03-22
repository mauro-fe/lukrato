<?php

declare(strict_types=1);

namespace Application\Core\Routing;

use Application\Core\Request;
use Application\Core\Response;

class ErrorResponseFactory
{
    public function notFound(?Request $request = null): Response
    {
        if ($this->wantsJson($request)) {
            return Response::errorResponse('Recurso não encontrado', 404, null, 'RESOURCE_NOT_FOUND');
        }

        return $this->viewError(404, BASE_PATH . '/views/errors/404.php', 'Página não encontrada');
    }

    public function forbidden(?Request $request = null): Response
    {
        if ($this->wantsJson($request)) {
            return Response::forbiddenResponse('Acesso negado');
        }

        return $this->viewError(403, BASE_PATH . '/views/errors/403.php', 'Acesso negado');
    }

    public function methodNotAllowed(?Request $request = null, array $allowedMethods = []): Response
    {
        $allowHeader = implode(', ', array_values(array_unique($allowedMethods)));

        if ($this->wantsJson($request)) {
            $response = Response::errorResponse('Metodo nao permitido', 405);

            return $allowHeader !== ''
                ? $response->header('Allow', $allowHeader)
                : $response;
        }

        $response = $this->viewError(405, BASE_PATH . '/views/errors/405.php', 'Metodo nao permitido');

        return $allowHeader !== ''
            ? $response->header('Allow', $allowHeader)
            : $response;
    }

    public function tooManyRequests(?Request $request = null, int $retryAfter = 60): Response
    {
        if ($this->wantsJson($request)) {
            return Response::errorResponse('Muitas requisições. Tente novamente mais tarde.', 429)
                ->header('Retry-After', (string) $retryAfter);
        }

        return $this->viewError(429, BASE_PATH . '/views/errors/429.php', 'Muitas requisições', [
            'retryAfter' => $retryAfter,
        ])->header('Retry-After', (string) $retryAfter);
    }

    public function viewError(int $code, string $viewPath, string $defaultMessage, array $data = []): Response
    {
        if (file_exists($viewPath)) {
            ob_start();
            extract($data, EXTR_SKIP);
            include $viewPath;
            $html = (string) ob_get_clean();

            return Response::htmlResponse($html, $code);
        }

        return Response::htmlResponse("<h2>{$code} | {$defaultMessage}</h2>", $code);
    }

    private function wantsJson(?Request $request = null): bool
    {
        return $request?->wantsJson() || $request?->isAjax();
    }
}
