<style>
.lk-onboarding-wrapper {
    min-height: 100vh;
    background: var(--color-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-6);
}

.lk-onboarding-card {
    width: 100%;
    max-width: 520px;
    background: var(--color-bg);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    box-shadow: var(--shadow-xl);
    border: 1px solid var(--glass-border);
}

.lk-onboarding-progress {
    margin-bottom: var(--spacing-6);
}

.lk-progress-text {
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
    margin-bottom: var(--spacing-2);
}

.lk-progress-bar {
    height: 6px;
    background: var(--color-surface-muted);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.lk-progress-fill {
    height: 100%;
    background: var(--color-primary);
    transition: var(--transition-normal);
}

.lk-onboarding-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: var(--spacing-2);
}

.lk-onboarding-header p {
    color: var(--color-text-muted);
    margin-bottom: var(--spacing-6);
}

.lk-form-group {
    margin-bottom: var(--spacing-5);
}

.lk-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: var(--spacing-2);
}

.lk-label.required::after {
    content: '*';
    color: var(--color-danger);
    margin-left: 4px;
}

.lk-input,
.lk-select {
    width: 100%;
    padding: var(--spacing-3) var(--spacing-4);
    border: 2px solid var(--glass-border);
    border-radius: var(--radius-md);
    background: var(--color-bg);
    color: var(--color-text);
    transition: var(--transition-normal);
}

.lk-input:focus,
.lk-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px var(--ring);
}

.lk-helper-text {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    margin-top: var(--spacing-2);
}

.lk-input-money {
    position: relative;
}

.lk-currency {
    position: absolute;
    left: var(--spacing-4);
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-text-muted);
    font-weight: 600;
}

.lk-input-with-prefix {
    padding-left: 50px;
}

.lk-btn-primary {
    width: 100%;
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    background: var(--color-primary);
    color: white;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: var(--transition-normal);
    box-shadow: var(--shadow-md);
}

.lk-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.lk-onboarding-hint {
    text-align: center;
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    margin-top: var(--spacing-4);
}

.lk-onboarding-error {
    background: var(--color-danger-bg, rgba(239, 68, 68, 0.1));
    color: var(--color-danger, #ef4444);
    padding: var(--spacing-3) var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-5);
    font-size: var(--font-size-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

@media (max-width: 600px) {
    .lk-onboarding-card {
        padding: var(--spacing-6);
    }
}
</style>
<div class="lk-onboarding-wrapper">
    <div class="lk-onboarding-card">

        <!-- Erro -->
        <?php if (!empty($_SESSION['error'])): ?>
        <div class="lk-onboarding-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Progresso -->
        <div class="lk-onboarding-progress">
            <div class="lk-progress-text">Etapa 1 de 2</div>
            <div class="lk-progress-bar">
                <div class="lk-progress-fill" style="width: 50%;"></div>
            </div>
        </div>

        <!-- Título -->
        <div class="lk-onboarding-header">
            <h1>Onde você guarda seu dinheiro?</h1>
            <p>Isso ajuda o Lukrato a organizar seus lançamentos.</p>
        </div>

        <!-- Form -->
        <form method="POST" action="<?= BASE_URL ?>api/onboarding/conta" class="lk-onboarding-form">
            <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

            <!-- Nome da Conta -->
            <div class="lk-form-group">
                <label class="lk-label required">
                    <i class="fas fa-wallet"></i>
                    Nome da Conta
                </label>
                <input type="text" name="nome" class="lk-input" placeholder="Ex: Nubank, Itaú, Carteira..." required>
            </div>

            <!-- Instituição Financeira -->
            <div class="lk-form-group">
                <label class="lk-label required">
                    <i class="fas fa-building"></i>
                    Instituição Financeira
                </label>
                <select name="instituicao_financeira_id" class="lk-select" required>
                    <option value="">Selecione a instituição</option>
                    <?php foreach ($instituicoes as $inst): ?>
                    <option value="<?= $inst->id ?>">
                        <?= htmlspecialchars($inst->nome) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Saldo Inicial -->
            <div class="lk-form-group">
                <label class="lk-label">
                    <i class="fas fa-coins"></i>
                    Saldo Inicial
                </label>
                <div class="lk-input-money">
                    <span class="lk-currency">R$</span>
                    <input type="text" name="saldo_inicial" value="0,00" class="lk-input lk-input-with-prefix">
                </div>
                <small class="lk-helper-text">
                    Opcional — você pode ajustar depois.
                </small>
            </div>

            <!-- Botão -->
            <button type="submit" class="lk-btn-primary">
                Continuar <i class="fas fa-arrow-right"></i>
            </button>

            <p class="lk-onboarding-hint">
                Leva menos de 30 segundos.
            </p>

        </form>

    </div>
</div>