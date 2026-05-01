<?php

declare(strict_types=1);

namespace Application\Controllers\Concerns;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Response;
use Application\Core\View;
use Application\Support\Admin\AdminModuleRegistry;

trait HandlesWebPresentation
{
    protected function renderResponse(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): Response
    {
        $normalizedViewPath = trim(str_replace('\\', '/', $viewPath), '/');
        $defaultViewId = trim(str_replace('/', '-', $normalizedViewPath), '-');

        if (!isset($data['currentViewPath']) || !is_string($data['currentViewPath']) || trim($data['currentViewPath']) === '') {
            $data['currentViewPath'] = $normalizedViewPath;
        }

        if (!isset($data['currentViewId']) || !is_string($data['currentViewId']) || trim($data['currentViewId']) === '') {
            $data['currentViewId'] = $defaultViewId;
        }

        if (empty($data['menu'])) {
            $data['menu'] = AdminModuleRegistry::resolveMenuByViewContext($viewPath, $data)
                ?? $this->inferMenuFromView($viewPath)
                ?? $data['menu']
                ?? null;
        }

        if ($header === 'admin/partials/header') {
            $data = $this->injectAdminLayoutData($data);
        }

        if ($header === 'site/partials/header') {
            $data = $this->injectSiteLayoutData($data);
        }

        $view = new View($viewPath, $data);
        if ($header) {
            $view->setHeader($header);
        }
        if ($footer) {
            $view->setFooter($footer);
        }

        return Response::htmlResponse($view->render());
    }

    protected function renderAdminResponse(string $viewPath, array $data = []): Response
    {
        return $this->renderResponse($viewPath, $data, 'admin/partials/header', 'admin/partials/footer');
    }

    protected function buildRedirectResponse(string $path, int $statusCode = 302): Response
    {
        $url = filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');

        return Response::redirectResponse($url, $statusCode);
    }

    protected function throwRedirectResponse(string $path, int $statusCode = 302): never
    {
        throw new HttpResponseException($this->buildRedirectResponse($path, $statusCode));
    }

    protected function setError(string $message): void
    {
        $this->putSessionValue('error', $message);
    }

    protected function setSuccess(string $message): void
    {
        $this->putSessionValue('success', $message);
    }

    protected function getError(): ?string
    {
        $message = $this->pullSessionValue('error');
        return is_string($message) ? $message : null;
    }

    protected function getSuccess(): ?string
    {
        $message = $this->pullSessionValue('success');
        return is_string($message) ? $message : null;
    }

    protected function putSessionValue(string $key, mixed $value): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        $_SESSION[$key] = $value;
    }

    protected function pullSessionValue(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION)) {
            return $default;
        }

        if (!array_key_exists($key, $_SESSION)) {
            return $default;
        }

        $value = $_SESSION[$key];
        unset($_SESSION[$key]);

        return $value;
    }

    protected function inferMenuFromView(string $viewPath): ?string
    {
        $menu = AdminModuleRegistry::inferMenuFromViewPath($viewPath);
        if ($menu !== null) {
            return $menu;
        }

        $trimmed = trim($viewPath, '/');
        $segments = preg_split('#[\\/]+#', $trimmed);
        if (($segments[0] ?? null) !== 'admin') {
            return null;
        }

        return match ($segments[1] ?? null) {
            // Backward-compatible fallback for any non-registered legacy path.
            'sysadmin' => 'super_admin',
            default    => null,
        };
    }
}
