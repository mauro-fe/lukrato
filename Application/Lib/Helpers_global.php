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
    function csrf_token(string $tokenId = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Cache por request para evitar múltiplas gerações no mesmo carregamento
        static $cache = [];
        if (isset($cache[$tokenId]) && is_string($cache[$tokenId]) && $cache[$tokenId] !== '') {
            return $cache[$tokenId];
        }

        // Reutiliza token existente na sessão quando disponível
        if (isset($_SESSION['csrf_tokens'][$tokenId]['value']) && is_string($_SESSION['csrf_tokens'][$tokenId]['value'])) {
            $cache[$tokenId] = (string) $_SESSION['csrf_tokens'][$tokenId]['value'];
            return $cache[$tokenId];
        }

        // Caso não exista, gera um novo
        $cache[$tokenId] = CsrfMiddleware::generateToken($tokenId);
        return $cache[$tokenId];
    }
}

if (!function_exists('csrf_input')) {

    function csrf_input(string $tokenId = 'default'): string
    {
        $token = csrf_token($tokenId);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(string $tokenId = 'default'): string
    {
        $token = csrf_token($tokenId);
        $tokenEsc = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $idEsc = htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8');

        return '<meta name="csrf-token" content="' . $tokenEsc . '">' . PHP_EOL
            . '<meta name="csrf-token-id" content="' . $idEsc . '">';
    }
}
if (!function_exists('loadPageCss')) {
    function loadPageCss(?string $name = null): void
    {
        $view = $name ?: ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        $cssPath = 'assets/css/' . $view . '.css';
        $abs     = __DIR__ . '/../../public/' . $cssPath;

        if (file_exists($abs)) {
            $v = filemtime($abs) ?: time();
            echo '<link rel="stylesheet" href="' . BASE_URL . $cssPath . '?v=' . $v . '">' . PHP_EOL;
        }
    }
}



if (!function_exists('loadPageJs')) {

    function loadPageJs(?string $view = null): void
    {
        $view = $view ?? ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        $candidates = [];
        $candidates[] = 'assets/js/' . $view . '.js';
        $candidates[] = 'assets/js/' . str_replace(['\\', '/'], '-', $view) . '.js';

        $parts = preg_split('#[\\/]+#', $view);
        if ($parts && count($parts) >= 2) {
            $candidates[] = 'assets/js/' . implode('-', array_slice($parts, 0, -1)) . '.js';
        }

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
