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
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Suporte para method spoofing via header (prioridade 1)
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        
        // Suporte via POST _method (prioridade 2)
        if ($method === 'POST' && isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }
        
        return $method;
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
