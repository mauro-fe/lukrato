<?php
// CSS: public/assets/css/layout/notifications-bell.css (carregado via header.php)
// JS:  resources/js/admin/global/notification-manager.js (carregado via Vite bundle)

/** @var int $initialUnread */
$initialUnread = (int)($initialUnread ?? 0);
$csrf = csrf_token();
$badgeStyle = $initialUnread > 0 ? 'inline-flex' : 'none';
$initialBadgeLabel = $initialUnread > 99 ? '99+' : $initialUnread;
?>
<button id="lk-bell" aria-label="Notificacoes" aria-expanded="false">
    <i data-lucide="bell"></i>
    <span id="lk-bell-badge" <?= $initialUnread > 0 ? '' : 'class="hidden"' ?>>
        <?= $initialBadgeLabel ?>
    </span>
</button>

<div id="lk-bell-menu" class="lk-popover hidden" data-csrf="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <div class="lk-popover-card">
        <div class="lk-popover-h">Avisos</div>
        <div id="lk-bell-list" class="lk-popover-b">
            <div class="py-3 text-center" style="opacity:.75">Clique no sino para carregar os avisos.</div>
        </div>
        <div class="lk-popover-f">
            <button id="lk-mark-read" class="lk-btn" disabled>Marcar como lidas</button>
        </div>
    </div>
</div>