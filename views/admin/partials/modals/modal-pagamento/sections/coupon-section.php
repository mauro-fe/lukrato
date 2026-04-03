<!-- ========== CAMPO DE CUPOM DE DESCONTO ========== -->
<div class="coupon-section">
    <div class="coupon-section__toggle" id="couponToggle">
        <i data-lucide="ticket"></i>
        <span>Tem um cupom de desconto?</span>
        <i data-lucide="chevron-down" class="coupon-section__chevron" id="couponChevron"></i>
    </div>
    <div class="coupon-section__body" id="couponBody" style="display:none">
        <div class="coupon-section__row">
            <input type="text" id="couponCodeInput" name="couponCode" class="payment-form__input coupon-input"
                placeholder="Digite o código do cupom" autocomplete="off" maxlength="30">
            <button type="button" id="couponApplyBtn" class="btn-coupon-apply" onclick="applyCoupon()">
                <i data-lucide="check"></i>
                <span>Aplicar</span>
            </button>
        </div>
        <div class="coupon-section__feedback" id="couponFeedback" style="display:none"></div>
    </div>
</div>