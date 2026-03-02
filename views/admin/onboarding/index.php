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
    <!-- Lucide Icons + FA Brands -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css"
        crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $base ?>assets/css/vendor/lucide-compat.css">
    <script src="<?= $base ?>assets/js/lucide.min.js"></script>
    <link rel="stylesheet" href="<?= $base ?>assets/css/core/variables.css">
</head>

<body style="margin:0;padding:0;background:var(--color-bg);">
    <link rel="stylesheet" href="<?= $base ?>assets/css/pages/onboarding.css">

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
                    <i data-lucide="wallet"></i>
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
                        <i data-lucide="pen"></i>
                        Nome da Conta <span class="lk-req">*</span>
                    </label>
                    <input type="text" name="nome" class="lk-input" placeholder="Ex: Nubank, Itaú, Carteira..." required
                        autocomplete="off" id="contaNomeInput">
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
                                <div class="lk-select-option" data-value="<?= $inst->id ?>">
                                    <?= htmlspecialchars($inst->nome) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

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

            </form>

        </div>
    </div>

    <?= function_exists("vite_scripts") ? vite_scripts("admin/onboarding/index.js") : "" ?>
    <script src="<?= $base ?>assets/js/lucide-init.js"></script>
</body>

</html>