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
    function loadPageCss(?string $view = null): void
    {
        // 1) De onde vem o nome da view?
        $view = $view ?? ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        // 2) Candidatos de caminhos para o CSS
        $candidates = [];

        // assets/css/admin/home/header.css  (mesma estrutura de pastas)
        $candidates[] = 'assets/css/' . $view . '.css';

        // assets/css/admin-home-header.css  (hífens)
        $candidates[] = 'assets/css/' . str_replace(['\\', '/'], '-', $view) . '.css';

        // assets/css/admin-home.css (opcional – pacote por pasta)
        $parts = preg_split('#[\\/]+#', $view);
        if ($parts && count($parts) >= 2) {
            $candidates[] = 'assets/css/' . implode('-', array_slice($parts, 0, -1)) . '.css';
        }

        // 3) Tenta injetar o primeiro que existir
        $publicRoot = __DIR__ . '/../../public/';
        foreach ($candidates as $cssPath) {
            if (file_exists($publicRoot . $cssPath)) {
                echo '<link rel="stylesheet" href="' . BASE_URL . $cssPath . '">' . PHP_EOL;
                return;
            }
        }
    }
}


if (!function_exists('loadPageJs')) {
    /**
     * Carrega dinamicamente um arquivo JS baseado na view atual
     * ou em um nome passado manualmente.
     *
     * Regras de resolução (na ordem):
     *   1) assets/js/{view}.js                     // mesma estrutura de pastas
     *   2) assets/js/{view-com-hifens}.js         // barras/contra-barras -> hífens
     *   3) assets/js/{pacote-da-pasta}.js         // opcional: junta pastas, sem o último segmento
     *
     * Ex.: view = "admin/home/header"
     *   -> assets/js/admin/home/header.js
     *   -> assets/js/admin-home-header.js
     *   -> assets/js/admin-home.js
     *
     * Uso:
     *   <?php loadPageJs(); ?> // usa $GLOBALS['current_view']
     * <?php loadPageJs('admin/home/header'); ?>
     */
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
