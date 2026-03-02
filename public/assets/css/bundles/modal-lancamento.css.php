<?php

/**
 * Modal Lançamento CSS - Concatenador de módulos
 * 
 * Serve todos os parciais da pasta modal-lancamento/ como um único arquivo CSS,
 * evitando múltiplas requisições HTTP dos @import.
 */

$cssDir = __DIR__ . '/../modal-lancamento';

$modules = [
    '_base-overlay.css',
    '_header.css',
    '_body.css',
    '_conta-info.css',
    '_historico.css',
    '_tipo-movimentacao.css',
    '_agendamento.css',
    '_formulario.css',
    '_checkbox.css',
    '_radio-buttons.css',
    '_preview-parcelamento.css',
    '_botao-voltar.css',
    '_footer.css',
    '_botoes.css',
    '_form-actions.css',
    '_validacao.css',
    '_responsive-tablet.css',
    '_responsive-mobile.css',
    '_forma-pagamento.css',
    '_datetime-inline.css',
    '_subcategoria-select.css',
];

$lastModified = 0;
foreach ($modules as $mod) {
    $path = $cssDir . '/' . $mod;
    if (file_exists($path)) {
        $mtime = filemtime($path);
        if ($mtime > $lastModified) {
            $lastModified = $mtime;
        }
    }
}

$etag = '"modal-' . md5($lastModified) . '"';

header('Content-Type: text/css; charset=UTF-8');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified)
) {
    http_response_code(304);
    exit;
}

$isProd = defined('APP_ENV') && APP_ENV === 'production';
if ($isProd) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: no-cache, must-revalidate');
}

foreach ($modules as $mod) {
    $path = $cssDir . '/' . $mod;
    if (file_exists($path)) {
        echo "/* === {$mod} === */\n";
        readfile($path);
        echo "\n\n";
    }
}
