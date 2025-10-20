<style>
    /* =========================================================
 * NAVBAR
 * =======================================================*/

    .lk-navbar {
        color: var(--color-text);
        padding: 30px 10px;
        box-shadow: var(--shadow-sm);
        border-bottom: 1px solid var(--color-border);
        position: sticky;
        top: 0;
        z-index: 1000;
        margin: 0 auto;
        border-radius: var(--radius-md);
        background-color: var(--glass-bg);
        height: 100px;
    }

    .lk-navbar-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-lg);
        height: 100%;
        padding: 0 20px;
    }

    /* ===============================
 * LADO ESQUERDO (logo / titulo)
 * =============================== */
    .lk-navbar-left {
        display: flex;
        align-items: center;
        gap: var(--spacing-lg);
    }

    .lk-navbar-left h1 {
        font-size: 1.6rem;
        font-weight: 600;
        color: var(--color-primary);
        margin: 0;
        border-bottom: 3px solid var(--color-primary);
        letter-spacing: 1px;
        line-height: 1.5;
    }

    .lk-navbar-left h1 span {
        color: var(--color-text);
        font-size: 1.4rem;

    }

    /* ===============================
 * LADO DIREITO (acoes / botoes)
 * =============================== */
    .lk-navbar-right {
        display: flex;
        align-items: center;
        gap: var(--spacing-lg);
    }

    .lk-navbar-right button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-xs);
        background-color: var(--color-primary);
        color: var(--color-text);
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        padding: 8px 14px;
        cursor: pointer;
        transition: all var(--transition-fast);
        height: 40px;
        margin-left: 20px;
    }

    .lk-navbar-right button:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
        transform: translateY(-2px);
    }

    .lk-navbar-right button:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .lk-navbar-right button i {
        font-size: 1rem;
    }

    /* ===============================
 * NOTIFICACOES
 * =============================== */
    .lk-navbar-notifications {
        position: relative;
    }

    #lk-bell {
        background-color: var(--color-primary);
        color: var(--color-text);
        border-radius: var(--radius-md);
        padding: 8px 12px;
        font-size: 1rem;
        transition: all var(--transition-fast);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #lk-bell:hover {
        background-color: var(--color-bg);
        color: #fff;
        border-color: var(--color-primary);
    }

    #lk-bell:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .lk-navbar-notifications .lk-popover {
        position: absolute;
        top: calc(100% + var(--spacing-sm));
        right: -30;
        z-index: 1100;
        width: clamp(250px, 40vw, 200px);
    }

    .lk-popover-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md, 0 12px 32px rgba(0, 0, 0, 0.35));
        overflow: hidden;
    }

    .lk-popover-h {
        padding: 12px 16px;
        font-weight: 600;
        border-bottom: 1px solid var(--color-border);
        background: var(--color-surface-muted);
    }

    .lk-popover-b {
        max-height: 320px;
        overflow-y: auto;
        padding: 12px 16px;
        background: var(--glass-bg);
    }

    .lk-popover-f {
        padding: 12px 16px;
        border-top: 1px solid var(--color-border);
        text-align: right;
        background: var(--color-surface-muted);
    }

    .lk-notification-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .lk-notification-item {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: var(--radius-md);
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        transition: border-color var(--transition-fast), background var(--transition-fast);
    }

    .lk-notification-item.unread {
        border-color: var(--color-primary);
        background: rgba(255, 255, 255, 0.08);
    }

    .lk-notification-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--color-text);
    }

    .lk-notification-message {
        font-size: 0.85rem;
        color: var(--color-text-muted);
        margin: 0;
        line-height: 1.4;
    }

    .lk-notification-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 0.75rem;
        color: var(--color-text-muted);
    }

    .lk-notification-link {
        color: var(--color-primary);
        text-decoration: none;
        font-weight: 600;
    }

    .lk-notification-link:hover {
        text-decoration: underline;
    }

    .lk-notification-empty,
    .lk-notification-loading,
    .lk-notification-error {
        font-size: 0.85rem;
        color: var(--color-text-muted);
        text-align: center;
        padding: 16px;
    }

    .lk-notification-error {
        color: var(--color-danger);
    }

    /* ===============================
 * RESPONSIVIDADE
 * =============================== */
    @media (max-width: 768px) {
        .lk-navbar {
            height: auto;
            padding: var(--spacing-md);
        }

        .lk-navbar-inner {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--spacing-md);
        }

        .lk-navbar-right {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .lk-navbar-right button {
            padding: 6px 10px;
            font-size: 0.85rem;
            height: 36px;
        }

        .lk-navbar-notifications {
            width: auto;
        }
    }

    /* =========================================================
 * HEADER CONTROLS (menu lateral & tema)
 * =======================================================*/

    .theme-toggle {
        border: 1px solid var(--glass-border);
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: background var(--transition-fast);
        color: var(--color-text);
    }

    .theme-toggle:hover {
        background: var(--glass-border);
    }

    .theme-toggle i {
        position: absolute;
        font-size: 18px;
        transition: opacity var(--transition-fast), transform var(--transition-fast);
    }

    .theme-toggle i.fa-sun {
        opacity: 1;
        transform: rotate(0deg);
    }

    .theme-toggle i.fa-moon {
        opacity: 0;
        transform: rotate(-90deg);
    }

    .theme-toggle.dark i.fa-sun {
        opacity: 0;
        transform: rotate(90deg);
    }

    .theme-toggle.dark i.fa-moon {
        opacity: 1;
        transform: rotate(0deg);
    }
</style>

<nav class="lk-navbar" data-aos="fade-up">
    <div class="lk-navbar-inner">
        <div class="lk-navbar-left">
            <h1><?= $pageTitle ?? 'Painel' ?> - <span><?= $subTitle ?></span></h1>
        </div>

        <div class="lk-navbar-right">
            <button id="toggleTheme" type="button" class="nav-item theme-toggle" aria-label="Alternar tema"
                title="Modo claro/escuro">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
            </button>

            <?php include __DIR__ . '/notificacoes/bell.php'; ?>


        </div>
    </div>
</nav>

<?php include __DIR__ . '/modals/modal_lancamento.php'; ?>
<?php include __DIR__ . '/modals/modal_agendamento.php'; ?>