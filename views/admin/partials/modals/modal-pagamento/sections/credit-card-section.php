<!-- ========== SEÇÃO CARTÃO DE CRÉDITO ========== -->
<div id="credit-card-section" class="payment-section is-visible">
    <div class="payment-form__field">
        <label for="card_holder" class="payment-form__label">Nome no cartão</label>
        <input type="text" id="card_holder" name="card_holder" class="payment-form__input"
            value="<?= htmlspecialchars($user->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: João da Silva">
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
            <input type="text" id="card_cpf" name="card_cpf" class="payment-form__input" placeholder="000.000.000-00"
                value="<?= htmlspecialchars($cpfValue, ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="payment-form__row">
        <div class="payment-form__field">
            <label for="card_phone" class="payment-form__label">Telefone</label>
            <input type="text" id="card_phone" name="card_phone" class="payment-form__input"
                placeholder="(00) 00000-0000" value="<?= htmlspecialchars($telefoneValue, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="payment-form__field">
            <label for="card_cep" class="payment-form__label">CEP</label>
            <input type="text" id="card_cep" name="card_cep" class="payment-form__input" placeholder="00000-000"
                value="<?= htmlspecialchars($cepValue, ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>
</div>