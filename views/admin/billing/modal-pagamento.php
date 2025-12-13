<?php

use Illuminate\Database\Capsule\Manager as DB;

/** @var \Application\Models\Usuario $user */

// Valores padrão
$cpfValue      = '';
$telefoneValue = '';
$cepValue      = '';

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

    // CEP – endereço principal
    $endereco = $user->enderecoPrincipal ?? null;
    if ($endereco && !empty($endereco->cep)) {
        $cepValue = $endereco->cep;
    }
}
?>

<style>
    /* ========================================================================== 
     MODAL DE PAGAMENTO (MANTENDO SEU LAYOUT)
     ========================================================================== */
    .payment-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: var(--spacing-4);
        animation: fadeIn 0.3s ease;
    }

    .payment-modal--open {
        display: flex;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .payment-modal__content {
        background: var(--color-surface);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .payment-modal__header {
        padding: var(--spacing-6);
        border-bottom: 1px solid var(--glass-border);
        text-align: center;
        position: relative;
    }

    .payment-modal__close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        border-radius: 50%;
        cursor: pointer;
        transition: all .2s ease;
        font-size: 1.25rem;
    }

    .payment-modal__close:hover {
        background: var(--color-danger);
        color: #fff;
        transform: rotate(90deg);
    }

    .payment-modal__title {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 700;
        margin: 0 0 var(--spacing-2);
        color: var(--color-text);
    }

    .payment-modal__subtitle {
        font-size: 1rem;
        color: var(--color-text-muted);
        margin: 0;
    }

    .payment-modal__price {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        font-size: 1.125rem;
        font-weight: 700;
        padding: var(--spacing-3) var(--spacing-5);
        border-radius: var(--radius-lg);
        margin-top: var(--spacing-4);
        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 30%, transparent);
    }

    .payment-modal__body {
        padding: var(--spacing-6);

    }

    .payment-form {
        display: grid;
        gap: var(--spacing-4);
    }

    .payment-form__row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--spacing-3);
    }

    .payment-form__field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .payment-form__label {
        font-size: .875rem;
        font-weight: 600;
        color: var(--color-text);
    }

    .payment-form__input {
        border-radius: var(--radius-md);
        border: 1px solid var(--glass-border);
        background: var(--color-surface-muted);
        padding: 10px 12px;
        font-size: .9375rem;
        color: var(--color-text);
        outline: none;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }

    .payment-form__input:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 40%, transparent);
        background: var(--color-surface);
    }

    .payment-form__actions {
        margin-top: var(--spacing-2);
        display: flex;
        justify-content: flex-end;
        gap: var(--spacing-3);
    }

    .btn-outline {
        border-radius: var(--radius-lg);
        padding: 10px 18px;
        border: 1px solid var(--color-surface-muted);
        background: transparent;
        color: var(--color-text-muted);
        cursor: pointer;
        font-size: .9375rem;
        transition: all .2s;
    }

    .btn-outline:hover {
        background: var(--color-surface-muted);
    }

    .btn-primary {
        border-radius: var(--radius-lg);
        padding: 12px 22px;
        border: none;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: #fff;
        cursor: pointer;
        font-size: .9375rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 40%, transparent);
        transition: all .2s;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px color-mix(in srgb, var(--color-primary) 50%, transparent);
    }

    .btn-primary[disabled] {
        opacity: .7;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    @media (max-width:768px) {
        .payment-modal {
            padding: var(--spacing-2);
        }

        .payment-modal__content {
            max-height: 95vh;
        }

        .payment-modal__header {
            padding: 24px 20px 20px;
        }

        .payment-modal__body {
            padding: 20px;
        }

        .payment-form__row {
            grid-template-columns: 1fr;
        }
    }

    /* Força o SweetAlert2 a ficar SEMPRE acima de qualquer modal */
    .swal2-container {
        z-index: 20000 !important;
    }

    .swal2-popup {
        z-index: 20001 !important;
    }
</style>

<div id="billing-modal" class="payment-modal" role="dialog" aria-labelledby="billing-modal-title" aria-modal="true">
    <div class="payment-modal__content">
        <div class="payment-modal__header">
            <button class="payment-modal__close" aria-label="Fechar modal" type="button"
                onclick="window.closeBillingModal?.()">
                <i class="fa-solid fa-times" aria-hidden="true"></i>
            </button>

            <h2 id="billing-modal-title" class="payment-modal__title">
                Pagamento Seguro
            </h2>

            <p id="billing-modal-text" class="payment-modal__subtitle">
                Complete os dados do cartão para ativar o Lukrato PRO
            </p>

            <div id="billing-modal-price" class="payment-modal__price" role="status" aria-live="polite">
                Selecione um plano para continuar
            </div>
        </div>

        <div class="payment-modal__body">
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


                <div class="payment-form__field">
                    <label for="card_holder" class="payment-form__label">Nome no cartão</label>
                    <input type="text" id="card_holder" name="card_holder" required class="payment-form__input"
                        value="<?= htmlspecialchars($user->nome ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ex: João da Silva">
                </div>

                <div class="payment-form__row">
                    <div class="payment-form__field">
                        <label for="card_number" class="payment-form__label">Número do cartão</label>
                        <input type="text" id="card_number" name="card_number" required inputmode="numeric"
                            maxlength="19" class="payment-form__input" placeholder="0000 0000 0000 0000">
                    </div>

                    <div class="payment-form__field">
                        <label for="card_cvv" class="payment-form__label">CVV</label>
                        <input type="text" id="card_cvv" name="card_cvv" required inputmode="numeric" maxlength="4"
                            class="payment-form__input" placeholder="123">
                    </div>
                </div>

                <div class="payment-form__row">
                    <div class="payment-form__field">
                        <label for="card_expiry" class="payment-form__label">Validade (MM/AA)</label>
                        <input type="text" id="card_expiry" name="card_expiry" required inputmode="numeric"
                            maxlength="5" class="payment-form__input" placeholder="07/29">
                    </div>

                    <div class="payment-form__field">
                        <label for="card_cpf" class="payment-form__label">CPF do titular</label>
                        <input type="text" id="card_cpf" name="card_cpf" required class="payment-form__input"
                            placeholder="000.000.000-00"
                            value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="payment-form__row">
                    <div class="payment-form__field">
                        <label for="card_phone" class="payment-form__label">Telefone</label>
                        <input type="text" id="card_phone" name="card_phone" required class="payment-form__input"
                            placeholder="(00) 00000-0000"
                            value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="payment-form__field">
                        <label for="card_cep" class="payment-form__label">CEP</label>
                        <input type="text" id="card_cep" name="card_cep" required class="payment-form__input"
                            placeholder="00000-000" value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="payment-form__actions">
                    <button type="button" class="btn-outline" onclick="window.closeBillingModal?.()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary" id="asaasSubmitBtn">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <span>Pagar com cartão</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const BASE_URL = '<?= BASE_URL ?>';

        const modal = document.getElementById('billing-modal');
        const modalTitle = document.getElementById('billing-modal-title');
        const modalText = document.getElementById('billing-modal-text');
        const modalPrice = document.getElementById('billing-modal-price');
        const form = document.getElementById('asaasPaymentForm');
        const submitBtn = document.getElementById('asaasSubmitBtn');
        const cardNumberInput = document.getElementById('card_number');
        const cardExpiryInput = document.getElementById('card_expiry');
        const cardCpfInput = document.getElementById('card_cpf');

        const inputPlanId = document.getElementById('asaas_plan_id');
        const inputPlanCode = document.getElementById('asaas_plan_code');
        const inputPlanName = document.getElementById('asaas_plan_name');
        const inputPlanAmount = document.getElementById('asaas_plan_amount');
        const inputPlanInterval = document.getElementById('asaas_plan_interval');

        // novos
        const inputPlanCycle = document.getElementById('asaas_plan_cycle');
        const inputPlanMonths = document.getElementById('asaas_plan_months');
        const inputPlanDiscount = document.getElementById('asaas_plan_discount');
        const inputBaseMonthly = document.getElementById('asaas_plan_amount_base_monthly');

        const planButtons = document.querySelectorAll('[data-plan-button]');

        const currencyFormatter = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        let currentPlanConfig = null;

        // ===============================
        // FORMATADORES
        // ===============================
        const onlyDigits = (v = '') => v.replace(/\D+/g, '');

        function formatCardNumber(value) {
            return onlyDigits(value).slice(0, 16).replace(/(\d{4})(?=\d)/g, '$1 ').trim();
        }

        function formatExpiry(value) {
            const digits = onlyDigits(value).slice(0, 4);
            if (digits.length <= 2) return digits;
            return digits.replace(/(\d{2})(\d{0,2})/, (m, mm, yy) => `${mm}/${yy}`);
        }

        function formatCpf(value) {
            const digits = onlyDigits(value).slice(0, 11);
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        }

        cardNumberInput?.addEventListener('input', (e) => {
            e.target.value = formatCardNumber(e.target.value);
        });

        cardExpiryInput?.addEventListener('input', (e) => {
            e.target.value = formatExpiry(e.target.value);
        });

        cardCpfInput?.addEventListener('input', (e) => {
            e.target.value = formatCpf(e.target.value);
        });

        function calcTotal(baseMonthly, months, discountPct) {
            const base = Number(baseMonthly) || 0;
            const m = Number(months) || 1;
            const d = Number(discountPct) || 0;
            const total = base * m * (1 - (d / 100));
            return Math.round((total + Number.EPSILON) * 100) / 100;
        }

        function cycleLabel(months) {
            if (months === 12) return 'ano';
            if (months === 6) return 'semestre';
            return 'mês';
        }

        function getActiveCycleFromUI() {
            const active = document.querySelector('.plan-billing-toggle__btn.is-active');
            if (!active) return {
                cycle: 'monthly',
                months: 1,
                discount: 0
            };
            return {
                cycle: active.dataset.cycle || 'monthly',
                months: Number(active.dataset.months || '1'),
                discount: Number(active.dataset.discount || '0')
            };
        }

        function syncPlanHiddenFields() {
            if (!currentPlanConfig) return;

            const total = calcTotal(currentPlanConfig.monthlyBase, currentPlanConfig.months, currentPlanConfig
                .discount);

            inputPlanId.value = currentPlanConfig.planId;
            inputPlanCode.value = currentPlanConfig.planCode;
            inputPlanName.value = currentPlanConfig.planName;

            inputPlanAmount.value = String(total);
            inputPlanInterval.value = cycleLabel(currentPlanConfig.months);

            inputPlanCycle.value = currentPlanConfig.cycle;
            inputPlanMonths.value = String(currentPlanConfig.months);
            inputPlanDiscount.value = String(currentPlanConfig.discount);
            inputBaseMonthly.value = String(currentPlanConfig.monthlyBase);

            if (modalPrice) {
                modalPrice.textContent =
                    `${currentPlanConfig.planName} - ${currencyFormatter.format(total)}/${cycleLabel(currentPlanConfig.months)}`;
            }
        }

        function openBillingModal(planConfig) {
            currentPlanConfig = planConfig;

            if (modalTitle) modalTitle.textContent = 'Pagamento Seguro';

            if (modalText) {
                modalText.textContent =
                    `Ativando o plano ${planConfig.planName}. Complete os dados do cartão para continuar.`;
            }

            syncPlanHiddenFields();

            modal.classList.add('payment-modal--open');
            document.body.style.overflow = 'hidden';
        }

        function closeBillingModal() {
            modal.classList.remove('payment-modal--open');
            document.body.style.overflow = '';
            currentPlanConfig = null;
            if (form) form.reset();
        }

        window.closeBillingModal = closeBillingModal;
        window.openBillingModal = openBillingModal;

        // Travar fechamento: apenas botÇões Cancelar ou X fecham o modal

        // Botões dos planos -> abre modal
        planButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const planId = btn.dataset.planId || null;
                const planCode = btn.dataset.planCode || null;
                const planName = btn.dataset.planName || 'Lukrato PRO';

                const monthlyBase = Number(btn.dataset.planMonthly ?? btn.dataset.planAmount ?? '0');

                if (!planId || !planCode || !monthlyBase || Number.isNaN(monthlyBase)) {
                    window.Swal?.fire('Plano inválido',
                        'Não foi possível identificar corretamente o plano. Recarregue a página.',
                        'warning');
                    return;
                }

                const picked = getActiveCycleFromUI();

                openBillingModal({
                    planId,
                    planCode,
                    planName,
                    monthlyBase,
                    cycle: picked.cycle,
                    months: picked.months,
                    discount: picked.discount
                });
            });
        });

        // Toggle Mensal/Semestral/Anual
        document.querySelectorAll('.plan-billing-toggle').forEach((group) => {
            const buttons = group.querySelectorAll('.plan-billing-toggle__btn');

            buttons.forEach((b) => {
                b.addEventListener('click', () => {
                    buttons.forEach(x => x.classList.remove('is-active'));
                    b.classList.add('is-active');

                    if (!currentPlanConfig) return;

                    currentPlanConfig.cycle = b.dataset.cycle || 'monthly';
                    currentPlanConfig.months = Number(b.dataset.months || '1');
                    currentPlanConfig.discount = Number(b.dataset.discount || '0');

                    if (modal?.classList.contains('payment-modal--open'))
                        syncPlanHiddenFields();
                });
            });
        });

        // Submit pagamento
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!currentPlanConfig) {
                window.Swal?.fire('Plano inválido', 'Selecione um plano novamente.', 'warning');
                return;
            }

            const fd = new FormData(form);

            const holderName = fd.get('card_holder')?.toString().trim() || '';
            const cardNumber = (fd.get('card_number')?.toString().replace(/\s+/g, '') || '');
            const cardCvv = fd.get('card_cvv')?.toString().trim() || '';
            const cardExpiry = fd.get('card_expiry')?.toString().trim() || '';

            const cpf = (fd.get('card_cpf')?.toString().trim() || '').replace(/\D+/g, '');
            const phone = (fd.get('card_phone')?.toString().trim() || '').replace(/\D+/g, '');
            const cep = (fd.get('card_cep')?.toString().trim() || '').replace(/\D+/g, '');

            if (!holderName || !cardNumber || !cardCvv || !cardExpiry || !cpf || !phone || !cep) {
                window.Swal?.fire('Campos obrigatórios', 'Preencha todos os dados do cartão.', 'warning');
                return;
            }

            const [month, year] = cardExpiry.split('/').map(v => v.trim());
            if (!month || !year) {
                window.Swal?.fire('Validade inválida', 'Informe a validade no formato MM/AA.', 'warning');
                return;
            }

            syncPlanHiddenFields();

            const payload = {
                plan_id: currentPlanConfig.planId,
                plan_code: currentPlanConfig.planCode,

                // novos (o backend valida e recalcula)
                cycle: inputPlanCycle.value,
                months: Number(inputPlanMonths.value || 1),
                discount: Number(inputPlanDiscount.value || 0),
                amount_base_monthly: Number(inputBaseMonthly.value || currentPlanConfig.monthlyBase ||
                    0),

                amount: Number(inputPlanAmount.value || 0),

                billingType: 'CREDIT_CARD',
                creditCard: {
                    holderName: holderName,
                    number: cardNumber,
                    expiryMonth: month,
                    expiryYear: (year.length === 2 ? '20' + year : year),
                    ccv: cardCvv
                },
                creditCardHolderInfo: {
                    name: holderName,
                    email: <?= json_encode($user->email ?? '') ?>,
                    cpfCnpj: cpf,
                    mobilePhone: phone,
                    postalCode: cep
                }
            };

            try {
                submitBtn.disabled = true;
                submitBtn.querySelector('span').textContent = 'Processando...';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Processando pagamento',
                        text: 'Estamos enviando seus dados ao Asaas com segurança.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                }

                const resp = await fetch(`${BASE_URL}premium/checkout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.CSRF || ''
                    },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });

                const json = await resp.json().catch(() => null);

                if (!resp.ok || !json || json.status !== 'success') {
                    throw new Error(json?.message || 'Não foi possível concluir o pagamento.');
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Sucesso! 🎉', json?.message || 'Pagamento realizado com sucesso.', 'success')
                        .then(() => window.location.reload());
                } else {
                    alert('Pagamento realizado com sucesso.');
                    window.location.reload();
                }

                closeBillingModal();
            } catch (error) {
                console.error('[Asaas] Erro ao processar pagamento:', error);
                if (typeof Swal !== 'undefined') Swal.fire('Erro', error.message || 'Pagamento recusado.',
                    'error');
                else alert(error.message || 'Erro ao processar pagamento.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Pagar com cartão';
            }
        });
    })();
</script>
