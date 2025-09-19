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
    function loadPageCss(?string $name = null): void
    {
        // se vier um nome, usa; senão cai no current_view
        $view = $name ?: ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        $cssPath = 'assets/css/' . $view . '.css';
        $abs     = __DIR__ . '/../../public/' . $cssPath;

        if (file_exists($abs)) {
            // cache-busting simples pelo mtime do arquivo
            $v = filemtime($abs) ?: time();
            echo '<link rel="stylesheet" href="' . BASE_URL . $cssPath . '?v=' . $v . '">' . PHP_EOL;
        }
    }
}



if (!function_exists('loadPageJs')) {

    function loadPageJs(?string $view = null): void
    {
        // 1) origem do nome
        $view = $view ?? ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        // 2) candidatos
        $candidates = [];
        $candidates[] = 'assets/js/' . $view . '.js';
        $candidates[] = 'assets/js/' . str_replace(['\\', '/'], '-', $view) . '.js';

        $parts = preg_split('#[\\/]+#', $view);
        if ($parts && count($parts) >= 2) {
            $candidates[] = 'assets/js/' . implode('-', array_slice($parts, 0, -1)) . '.js';
        }

        // 3) injeta o primeiro que existir
        $publicRoot = __DIR__ . '/../../public/';
        foreach ($candidates as $jsPath) {
            if (file_exists($publicRoot . $jsPath)) {
                echo '<script src="' . BASE_URL . $jsPath . '" defer></script>' . PHP_EOL;
                return;
            }
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
