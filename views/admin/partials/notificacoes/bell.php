<style>
    .hidden {
        display: none !important;
    }
</style>

<?php

/** @var int $initialUnread */
/** @var string $csrf */
$initialUnread = (int)($initialUnread ?? 0);
$badgeStyle = $initialUnread > 0 ? 'inline-block' : 'none';
?>
<button id="lk-bell" class="btn btn-ghost relative" aria-label="Notificacoes">
    <i class="fas fa-bell"></i>
    <span id="lk-bell-badge" class="absolute -top-1 -right-1 rounded-full text-xs px-1.5 py-0.5"
        style="background: var(--color-danger); color: #fff; display: <?= $badgeStyle ?>;">
        <?= $initialUnread ?>
    </span>
</button>

<div id="lk-bell-menu" class="lk-popover hidden" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="lk-popover-card">
        <div class="lk-popover-h">Avisos</div>
        <div id="lk-bell-list" class="lk-popover-b"></div>
        <div class="lk-popover-f">
            <button id="lk-mark-read" class="lk-btn">Marcar como lidas</button>
        </div>
    </div>
</div>