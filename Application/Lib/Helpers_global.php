<?php

use Carbon\Carbon;
use Application\Lib\Helpers;
use Application\Middlewares\CsrfMiddleware;

if (!function_exists('now')) {
    function now(): Carbon
    {
        return Carbon::now();
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        return Helpers::slugify($text);
    }
}

if (!function_exists('escape')) {
    function escape($value)
    {
        return Helpers::escapeHtml($value);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Retorna apenas o valor do token CSRF (sem HTML)
     *
     * @param string $tokenId
     * @return string
     */
    function csrf_token(string $tokenId = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return CsrfMiddleware::generateToken($tokenId);
    }
}

if (!function_exists('csrf_input')) {
    /**
     * Gera o input HTML de token CSRF
     *
     * @param string $tokenId
     * @return string
     */
    function csrf_input(string $tokenId = 'default'): string
    {
        $token = csrf_token($tokenId);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
if (!function_exists('loadPageCss')) {
    function loadPageCss(): void
    {
        $view = $GLOBALS['current_view'] ?? '';
        $cssPath = 'assets/css/' . $view . '.css';

        if (file_exists(__DIR__ . '/../../public/' . $cssPath)) {
            echo '<link rel="stylesheet" href="' . BASE_URL . $cssPath . '">' . PHP_EOL;
        }
    }
}

if (!function_exists('loadPageJs')) {
    function loadPageJs(): void
    {
        $view = $GLOBALS['current_view'] ?? '';
        $jsPath = 'assets/js/' . $view . '.js';

        if (file_exists(__DIR__ . '/../../public/' . $jsPath)) {
            echo '<script src="' . BASE_URL . $jsPath . '"></script>' . PHP_EOL;
        }
    }
}

function buscarValor($respostas, string $chave): ?string
{
    if ($respostas instanceof \Illuminate\Support\Collection) {
        $respostas = $respostas->all();
    }

    foreach ($respostas as $resposta) {
        if ($resposta->chave === $chave) {
            return $resposta->valor;
        }
    }
    return null;
}

