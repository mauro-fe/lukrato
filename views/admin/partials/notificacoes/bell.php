<?php
// CSS: resources/css/admin/base.css (carregado via Vite)
// JS:  resources/js/admin/global/notification-manager.js (carregado via Vite bundle)

/** @var int $initialUnread */
$initialUnread = (int)($initialUnread ?? 0);
$csrf = csrf_token();
$badgeStyle = $initialUnread > 0 ? 'inline-flex' : 'none';
$initialBadgeLabel = $initialUnread > 99 ? '99+' : $initialUnread;
?>
<button id="lk-bell" type="button" aria-label="Notificacoes" aria-haspopup="dialog" aria-expanded="false"
    aria-controls="lk-bell-menu">
    <i data-lucide="bell"></i>
    <span id="lk-bell-badge" <?= $initialUnread > 0 ? '' : 'class="hidden"' ?>>
        <?= $initialBadgeLabel ?>
    </span>
</button>

<div id="lk-bell-menu" class="lk-popover hidden" role="dialog" aria-labelledby="lk-bell-title"
    data-csrf="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <div class="lk-popover-card">
        <div class="lk-popover-h">
            <div class="lk-popover-title-group">
                <span class="lk-popover-title-icon" aria-hidden="true">
                    <i data-lucide="bell-ring"></i>
                </span>
                <div class="lk-popover-title-copy">
                    <h2 id="lk-bell-title">Avisos</h2>
                    <span>Central de notificações</span>
                </div>
            </div>
            <span class="lk-popover-summary" id="lk-bell-summary">
                <?= $initialUnread > 0 ? $initialBadgeLabel . ' nao lido(s)' : 'Tudo lido' ?>
            </span>
        </div>
        <div id="lk-bell-list" class="lk-popover-b">
            <div class="lk-popover-state lk-popover-state--empty">
                <span class="lk-popover-state-icon" aria-hidden="true">
                    <i data-lucide="bell"></i>
                </span>
                <span>Clique no sino para carregar os avisos.</span>
            </div>
        </div>
        <div class="lk-popover-f">
            <button id="lk-mark-read" class="lk-btn" type="button" disabled>
                <i data-lucide="check-check"></i>
                <span>Marcar como lidas</span>
            </button>
        </div>
    </div>
</div>