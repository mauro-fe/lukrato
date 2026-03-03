<?php

/**
 * 404 - Página Não Encontrada
 */

$errorCode        = 404;
$errorTitle       = 'Página não encontrada';
$errorDescription = 'A página que você está procurando pode ter sido removida, teve seu nome alterado ou está temporariamente indisponível.';
$pageTitle        = '404 - Página Não Encontrada | Lukrato';

// Ícone: File com interrogação (Lucide file-question)
$errorIconSvg = '<svg viewBox="0 0 24 24">
    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
    <path d="M10 10.3c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/>
    <path d="M12 17h.01"/>
</svg>';

include __DIR__ . '/partials/error-layout.php';