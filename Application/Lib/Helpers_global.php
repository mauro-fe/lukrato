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

        static $cache = [];
        if (isset($cache[$tokenId]) && is_string($cache[$tokenId]) && $cache[$tokenId] !== '') {
            return $cache[$tokenId];
        }

        if (isset($_SESSION['csrf_tokens'][$tokenId]['value']) && is_string($_SESSION['csrf_tokens'][$tokenId]['value'])) {
            $cache[$tokenId] = (string) $_SESSION['csrf_tokens'][$tokenId]['value'];
            return $cache[$tokenId];
        }

        $cache[$tokenId] = CsrfMiddleware::generateToken($tokenId);
        return $cache[$tokenId];
    }
}

if (!function_exists('csrf_input')) {

    function csrf_input(string $tokenId = 'default'): string
    {
        $token = csrf_token($tokenId);
        $tokenEsc = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $idEsc = htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="csrf_token" data-csrf-id="' . $idEsc . '" value="' . $tokenEsc . '">';
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(string $tokenId = 'default'): string
    {
        $token = csrf_token($tokenId);
        $tokenEsc = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $idEsc = htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8');

        return '<meta name="csrf-token" data-csrf-id="' . $idEsc . '" content="' . $tokenEsc . '">' . PHP_EOL
            . '<meta name="csrf-token-id" content="' . $idEsc . '">';
    }
}

if (!function_exists('public_path')) {
    /**
     * Retorna o caminho absoluto da pasta pública (document root da aplicação).
     * Funciona com /public no local e também quando o public é a própria raiz no host.
     */
    function public_path(string $relative = ''): string
    {
        // Se existe constante, use (você pode definir em config quando quiser)
        if (defined('PUBLIC_PATH') && is_string(PUBLIC_PATH) && PUBLIC_PATH !== '') {
            $base = rtrim(PUBLIC_PATH, DIRECTORY_SEPARATOR);
        } else {
            // SCRIPT_FILENAME costuma apontar pro arquivo realmente executado (index.php)
            $script = $_SERVER['SCRIPT_FILENAME'] ?? '';

            // pasta onde está o index.php (ou arquivo front controller)
            $base = $script ? dirname($script) : getcwd();

            // Se o index.php estiver dentro de /public, ok.
            // Se estiver fora, tentamos achar /public ao lado.
            if ($base && is_dir($base . DIRECTORY_SEPARATOR . 'public')) {
                $base = $base . DIRECTORY_SEPARATOR . 'public';
            }
        }

        $relative = ltrim($relative, '/\\');
        return $relative === ''
            ? $base
            : $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    }
}

if (!function_exists('asset_url')) {
    /**
     * Monta URL do asset usando BASE_URL e normaliza barras.
     */
    function asset_url(string $path): string
    {
        $path = ltrim($path, '/');
        return rtrim(BASE_URL, '/') . '/' . $path;
    }
}

if (!function_exists('loadPageCss')) {
    function loadPageCss(?string $name = null): void
    {
        $view = $name ?: ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        // 1. Preferir concatenador PHP (.css.php) em bundles/
        $phpPath = 'assets/css/bundles/' . $view . '.css.php';
        $absPhp  = public_path($phpPath);

        if (is_file($absPhp)) {
            $v = @filemtime($absPhp) ?: time();
            echo '<link rel="stylesheet" href="' . asset_url($phpPath) . '?v=' . $v . '">' . PHP_EOL;
            return;
        }

        // 2. Fallback: CSS estático nas subpastas organizadas
        $folders = ['pages', 'layout', 'modules', 'auth', 'core', ''];
        foreach ($folders as $folder) {
            $cssPath = 'assets/css/' . ($folder ? $folder . '/' : '') . $view . '.css';
            $abs     = public_path($cssPath);

            if (is_file($abs)) {
                $v = @filemtime($abs) ?: time();
                echo '<link rel="stylesheet" href="' . asset_url($cssPath) . '?v=' . $v . '">' . PHP_EOL;
                return;
            }
        }
    }
}

if (!function_exists('loadPageJs')) {
    function loadPageJs(?string $view = null): void
    {
        static $loadedScripts = [];

        $view = $view ?? ($GLOBALS['current_view'] ?? '');
        if ($view === '') return;

        // ── Vite entry points ──────────────────────────────────
        // Mapeamento: identificador da view → entry relativo a resources/js/
        static $viteEntries = [
            'admin-lancamentos-index'   => 'admin/lancamentos/index.js',
            'admin-cartoes-index'       => 'admin/cartoes/index.js',
            'admin-contas-index'        => 'admin/contas/index.js',
            'admin-faturas-index'       => 'admin/faturas/index.js',
            'admin-financas-index'      => 'admin/financas/index.js',
            'admin-relatorios-index'    => 'admin/relatorios/index.js',
            'admin-categorias-index'    => 'admin/categorias/index.js',
            'admin-dashboard-index'     => 'admin/dashboard/index.js',
            'admin-cartoes-arquivadas'  => 'admin/cartoes-arquivadas/index.js',
            'admin-gamification-index'  => 'admin/gamification/index.js',
            'admin-billing-index'       => 'admin/billing/index.js',
            'admin-perfil-index'        => 'admin/perfil/index.js',
            'admin-sysadmin-index'      => 'admin/sysadmin/index.js',
            'admin-auth-login'          => 'admin/auth/login/index.js',
            'admin-auth-forgot-password' => 'admin/auth/forgot-password/index.js',
            'admin-auth-reset-password' => 'admin/auth/reset-password/index.js',
            'admin-auth-verify-email'   => 'admin/auth/verify-email/index.js',
            'admin-contas-arquivadas'   => 'admin/contas-arquivadas/index.js',
            'admin-onboarding-index'    => 'admin/onboarding/index.js',
            'admin-onboarding-lancamento' => 'admin/onboarding/lancamento.js',
            'admin-sysadmin-communications' => 'admin/sysadmin/communications.js',
            'admin-sysadmin-cupons'     => 'admin/sysadmin/cupons.js',
        ];

        if (isset($viteEntries[$view]) && function_exists('vite_scripts')) {
            $entry = $viteEntries[$view];
            $key   = 'vite:' . $entry;
            if (!in_array($key, $loadedScripts, true)) {
                echo vite_scripts($entry);
                $loadedScripts[] = $key;
            }
            return;
        }

        // ── Legacy fallback (arquivos em assets/js/) ───────────
        $candidates = [];
        $candidates[] = 'assets/js/' . $view . '.js';
        $candidates[] = 'assets/js/' . str_replace(['\\', '/'], '-', $view) . '.js';

        $parts = preg_split('#[\\/]+#', $view);
        if ($parts && count($parts) >= 2) {
            $candidates[] = 'assets/js/' . implode('-', array_slice($parts, 0, -1)) . '.js';
        }

        foreach ($candidates as $jsPath) {
            $abs = public_path($jsPath);

            if (is_file($abs)) {
                if (in_array($jsPath, $loadedScripts, true)) return;

                $v = @filemtime($abs) ?: time();
                echo '<script src="' . asset_url($jsPath) . '?v=' . $v . '" defer></script>' . PHP_EOL;

                $loadedScripts[] = $jsPath;
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

function config(string $key, $default = null)
{
    static $configs = [];

    [$file, $item] = explode('.', $key, 2);

    if (!isset($configs[$file])) {
        $path = __DIR__ . "/../Config/{$file}.php";

        $configs[$file] = file_exists($path)
            ? require $path
            : [];
    }

    return $configs[$file][$item] ?? $default;
}

/**
 * Converte um nome de intervalo (EN/PT) para um rótulo amigável em PT-BR.
 */
function formatInterval(string $interval): string
{
    return match (strtolower($interval)) {
        'year', 'ano', 'anual', 'annual' => 'ano',
        'week', 'semanal'                => 'semana',
        'day', 'dia', 'daily'            => 'dia',
        default                          => 'mês',
    };
}

/**
 * Mapeia um ícone FontAwesome (fa-xxx) para o equivalente Lucide.
 * Retorna o nome Lucide pronto para uso em <i data-lucide="...">.
 */
function faToLucideIcon(string $faIcon, string $fallback = 'layers'): string
{
    static $map = [
        'layer-group' => 'layers',
        'rocket'      => 'rocket',
        'crown'       => 'crown',
        'gem'         => 'gem',
        'star'        => 'star',
        'bolt'        => 'zap',
        'shield-alt'  => 'shield',
        'shield'      => 'shield',
        'infinity'    => 'infinity',
        'gift'        => 'gift',
        'trophy'      => 'trophy',
        'fire'        => 'flame',
        'briefcase'   => 'briefcase',
        'wallet'      => 'wallet',
    ];

    $key = ltrim(trim($faIcon), 'fa-');
    return $map[$key] ?? $fallback;
}
