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
    <title>Lukrato — Primeiro Lançamento</title>
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">
    <?= csrf_meta('default') ?>
    <link rel="icon" type="image/png" href="<?= $favicon ?>">
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css"
        crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $base ?>assets/css/lucide-compat.css">
    <script src="<?= $base ?>assets/js/lucide.min.js"></script>
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
            background: linear-gradient(135deg, var(--color-success), color-mix(in srgb, var(--color-success) 70%, #fff));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-4);
            box-shadow: 0 8px 24px color-mix(in srgb, var(--color-success) 30%, transparent);
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
            font-family: var(--font-primary);
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
        }

        .lk-input-with-prefix {
            padding-left: 50px;
        }

        /* ───── Toggle Receita/Despesa ───── */
        .lk-tipo-toggle {
            display: flex;
            gap: 4px;
            background: var(--color-bg);
            border-radius: var(--radius-md);
            padding: 4px;
            border: 2px solid var(--glass-border);
        }

        .lk-tipo-btn {
            flex: 1;
            padding: 10px var(--spacing-3);
            border: none;
            border-radius: calc(var(--radius-md) - 2px);
            background: transparent;
            color: var(--color-text-muted);
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
        }

        .lk-tipo-btn.active-despesa {
            background: var(--color-danger, #ef4444);
            color: white;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--color-danger) 35%, transparent);
        }

        .lk-tipo-btn.active-receita {
            background: var(--color-success, #10b981);
            color: white;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--color-success) 35%, transparent);
        }

        .lk-tipo-btn:hover:not(.active-despesa):not(.active-receita) {
            background: var(--color-surface-muted);
        }

        /* ───── Conta info card ───── */
        .lk-conta-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: 10px var(--spacing-4);
            background: var(--color-bg);
            border: 2px solid var(--glass-border);
            border-radius: var(--radius-md);
        }

        .lk-conta-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            background: var(--color-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .lk-conta-details {
            flex: 1;
            min-width: 0;
        }

        .lk-conta-name {
            font-weight: 600;
            color: var(--color-text);
            font-size: 0.88rem;
            font-family: var(--font-primary);
        }

        .lk-conta-inst {
            font-size: 0.75rem;
            color: var(--color-text-muted);
        }

        .lk-conta-check {
            color: var(--color-success, #10b981);
            font-size: 1rem;
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

        @media (max-width: 500px) {
            .lk-onboarding-card {
                padding: var(--spacing-5);
            }

            .lk-ob-hero h1 {
                font-size: 1.2rem;
            }

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
                    <i data-lucide="circle-alert"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Steps -->
            <div class="lk-steps">
                <div class="lk-step done">
                    <div class="lk-step-circle"><i data-lucide="check" style="font-size:0.7rem"></i></div>
                </div>
                <div class="lk-step active">
                    <div class="lk-step-line"></div>
                    <div class="lk-step-circle">2</div>
                </div>
            </div>

            <!-- Hero -->
            <div class="lk-ob-hero">
                <div class="lk-ob-hero-icon">
                    <i data-lucide="receipt"></i>
                </div>
                <h1>Registre seu primeiro lançamento</h1>
                <p>Pode ser algo simples — uma despesa recente ou seu salário do mês.</p>
            </div>

            <!-- Form -->
            <form method="POST" action="<?= BASE_URL ?>api/onboarding/lancamento" class="lk-onboarding-form"
                id="onboardingLancamentoForm">
                <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>
                <input type="hidden" name="conta_id" value="<?= $conta->id ?>">

                <!-- Conta (visual, não editável) -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i data-lucide="wallet"></i>
                        Conta
                    </label>
                    <div class="lk-conta-info">
                        <div class="lk-conta-icon">
                            <i data-lucide="landmark"></i>
                        </div>
                        <div class="lk-conta-details">
                            <div class="lk-conta-name"><?= htmlspecialchars($conta->nome) ?></div>
                            <?php if ($conta->instituicaoFinanceira): ?>
                                <div class="lk-conta-inst"><?= htmlspecialchars($conta->instituicaoFinanceira->nome) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="lk-conta-check">
                            <i data-lucide="circle-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Tipo: Receita / Despesa -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i data-lucide="arrow-left-right"></i>
                        Tipo <span class="lk-req">*</span>
                    </label>
                    <input type="hidden" name="tipo" id="tipoInput" value="despesa">
                    <div class="lk-tipo-toggle">
                        <button type="button" class="lk-tipo-btn active-despesa" data-tipo="despesa" id="btnDespesa">
                            <i data-lucide="arrow-down"></i> Despesa
                        </button>
                        <button type="button" class="lk-tipo-btn" data-tipo="receita" id="btnReceita">
                            <i data-lucide="arrow-up"></i> Receita
                        </button>
                    </div>
                </div>

                <!-- Valor -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i data-lucide="dollar-sign"></i>
                        Valor <span class="lk-req">*</span>
                    </label>
                    <div class="lk-input-money">
                        <span class="lk-currency">R$</span>
                        <input type="text" name="valor" class="lk-input lk-input-with-prefix" placeholder="0,00"
                            required inputmode="decimal" id="valorInput">
                    </div>
                </div>

                <!-- Categoria -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i data-lucide="tag"></i>
                        Categoria <span class="lk-req">*</span>
                    </label>
                    <select name="categoria_id" class="lk-select" required id="categoriaSelect">
                        <option value="">Selecione a categoria</option>
                        <?php foreach ($categoriasDespesa as $cat): ?>
                            <option value="<?= $cat->id ?>" data-tipo="despesa">
                                <?= htmlspecialchars($cat->nome) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php foreach ($categoriasReceita as $cat): ?>
                            <option value="<?= $cat->id ?>" data-tipo="receita" style="display:none;" disabled>
                                <?= htmlspecialchars($cat->nome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Descrição -->
                <div class="lk-form-group">
                    <label class="lk-label">
                        <i data-lucide="pencil"></i>
                        Descrição <span class="lk-req">*</span>
                    </label>
                    <input type="text" name="descricao" class="lk-input" placeholder="Ex: Almoço, Salário, Uber..."
                        required maxlength="190" id="descricaoInput">
                </div>

                <!-- Botão -->
                <button type="submit" class="lk-btn-primary" id="btnSubmit">
                    Concluir e começar a usar!
                    <i data-lucide="check"></i>
                </button>

                <p class="lk-onboarding-hint">
                    <i data-lucide="info"></i>
                    Você poderá adicionar mais lançamentos depois
                </p>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoInput = document.getElementById('tipoInput');
            const btnDespesa = document.getElementById('btnDespesa');
            const btnReceita = document.getElementById('btnReceita');
            const categoriaSelect = document.getElementById('categoriaSelect');
            const valorInput = document.getElementById('valorInput');

            // Toggle tipo receita/despesa
            function setTipo(tipo) {
                tipoInput.value = tipo;

                btnDespesa.className = 'lk-tipo-btn' + (tipo === 'despesa' ? ' active-despesa' : '');
                btnReceita.className = 'lk-tipo-btn' + (tipo === 'receita' ? ' active-receita' : '');

                const options = categoriaSelect.querySelectorAll('option[data-tipo]');
                categoriaSelect.value = '';

                options.forEach(opt => {
                    if (opt.dataset.tipo === tipo) {
                        opt.style.display = '';
                        opt.disabled = false;
                    } else {
                        opt.style.display = 'none';
                        opt.disabled = true;
                    }
                });
            }

            btnDespesa.addEventListener('click', () => setTipo('despesa'));
            btnReceita.addEventListener('click', () => setTipo('receita'));

            // Máscara de valor (formato BR)
            if (valorInput) {
                valorInput.addEventListener('input', function(e) {
                    let val = e.target.value.replace(/[^\d]/g, '');
                    if (val === '') {
                        e.target.value = '';
                        return;
                    }
                    val = parseInt(val, 10);
                    const formatted = (val / 100).toFixed(2)
                        .replace('.', ',')
                        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    e.target.value = formatted;
                });

                valorInput.addEventListener('keydown', function(e) {
                    if ([8, 9, 13, 27, 46, 37, 38, 39, 40].includes(e.keyCode)) return;
                    if ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode)) return;
                    if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    <script src="<?= $base ?>assets/js/lucide-init.js"></script>
</body>

</html>