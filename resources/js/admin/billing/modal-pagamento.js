/**
 * ============================================================================
 * LUKRATO — Modal de Pagamento (Vite Module)
 * ============================================================================
 * Gerencia o modal de pagamento: cartão, PIX, boleto.
 * Lê configurações via data-* attributes do elemento #billing-modal.
 *
 * Substitui: public/assets/js/modal-pagamento.js
 * ============================================================================
 */

import { getCSRFToken, getBaseUrl } from '../shared/api.js';

// ─── Config ─────────────────────────────────────────────────────────────────

const modalEl = document.getElementById('billing-modal');
const ds = modalEl?.dataset || {};
const BASE_URL = getBaseUrl();
const CSRF_TOKEN = getCSRFToken();
const userDataComplete = {
    pix: ds.pixComplete === '1',
    boleto: ds.boletoComplete === '1',
    cpf: ds.cpf || '',
    phone: ds.phone || '',
    cep: ds.cep || '',
    endereco: ds.endereco || '',
    email: ds.email || '',
};

// ─── DOM Elements ───────────────────────────────────────────────────────────

const modal = modalEl;
const modalTitle = document.getElementById('billing-modal-title');
const modalText = document.getElementById('billing-modal-text');
const modalPrice = document.getElementById('billing-modal-price');
const form = document.getElementById('asaasPaymentForm');
const submitBtn = document.getElementById('asaasSubmitBtn');

const cardNumberInput = document.getElementById('card_number');
const cardExpiryInput = document.getElementById('card_expiry');
const cardCpfInput = document.getElementById('card_cpf');
const cardPhoneInput = document.getElementById('card_phone');

const pixCpfInput = document.getElementById('pix_cpf');
const pixPhoneInput = document.getElementById('pix_phone');

const boletoCpfInput = document.getElementById('boleto_cpf');
const boletoPhoneInput = document.getElementById('boleto_phone');
const boletoCepInput = document.getElementById('boleto_cep');

const inputPlanId = document.getElementById('asaas_plan_id');
const inputPlanCode = document.getElementById('asaas_plan_code');
const inputPlanName = document.getElementById('asaas_plan_name');
const inputPlanAmount = document.getElementById('asaas_plan_amount');
const inputPlanInterval = document.getElementById('asaas_plan_interval');
const inputPlanCycle = document.getElementById('asaas_plan_cycle');
const inputPlanMonths = document.getElementById('asaas_plan_months');
const inputPlanDiscount = document.getElementById('asaas_plan_discount');
const inputBaseMonthly = document.getElementById('asaas_plan_amount_base_monthly');
const inputBillingType = document.getElementById('asaas_billing_type');

const creditCardSection = document.getElementById('credit-card-section');
const pixSection = document.getElementById('pix-section');
const boletoSection = document.getElementById('boleto-section');
const paymentMethodBtns = document.querySelectorAll('.payment-method-btn');

const pixQrCodeContainer = document.getElementById('pix-qrcode-container');
const pixQrCodeImg = document.getElementById('pix-qrcode-img');
const pixCopyPasteCode = document.getElementById('pix-copy-paste-code');
const pixCopyBtn = document.getElementById('pix-copy-btn');
const pixPendingStatus = document.getElementById('pix-pending-status');

const boletoContainer = document.getElementById('boleto-container');
const boletoLinhaDigitavel = document.getElementById('boleto-linha-digitavel');
const boletoCopyBtn = document.getElementById('boleto-copy-btn');
const boletoDownloadLink = document.getElementById('boleto-download-link');
const boletoPendingStatus = document.getElementById('boleto-pending-status');

const pendingPaymentSection = document.getElementById('pending-payment-section');
const pendingBillingType = document.getElementById('pending-billing-type');
const pendingCreatedAt = document.getElementById('pending-created-at');
const pendingPixQrcode = document.getElementById('pending-pix-qrcode');
const pendingPixCopyArea = document.getElementById('pending-pix-copy-area');
const pendingPixCode = document.getElementById('pending-pix-code');
const pendingPixCopyBtn = document.getElementById('pending-pix-copy-btn');
const pendingBoletoCode = document.getElementById('pending-boleto-code');
const pendingBoletoDownload = document.getElementById('pending-boleto-download');
const pendingCopyBtn = document.getElementById('pending-copy-btn');
const cancelPendingBtn = document.getElementById('cancel-pending-btn');
const modalBody = document.querySelector('.payment-modal__body');

const planButtons = document.querySelectorAll('[data-plan-button]');

const currencyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

// ─── State ──────────────────────────────────────────────────────────────────

let currentPlanConfig = null;
let currentBillingType = 'CREDIT_CARD';
let paymentPollingInterval = null;
let hasPendingPayment = false;
let pendingPaymentData = null;
let paymentMethodsLocked = false;
let appliedCoupon = null;

// ─── Cupom de Desconto ──────────────────────────────────────────────────────

const couponToggle = document.getElementById('couponToggle');
const couponBody = document.getElementById('couponBody');
const couponChevron = document.getElementById('couponChevron');
const couponInput = document.getElementById('couponCodeInput');
const couponApplyBtn = document.getElementById('couponApplyBtn');
const couponFeedback = document.getElementById('couponFeedback');
const couponSection = document.querySelector('.coupon-section');

couponToggle?.addEventListener('click', () => {
    const isOpen = couponBody.style.display !== 'none';
    couponBody.style.display = isOpen ? 'none' : 'block';
    couponToggle.classList.toggle('is-open', !isOpen);
});

couponInput?.addEventListener('input', () => {
    couponInput.value = couponInput.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
});

couponInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); }
});

async function applyCoupon() {
    const code = couponInput?.value.trim();
    if (!code) { showCouponFeedback('error', 'Digite um código de cupom.'); return; }

    couponApplyBtn.disabled = true;
    couponApplyBtn.querySelector('span').textContent = 'Validando...';

    try {
        const resp = await fetch(`${BASE_URL}api/cupons/validar?codigo=${encodeURIComponent(code)}`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await resp.json();

        if (json.success && json.data?.cupom) {
            const c = json.data.cupom;
            appliedCoupon = c;
            couponInput.disabled = true;
            couponSection?.classList.add('has-coupon');
            showCouponFeedback('success',
                `<i data-lucide="check-circle-2"></i>
                 <span>Cupom <strong>${c.codigo}</strong> aplicado! Desconto: <strong>${c.desconto_formatado}</strong></span>
                 <button type="button" class="coupon-remove-btn" onclick="removeCoupon()" title="Remover cupom"><i data-lucide="x"></i></button>`);
            updatePriceWithCoupon();
        } else {
            appliedCoupon = null;
            showCouponFeedback('error', `<i data-lucide="x-circle"></i><span>${json.message || 'Cupom inválido.'}</span>`);
        }
    } catch (err) {
        console.error('Erro ao validar cupom:', err);
        showCouponFeedback('error', '<i data-lucide="alert-circle"></i><span>Erro ao validar cupom. Tente novamente.</span>');
    } finally {
        couponApplyBtn.disabled = false;
        couponApplyBtn.querySelector('span').textContent = 'Aplicar';
    }
}

function removeCoupon() {
    appliedCoupon = null;
    couponInput.disabled = false;
    couponInput.value = '';
    couponSection?.classList.remove('has-coupon');
    couponFeedback.style.display = 'none';
    updatePriceWithCoupon();
}

function showCouponFeedback(type, html) {
    couponFeedback.className = 'coupon-section__feedback ' + type;
    couponFeedback.innerHTML = html;
    couponFeedback.style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}

function updatePriceWithCoupon() {
    const priceEl = document.getElementById('billing-modal-price');
    if (!currentPlanConfig || !priceEl) return;

    const total = calcTotal(currentPlanConfig.monthlyBase, currentPlanConfig.months, currentPlanConfig.discount);

    if (appliedCoupon && total > 0) {
        let discountValue = appliedCoupon.tipo_desconto === 'percentual'
            ? total * (appliedCoupon.valor_desconto / 100)
            : appliedCoupon.valor_desconto;
        const finalTotal = Math.max(0, total - discountValue);
        priceEl.innerHTML = `<span style="text-decoration:line-through;opacity:0.5;font-size:0.85em;">${currencyFormatter.format(total)}</span> ${currencyFormatter.format(finalTotal)}`;
    } else {
        priceEl.textContent = currencyFormatter.format(total);
    }
}

window.applyCoupon = applyCoupon;
window.removeCoupon = removeCoupon;

// ─── Payment Methods Lock ───────────────────────────────────────────────────

const paymentMethodSelector = document.querySelector('.payment-method-selector');

function lockPaymentMethods() {
    paymentMethodsLocked = true;
    paymentMethodSelector?.classList.add('is-locked');
    paymentMethodBtns.forEach(btn => { btn.classList.add('is-locked'); btn.setAttribute('aria-disabled', 'true'); });
}

function unlockPaymentMethods() {
    paymentMethodsLocked = false;
    paymentMethodSelector?.classList.remove('is-locked');
    paymentMethodBtns.forEach(btn => { btn.classList.remove('is-locked'); btn.removeAttribute('aria-disabled'); });
}

// ─── Formatters ─────────────────────────────────────────────────────────────

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
    return digits.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3').replace(/(\d{3})\.(\d{3})\.(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
}

function formatPhone(value) {
    const digits = onlyDigits(value).slice(0, 11);
    if (digits.length <= 2) return digits;
    if (digits.length <= 6) return digits.replace(/(\d{2})(\d+)/, '($1) $2');
    if (digits.length <= 10) return digits.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
    return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
}

function formatCep(value) {
    const digits = onlyDigits(value).slice(0, 8);
    if (digits.length <= 5) return digits;
    return digits.replace(/(\d{5})(\d+)/, '$1-$2');
}

// Format listeners
cardNumberInput?.addEventListener('input', (e) => e.target.value = formatCardNumber(e.target.value));
cardExpiryInput?.addEventListener('input', (e) => e.target.value = formatExpiry(e.target.value));
[cardCpfInput, pixCpfInput, boletoCpfInput].forEach(input => {
    input?.addEventListener('input', (e) => e.target.value = formatCpf(e.target.value));
});
[cardPhoneInput, pixPhoneInput, boletoPhoneInput].forEach(input => {
    input?.addEventListener('input', (e) => e.target.value = formatPhone(e.target.value));
});
boletoCepInput?.addEventListener('input', (e) => e.target.value = formatCep(e.target.value));

// ─── Helper Functions ───────────────────────────────────────────────────────

function calcTotal(baseMonthly, months, discountPct) {
    const base = Number(baseMonthly) || 0;
    const m = Number(months) || 1;
    const d = Number(discountPct) || 0;
    return Math.round((base * m * (1 - (d / 100)) + Number.EPSILON) * 100) / 100;
}

function cycleLabel(months) {
    if (months === 12) return 'ano';
    if (months === 6) return 'semestre';
    return 'mês';
}

function getActiveCycleFromUI() {
    const active = document.querySelector('.plan-billing-toggle__btn.is-active');
    if (!active) return { cycle: 'monthly', months: 1, discount: 0 };
    return {
        cycle: active.dataset.cycle || 'monthly',
        months: Number(active.dataset.months || '1'),
        discount: Number(active.dataset.discount || '0')
    };
}

function updateSubmitButton() {
    const btnSpan = submitBtn?.querySelector('span');
    const btnIcon = submitBtn?.querySelector('i, svg.lucide');
    if (!btnSpan || !btnIcon) return;

    function swapIcon(iconName) {
        const parent = btnIcon.parentNode;
        const newIcon = document.createElement('i');
        newIcon.setAttribute('data-lucide', iconName);
        parent.replaceChild(newIcon, btnIcon);
        if (window.lucide) lucide.createIcons();
    }

    switch (currentBillingType) {
        case 'PIX': btnSpan.textContent = 'Gerar PIX'; btnIcon.className = 'fa-brands fa-pix'; break;
        case 'BOLETO': btnSpan.textContent = 'Gerar Boleto'; swapIcon('scan-line'); break;
        default: btnSpan.textContent = 'Pagar com cartão'; swapIcon('lock');
    }
}

function syncPlanHiddenFields() {
    if (!currentPlanConfig) return;
    const total = calcTotal(currentPlanConfig.monthlyBase, currentPlanConfig.months, currentPlanConfig.discount);

    inputPlanId.value = currentPlanConfig.planId;
    inputPlanCode.value = currentPlanConfig.planCode;
    inputPlanName.value = currentPlanConfig.planName;
    inputPlanAmount.value = String(total);
    inputPlanInterval.value = cycleLabel(currentPlanConfig.months);
    inputPlanCycle.value = currentPlanConfig.cycle;
    inputPlanMonths.value = String(currentPlanConfig.months);
    inputPlanDiscount.value = String(currentPlanConfig.discount);
    inputBaseMonthly.value = String(currentPlanConfig.monthlyBase);
    inputBillingType.value = currentBillingType;

    if (modalPrice) {
        modalPrice.textContent = `${currentPlanConfig.planName} - ${currencyFormatter.format(total)}/${cycleLabel(currentPlanConfig.months)}`;
    }
    if (appliedCoupon) updatePriceWithCoupon();
}

function updateModalText() {
    if (!modalText) return;
    const planName = currentPlanConfig?.planName || 'Lukrato PRO';
    switch (currentBillingType) {
        case 'PIX': modalText.textContent = `Ativando o plano ${planName}. Pague instantaneamente via PIX.`; break;
        case 'BOLETO': modalText.textContent = `Ativando o plano ${planName}. Gere o boleto para pagamento.`; break;
        default: modalText.textContent = `Ativando o plano ${planName}. Complete os dados do cartão.`;
    }
}

function switchPaymentMethod(method) {
    currentBillingType = method;

    paymentMethodBtns.forEach(btn => {
        const isActive = btn.dataset.method === method;
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive);
    });

    creditCardSection?.classList.toggle('is-visible', method === 'CREDIT_CARD');
    pixSection?.classList.toggle('is-visible', method === 'PIX');
    boletoSection?.classList.toggle('is-visible', method === 'BOLETO');

    pixQrCodeContainer?.classList.remove('is-visible');
    boletoContainer?.classList.remove('is-visible');
    pixPendingStatus?.classList.remove('is-visible');
    boletoPendingStatus?.classList.remove('is-visible');

    const pixFieldsContainer = pixSection?.querySelector('.pix-boleto-fields');
    const boletoFieldsContainer = boletoSection?.querySelector('.pix-boleto-fields');
    if (pixFieldsContainer) pixFieldsContainer.style.display = userDataComplete.pix ? 'none' : 'block';
    if (boletoFieldsContainer) boletoFieldsContainer.style.display = userDataComplete.boleto ? 'none' : 'block';

    updateSubmitButton();
    updateModalText();
    syncPlanHiddenFields();
    if (submitBtn) submitBtn.disabled = false;
    if (method === 'PIX') checkPendingPix();
}

// ─── Pending PIX ────────────────────────────────────────────────────────────

async function checkPendingPix() {
    try {
        const resp = await fetch(`${BASE_URL}premium/pending-pix`, { credentials: 'include', headers: { 'Accept': 'application/json' } });
        const json = await resp.json();
        if (json.success && json.data?.hasPending && json.data?.pix) {
            showPendingPaymentSection({
                billingType: 'PIX', createdAt: json.data.createdAt || new Date().toLocaleString('pt-BR'),
                paymentId: json.data.paymentId,
                pix: { qrCodeImage: json.data.pix.qrCodeImage, payload: json.data.pix.payload }
            });
        } else if (json.data?.paid) {
            window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado.', 'success').then(() => window.location.reload());
        }
    } catch (err) { /* silencioso */ }
}

// ─── Clipboard ──────────────────────────────────────────────────────────────

async function copyToClipboard(text, button) {
    try {
        await navigator.clipboard.writeText(text);
        const span = button.querySelector('span');
        const originalText = span?.textContent;
        button.classList.add('copied');
        if (span) span.textContent = 'Copiado!';
        setTimeout(() => { button.classList.remove('copied'); if (span) span.textContent = originalText; }, 2000);
    } catch (err) {
        window.Swal?.fire('Erro', 'Não foi possível copiar. Selecione e copie manualmente.', 'warning');
    }
}

pixCopyBtn?.addEventListener('click', () => { const code = pixCopyPasteCode?.value; if (code) copyToClipboard(code, pixCopyBtn); });
boletoCopyBtn?.addEventListener('click', () => { const code = boletoLinhaDigitavel?.textContent; if (code) copyToClipboard(code, boletoCopyBtn); });

// ─── Payment Polling ────────────────────────────────────────────────────────

function startPaymentPolling(paymentId) {
    if (paymentPollingInterval) clearInterval(paymentPollingInterval);
    paymentPollingInterval = setInterval(async () => {
        try {
            const resp = await fetch(`${BASE_URL}premium/check-payment/${paymentId}`, { credentials: 'include', headers: { 'Accept': 'application/json' } });
            const json = await resp.json();
            if (json.success && json.data?.paid) {
                clearInterval(paymentPollingInterval);
                window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado com sucesso.', 'success').then(() => window.location.reload());
            }
        } catch (err) { console.error('[Polling] Erro:', err); }
    }, 5000);
}

function stopPaymentPolling() {
    if (paymentPollingInterval) { clearInterval(paymentPollingInterval); paymentPollingInterval = null; }
}

// ─── Pending Payment (Any Type) ─────────────────────────────────────────────

async function checkPendingPayment() {
    try {
        const resp = await fetch(`${BASE_URL}premium/pending-payment`, { credentials: 'include', headers: { 'Accept': 'application/json' } });
        const json = await resp.json();
        if (json.success && json.data?.hasPending) {
            hasPendingPayment = true;
            pendingPaymentData = json.data;
            showPendingPaymentSection(json.data);
            return true;
        } else if (json.data?.paid) {
            window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado.', 'success').then(() => window.location.reload());
            return true;
        }
        hasPendingPayment = false;
        pendingPaymentData = null;
        hidePendingPaymentSection();
        return false;
    } catch (err) {
        hasPendingPayment = false;
        hidePendingPaymentSection();
        return false;
    }
}

function showPendingPaymentSection(data) {
    if (modalBody) modalBody.style.display = 'none';
    pendingPaymentSection?.classList.add('is-visible');

    const billingTypeLabel = data.billingType === 'PIX' ? 'PIX' : 'Boleto';
    const typeClass = data.billingType === 'PIX' ? 'pending-type-pix' : 'pending-type-boleto';

    if (pendingBillingType) { pendingBillingType.textContent = billingTypeLabel; pendingBillingType.className = 'pending-payment-section__info-value ' + typeClass; }
    if (pendingCreatedAt) pendingCreatedAt.textContent = data.createdAt || '-';

    if (pendingPixQrcode) pendingPixQrcode.style.display = 'none';
    if (pendingPixCopyArea) pendingPixCopyArea.style.display = 'none';
    if (pendingBoletoCode) pendingBoletoCode.style.display = 'none';
    if (pendingBoletoDownload) pendingBoletoDownload.style.display = 'none';
    if (pendingCopyBtn) pendingCopyBtn.style.display = 'none';

    if (data.billingType === 'PIX' && data.pix) {
        if (pendingPixQrcode && data.pix.qrCodeImage) { pendingPixQrcode.src = data.pix.qrCodeImage; pendingPixQrcode.style.display = 'block'; }
        if (pendingPixCode && data.pix.payload) { pendingPixCode.value = data.pix.payload; if (pendingPixCopyArea) pendingPixCopyArea.style.display = 'block'; }
        if (data.paymentId) startPaymentPolling(data.paymentId);
    } else if (data.billingType === 'BOLETO' && data.boleto) {
        if (pendingBoletoCode && data.boleto.identificationField) { pendingBoletoCode.textContent = data.boleto.identificationField; pendingBoletoCode.style.display = 'block'; if (pendingCopyBtn) pendingCopyBtn.style.display = 'flex'; }
        if (pendingBoletoDownload && data.boleto.bankSlipUrl) { pendingBoletoDownload.href = data.boleto.bankSlipUrl; pendingBoletoDownload.style.display = 'flex'; }
        if (data.paymentId) startPaymentPolling(data.paymentId);
    }
}

function hidePendingPaymentSection() {
    pendingPaymentSection?.classList.remove('is-visible');
    if (modalBody) modalBody.style.display = '';
}

// ─── Cancel Pending Payment ─────────────────────────────────────────────────

async function cancelPendingPayment() {
    const result = await window.Swal?.fire({
        title: 'Cancelar pagamento?', text: 'Você poderá escolher outro método de pagamento após cancelar.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, cancelar', cancelButtonText: 'Não, manter'
    });
    if (!result?.isConfirmed) return;

    try {
        cancelPendingBtn.disabled = true;
        cancelPendingBtn.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> <span>Cancelando...</span>';

        const resp = await fetch(`${BASE_URL}premium/cancel-pending`, {
            method: 'POST', credentials: 'include',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN }
        });
        const json = await resp.json();

        if (json.success) {
            hasPendingPayment = false; pendingPaymentData = null;
            stopPaymentPolling(); hidePendingPaymentSection();
            window.Swal?.fire({ icon: 'success', title: 'Pagamento cancelado!', text: 'Agora você pode escolher outro método.', timer: 2000, showConfirmButton: false });
        } else {
            throw new Error(json.message || 'Erro ao cancelar pagamento');
        }
    } catch (err) {
        window.Swal?.fire('Erro', err.message || 'Não foi possível cancelar o pagamento.', 'error');
    } finally {
        cancelPendingBtn.disabled = false;
        cancelPendingBtn.innerHTML = '<i data-lucide="x-circle"></i> <span>Cancelar e escolher outro método</span>';
    }
}

cancelPendingBtn?.addEventListener('click', cancelPendingPayment);
pendingPixCopyBtn?.addEventListener('click', () => { const code = pendingPixCode?.value; if (code) copyToClipboard(code, pendingPixCopyBtn); });
pendingCopyBtn?.addEventListener('click', () => { const code = pendingBoletoCode?.textContent; if (code) copyToClipboard(code, pendingCopyBtn); });

// ─── Modal Open/Close ───────────────────────────────────────────────────────

async function openBillingModal(planConfig) {
    currentPlanConfig = planConfig;
    currentBillingType = 'CREDIT_CARD';
    if (modalTitle) modalTitle.textContent = 'Pagamento Seguro';

    modal.classList.add('payment-modal--open');
    document.body.style.overflow = 'hidden';

    const hasPending = await checkPendingPayment();
    if (!hasPending) {
        hidePendingPaymentSection();
        switchPaymentMethod('CREDIT_CARD');
        syncPlanHiddenFields();
    }
}

function closeBillingModal() {
    modal.classList.remove('payment-modal--open');
    document.body.style.overflow = '';
    currentPlanConfig = null;
    stopPaymentPolling();
    hasPendingPayment = false; pendingPaymentData = null;
    hidePendingPaymentSection();
    unlockPaymentMethods();

    pixQrCodeContainer?.classList.remove('is-visible');
    boletoContainer?.classList.remove('is-visible');
    pixPendingStatus?.classList.remove('is-visible');
    boletoPendingStatus?.classList.remove('is-visible');

    if (form) form.reset();
    removeCoupon();
    if (couponBody) couponBody.style.display = 'none';
    couponToggle?.classList.remove('is-open');
}

window.closeBillingModal = closeBillingModal;
window.openBillingModal = openBillingModal;

// ─── Event Listeners ────────────────────────────────────────────────────────

paymentMethodBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        if (paymentMethodsLocked) return;
        const method = btn.dataset.method;
        if (method) switchPaymentMethod(method);
    });
});

planButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        const planId = btn.dataset.planId || null;
        const planCode = btn.dataset.planCode || null;
        const planName = btn.dataset.planName || 'Lukrato PRO';
        const monthlyBase = Number(btn.dataset.planMonthly ?? btn.dataset.planAmount ?? '0');

        if (!planId || !planCode || !monthlyBase || Number.isNaN(monthlyBase)) {
            window.Swal?.fire('Plano inválido', 'Não foi possível identificar o plano. Recarregue a página.', 'warning');
            return;
        }
        const picked = getActiveCycleFromUI();
        openBillingModal({ planId, planCode, planName, monthlyBase, cycle: picked.cycle, months: picked.months, discount: picked.discount });
    });
});

// Toggle Mensal/Semestral/Anual
document.querySelectorAll('.plan-billing-toggle').forEach((group) => {
    const buttons = group.querySelectorAll('.plan-billing-toggle__btn');
    buttons.forEach((b) => {
        b.addEventListener('click', () => {
            buttons.forEach(x => x.classList.remove('is-active'));
            b.classList.add('is-active');

            const card = group.closest('.plan-card');
            const priceElement = card ? card.querySelector('.plan-card__price') : null;
            if (priceElement) {
                const basePrice = Number(priceElement.dataset.basePrice || 0);
                const months = Number(b.dataset.months || 1);
                const discount = Number(b.dataset.discount || 0);
                const total = calcTotal(basePrice, months, discount);
                const period = cycleLabel(months);

                const priceValueElement = priceElement.querySelector('.plan-card__price-value');
                const pricePeriodElement = priceElement.querySelector('.plan-card__price-period');

                if (priceValueElement) {
                    priceValueElement.style.animation = 'none';
                    void priceValueElement.offsetWidth;
                    priceValueElement.textContent = `R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    priceValueElement.style.animation = 'priceAppear 0.5s ease';
                }
                if (pricePeriodElement) pricePeriodElement.textContent = `/${period}`;
            }

            if (!currentPlanConfig) return;
            currentPlanConfig.cycle = b.dataset.cycle || 'monthly';
            currentPlanConfig.months = Number(b.dataset.months || '1');
            currentPlanConfig.discount = Number(b.dataset.discount || '0');
            if (modal?.classList.contains('payment-modal--open')) syncPlanHiddenFields();
        });
    });
});

// ─── Form Submit ────────────────────────────────────────────────────────────

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentPlanConfig) { window.Swal?.fire('Plano inválido', 'Selecione um plano novamente.', 'warning'); return; }

    syncPlanHiddenFields();
    const fd = new FormData(form);
    let payload = {
        plan_id: currentPlanConfig.planId, plan_code: currentPlanConfig.planCode,
        cycle: inputPlanCycle.value, months: Number(inputPlanMonths.value || 1),
        discount: Number(inputPlanDiscount.value || 0),
        amount_base_monthly: Number(inputBaseMonthly.value || currentPlanConfig.monthlyBase || 0),
        couponCode: appliedCoupon ? appliedCoupon.codigo : null,
        amount: Number(inputPlanAmount.value || 0), billingType: currentBillingType
    };

    if (currentBillingType === 'CREDIT_CARD') {
        const holderName = fd.get('card_holder')?.toString().trim() || '';
        const cardNumber = (fd.get('card_number')?.toString().replace(/\s+/g, '') || '');
        const cardCvv = fd.get('card_cvv')?.toString().trim() || '';
        const cardExpiry = fd.get('card_expiry')?.toString().trim() || '';
        const cpf = onlyDigits(fd.get('card_cpf')?.toString() || '');
        const phone = onlyDigits(fd.get('card_phone')?.toString() || '');
        const cep = onlyDigits(fd.get('card_cep')?.toString() || '');

        if (!holderName || !cardNumber || !cardCvv || !cardExpiry || !cpf || !phone || !cep) {
            window.Swal?.fire('Campos obrigatórios', 'Preencha todos os dados do cartão.', 'warning'); return;
        }
        const [month, year] = cardExpiry.split('/').map(v => v.trim());
        if (!month || !year) { window.Swal?.fire('Validade inválida', 'Informe a validade no formato MM/AA.', 'warning'); return; }

        payload.creditCard = { holderName, number: cardNumber, expiryMonth: month, expiryYear: (year.length === 2 ? '20' + year : year), ccv: cardCvv };
        payload.creditCardHolderInfo = { name: holderName, email: userDataComplete.email || '', cpfCnpj: cpf, mobilePhone: phone, postalCode: cep };
    } else if (currentBillingType === 'PIX') {
        const cpf = userDataComplete.pix ? userDataComplete.cpf : onlyDigits(fd.get('pix_cpf')?.toString() || '');
        const phone = userDataComplete.pix ? userDataComplete.phone : onlyDigits(fd.get('pix_phone')?.toString() || '');
        if (!cpf || cpf.length !== 11) { window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o PIX.', 'warning'); return; }
        payload.holderInfo = { cpfCnpj: cpf, mobilePhone: phone, email: userDataComplete.email };
    } else if (currentBillingType === 'BOLETO') {
        const cpf = userDataComplete.boleto ? userDataComplete.cpf : onlyDigits(fd.get('boleto_cpf')?.toString() || '');
        const phone = userDataComplete.boleto ? userDataComplete.phone : onlyDigits(fd.get('boleto_phone')?.toString() || '');
        const cep = userDataComplete.boleto ? userDataComplete.cep : onlyDigits(fd.get('boleto_cep')?.toString() || '');
        const endereco = userDataComplete.boleto ? userDataComplete.endereco : (fd.get('boleto_endereco')?.toString().trim() || '');
        if (!cpf || cpf.length !== 11) { window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o boleto.', 'warning'); return; }
        if (!cep || cep.length !== 8) { window.Swal?.fire('CEP inválido', 'Informe um CEP válido para gerar o boleto.', 'warning'); return; }
        payload.holderInfo = { cpfCnpj: cpf, mobilePhone: phone, postalCode: cep, address: endereco, email: userDataComplete.email };
    }

    try {
        submitBtn.disabled = true;
        submitBtn.querySelector('span').textContent = 'Processando...';

        const loadingTitle = currentBillingType === 'PIX' ? 'Gerando PIX...' : currentBillingType === 'BOLETO' ? 'Gerando Boleto...' : 'Processando pagamento...';
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: loadingTitle, text: 'Aguarde enquanto processamos sua solicitação.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        }

        const resp = await fetch(`${BASE_URL}premium/checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            credentials: 'include', body: JSON.stringify(payload)
        });
        const json = await resp.json().catch(() => null);
        if (!resp.ok || !json || !json.success) throw new Error(json?.message || 'Não foi possível processar a solicitação.');

        Swal?.close();

        if (currentBillingType === 'CREDIT_CARD') {
            window.Swal?.fire('Sucesso! 🎉', json?.message || 'Pagamento realizado com sucesso.', 'success').then(() => window.location.reload());
            closeBillingModal();
        } else if (currentBillingType === 'PIX') {
            const pix = json.data?.pix;
            if (pix) {
                showPendingPaymentSection({ billingType: 'PIX', createdAt: new Date().toLocaleString('pt-BR'), paymentId: json.data?.paymentId, pix: { qrCodeImage: pix.qrCodeImage, payload: pix.payload } });
                window.Swal?.fire({ icon: 'success', title: 'PIX gerado!', text: 'Escaneie o QR Code ou copie o código para pagar.', confirmButtonText: 'Entendi' });
            } else {
                throw new Error('PIX gerado mas dados não recebidos. Tente novamente.');
            }
        } else if (currentBillingType === 'BOLETO') {
            const boleto = json.data?.boleto;
            if (boleto) {
                showPendingPaymentSection({ billingType: 'BOLETO', createdAt: new Date().toLocaleString('pt-BR'), paymentId: json.data.paymentId, boleto: { identificationField: boleto.identificationField, bankSlipUrl: boleto.bankSlipUrl } });
                window.Swal?.fire({ icon: 'success', title: 'Boleto gerado!', text: 'Copie o código ou baixe o PDF para pagar.', confirmButtonText: 'Entendi' });
            } else {
                throw new Error('Boleto criado mas os dados não foram recebidos. Tente novamente.');
            }
        }
    } catch (error) {
        console.error('[Checkout] Erro:', error);
        Swal?.close();
        window.Swal?.fire('Erro', error.message || 'Erro ao processar. Tente novamente.', 'error');
    } finally {
        submitBtn.disabled = false;
        updateSubmitButton();
    }
});
