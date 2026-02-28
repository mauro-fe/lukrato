<?php

use Illuminate\Database\Capsule\Manager as DB;

/** @var \Application\Models\Usuario $user */

// Valores padrão
$cpfValue      = '';
$telefoneValue = '';
$cepValue      = '';
$enderecoValue = '';

if (isset($user) && $user) {
    // CPF – documentos.id_tipo = 1 (CPF)
    $cpfValue = DB::table('documentos')
        ->where('id_usuario', $user->id)
        ->where('id_tipo', 1) // 1 = CPF
        ->value('numero') ?? '';

    // Telefone – telefones + ddd
    $telRow = DB::table('telefones as t')
        ->leftJoin('ddd as d', 'd.id_ddd', '=', 't.id_ddd')
        ->where('t.id_usuario', $user->id)
        ->orderBy('t.id_telefone')
        ->first();

    if ($telRow) {
        $ddd = trim((string)($telRow->codigo ?? ''));
        $num = trim((string)($telRow->numero ?? ''));
        if ($ddd !== '' && $num !== '') {
            $telefoneValue = sprintf('(%s) %s', $ddd, $num);
        }
    }

    // CEP e Endereço – endereço principal ou primeiro endereço disponível
    $endereco = $user->enderecoPrincipal ?? null;

    // Se não tem endereço principal, tentar buscar qualquer endereço
    if (!$endereco || empty($endereco->cep)) {
        $endereco = DB::table('enderecos')
            ->where('user_id', $user->id)
            ->whereNotNull('cep')
            ->where('cep', '!=', '')
            ->first();
    }

    if ($endereco && !empty($endereco->cep)) {
        $cepValue = $endereco->cep;
        $enderecoValue = ($endereco->logradouro ?? '') . ($endereco->numero ? ', ' . $endereco->numero : '');
    }
}

// Verificar se os dados estão completos para cada método
$cpfDigits = preg_replace('/\D/', '', $cpfValue);
$phoneDigits = preg_replace('/\D/', '', $telefoneValue);
$cepDigits = preg_replace('/\D/', '', $cepValue);

// PIX precisa apenas de CPF (11 dígitos)
$pixDataComplete = strlen($cpfDigits) === 11;

// Boleto precisa de CPF + CEP
$boletoDataComplete = strlen($cpfDigits) === 11 && strlen($cepDigits) === 8;
?>


<div id="billing-modal" class="payment-modal" role="dialog" aria-labelledby="billing-modal-title" aria-modal="true">
    <div class="payment-modal__content">
        <div class="payment-modal__header">
            <button class="payment-modal__close" aria-label="Fechar modal" type="button"
                onclick="window.closeBillingModal?.()">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>

            <h2 id="billing-modal-title" class="payment-modal__title">
                Pagamento Seguro
            </h2>

            <p id="billing-modal-text" class="payment-modal__subtitle">
                Escolha a forma de pagamento para ativar o Lukrato PRO
            </p>

            <div id="billing-modal-price" class="payment-modal__price" role="status" aria-live="polite">
                Selecione um plano para continuar
            </div>
        </div>

        <!-- ========== SEÇÃO DE PAGAMENTO PENDENTE (BLOQUEIO) ========== -->
        <div id="pending-payment-section" class="pending-payment-section">
            <div class="pending-payment-section__icon">
                <i data-lucide="clock"></i>
            </div>
            <h3 class="pending-payment-section__title">
                Você tem um pagamento pendente
            </h3>
            <p class="pending-payment-section__description">
                Você já gerou um pagamento que ainda está aguardando confirmação.
                Para escolher outro método, cancele o atual primeiro.
            </p>

            <div class="pending-payment-section__info">
                <div class="pending-payment-section__info-row">
                    <span class="pending-payment-section__info-label">Método:</span>
                    <span id="pending-billing-type" class="pending-payment-section__info-value">-</span>
                </div>
                <div class="pending-payment-section__info-row">
                    <span class="pending-payment-section__info-label">Criado em:</span>
                    <span id="pending-created-at" class="pending-payment-section__info-value">-</span>
                </div>
            </div>

            <!-- QR Code PIX (aparece apenas se for PIX) -->
            <img id="pending-pix-qrcode" class="pending-payment-section__qrcode" src="" alt="QR Code PIX"
                style="display:none">

            <!-- Código PIX para copiar -->
            <div id="pending-pix-copy-area" style="display:none; width: 100%; max-width: 350px;">
                <div class="pix-copy-paste__wrapper" style="margin-top: var(--spacing-2);">
                    <input type="text" id="pending-pix-code" class="pix-copy-paste__input" readonly>
                    <button type="button" id="pending-pix-copy-btn" class="pix-copy-paste__btn">
                        <i data-lucide="copy"></i>
                        <span>Copiar</span>
                    </button>
                </div>
            </div>

            <!-- Linha digitável do boleto (aparece apenas se for BOLETO) -->
            <div id="pending-boleto-code" class="pending-payment-section__boleto-code" style="display:none"></div>

            <div class="pending-payment-section__actions">
                <a id="pending-boleto-download" href="#" target="_blank" class="btn-primary" style="display:none">
                    <i data-lucide="download"></i>
                    <span>Baixar Boleto</span>
                </a>
                <button type="button" id="pending-copy-btn" class="btn-primary" style="display:none">
                    <i data-lucide="copy"></i>
                    <span>Copiar código do boleto</span>
                </button>
                <button type="button" id="cancel-pending-btn" class="pending-payment-section__cancel-btn">
                    <i data-lucide="x-circle"></i>
                    <span>Cancelar e escolher outro método</span>
                </button>
            </div>
        </div>

        <div class="payment-modal__body">
            <!-- Seletor de Método de Pagamento -->
            <div class="payment-method-selector" role="tablist" aria-label="Método de pagamento">
                <button type="button" class="payment-method-btn is-active" data-method="CREDIT_CARD" role="tab"
                    aria-selected="true">
                    <i data-lucide="credit-card" aria-hidden="true"></i>
                    <span>Cartão</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="PIX" role="tab" aria-selected="false">
                    <i class="fa-brands fa-pix" aria-hidden="true"></i>
                    <span>PIX</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="BOLETO" role="tab" aria-selected="false">
                    <i data-lucide="scan-line" aria-hidden="true"></i>
                    <span>Boleto</span>
                </button>
            </div>

            <form id="asaasPaymentForm" class="payment-form" autocomplete="off">

                <!-- Hidden fields sobre o plano selecionado -->
                <input type="hidden" name="plan_id" id="asaas_plan_id">
                <input type="hidden" name="plan_code" id="asaas_plan_code">
                <input type="hidden" name="plan_name" id="asaas_plan_name">
                <input type="hidden" name="amount" id="asaas_plan_amount">
                <input type="hidden" name="interval" id="asaas_plan_interval">
                <input type="hidden" name="cycle" id="asaas_plan_cycle" value="monthly">
                <input type="hidden" name="months" id="asaas_plan_months" value="1">
                <input type="hidden" name="discount" id="asaas_plan_discount" value="0">
                <input type="hidden" name="amount_base_monthly" id="asaas_plan_amount_base_monthly" value="">
                <input type="hidden" name="billing_type" id="asaas_billing_type" value="CREDIT_CARD">

                <!-- ========== CAMPO DE CUPOM DE DESCONTO ========== -->
                <div class="coupon-section">
                    <div class="coupon-section__toggle" id="couponToggle">
                        <i data-lucide="ticket"></i>
                        <span>Tem um cupom de desconto?</span>
                        <i data-lucide="chevron-down" class="coupon-section__chevron" id="couponChevron"></i>
                    </div>
                    <div class="coupon-section__body" id="couponBody" style="display:none">
                        <div class="coupon-section__row">
                            <input type="text" id="couponCodeInput" name="couponCode"
                                class="payment-form__input coupon-input" placeholder="Digite o código do cupom"
                                autocomplete="off" maxlength="30">
                            <button type="button" id="couponApplyBtn" class="btn-coupon-apply" onclick="applyCoupon()">
                                <i data-lucide="check"></i>
                                <span>Aplicar</span>
                            </button>
                        </div>
                        <div class="coupon-section__feedback" id="couponFeedback" style="display:none"></div>
                    </div>
                </div>

                <!-- ========== SEÇÃO CARTÃO DE CRÉDITO ========== -->
                <div id="credit-card-section" class="payment-section is-visible">
                    <div class="payment-form__field">
                        <label for="card_holder" class="payment-form__label">Nome no cartão</label>
                        <input type="text" id="card_holder" name="card_holder" class="payment-form__input"
                            value="<?= htmlspecialchars($user->nome ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex: João da Silva">
                    </div>

                    <div class="payment-form__row">
                        <div class="payment-form__field">
                            <label for="card_number" class="payment-form__label">Número do cartão</label>
                            <input type="text" id="card_number" name="card_number" inputmode="numeric" maxlength="19"
                                class="payment-form__input" placeholder="0000 0000 0000 0000">
                        </div>

                        <div class="payment-form__field">
                            <label for="card_cvv" class="payment-form__label">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" inputmode="numeric" maxlength="4"
                                class="payment-form__input" placeholder="123">
                        </div>
                    </div>

                    <div class="payment-form__row">
                        <div class="payment-form__field">
                            <label for="card_expiry" class="payment-form__label">Validade (MM/AA)</label>
                            <input type="text" id="card_expiry" name="card_expiry" inputmode="numeric" maxlength="5"
                                class="payment-form__input" placeholder="07/29">
                        </div>

                        <div class="payment-form__field">
                            <label for="card_cpf" class="payment-form__label">CPF do titular</label>
                            <input type="text" id="card_cpf" name="card_cpf" class="payment-form__input"
                                placeholder="000.000.000-00"
                                value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="payment-form__row">
                        <div class="payment-form__field">
                            <label for="card_phone" class="payment-form__label">Telefone</label>
                            <input type="text" id="card_phone" name="card_phone" class="payment-form__input"
                                placeholder="(00) 00000-0000"
                                value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="payment-form__field">
                            <label for="card_cep" class="payment-form__label">CEP</label>
                            <input type="text" id="card_cep" name="card_cep" class="payment-form__input"
                                placeholder="00000-000" value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>

                <!-- ========== SEÇÃO PIX ========== -->
                <div id="pix-section" class="payment-section">
                    <div class="pix-boleto-area">
                        <div class="pix-boleto-area__icon">
                            <i class="fa-brands fa-pix"></i>
                        </div>
                        <h3 class="pix-boleto-area__title">Pagamento via PIX</h3>
                        <?php if ($pixDataComplete): ?>
                            <p class="pix-boleto-area__description pix-boleto-area__description--auto">
                                <i data-lucide="circle-check"></i>
                                Seus dados já estão cadastrados! Clique em "Gerar PIX" para continuar.
                            </p>
                        <?php else: ?>
                            <p class="pix-boleto-area__description">
                                Pague instantaneamente usando o QR Code ou copie o código PIX.<br>
                                O plano será ativado automaticamente após a confirmação do pagamento.
                            </p>
                        <?php endif; ?>

                        <!-- Campos obrigatórios para PIX (escondidos se dados completos) -->
                        <div class="pix-boleto-fields" <?= $pixDataComplete ? 'style="display:none"' : '' ?>>
                            <div class="payment-form__row">
                                <div class="payment-form__field">
                                    <label for="pix_cpf" class="payment-form__label">CPF</label>
                                    <input type="text" id="pix_cpf" name="pix_cpf" class="payment-form__input"
                                        placeholder="000.000.000-00"
                                        value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="pix_phone" class="payment-form__label">Telefone</label>
                                    <input type="text" id="pix_phone" name="pix_phone" class="payment-form__input"
                                        placeholder="(00) 00000-0000"
                                        value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- QR Code (aparece após gerar) -->
                        <div id="pix-qrcode-container" class="qr-code-container">
                            <img id="pix-qrcode-img" src="" alt="QR Code PIX">
                            <div class="pix-copy-paste">
                                <span class="pix-copy-paste__label">Código PIX (copia e cola)</span>
                                <div class="pix-copy-paste__wrapper">
                                    <input type="text" id="pix-copy-paste-code" class="pix-copy-paste__input" readonly>
                                    <button type="button" id="pix-copy-btn" class="pix-copy-paste__btn">
                                        <i data-lucide="copy"></i>
                                        <span>Copiar</span>
                                    </button>
                                </div>
                            </div>
                            <div id="pix-pending-status" class="payment-pending-status">
                                <i data-lucide="clock"></i>
                                <span>Aguardando pagamento...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== SEÇÃO BOLETO ========== -->
                <div id="boleto-section" class="payment-section">
                    <div class="pix-boleto-area">
                        <div class="pix-boleto-area__icon">
                            <i data-lucide="scan-line"></i>
                        </div>
                        <h3 class="pix-boleto-area__title">Pagamento via Boleto</h3>
                        <?php if ($boletoDataComplete): ?>
                            <p class="pix-boleto-area__description pix-boleto-area__description--auto">
                                <i data-lucide="circle-check"></i>
                                Seus dados já estão cadastrados! Clique em "Gerar Boleto" para continuar.
                            </p>
                        <?php else: ?>
                            <p class="pix-boleto-area__description">
                                Gere o boleto bancário e pague em qualquer banco ou lotérica.<br>
                                O plano será ativado em até 3 dias úteis após a confirmação.
                            </p>
                        <?php endif; ?>

                        <!-- Campos obrigatórios para Boleto (escondidos se dados completos) -->
                        <div class="pix-boleto-fields" <?= $boletoDataComplete ? 'style="display:none"' : '' ?>>
                            <div class="payment-form__row">
                                <div class="payment-form__field">
                                    <label for="boleto_cpf" class="payment-form__label">CPF</label>
                                    <input type="text" id="boleto_cpf" name="boleto_cpf" class="payment-form__input"
                                        placeholder="000.000.000-00"
                                        value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="boleto_phone" class="payment-form__label">Telefone</label>
                                    <input type="text" id="boleto_phone" name="boleto_phone" class="payment-form__input"
                                        placeholder="(00) 00000-0000"
                                        value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <div class="payment-form__row">
                                <div class="payment-form__field">
                                    <label for="boleto_cep" class="payment-form__label">CEP</label>
                                    <input type="text" id="boleto_cep" name="boleto_cep" class="payment-form__input"
                                        placeholder="00000-000"
                                        value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="payment-form__field">
                                    <label for="boleto_endereco" class="payment-form__label">Endereço</label>
                                    <input type="text" id="boleto_endereco" name="boleto_endereco"
                                        class="payment-form__input" placeholder="Rua, número"
                                        value="<?= htmlspecialchars($enderecoValue, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Boleto gerado (aparece após gerar) -->
                        <div id="boleto-container" class="boleto-container">
                            <div class="boleto-linha-digitavel" id="boleto-linha-digitavel"></div>
                            <div class="boleto-actions">
                                <button type="button" id="boleto-copy-btn" class="btn-primary">
                                    <i data-lucide="copy"></i>
                                    <span>Copiar código</span>
                                </button>
                                <a id="boleto-download-link" href="#" target="_blank" class="btn-primary">
                                    <i data-lucide="download"></i>
                                    <span>Baixar boleto</span>
                                </a>
                            </div>
                            <div id="boleto-pending-status" class="payment-pending-status">
                                <i data-lucide="clock"></i>
                                <span>Aguardando pagamento (até 3 dias úteis)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-form__actions">
                    <button type="button" class="btn-outline" onclick="window.closeBillingModal?.()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary" id="asaasSubmitBtn">
                        <i data-lucide="lock" aria-hidden="true"></i>
                        <span>Pagar com cartão</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    window.BILLING_CONFIG = {
        BASE_URL: '<?= BASE_URL ?>',
        userDataComplete: {
            pix: <?= $pixDataComplete ? 'true' : 'false' ?>,
            boleto: <?= $boletoDataComplete ? 'true' : 'false' ?>,
            cpf: '<?= htmlspecialchars($cpfDigits, ENT_QUOTES, 'UTF-8') ?>',
            phone: '<?= htmlspecialchars($phoneDigits, ENT_QUOTES, 'UTF-8') ?>',
            cep: '<?= htmlspecialchars($cepDigits, ENT_QUOTES, 'UTF-8') ?>',
            endereco: '<?= htmlspecialchars($enderecoValue, ENT_QUOTES, 'UTF-8') ?>',
            email: <?= json_encode($user->email ?? '') ?>
        }
    };
</script>
<!-- modal-pagamento.js carregado via Vite (importado pelo billing module) -->