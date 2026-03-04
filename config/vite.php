<?php

/**
 * Vite Asset Helper
 * 
 * Resolve entry points para os arquivos gerados pelo Vite build.
 * Em desenvolvimento, serve direto do Vite dev server.
 * Em produção, lê o manifest.json gerado pelo build.
 */

defined('VITE_DEV_SERVER') || define('VITE_DEV_SERVER', 'http://localhost:5173');

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

    $manifestPath = dirname(__DIR__) . '/public/build/.vite/manifest.json';
    if (!file_exists($manifestPath)) {
        $manifest = [];
        return $manifest;
    }

    $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
    return $manifest;
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

    // O entry no manifest usa o caminho relativo ao root do Vite
    if (isset($manifest[$entry])) {
        return BASE_URL . 'build/' . $manifest[$entry]['file'];
    }

    // Fallback: tentar com prefixo admin/
    $altEntry = 'admin/' . $entry;
    if (isset($manifest[$altEntry])) {
        return BASE_URL . 'build/' . $manifest[$altEntry]['file'];
    }

    // Se não encontrou no manifest, retorna o path direto (dev fallback)
    return BASE_URL . 'build/' . $entry;
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
    $manifestEntry = $manifest[$entry] ?? null;

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
        return sprintf(
            '<link rel="stylesheet" href="%s/%s">' . "\n",
            VITE_DEV_SERVER,
            $entry
        );
    }

    $manifest = vite_manifest();

    // Buscar no manifest — Vite usa o path relativo ao root do config
    // Para CSS entries, o manifest key pode usar caminhos variados
    $manifestEntry = $manifest[$entry] ?? null;

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

    // Fallback: tentar path direto
    return sprintf(
        '<link rel="stylesheet" href="%sbuild/%s">' . "\n",
        BASE_URL,
        $entry
    );
}
