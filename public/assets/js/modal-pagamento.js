/**
 * Modal de Pagamento – Billing Modal JS
 *
 * Depende de window.BILLING_CONFIG (definido inline no PHP):
 *   { baseUrl, csrfToken, userDataComplete: { pix, boleto, cpf, phone, cep, endereco, email } }
 */
(function () {
    'use strict';

    const CONFIG = window.BILLING_CONFIG || {};
    const BASE_URL = CONFIG.baseUrl || '';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const userDataComplete = CONFIG.userDataComplete || {};

    // ===============================
    // ELEMENTOS DO DOM
    // ===============================
    const modal = document.getElementById('billing-modal');
    const modalTitle = document.getElementById('billing-modal-title');
    const modalText = document.getElementById('billing-modal-text');
    const modalPrice = document.getElementById('billing-modal-price');
    const form = document.getElementById('asaasPaymentForm');
    const submitBtn = document.getElementById('asaasSubmitBtn');

    // Inputs de cartão
    const cardNumberInput = document.getElementById('card_number');
    const cardExpiryInput = document.getElementById('card_expiry');
    const cardCpfInput = document.getElementById('card_cpf');
    const cardPhoneInput = document.getElementById('card_phone');

    // Inputs PIX
    const pixCpfInput = document.getElementById('pix_cpf');
    const pixPhoneInput = document.getElementById('pix_phone');

    // Inputs Boleto
    const boletoCpfInput = document.getElementById('boleto_cpf');
    const boletoPhoneInput = document.getElementById('boleto_phone');
    const boletoCepInput = document.getElementById('boleto_cep');

    // Hidden fields
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

    // Seções de pagamento
    const creditCardSection = document.getElementById('credit-card-section');
    const pixSection = document.getElementById('pix-section');
    const boletoSection = document.getElementById('boleto-section');
    const paymentMethodBtns = document.querySelectorAll('.payment-method-btn');

    // PIX elements
    const pixQrCodeContainer = document.getElementById('pix-qrcode-container');
    const pixQrCodeImg = document.getElementById('pix-qrcode-img');
    const pixCopyPasteCode = document.getElementById('pix-copy-paste-code');
    const pixCopyBtn = document.getElementById('pix-copy-btn');
    const pixPendingStatus = document.getElementById('pix-pending-status');

    // Boleto elements
    const boletoContainer = document.getElementById('boleto-container');
    const boletoLinhaDigitavel = document.getElementById('boleto-linha-digitavel');
    const boletoCopyBtn = document.getElementById('boleto-copy-btn');
    const boletoDownloadLink = document.getElementById('boleto-download-link');
    const boletoPendingStatus = document.getElementById('boleto-pending-status');

    // Pending payment elements
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

    const currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    let currentPlanConfig = null;
    let currentBillingType = 'CREDIT_CARD';
    let paymentPollingInterval = null;
    let hasPendingPayment = false;
    let pendingPaymentData = null;
    let paymentMethodsLocked = false;
    let appliedCoupon = null; // { codigo, tipo_desconto, valor_desconto, desconto_formatado }

    // ===============================
    // CUPOM DE DESCONTO
    // ===============================
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
        if (e.key === 'Enter') {
            e.preventDefault();
            applyCoupon();
        }
    });

    async function applyCoupon() {
        const code = couponInput?.value.trim();
        if (!code) {
            showCouponFeedback('error', 'Digite um código de cupom.');
            return;
        }

        couponApplyBtn.disabled = true;
        couponApplyBtn.querySelector('span').textContent = 'Validando...';

        try {
            const resp = await fetch(`${BASE_URL}api/cupons/validar?codigo=${encodeURIComponent(code)}`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
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
                         <button type="button" class="coupon-remove-btn" onclick="removeCoupon()" title="Remover cupom"><i data-lucide="x"></i></button>`
                );
                updatePriceWithCoupon();
            } else {
                appliedCoupon = null;
                showCouponFeedback('error',
                    `<i data-lucide="x-circle"></i><span>${json.message || 'Cupom inválido.'}</span>`);
            }
        } catch (err) {
            console.error('Erro ao validar cupom:', err);
            showCouponFeedback('error',
                '<i data-lucide="alert-circle"></i><span>Erro ao validar cupom. Tente novamente.</span>');
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

        const base = currentPlanConfig.monthlyBase || 0;
        const months = currentPlanConfig.months || 1;
        const planDiscount = currentPlanConfig.discount || 0;
        const total = calcTotal(base, months, planDiscount);

        if (appliedCoupon && total > 0) {
            let discountValue = 0;
            if (appliedCoupon.tipo_desconto === 'percentual') {
                discountValue = total * (appliedCoupon.valor_desconto / 100);
            } else {
                discountValue = appliedCoupon.valor_desconto;
            }
            const finalTotal = Math.max(0, total - discountValue);
            priceEl.innerHTML =
                `<span style="text-decoration:line-through;opacity:0.5;font-size:0.85em;">${currencyFormatter.format(total)}</span> ${currencyFormatter.format(finalTotal)}`;
        } else {
            priceEl.textContent = currencyFormatter.format(total);
        }
    }

    window.applyCoupon = applyCoupon;
    window.removeCoupon = removeCoupon;

    // ===============================
    // TRAVAR/DESTRAVAR TABS DE PAGAMENTO
    // ===============================
    const paymentMethodSelector = document.querySelector('.payment-method-selector');

    function lockPaymentMethods() {
        paymentMethodsLocked = true;
        paymentMethodSelector?.classList.add('is-locked');
        paymentMethodBtns.forEach(btn => {
            btn.classList.add('is-locked');
            btn.setAttribute('aria-disabled', 'true');
        });
    }

    function unlockPaymentMethods() {
        paymentMethodsLocked = false;
        paymentMethodSelector?.classList.remove('is-locked');
        paymentMethodBtns.forEach(btn => {
            btn.classList.remove('is-locked');
            btn.removeAttribute('aria-disabled');
        });
    }

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

    // Event listeners para formatação
    cardNumberInput?.addEventListener('input', (e) => e.target.value = formatCardNumber(e.target.value));
    cardExpiryInput?.addEventListener('input', (e) => e.target.value = formatExpiry(e.target.value));

    // Formatação de CPF para todos os campos
    [cardCpfInput, pixCpfInput, boletoCpfInput].forEach(input => {
        input?.addEventListener('input', (e) => e.target.value = formatCpf(e.target.value));
    });

    // Formatação de telefone
    [cardPhoneInput, pixPhoneInput, boletoPhoneInput].forEach(input => {
        input?.addEventListener('input', (e) => e.target.value = formatPhone(e.target.value));
    });

    // Formatação de CEP
    boletoCepInput?.addEventListener('input', (e) => e.target.value = formatCep(e.target.value));

    // ===============================
    // FUNÇÕES AUXILIARES
    // ===============================
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
        inputBillingType.value = currentBillingType;

        if (modalPrice) {
            modalPrice.textContent =
                `${currentPlanConfig.planName} - ${currencyFormatter.format(total)}/${cycleLabel(currentPlanConfig.months)}`;
        }

        // Atualizar preço com cupom se aplicado
        if (appliedCoupon) {
            updatePriceWithCoupon();
        }
        const btnIcon = submitBtn?.querySelector('i, svg.lucide');

        if (!btnSpan || !btnIcon) return;

        // Função helper: trocar ícone Lucide dinamicamente
        function swapIcon(iconName) {
            const parent = btnIcon.parentNode;
            const newIcon = document.createElement('i');
            newIcon.setAttribute('data-lucide', iconName);
            parent.replaceChild(newIcon, btnIcon);
            if (window.lucide) lucide.createIcons();
        }

        switch (currentBillingType) {
            case 'PIX':
                btnSpan.textContent = 'Gerar PIX';
                btnIcon.className = 'fa-brands fa-pix';
                break;
            case 'BOLETO':
                btnSpan.textContent = 'Gerar Boleto';
                swapIcon('scan-line');
                break;
            default:
                btnSpan.textContent = 'Pagar com cartão';
                swapIcon('lock');
        }
    }

    function updateModalText() {
        if (!modalText) return;

        const planName = currentPlanConfig?.planName || 'Lukrato PRO';

        switch (currentBillingType) {
            case 'PIX':
                modalText.textContent = `Ativando o plano ${planName}. Pague instantaneamente via PIX.`;
                break;
            case 'BOLETO':
                modalText.textContent = `Ativando o plano ${planName}. Gere o boleto para pagamento.`;
                break;
            default:
                modalText.textContent = `Ativando o plano ${planName}. Complete os dados do cartão.`;
        }
    }

    function switchPaymentMethod(method) {
        currentBillingType = method;

        // Atualizar botões
        paymentMethodBtns.forEach(btn => {
            const isActive = btn.dataset.method === method;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive);
        });

        // Atualizar seções visíveis
        creditCardSection?.classList.toggle('is-visible', method === 'CREDIT_CARD');
        pixSection?.classList.toggle('is-visible', method === 'PIX');
        boletoSection?.classList.toggle('is-visible', method === 'BOLETO');

        // Esconder containers de resultado se mudar de método
        pixQrCodeContainer?.classList.remove('is-visible');
        boletoContainer?.classList.remove('is-visible');
        pixPendingStatus?.classList.remove('is-visible');
        boletoPendingStatus?.classList.remove('is-visible');

        // Esconder campos de formulário se dados já existem
        const pixFieldsContainer = pixSection?.querySelector('.pix-boleto-fields');
        const boletoFieldsContainer = boletoSection?.querySelector('.pix-boleto-fields');

        if (pixFieldsContainer) {
            pixFieldsContainer.style.display = userDataComplete.pix ? 'none' : 'block';
        }
        if (boletoFieldsContainer) {
            boletoFieldsContainer.style.display = userDataComplete.boleto ? 'none' : 'block';
        }

        // Atualizar botão e texto
        updateSubmitButton();
        updateModalText();
        syncPlanHiddenFields();

        // Habilitar submit
        if (submitBtn) submitBtn.disabled = false;

        // Se selecionou PIX, verificar se tem PIX pendente
        if (method === 'PIX') {
            checkPendingPix();
        }
    }

    // ===============================
    // VERIFICAR PIX PENDENTE
    // ===============================
    async function checkPendingPix() {
        try {
            const resp = await fetch(`${BASE_URL}premium/pending-pix`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const json = await resp.json();

            if (json.status === 'success' && json.data?.hasPending && json.data?.pix) {
                // Tem PIX pendente! Mostrar seção de pagamento pendente
                const pix = json.data.pix;

                showPendingPaymentSection({
                    billingType: 'PIX',
                    createdAt: json.data.createdAt || new Date().toLocaleString('pt-BR'),
                    paymentId: json.data.paymentId,
                    pix: {
                        qrCodeImage: pix.qrCodeImage,
                        payload: pix.payload
                    }
                });
            } else if (json.data?.paid) {
                // Já foi pago! Recarregar página
                window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado.', 'success')
                    .then(() => window.location.reload());
            }
            // Se não tem pendente, não faz nada - usuário precisa clicar em Gerar PIX
        } catch (err) {
            // Silencioso - se falhar, usuário pode gerar novo PIX
        }
    }

    // ===============================
    // COPY TO CLIPBOARD
    // ===============================
    async function copyToClipboard(text, button) {
        try {
            await navigator.clipboard.writeText(text);
            const span = button.querySelector('span');
            const originalText = span?.textContent;

            button.classList.add('copied');
            if (span) span.textContent = 'Copiado!';

            setTimeout(() => {
                button.classList.remove('copied');
                if (span) span.textContent = originalText;
            }, 2000);
        } catch (err) {
            console.error('Erro ao copiar:', err);
            window.Swal?.fire('Erro', 'Não foi possível copiar. Selecione e copie manualmente.', 'warning');
        }
    }

    pixCopyBtn?.addEventListener('click', () => {
        const code = pixCopyPasteCode?.value;
        if (code) copyToClipboard(code, pixCopyBtn);
    });

    boletoCopyBtn?.addEventListener('click', () => {
        const code = boletoLinhaDigitavel?.textContent;
        if (code) copyToClipboard(code, boletoCopyBtn);
    });

    // ===============================
    // POLLING PARA VERIFICAR PAGAMENTO
    // ===============================
    function startPaymentPolling(paymentId) {
        if (paymentPollingInterval) clearInterval(paymentPollingInterval);

        paymentPollingInterval = setInterval(async () => {
            try {
                const resp = await fetch(`${BASE_URL}premium/check-payment/${paymentId}`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await resp.json();

                if (json.status === 'success' && json.data?.paid) {
                    clearInterval(paymentPollingInterval);
                    window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado com sucesso.',
                        'success')
                        .then(() => window.location.reload());
                }
            } catch (err) {
                console.error('[Polling] Erro:', err);
            }
        }, 5000); // Verificar a cada 5 segundos
    }

    function stopPaymentPolling() {
        if (paymentPollingInterval) {
            clearInterval(paymentPollingInterval);
            paymentPollingInterval = null;
        }
    }

    // ===============================
    // VERIFICAR PAGAMENTO PENDENTE (QUALQUER TIPO)
    // ===============================
    async function checkPendingPayment() {
        try {
            const resp = await fetch(`${BASE_URL}premium/pending-payment`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const json = await resp.json();

            if (json.status === 'success' && json.data?.hasPending) {
                hasPendingPayment = true;
                pendingPaymentData = json.data;
                showPendingPaymentSection(json.data);
                return true;
            } else if (json.data?.paid) {
                // Já foi pago! Recarregar página
                window.Swal?.fire('Pagamento confirmado! 🎉', 'Seu plano foi ativado.', 'success')
                    .then(() => window.location.reload());
                return true;
            }

            hasPendingPayment = false;
            pendingPaymentData = null;
            hidePendingPaymentSection();
            return false;
        } catch (err) {
            console.error('[checkPendingPayment] Erro:', err);
            hasPendingPayment = false;
            hidePendingPaymentSection();
            return false;
        }
    }

    function showPendingPaymentSection(data) {
        // Esconder o body normal do modal (métodos de pagamento)
        if (modalBody) modalBody.style.display = 'none';

        // Mostrar seção de pagamento pendente
        pendingPaymentSection?.classList.add('is-visible');

        // Preencher informações
        const billingTypeLabel = data.billingType === 'PIX' ? 'PIX' : 'Boleto';
        const typeClass = data.billingType === 'PIX' ? 'pending-type-pix' : 'pending-type-boleto';

        if (pendingBillingType) {
            pendingBillingType.textContent = billingTypeLabel;
            pendingBillingType.className = 'pending-payment-section__info-value ' + typeClass;
        }
        if (pendingCreatedAt) pendingCreatedAt.textContent = data.createdAt || '-';

        // Esconder todos os elementos específicos primeiro
        if (pendingPixQrcode) pendingPixQrcode.style.display = 'none';
        if (pendingPixCopyArea) pendingPixCopyArea.style.display = 'none';
        if (pendingBoletoCode) pendingBoletoCode.style.display = 'none';
        if (pendingBoletoDownload) pendingBoletoDownload.style.display = 'none';
        if (pendingCopyBtn) pendingCopyBtn.style.display = 'none';

        if (data.billingType === 'PIX' && data.pix) {
            // Mostrar QR Code PIX
            if (pendingPixQrcode && data.pix.qrCodeImage) {
                pendingPixQrcode.src = data.pix.qrCodeImage;
                pendingPixQrcode.style.display = 'block';
            }
            if (pendingPixCode && data.pix.payload) {
                pendingPixCode.value = data.pix.payload;
                if (pendingPixCopyArea) pendingPixCopyArea.style.display = 'block';
            }

            // Iniciar polling para PIX
            if (data.paymentId) {
                startPaymentPolling(data.paymentId);
            }
        } else if (data.billingType === 'BOLETO' && data.boleto) {
            // Mostrar linha digitável do boleto
            if (pendingBoletoCode && data.boleto.identificationField) {
                pendingBoletoCode.textContent = data.boleto.identificationField;
                pendingBoletoCode.style.display = 'block';
                if (pendingCopyBtn) pendingCopyBtn.style.display = 'flex';
            }
            if (pendingBoletoDownload && data.boleto.bankSlipUrl) {
                pendingBoletoDownload.href = data.boleto.bankSlipUrl;
                pendingBoletoDownload.style.display = 'flex';
            }

            // Iniciar polling para Boleto também
            if (data.paymentId) {
                startPaymentPolling(data.paymentId);
            }
        }
    }

    function hidePendingPaymentSection() {
        pendingPaymentSection?.classList.remove('is-visible');
        if (modalBody) modalBody.style.display = '';
    }

    // Cancelar pagamento pendente
    async function cancelPendingPayment() {
        const result = await window.Swal?.fire({
            title: 'Cancelar pagamento?',
            text: 'Você poderá escolher outro método de pagamento após cancelar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Não, manter'
        });

        if (!result?.isConfirmed) return;

        try {
            cancelPendingBtn.disabled = true;
            cancelPendingBtn.innerHTML =
                '<i data-lucide="loader-2" class="icon-spin"></i> <span>Cancelando...</span>';

            const resp = await fetch(`${BASE_URL}premium/cancel-pending`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });
            const json = await resp.json();

            if (json.status === 'success') {
                hasPendingPayment = false;
                pendingPaymentData = null;
                stopPaymentPolling();
                hidePendingPaymentSection();

                window.Swal?.fire({
                    icon: 'success',
                    title: 'Pagamento cancelado!',
                    text: 'Agora você pode escolher outro método.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(json.message || 'Erro ao cancelar pagamento');
            }
        } catch (err) {
            console.error('[cancelPendingPayment] Erro:', err);
            window.Swal?.fire('Erro', err.message || 'Não foi possível cancelar o pagamento.', 'error');
        } finally {
            cancelPendingBtn.disabled = false;
            cancelPendingBtn.innerHTML =
                '<i data-lucide="x-circle"></i> <span>Cancelar e escolher outro método</span>';
        }
    }

    // Event listeners para seção de pagamento pendente
    cancelPendingBtn?.addEventListener('click', cancelPendingPayment);

    pendingPixCopyBtn?.addEventListener('click', () => {
        const code = pendingPixCode?.value;
        if (code) copyToClipboard(code, pendingPixCopyBtn);
    });

    pendingCopyBtn?.addEventListener('click', () => {
        const code = pendingBoletoCode?.textContent;
        if (code) copyToClipboard(code, pendingCopyBtn);
    });

    // ===============================
    // MODAL OPEN/CLOSE
    // ===============================
    async function openBillingModal(planConfig) {
        currentPlanConfig = planConfig;
        currentBillingType = 'CREDIT_CARD';

        if (modalTitle) modalTitle.textContent = 'Pagamento Seguro';

        // Primeiro, abrir o modal
        modal.classList.add('payment-modal--open');
        document.body.style.overflow = 'hidden';

        // Verificar se tem pagamento pendente
        const hasPending = await checkPendingPayment();

        if (!hasPending) {
            // Se não tem pendente, mostrar métodos normalmente
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

        // Reset estado de pagamento pendente
        hasPendingPayment = false;
        pendingPaymentData = null;
        hidePendingPaymentSection();

        // Destravar abas de pagamento
        unlockPaymentMethods();

        // Reset containers
        pixQrCodeContainer?.classList.remove('is-visible');
        boletoContainer?.classList.remove('is-visible');
        pixPendingStatus?.classList.remove('is-visible');
        boletoPendingStatus?.classList.remove('is-visible');

        if (form) form.reset();

        // Reset cupom
        removeCoupon();
        if (couponBody) couponBody.style.display = 'none';
        couponToggle?.classList.remove('is-open');
    }

    window.closeBillingModal = closeBillingModal;
    window.openBillingModal = openBillingModal;

    // ===============================
    // EVENT LISTENERS
    // ===============================

    // Seletor de método de pagamento
    paymentMethodBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Não permitir trocar método se estiver travado
            if (paymentMethodsLocked) return;

            const method = btn.dataset.method;
            if (method) switchPaymentMethod(method);
        });
    });

    // Botões dos planos -> abre modal
    planButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const planId = btn.dataset.planId || null;
            const planCode = btn.dataset.planCode || null;
            const planName = btn.dataset.planName || 'Lukrato PRO';
            const monthlyBase = Number(btn.dataset.planMonthly ?? btn.dataset.planAmount ?? '0');

            if (!planId || !planCode || !monthlyBase || Number.isNaN(monthlyBase)) {
                window.Swal?.fire('Plano inválido',
                    'Não foi possível identificar o plano. Recarregue a página.', 'warning');
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

                // Atualizar o preço exibido no card do plano Pro
                const proPriceElement = document.getElementById('planProPrice');
                if (proPriceElement) {
                    const basePrice = Number(proPriceElement.dataset.basePrice || 0);
                    const months = Number(b.dataset.months || 1);
                    const discount = Number(b.dataset.discount || 0);
                    const total = calcTotal(basePrice, months, discount);
                    const period = cycleLabel(months);

                    const priceValueElement = proPriceElement.querySelector(
                        '.plan-card__price-value');
                    const pricePeriodElement = proPriceElement.querySelector(
                        '.plan-card__price-period');

                    if (priceValueElement) {
                        priceValueElement.style.animation = 'none';
                        void priceValueElement.offsetWidth;
                        priceValueElement.textContent =
                            `R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        priceValueElement.style.animation = 'priceAppear 0.5s ease';
                    }
                    if (pricePeriodElement) {
                        pricePeriodElement.textContent = `/${period}`;
                    }
                }

                if (!currentPlanConfig) return;

                currentPlanConfig.cycle = b.dataset.cycle || 'monthly';
                currentPlanConfig.months = Number(b.dataset.months || '1');
                currentPlanConfig.discount = Number(b.dataset.discount || '0');

                if (modal?.classList.contains('payment-modal--open'))
                    syncPlanHiddenFields();
            });
        });
    });

    // ===============================
    // SUBMIT DO FORMULÁRIO
    // ===============================
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!currentPlanConfig) {
            window.Swal?.fire('Plano inválido', 'Selecione um plano novamente.', 'warning');
            return;
        }

        syncPlanHiddenFields();

        const fd = new FormData(form);
        let payload = {
            plan_id: currentPlanConfig.planId,
            plan_code: currentPlanConfig.planCode,
            cycle: inputPlanCycle.value,
            months: Number(inputPlanMonths.value || 1),
            discount: Number(inputPlanDiscount.value || 0),
            amount_base_monthly: Number(inputBaseMonthly.value || currentPlanConfig.monthlyBase ||
                0),
            couponCode: appliedCoupon ? appliedCoupon.codigo : null,
            amount: Number(inputPlanAmount.value || 0),
            billingType: currentBillingType
        };

        // Validar e montar payload baseado no método
        if (currentBillingType === 'CREDIT_CARD') {
            const holderName = fd.get('card_holder')?.toString().trim() || '';
            const cardNumber = (fd.get('card_number')?.toString().replace(/\s+/g, '') || '');
            const cardCvv = fd.get('card_cvv')?.toString().trim() || '';
            const cardExpiry = fd.get('card_expiry')?.toString().trim() || '';
            const cpf = onlyDigits(fd.get('card_cpf')?.toString() || '');
            const phone = onlyDigits(fd.get('card_phone')?.toString() || '');
            const cep = onlyDigits(fd.get('card_cep')?.toString() || '');

            if (!holderName || !cardNumber || !cardCvv || !cardExpiry || !cpf || !phone || !cep) {
                window.Swal?.fire('Campos obrigatórios', 'Preencha todos os dados do cartão.',
                    'warning');
                return;
            }

            const [month, year] = cardExpiry.split('/').map(v => v.trim());
            if (!month || !year) {
                window.Swal?.fire('Validade inválida', 'Informe a validade no formato MM/AA.',
                    'warning');
                return;
            }

            payload.creditCard = {
                holderName,
                number: cardNumber,
                expiryMonth: month,
                expiryYear: (year.length === 2 ? '20' + year : year),
                ccv: cardCvv
            };
            payload.creditCardHolderInfo = {
                name: holderName,
                email: userDataComplete.email || '',
                cpfCnpj: cpf,
                mobilePhone: phone,
                postalCode: cep
            };

        } else if (currentBillingType === 'PIX') {
            // Usar dados do banco se disponíveis, senão do formulário
            const cpf = userDataComplete.pix ? userDataComplete.cpf : onlyDigits(fd.get('pix_cpf')
                ?.toString() || '');
            const phone = userDataComplete.pix ? userDataComplete.phone : onlyDigits(fd.get('pix_phone')
                ?.toString() || '');

            if (!cpf || cpf.length !== 11) {
                window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o PIX.', 'warning');
                return;
            }

            payload.holderInfo = {
                cpfCnpj: cpf,
                mobilePhone: phone,
                email: userDataComplete.email
            };

        } else if (currentBillingType === 'BOLETO') {
            // Usar dados do banco se disponíveis, senão do formulário
            const cpf = userDataComplete.boleto ? userDataComplete.cpf : onlyDigits(fd.get('boleto_cpf')
                ?.toString() || '');
            const phone = userDataComplete.boleto ? userDataComplete.phone : onlyDigits(fd.get(
                'boleto_phone')?.toString() || '');
            const cep = userDataComplete.boleto ? userDataComplete.cep : onlyDigits(fd.get('boleto_cep')
                ?.toString() || '');
            const endereco = userDataComplete.boleto ? userDataComplete.endereco : (fd.get(
                'boleto_endereco')?.toString().trim() || '');

            if (!cpf || cpf.length !== 11) {
                window.Swal?.fire('CPF inválido', 'Informe um CPF válido para gerar o boleto.',
                    'warning');
                return;
            }

            if (!cep || cep.length !== 8) {
                window.Swal?.fire('CEP inválido', 'Informe um CEP válido para gerar o boleto.',
                    'warning');
                return;
            }

            payload.holderInfo = {
                cpfCnpj: cpf,
                mobilePhone: phone,
                postalCode: cep,
                address: endereco,
                email: userDataComplete.email
            };
        }

        // Enviar requisição
        try {
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.querySelector('span').textContent;
            submitBtn.querySelector('span').textContent = 'Processando...';

            const loadingTitle = currentBillingType === 'PIX' ? 'Gerando PIX...' :
                currentBillingType === 'BOLETO' ? 'Gerando Boleto...' :
                    'Processando pagamento...';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: loadingTitle,
                    text: 'Aguarde enquanto processamos sua solicitação.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            }

            const resp = await fetch(`${BASE_URL}premium/checkout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            });

            const json = await resp.json().catch(() => null);

            if (!resp.ok || !json || json.status !== 'success') {
                throw new Error(json?.message || 'Não foi possível processar a solicitação.');
            }

            Swal?.close();

            // Tratamento baseado no método de pagamento
            if (currentBillingType === 'CREDIT_CARD') {
                window.Swal?.fire('Sucesso! 🎉', json?.message || 'Pagamento realizado com sucesso.',
                    'success')
                    .then(() => window.location.reload());
                closeBillingModal();

            } else if (currentBillingType === 'PIX') {
                // Exibir seção de pagamento pendente com QR Code
                const pix = json.data?.pix;
                if (pix) {
                    // Mostrar seção de pagamento pendente
                    showPendingPaymentSection({
                        billingType: 'PIX',
                        createdAt: new Date().toLocaleString('pt-BR'),
                        paymentId: json.data?.paymentId,
                        pix: {
                            qrCodeImage: pix.qrCodeImage,
                            payload: pix.payload
                        }
                    });

                    window.Swal?.fire({
                        icon: 'success',
                        title: 'PIX gerado!',
                        text: 'Escaneie o QR Code ou copie o código para pagar.',
                        confirmButtonText: 'Entendi'
                    });
                } else {
                    throw new Error('PIX gerado mas dados não recebidos. Tente novamente.');
                }

            } else if (currentBillingType === 'BOLETO') {
                // Exibir seção de pagamento pendente com boleto
                if (json.data?.boleto) {
                    const boleto = json.data.boleto;

                    // Mostrar seção de pagamento pendente
                    showPendingPaymentSection({
                        billingType: 'BOLETO',
                        createdAt: new Date().toLocaleString('pt-BR'),
                        paymentId: json.data.paymentId,
                        boleto: {
                            identificationField: boleto.identificationField,
                            bankSlipUrl: boleto.bankSlipUrl
                        }
                    });

                    window.Swal?.fire({
                        icon: 'success',
                        title: 'Boleto gerado!',
                        text: 'Copie o código ou baixe o PDF para pagar.',
                        confirmButtonText: 'Entendi'
                    });
                }
            }

        } catch (error) {
            console.error('[Checkout] Erro:', error);
            Swal?.close();
            window.Swal?.fire('Erro', error.message || 'Erro ao processar. Tente novamente.',
                'error');
        } finally {
            if (currentBillingType === 'CREDIT_CARD') {
                submitBtn.disabled = false;
                updateSubmitButton();
            }
        }
    });
})();
