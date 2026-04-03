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
                        placeholder="000.000.000-00" value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
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