<?php

/**
 * Vite Asset Helper
 * 
 * Resolve entry points para os arquivos gerados pelo Vite build.
 * Em desenvolvimento, serve direto do Vite dev server.
 * Em produção, lê o manifest.json gerado pelo build.
 */

defined('VITE_DEV_SERVER') || define('VITE_DEV_SERVER', 'http://localhost:5173');

function vite_manifest_paths(): array
{
    return [
        dirname(__DIR__) . '/public/build/manifest.json',
        dirname(__DIR__) . '/public/build/.vite/manifest.json',
    ];
}

function vite_css_entry_paths(): array
{
    return [
        'admin-base' => 'resources/css/admin/base.css',
        'auth-login-style' => 'resources/css/admin/auth/admin-auth-login.css',
        'auth-shared-style' => 'resources/css/admin/auth/auth-shared.css',
        'auth-verify-email-style' => 'resources/css/admin/auth/auth-verify-email.css',
        'site-app' => 'resources/css/site/app.css',
        'site-base' => 'resources/css/site/base.css',
        'site-legal' => 'resources/css/site/legal.css',
        'site-aprenda' => 'resources/css/site/aprenda.css',
        'site-card-style' => 'resources/css/site/card.css',
        'site-google-confirm' => 'resources/css/site/auth/google-confirm.css',
        'error-page' => 'resources/css/errors/page.css',
    ];
}

function vite_project_path(string $relativePath): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
}

function vite_dev_css_url(string $entry): string
{
    $entryMap = vite_css_entry_paths();
    $sourcePath = null;

    if (isset($entryMap[$entry])) {
        $sourcePath = vite_project_path($entryMap[$entry]);
    } elseif (str_contains($entry, '/')) {
        $sourcePath = vite_project_path('resources/js/' . ltrim($entry, '/'));
    }

    if ($sourcePath === null || !file_exists($sourcePath)) {
        return '';
    }

    return VITE_DEV_SERVER . '/@fs/' . str_replace('\\', '/', realpath($sourcePath) ?: $sourcePath);
}

/**
 * Verifica se o Vite dev server está rodando
 */
function vite_is_dev(): bool
{
    static $isDev = null;
    if ($isDev !== null) return $isDev;

    // Em produção, nunca é dev
    if (defined('APP_ENV') && APP_ENV === 'production') {
        $isDev = false;
        return false;
    }

    // Checar se existe o arquivo de hot reload do Vite
    $hotFile = dirname(__DIR__) . '/public/build/.vite/hot';
    if (file_exists($hotFile)) {
        $hotTarget = trim((string) @file_get_contents($hotFile));
        if ($hotTarget !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?(?:/|$)#i', $hotTarget) !== 1) {
            $isDev = false;
            return false;
        }

        $isDev = true;
        return true;
    }

    $isDev = false;
    return false;
}

/**
 * Carrega o manifest do Vite build
 */
function vite_manifest(): array
{
    static $manifest = null;
    if ($manifest !== null) return $manifest;

    foreach (vite_manifest_paths() as $manifestPath) {
        if (!file_exists($manifestPath)) {
            continue;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
        return $manifest;
    }

    $manifest = [];
    return $manifest;
}

/**
 * Resolve um entry no manifest por chave, chave alternativa ou nome lógico.
 */
function vite_find_manifest_entry(string $entry, array $manifest): ?array
{
    if (isset($manifest[$entry])) {
        return $manifest[$entry];
    }

    $altEntry = 'admin/' . ltrim($entry, '/');
    if (isset($manifest[$altEntry])) {
        return $manifest[$altEntry];
    }

    foreach ($manifest as $value) {
        $manifestName = $value['name'] ?? null;
        $manifestNames = $value['names'] ?? [];

        if ($manifestName === $entry || in_array($entry . '.js', $manifestNames, true) || in_array($entry . '.css', $manifestNames, true)) {
            return $value;
        }
    }

    return null;
}

/**
 * Retorna a URL do asset Vite
 * 
 * @param string $entry Caminho relativo ao resources/js/ (ex: "admin/lancamentos/index.js")
 * @return string URL completa do asset
 */
function vite_asset(string $entry): string
{
    if (vite_is_dev()) {
        return VITE_DEV_SERVER . '/' . $entry;
    }

    $manifest = vite_manifest();
    $manifestEntry = vite_find_manifest_entry($entry, $manifest);

    if ($manifestEntry && !empty($manifestEntry['file'])) {
        return BASE_URL . 'build/' . $manifestEntry['file'];
    }

    $normalizedEntry = ltrim(str_replace('\\', '/', $entry), '/');
    if (!str_contains($normalizedEntry, '..')) {
        $candidatePath = dirname(__DIR__) . '/public/build/' . $normalizedEntry;
        if (file_exists($candidatePath)) {
            return BASE_URL . 'build/' . $normalizedEntry;
        }
    }

    return '';
}

/**
 * Gera as tags <script> para um entry point Vite
 * 
 * @param string $entry Caminho relativo (ex: "admin/lancamentos/index.js")
 * @return string HTML com script tags
 */
function vite_scripts(string $entry): string
{
    static $viteClientLoaded = false;

    if (vite_is_dev()) {
        $html = '';
        // Carrega o client HMR apenas uma vez
        if (!$viteClientLoaded) {
            $html .= sprintf(
                '<script type="module" src="%s/@vite/client"></script>' . "\n",
                VITE_DEV_SERVER
            );
            $viteClientLoaded = true;
        }
        $html .= sprintf(
            '<script type="module" src="%s/%s"></script>' . "\n",
            VITE_DEV_SERVER,
            $entry
        );
        return $html;
    }

    $manifest = vite_manifest();
    $html = '';

    // Buscar entry no manifest
    $manifestEntry = vite_find_manifest_entry($entry, $manifest);

    if ($manifestEntry) {
        // CSS imports do chunk
        if (!empty($manifestEntry['css'])) {
            foreach ($manifestEntry['css'] as $cssFile) {
                $html .= sprintf(
                    '<link rel="stylesheet" href="%sbuild/%s">' . "\n",
                    BASE_URL,
                    $cssFile
                );
            }
        }

        // Preload dos chunks importados (performance)
        if (!empty($manifestEntry['imports'])) {
            foreach ($manifestEntry['imports'] as $importKey) {
                if (isset($manifest[$importKey])) {
                    $html .= sprintf(
                        '<link rel="modulepreload" href="%sbuild/%s">' . "\n",
                        BASE_URL,
                        $manifest[$importKey]['file']
                    );
                }
            }
        }

        // Script principal
        $html .= sprintf(
            '<script type="module" src="%sbuild/%s"></script>' . "\n",
            BASE_URL,
            $manifestEntry['file']
        );
    }

    return $html;
}

/**
 * Gera a tag <link> para um entry point CSS do Vite
 * 
 * @param string $entry Caminho relativo (ex: "css/site/app.css" relativo a resources/)
 * @return string HTML com link tag
 */
function vite_css(string $entry): string
{
    if (vite_is_dev()) {
        $devUrl = vite_dev_css_url($entry);
        if ($devUrl === '') {
            return '';
        }

        return sprintf(
            '<link rel="stylesheet" href="%s">' . "\n",
            $devUrl
        );
    }

    $manifest = vite_manifest();

    // Buscar no manifest — Vite usa o path relativo ao root do config
    // Para CSS entries, também suportamos lookup por nome lógico
    $manifestEntry = vite_find_manifest_entry($entry, $manifest);

    // Tentar path alternativos
    if (!$manifestEntry) {
        // Tentar com ../ prefix (CSS fica fora do root JS)
        $altEntry = '../css/' . basename(dirname($entry)) . '/' . basename($entry);
        $manifestEntry = $manifest[$altEntry] ?? null;
    }
    if (!$manifestEntry) {
        // Buscar por nome do arquivo em qualquer path do manifest
        foreach ($manifest as $key => $value) {
            if (str_contains($key, basename($entry))) {
                $manifestEntry = $value;
                break;
            }
        }
    }

    if ($manifestEntry) {
        // Um CSS entry gera um .css file direto
        $file = $manifestEntry['file'] ?? '';
        if ($file) {
            return sprintf(
                '<link rel="stylesheet" href="%sbuild/%s">' . "\n",
                BASE_URL,
                $file
            );
        }
    }

    // Fallback seguro: evita apontar para arquivo inexistente (retornaria HTML e erro de MIME)
    $normalizedEntry = ltrim(str_replace('\\', '/', $entry), '/');
    if (!str_contains($normalizedEntry, '..')) {
        $candidatePath = dirname(__DIR__) . '/public/build/' . $normalizedEntry;
        if (file_exists($candidatePath)) {
            return sprintf(
                '<link rel="stylesheet" href="%sbuild/%s">' . "\n",
                BASE_URL,
                $normalizedEntry
            );
        }
    }

    return '';
}
