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

                <!-- Divisória -->
                <div class="lk-skip-divider">
                    <span>ou</span>
                </div>

                <!-- Botão Skip -->
                <button type="button" class="lk-skip-btn" id="btnSkipOnboarding">
                    Explorar o Lukrato
                    <i data-lucide="arrow-right"></i>
                </button>

                <p class="lk-onboarding-hint">
                    <i data-lucide="info"></i>
                    Você poderá adicionar lançamentos depois pelo menu
                </p>
            </form>

        </div>
    </div>

    <?= function_exists("vite_scripts") ? vite_scripts("admin/onboarding/lancamento.js") : "" ?>
    <script src="<?= $base ?>assets/js/lucide-init.js"></script>
</body>

</html>