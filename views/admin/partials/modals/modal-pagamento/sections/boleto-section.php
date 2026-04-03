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
                        placeholder="000.000.000-00" value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
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
                        placeholder="00000-000" value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="payment-form__field">
                    <label for="boleto_endereco" class="payment-form__label">Endereço</label>
                    <input type="text" id="boleto_endereco" name="boleto_endereco" class="payment-form__input"
                        placeholder="Rua, número" value="<?= htmlspecialchars($enderecoValue, ENT_QUOTES, 'UTF-8') ?>">
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