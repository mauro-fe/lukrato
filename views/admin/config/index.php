<?php
// views/admin/config/index.php
$pageTitle = "Configurações";
$admin_username = $_SESSION['admin_username'] ?? 'admin';

// valores atuais vindos do banco (exemplos; substitua pelas variáveis reais do seu controller)
$current = [
    'locale'         => $current['locale']         ?? 'pt_BR',
    'timezone'       => $current['timezone']       ?? 'America/Sao_Paulo',
    'currency'       => $current['currency']       ?? 'BRL',
    'date_format'    => $current['date_format']    ?? 'dd/mm/yyyy',
    'number_format'  => $current['number_format']  ?? 'pt-BR',

    'theme'          => $current['theme']          ?? 'system',
    'density'        => $current['density']        ?? 'comfortable',
    'sidebar'        => $current['sidebar']        ?? 'expanded',
    'show_balances'  => $current['show_balances']  ?? 'yes',

    'default_account' => $current['default_account'] ?? null,
    'month_start'    => $current['month_start']    ?? 1,

    'notify_email'   => $current['notify_email']   ?? 'yes',
    'notify_low'     => $current['notify_low']     ?? 'no',
    'notify_goals'   => $current['notify_goals']   ?? 'yes',
];

// exemplo de contas para o select; troque pelo que vier do banco
$contas = $contas ?? [
    ['id' => 1, 'nome' => 'Carteira'],
    ['id' => 2, 'nome' => 'Caixa Econômica'],
    ['id' => 3, 'nome' => 'Nubank'],
];
?>

<main class="main-content position-relative border-radius-lg">
    <!-- Navbar / Breadcrumb -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-sm border-radius-xl bg-white" id="navbarBlur" navbar-scroll="true">
        <div class="container-fluid py-1 px-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm">
                        <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/home" class="text-muted"><?= htmlspecialchars($admin_username) ?></a>
                    </li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Configurações</li>
                </ol>
                <h6 class="font-weight-bolder mb-0">Configurações</h6>
            </nav>
        </div>
    </nav>

    <!-- Conteúdo -->
    <div class="lukrato-config-wrapper">
        <header class="lkc-header">
            <div class="lkc-title">
                <span class="lkc-icon">⚙️</span>
                <h1>Configurações do Sistema</h1>
                <p class="lkc-sub">Ajuste preferências gerais, aparência, financeiro e notificações.</p>
            </div>
        </header>

        <!-- Abas -->
        <div class="lkc-tabs" data-active="geral">
            <button class="lkc-tab is-active" data-tab="geral">Geral</button>
            <button class="lkc-tab" data-tab="aparencia">Aparência</button>
            <button class="lkc-tab" data-tab="financeiro">Financeiro</button>
            <button class="lkc-tab" data-tab="notificacoes">Notificações</button>
        </div>

        <form id="form-config" class="lkc-form"
            action="<?= BASE_URL ?>api/config"
            method="POST" enctype="multipart/form-data" novalidate>
            <?= csrf_input('default') ?>

            <!-- TAB: GERAL -->
            <section class="lkc-panel is-active" data-panel="geral">
                <div class="lkc-grid">
                    <div class="lkc-field">
                        <label for="locale">Idioma</label>
                        <select id="locale" name="locale" class="lkc-input">
                            <option value="pt_BR" <?= $current['locale'] === 'pt_BR' ? 'selected' : '' ?>>Português (Brasil)</option>
                            <option value="en_US" <?= $current['locale'] === 'en_US' ? 'selected' : '' ?>>English (US)</option>
                            <option value="es_ES" <?= $current['locale'] === 'es_ES' ? 'selected' : '' ?>>Español</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="timezone">Fuso horário</label>
                        <select id="timezone" name="timezone" class="lkc-input">
                            <option value="America/Sao_Paulo" <?= $current['timezone'] === 'America/Sao_Paulo' ? 'selected' : '' ?>>America/Sao_Paulo</option>
                            <option value="America/Manaus" <?= $current['timezone'] === 'America/Manaus' ? 'selected' : '' ?>>America/Manaus</option>
                            <option value="UTC" <?= $current['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="currency">Moeda padrão</label>
                        <select id="currency" name="currency" class="lkc-input">
                            <option value="BRL" <?= $current['currency'] === 'BRL' ? 'selected' : '' ?>>BRL (R$)</option>
                            <option value="USD" <?= $current['currency'] === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                            <option value="EUR" <?= $current['currency'] === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="date_format">Formato de data</label>
                        <select id="date_format" name="date_format" class="lkc-input">
                            <option value="dd/mm/yyyy" <?= $current['date_format'] === 'dd/mm/yyyy' ? 'selected' : '' ?>>dd/mm/yyyy</option>
                            <option value="mm/dd/yyyy" <?= $current['date_format'] === 'mm/dd/yyyy' ? 'selected' : '' ?>>mm/dd/yyyy</option>
                            <option value="yyyy-mm-dd" <?= $current['date_format'] === 'yyyy-mm-dd' ? 'selected' : '' ?>>yyyy-mm-dd</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="number_format">Formato numérico</label>
                        <select id="number_format" name="number_format" class="lkc-input">
                            <option value="pt-BR" <?= $current['number_format'] === 'pt-BR' ? 'selected' : '' ?>>1.234,56 (pt-BR)</option>
                            <option value="en-US" <?= $current['number_format'] === 'en-US' ? 'selected' : '' ?>>1,234.56 (en-US)</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- TAB: APARÊNCIA -->
            <section class="lkc-panel" data-panel="aparencia">
                <div class="lkc-grid">
                    <div class="lkc-field">
                        <label for="theme">Tema</label>
                        <select id="theme" name="theme" class="lkc-input">
                            <option value="system" <?= $current['theme'] === 'system' ? 'selected' : '' ?>>Seguir o sistema</option>
                            <option value="light" <?= $current['theme'] === 'light' ? 'selected' : '' ?>>Claro</option>
                            <option value="dark" <?= $current['theme'] === 'dark'  ? 'selected' : '' ?>>Escuro</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="density">Densidade</label>
                        <select id="density" name="density" class="lkc-input">
                            <option value="comfortable" <?= $current['density'] === 'comfortable' ? 'selected' : '' ?>>Confortável</option>
                            <option value="compact" <?= $current['density'] === 'compact'    ? 'selected' : '' ?>>Compacta</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="sidebar">Barra lateral</label>
                        <select id="sidebar" name="sidebar" class="lkc-input">
                            <option value="expanded" <?= $current['sidebar'] === 'expanded' ? 'selected' : '' ?>>Expandida</option>
                            <option value="collapsed" <?= $current['sidebar'] === 'collapsed' ? 'selected' : '' ?>>Recolhida</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="show_balances">Mostrar saldos na home</label>
                        <select id="show_balances" name="show_balances" class="lkc-input">
                            <option value="yes" <?= $current['show_balances'] === 'yes' ? 'selected' : '' ?>>Sim</option>
                            <option value="no" <?= $current['show_balances'] === 'no' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- TAB: FINANCEIRO -->
            <section class="lkc-panel" data-panel="financeiro">
                <div class="lkc-grid">
                    <div class="lkc-field">
                        <label for="default_account">Conta padrão</label>
                        <select id="default_account" name="default_account" class="lkc-input">
                            <option value="">— selecionar —</option>
                            <?php foreach ($contas as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (string)$current['default_account'] === (string)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="lkc-help">Usada como padrão ao criar lançamentos e relatórios rápidos.</small>
                    </div>

                    <div class="lkc-field">
                        <label for="month_start">Início do mês contábil</label>
                        <input type="number" min="1" max="28" id="month_start" name="month_start"
                            class="lkc-input" value="<?= (int)$current['month_start'] ?>">
                        <small class="lkc-help">Dia de início para somatórios mensais (ex.: 1, 5).</small>
                    </div>
                </div>
            </section>

            <!-- TAB: NOTIFICAÇÕES -->
            <section class="lkc-panel" data-panel="notificacoes">
                <div class="lkc-grid">
                    <div class="lkc-field">
                        <label for="notify_email">Receber e-mails do sistema</label>
                        <select id="notify_email" name="notify_email" class="lkc-input">
                            <option value="yes" <?= $current['notify_email'] === 'yes' ? 'selected' : '' ?>>Sim</option>
                            <option value="no" <?= $current['notify_email'] === 'no' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="notify_low">Alerta de saldo baixo</label>
                        <select id="notify_low" name="notify_low" class="lkc-input">
                            <option value="yes" <?= $current['notify_low'] === 'yes' ? 'selected' : '' ?>>Ativar</option>
                            <option value="no" <?= $current['notify_low'] === 'no' ? 'selected' : '' ?>>Desativar</option>
                        </select>
                    </div>

                    <div class="lkc-field">
                        <label for="notify_goals">Aviso ao atingir metas</label>
                        <select id="notify_goals" name="notify_goals" class="lkc-input">
                            <option value="yes" <?= $current['notify_goals'] === 'yes' ? 'selected' : '' ?>>Ativar</option>
                            <option value="no" <?= $current['notify_goals'] === 'no' ? 'selected' : '' ?>>Desativar</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Ações -->
            <div class="lkc-actions">
                <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/home" class="lkc-btn lkc-btn--ghost">Cancelar</a>
                <button type="submit" class="lkc-btn lkc-btn--primary" id="btn-salvar">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</main>

<style>
    /* ====== Tokens básicos (coerente com Lukrato) ====== */
    :root {
        --lk-orange: #e67e22;
        --lk-blue: #0b2b47;
        /* fundo da área principal (dark) */
        --lk-blue-2: #0f3556;
        --lk-text: #e6eef5;
        --lk-muted: #b7c5d1;
        --lk-card: #0f2f4f;
        --lk-border: #1b476f;
        --lk-white: #ffffff;
        --lk-success: #2ecc71;
        --lk-danger: #e74c3c;
        --radius: 14px;
    }

    /* ====== Wrapper ====== */
    .lukrato-config-wrapper {
        padding: 24px 28px 48px;
        background: var(--lk-blue);
        color: var(--lk-text);
        min-height: calc(100vh - 120px);
    }

    .lkc-header .lkc-title {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 4px;
    }

    .lkc-header h1 {
        font-size: 1.6rem;
        margin: 0
    }

    .lkc-header .lkc-sub {
        color: var(--lk-muted);
        margin: 0 0 16px
    }

    .lkc-icon {
        font-size: 1.4rem
    }

    /* ====== Abas ====== */
    .lkc-tabs {
        display: flex;
        gap: 8px;
        margin: 10px 0 16px
    }

    .lkc-tab {
        appearance: none;
        border: 1px solid var(--lk-border);
        background: var(--lk-card);
        color: var(--lk-text);
        padding: 10px 14px;
        border-radius: 999px;
        cursor: pointer;
        transition: transform .15s ease, background .15s ease, border .15s ease;
        font-weight: 600
    }

    .lkc-tab:hover {
        transform: translateY(-1px)
    }

    .lkc-tab.is-active {
        background: var(--lk-orange);
        border-color: var(--lk-orange);
        color: #111
    }

    /* ====== Painéis ====== */
    .lkc-panel {
        display: none;
        background: var(--lk-card);
        border: 1px solid var(--lk-border);
        border-radius: var(--radius);
        padding: 18px;
        margin-bottom: 18px
    }

    .lkc-panel.is-active {
        display: block
    }

    /* ====== Grid de campos ====== */
    .lkc-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 14px
    }

    .lkc-field {
        grid-column: span 6
    }

    @media (max-width: 900px) {
        .lkc-field {
            grid-column: span 12
        }
    }

    .lkc-field label {
        display: block;
        margin: 0 0 6px;
        color: var(--lk-muted);
        font-weight: 600
    }

    .lkc-input {
        width: 100%;
        border-radius: 10px;
        border: 1px solid var(--lk-border);
        background: #072138;
        color: var(--lk-white);
        padding: 12px 12px;
        outline: none
    }

    .lkc-input:focus {
        border-color: var(--lk-orange);
        box-shadow: 0 0 0 2px rgba(230, 126, 34, .2)
    }

    .lkc-help {
        display: block;
        color: var(--lk-muted);
        margin-top: 6px;
        font-size: .85rem
    }

    /* ====== Ações ====== */
    .lkc-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 16px
    }

    .lkc-btn {
        appearance: none;
        border: 1px solid var(--lk-border);
        background: var(--lk-card);
        color: var(--lk-white);
        padding: 12px 16px;
        border-radius: 999px;
        cursor: pointer;
        font-weight: 700;
        transition: filter .15s ease, transform .15s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px
    }

    .lkc-btn:hover {
        transform: translateY(-1px)
    }

    .lkc-btn--primary {
        background: var(--lk-orange);
        border-color: var(--lk-orange);
        color: #111
    }

    .lkc-btn--ghost {
        background: transparent
    }
</style>

<script>
    // Tabs simples
    (function() {
        const tabs = document.querySelectorAll('.lkc-tab');
        const panels = document.querySelectorAll('.lkc-panel');
        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-tab');
                tabs.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                panels.forEach(p => {
                    p.classList.toggle('is-active', p.getAttribute('data-panel') === id);
                });
            });
        });
    })();

    // UX: desabilita botão durante envio
    document.getElementById('form-config')?.addEventListener('submit', function() {
        const btn = document.getElementById('btn-salvar');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Salvando...';
        }
    });
</script>