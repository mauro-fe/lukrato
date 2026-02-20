<?php

use Application\Lib\Auth;

$base = BASE_URL;
$favicon = rtrim(BASE_URL, '/') . '/assets/img/icone.png?v=1';
$theme = $userTheme ?? 'dark';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= htmlspecialchars($theme) ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lukrato — Bem-vindo</title>
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">
    <?= csrf_meta('default') ?>
    <link rel="icon" type="image/png" href="<?= $favicon ?>">
<<<<<<< HEAD
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $base ?>assets/css/lucide-compat.css">
    <script src="<?= $base ?>assets/js/lucide.min.js"></script>
=======
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous">
>>>>>>> e6eb93ea585cc07b573cb47bc72ce7f3e6386b68
    <link rel="stylesheet" href="<?= $base ?>assets/css/variables.css">
</head>

<body style="margin:0;padding:0;background:var(--color-bg);">
    <style>
        /* ───── Reset ───── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* ───── Onboarding Layout ───── */
        .lk-onboarding-wrapper {
            min-height: 100vh;
            background: var(--color-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-6);
        }

        .lk-onboarding-card {
            width: 100%;
            max-width: 480px;
            background: var(--glass-bg);
            border-radius: var(--radius-xl);
            padding: clamp(24px, 5vw, 40px);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--glass-border);
            animation: obCardIn 0.5s ease-out both;
        }

        @keyframes obCardIn {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ───── Steps indicator ───── */
        .lk-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: var(--spacing-6);
        }

        .lk-step {
            display: flex;
            align-items: center;
            gap: 0;
        }

        .lk-step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            border: 2px solid var(--glass-border);
            background: var(--color-surface);
            color: var(--color-text-muted);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .lk-step.active .lk-step-circle {
            border-color: var(--color-primary);
            background: var(--color-primary);
            color: #fff;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 20%, transparent);
        }

        .lk-step.done .lk-step-circle {
            border-color: var(--color-success);
            background: var(--color-success);
            color: #fff;
        }

        .lk-step-line {
            width: 60px;
            height: 2px;
            background: var(--glass-border);
            margin: 0 var(--spacing-2);
        }

        .lk-step.active~.lk-step .lk-step-line,
        .lk-step.active .lk-step-line {
            background: var(--glass-border);
        }

        .lk-step.done+.lk-step .lk-step-line,
        .lk-step.done .lk-step-line {
            background: var(--color-success);
        }

        /* ───── Hero ───── */
        .lk-ob-hero {
            text-align: center;
            margin-bottom: var(--spacing-6);

        }

        .lk-ob-hero-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 70%, #fff));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-4);
            box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 30%, transparent);
            animation: obIconPulse 2s ease-in-out infinite;
        }

        @keyframes obIconPulse {

            0%,
            100% {
                box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 30%, transparent);
            }

            50% {
                box-shadow: 0 8px 32px color-mix(in srgb, var(--color-primary) 50%, transparent);
            }
        }

        .lk-ob-hero-icon i {
            font-size: 28px;
            color: #fff;
        }

        .lk-ob-hero h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 6px;
            line-height: 1.3;
            font-family: var(--font-primary) !important;
        }

        .lk-ob-hero p {
            color: var(--color-text-muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        /* ───── Form ───── */
        .lk-form-group {
            margin-bottom: var(--spacing-5);
        }

        .lk-label {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--color-text);
            margin-bottom: var(--spacing-2);
            font-family: var(--font-primary) !important;
        }

        .lk-label .lk-req {
            color: var(--color-danger);
            margin-left: 2px;
        }

        .lk-input,
        .lk-select {
            width: 100%;
            padding: 12px var(--spacing-4);
            border: 2px solid var(--glass-border);
            border-radius: var(--radius-md);
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .lk-input:focus,
        .lk-select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 12%, transparent);
        }

        .lk-input::placeholder {
            color: var(--color-text-muted);
            opacity: 0.6;
        }

        .lk-helper-text {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .lk-helper-text i {
            font-size: 0.7rem;
        }

        /* ───── Money input ───── */
        .lk-input-money {
            position: relative;
        }

        .lk-currency {
            position: absolute;
            left: var(--spacing-4);
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            font-weight: 600;
            font-size: 0.92rem;
            font-family: var(--font-primary) !important;
        }

        .lk-input-with-prefix {
            padding-left: 50px;
        }

        /* ───── Primary button ───── */
        .lk-btn-primary {
            width: 100%;
            padding: 14px var(--spacing-4);
            border-radius: var(--radius-md);
            background: var(--color-primary);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--color-primary) 35%, transparent);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
        }

        .lk-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px color-mix(in srgb, var(--color-primary) 45%, transparent);
        }

        .lk-btn-primary:active {
            transform: translateY(0);
        }

        /* ───── Hint ───── */
        .lk-onboarding-hint {
            text-align: center;
            font-size: 0.75rem;
            color: var(--color-text-muted);
            margin-top: var(--spacing-3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .lk-onboarding-hint i {
            font-size: 0.7rem;
        }

        /* ───── Error ───── */
        .lk-onboarding-error {
            background: color-mix(in srgb, var(--color-danger) 10%, var(--color-surface));
            color: var(--color-danger, #ef4444);
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-5);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            border: 1px solid color-mix(in srgb, var(--color-danger) 25%, transparent);
        }

        /* ───── Institution select with search ───── */
        .lk-select-wrapper {
            position: relative;
        }

        .lk-select-search {
            width: 100%;
            padding: 12px var(--spacing-4);
            padding-right: 40px;
            border: 2px solid var(--glass-border);
            border-radius: var(--radius-md);
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .lk-select-search:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 12%, transparent);
        }

        .lk-select-arrow {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            font-size: 0.75rem;
            pointer-events: none;
            transition: transform 0.2s;
        }

        .lk-select-wrapper.open .lk-select-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .lk-select-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 100;
            display: none;
        }

        .lk-select-wrapper.open .lk-select-dropdown {
            display: block;
            animation: obDropIn 0.15s ease-out;
        }

        @keyframes obDropIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lk-select-option {
            padding: 10px var(--spacing-4);
            cursor: pointer;
            font-size: 0.88rem;
            color: var(--color-text);
            transition: background 0.15s;
            font-family: var(--font-primary) !important;
        }

        .lk-select-option:hover,
        .lk-select-option.highlighted {
            background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
        }

<<<<<<< HEAD
        <!-- Erro -->
        <?php if (!empty($_SESSION['error'])): ?>
        <div class="lk-onboarding-error">
            <i data-lucide="circle-alert"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
=======
        .lk-select-option.selected {
            color: var(--color-primary);
            font-weight: 600;
        }
>>>>>>> e6eb93ea585cc07b573cb47bc72ce7f3e6386b68

        .lk-select-empty {
            padding: 12px var(--spacing-4);
            color: var(--color-text-muted);
            font-size: 0.85rem;
            text-align: center;
        }

<<<<<<< HEAD
        <!-- Hero -->
        <div class="lk-ob-hero">
            <div class="lk-ob-hero-icon">
                <i data-lucide="wallet"></i>
            </div>
            <h1>Onde você guarda seu dinheiro?</h1>
            <p>Adicione sua principal conta para começar a organizar suas finanças.</p>
        </div>
=======
        @media (max-width: 500px) {
            .lk-onboarding-card {
                padding: var(--spacing-5);
            }
>>>>>>> e6eb93ea585cc07b573cb47bc72ce7f3e6386b68

            .lk-ob-hero h1 {
                font-size: 1.2rem;
                font-family: var(--font-primary) !important;
            }

<<<<<<< HEAD
            <!-- Nome da Conta -->
            <div class="lk-form-group">
                <label class="lk-label">
                    <i data-lucide="pen"></i>
                    Nome da Conta <span class="lk-req">*</span>
                </label>
                <input type="text" name="nome" class="lk-input" placeholder="Ex: Nubank, Itaú, Carteira..." required autocomplete="off" id="contaNomeInput">
            </div>

            <!-- Instituição Financeira (searchable) -->
            <div class="lk-form-group">
                <label class="lk-label">
                    <i data-lucide="landmark"></i>
                    Instituição Financeira <span class="lk-req">*</span>
                </label>
                <input type="hidden" name="instituicao_financeira_id" id="instituicaoHidden" required>
                <div class="lk-select-wrapper" id="instSelectWrapper">
                    <input type="text" class="lk-select-search" id="instSearchInput"
                           placeholder="Busque ou selecione..." autocomplete="off" readonly>
                    <i data-lucide="chevron-down" class="lk-select-arrow"></i>
                    <div class="lk-select-dropdown" id="instDropdown">
                        <?php foreach ($instituicoes as $inst): ?>
                        <div class="lk-select-option" data-value="<?= $inst->id ?>"><?= htmlspecialchars($inst->nome) ?></div>
                        <?php endforeach; ?>
=======
            .lk-step-line {
                width: 40px;
            }
        }
    </style>

    <div class="lk-onboarding-wrapper">
        <div class="lk-onboarding-card">

            <!-- Logo -->
            <div style="text-align:center;margin-bottom:var(--spacing-5);">
                <img src="<?= $base ?>assets/img/icone.png" alt="Lukrato"
                    style="width:40px;height:40px;border-radius:10px;">
            </div>

            <!-- Erro -->
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="lk-onboarding-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Steps -->
            <div class="lk-steps">
                <div class="lk-step active">
                    <div class="lk-step-circle">1</div>
                </div>
                <div class="lk-step">
                    <div class="lk-step-line"></div>
                    <div class="lk-step-circle">2</div>
                </div>
            </div>

            <!-- Hero -->
            <div class="lk-ob-hero">
                <div class="lk-ob-hero-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h1>Onde você guarda seu dinheiro?</h1>
                <p>Adicione sua principal conta para começar a organizar suas finanças.</p>
            </div>

            <!-- Form -->
            <form method="POST" action="<?= BASE_URL ?>api/onboarding/conta" class="lk-onboarding-form"
                id="onboardingContaForm">
                <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

                <!-- Nome da Conta -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i class="fas fa-pen"></i>
                        Nome da Conta <span class="lk-req">*</span>
                    </label>
                    <input type="text" name="nome" class="lk-input" placeholder="Ex: Nubank, Itaú, Carteira..." required
                        autocomplete="off" id="contaNomeInput">
                </div>

                <!-- Instituição Financeira (searchable) -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i class="fas fa-university"></i>
                        Instituição Financeira <span class="lk-req">*</span>
                    </label>
                    <input type="hidden" name="instituicao_financeira_id" id="instituicaoHidden" required>
                    <div class="lk-select-wrapper" id="instSelectWrapper">
                        <input type="text" class="lk-select-search" id="instSearchInput"
                            placeholder="Busque ou selecione..." autocomplete="off" readonly>
                        <i class="fas fa-chevron-down lk-select-arrow"></i>
                        <div class="lk-select-dropdown" id="instDropdown">
                            <?php foreach ($instituicoes as $inst): ?>
                                <div class="lk-select-option" data-value="<?= $inst->id ?>">
                                    <?= htmlspecialchars($inst->nome) ?></div>
                            <?php endforeach; ?>
                        </div>
>>>>>>> e6eb93ea585cc07b573cb47bc72ce7f3e6386b68
                    </div>
                </div>

<<<<<<< HEAD
            <!-- Saldo Inicial -->
            <div class="lk-form-group">
                <label class="lk-label">
                    <i data-lucide="coins"></i>
                    Saldo Inicial
                </label>
                <div class="lk-input-money">
                    <span class="lk-currency">R$</span>
                    <input type="text" name="saldo_inicial" value="0,00" class="lk-input lk-input-with-prefix"
                           inputmode="decimal" id="saldoInput">
                </div>
                <small class="lk-helper-text">
                    <i data-lucide="info"></i>
                    Opcional — você pode ajustar depois.
                </small>
            </div>

            <!-- Botão -->
            <button type="submit" class="lk-btn-primary" id="btnSubmitConta">
                Continuar
                <i data-lucide="arrow-right"></i>
            </button>

            <p class="lk-onboarding-hint">
                <i data-lucide="zap"></i>
                Leva menos de 30 segundos
            </p>
=======
                <!-- Saldo Inicial -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i class="fas fa-coins"></i>
                        Saldo Inicial
                    </label>
                    <div class="lk-input-money">
                        <span class="lk-currency">R$</span>
                        <input type="text" name="saldo_inicial" value="0,00" class="lk-input lk-input-with-prefix"
                            inputmode="decimal" id="saldoInput">
                    </div>
                    <small class="lk-helper-text">
                        <i class="fas fa-info-circle"></i>
                        Opcional — você pode ajustar depois.
                    </small>
                </div>

                <!-- Botão -->
                <button type="submit" class="lk-btn-primary" id="btnSubmitConta">
                    Continuar
                    <i class="fas fa-arrow-right"></i>
                </button>

                <p class="lk-onboarding-hint">
                    <i class="fas fa-bolt"></i>
                    Leva menos de 30 segundos
                </p>
>>>>>>> e6eb93ea585cc07b573cb47bc72ce7f3e6386b68

            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ─── Currency mask for saldo_inicial ───
            const saldoInput = document.getElementById('saldoInput');
            if (saldoInput) {
                saldoInput.addEventListener('focus', function() {
                    // Select all on focus for easy editing
                    setTimeout(() => this.select(), 50);
                });

                saldoInput.addEventListener('input', function(e) {
                    let val = e.target.value.replace(/[^\d]/g, '');
                    if (val === '') {
                        e.target.value = '0,00';
                        return;
                    }
                    val = parseInt(val, 10);
                    const formatted = (val / 100).toFixed(2)
                        .replace('.', ',')
                        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    e.target.value = formatted;
                });

                saldoInput.addEventListener('keydown', function(e) {
                    // Allow: backspace, delete, tab, enter, arrows
                    if ([8, 9, 13, 27, 46, 37, 38, 39, 40].includes(e.keyCode)) return;
                    // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    if ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode)) return;
                    // Block non-numbers
                    if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            }

            // ─── Searchable institution select ───
            const wrapper = document.getElementById('instSelectWrapper');
            const searchInput = document.getElementById('instSearchInput');
            const dropdown = document.getElementById('instDropdown');
            const hiddenInput = document.getElementById('instituicaoHidden');
            const options = dropdown ? Array.from(dropdown.querySelectorAll('.lk-select-option')) : [];

            let isOpen = false;

            function openDropdown() {
                wrapper.classList.add('open');
                searchInput.removeAttribute('readonly');
                isOpen = true;
                filterOptions('');
            }

            function closeDropdown() {
                wrapper.classList.remove('open');
                searchInput.setAttribute('readonly', '');
                isOpen = false;
                // Restore selected text
                const selected = options.find(o => o.classList.contains('selected'));
                searchInput.value = selected ? selected.textContent.trim() : '';
            }

            function filterOptions(query) {
                const q = query.toLowerCase().trim();
                let visibleCount = 0;
                options.forEach(opt => {
                    const match = !q || opt.textContent.toLowerCase().includes(q);
                    opt.style.display = match ? '' : 'none';
                    if (match) visibleCount++;
                });
                // Show empty message
                let emptyMsg = dropdown.querySelector('.lk-select-empty');
                if (visibleCount === 0) {
                    if (!emptyMsg) {
                        emptyMsg = document.createElement('div');
                        emptyMsg.className = 'lk-select-empty';
                        emptyMsg.textContent = 'Nenhuma instituição encontrada';
                        dropdown.appendChild(emptyMsg);
                    }
                    emptyMsg.style.display = '';
                } else if (emptyMsg) {
                    emptyMsg.style.display = 'none';
                }
            }

            function selectOption(opt) {
                options.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                hiddenInput.value = opt.dataset.value;
                searchInput.value = opt.textContent.trim();
                closeDropdown();
            }

            if (searchInput) {
                searchInput.addEventListener('click', () => {
                    if (!isOpen) {
                        openDropdown();
                        searchInput.value = '';
                    }
                });

                searchInput.addEventListener('input', () => {
                    filterOptions(searchInput.value);
                });

                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeDropdown();
                });
            }

            options.forEach(opt => {
                opt.addEventListener('click', () => selectOption(opt));
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (wrapper && !wrapper.contains(e.target)) {
                    closeDropdown();
                }
            });

            // ─── Form validation ───
            const form = document.getElementById('onboardingContaForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!hiddenInput.value) {
                        e.preventDefault();
                        searchInput.focus();
                        wrapper.classList.add('open');
                        searchInput.style.borderColor = 'var(--color-danger)';
                        setTimeout(() => searchInput.style.borderColor = '', 2000);
                    }
                });
            }
        });
<<<<<<< HEAD
    }
});
</script>
<script src="<?= $base ?>assets/js/lucide-init.js"></script>
=======
    </script>
>>>>>>> e6eb93ea585cc07b573cb47bc72ce7f3e6386b68
</body>

</html>