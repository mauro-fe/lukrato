<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class RequestHandler
{
    public function parseRoute(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $basePath = $this->getBasePath();

        if ($basePath && strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        $parsedUrl = parse_url($requestUri);
        $route = $parsedUrl['path'] ?? '/';

        return $this->normalizeRoute($route);
    }

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    private function getBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/index.php', '', dirname($scriptName));
        $basePath = rtrim($basePath, '/');

        return ($basePath === '' || $basePath === '.') ? '' : $basePath;
    }

    private function normalizeRoute(string $route): string
    {
        $route = '/' . trim($route, '/');
        return ($route === '//') ? '/' : $route;
    }
}
