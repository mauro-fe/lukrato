<div class="payment-form__actions">
    <?php if (($billingPaymentMode ?? 'modal') === 'page'): ?>
        <a href="<?= BASE_URL ?>billing" class="btn-outline">
            Voltar
        </a>
    <?php else: ?>
        <button type="button" class="btn-outline" onclick="window.closeBillingModal?.()">
            Cancelar
        </button>
    <?php endif; ?>
    <button type="submit" class="btn-primary" id="asaasSubmitBtn">
        <i data-lucide="lock" aria-hidden="true"></i>
        <span>Pagar com cartão</span>
    </button>
</div>
