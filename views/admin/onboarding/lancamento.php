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
    background: var(--glass-bg);
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
    font-size: 1rem;
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

/* Toggle Receita/Despesa */
.lk-tipo-toggle {
    display: flex;
    gap: var(--spacing-2);
    background: var(--color-bg);
    border-radius: var(--radius-md);
    padding: 4px;
    border: 2px solid var(--glass-border);
}

.lk-tipo-btn {
    flex: 1;
    padding: var(--spacing-3) var(--spacing-4);
    border: none;
    border-radius: var(--radius-sm);
    background: transparent;
    color: var(--color-text-muted);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition-normal);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
}

.lk-tipo-btn.active-despesa {
    background: var(--color-danger, #ef4444);
    color: white;
    box-shadow: var(--shadow-sm);
}

.lk-tipo-btn.active-receita {
    background: var(--color-success, #10b981);
    color: white;
    box-shadow: var(--shadow-sm);
}

.lk-tipo-btn:hover:not(.active-despesa):not(.active-receita) {
    background: var(--color-surface-muted);
}

/* Conta info card */
.lk-conta-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--color-bg);
    border: 2px solid var(--glass-border);
    border-radius: var(--radius-md);
}

.lk-conta-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    background: var(--color-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.lk-conta-details {
    flex: 1;
    min-width: 0;
}

.lk-conta-name {
    font-weight: 600;
    color: var(--color-text);
    font-size: var(--font-size-sm);
}

.lk-conta-inst {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}

.lk-conta-check {
    color: var(--color-success, #10b981);
    font-size: 1.1rem;
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
            <div class="lk-progress-text">Etapa 2 de 2</div>
            <div class="lk-progress-bar">
                <div class="lk-progress-fill" style="width: 100%;"></div>
            </div>
        </div>

        <!-- Título -->
        <div class="lk-onboarding-header">
            <h1>Registre seu primeiro lançamento</h1>
            <p>Pode ser algo simples — uma despesa recente ou seu salário do mês.</p>
        </div>

        <!-- Form -->
        <form method="POST" action="<?= BASE_URL ?>api/onboarding/lancamento" class="lk-onboarding-form"
            id="onboardingLancamentoForm">
            <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>
            <input type="hidden" name="conta_id" value="<?= $conta->id ?>">

            <!-- Conta (visual, não editável) -->
            <div class="lk-form-group">
                <label class="lk-label">
                    <i class="fas fa-wallet"></i>
                    Conta
                </label>
                <div class="lk-conta-info">
                    <div class="lk-conta-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="lk-conta-details">
                        <div class="lk-conta-name"><?= htmlspecialchars($conta->nome) ?></div>
                        <?php if ($conta->instituicaoFinanceira): ?>
                        <div class="lk-conta-inst"><?= htmlspecialchars($conta->instituicaoFinanceira->nome) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="lk-conta-check">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Tipo: Receita / Despesa -->
            <div class="lk-form-group">
                <label class="lk-label required">
                    <i class="fas fa-exchange-alt"></i>
                    Tipo
                </label>
                <input type="hidden" name="tipo" id="tipoInput" value="despesa">
                <div class="lk-tipo-toggle">
                    <button type="button" class="lk-tipo-btn active-despesa" data-tipo="despesa" id="btnDespesa">
                        <i class="fas fa-arrow-down"></i> Despesa
                    </button>
                    <button type="button" class="lk-tipo-btn" data-tipo="receita" id="btnReceita">
                        <i class="fas fa-arrow-up"></i> Receita
                    </button>
                </div>
            </div>

            <!-- Valor -->
            <div class="lk-form-group">
                <label class="lk-label required">
                    <i class="fas fa-dollar-sign"></i>
                    Valor
                </label>
                <div class="lk-input-money">
                    <span class="lk-currency">R$</span>
                    <input type="text" name="valor" class="lk-input lk-input-with-prefix" placeholder="0,00" required
                        inputmode="decimal" id="valorInput">
                </div>
            </div>

            <!-- Categoria -->
            <div class="lk-form-group">
                <label class="lk-label required">
                    <i class="fas fa-tag"></i>
                    Categoria
                </label>
                <select name="categoria_id" class="lk-select" required id="categoriaSelect">
                    <option value="">Selecione a categoria</option>
                    <?php foreach ($categoriasDespesa as $cat): ?>
                    <option value="<?= $cat->id ?>" data-tipo="despesa">
                        <?= htmlspecialchars($cat->nome) ?>
                    </option>
                    <?php endforeach; ?>
                    <?php foreach ($categoriasReceita as $cat): ?>
                    <option value="<?= $cat->id ?>" data-tipo="receita" style="display:none;" disabled>
                        <?= htmlspecialchars($cat->nome) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Descrição -->
            <div class="lk-form-group">
                <label class="lk-label required">
                    <i class="fas fa-pencil-alt"></i>
                    Descrição
                </label>
                <input type="text" name="descricao" class="lk-input" placeholder="Ex: Almoço, Salário, Uber..." required
                    maxlength="190" id="descricaoInput">
            </div>

            <!-- Botão -->
            <button type="submit" class="lk-btn-primary" id="btnSubmit">
                Concluir e começar a usar! <i class="fas fa-check"></i>
            </button>

            <p class="lk-onboarding-hint">
                Você poderá adicionar mais lançamentos depois.
            </p>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoInput = document.getElementById('tipoInput');
    const btnDespesa = document.getElementById('btnDespesa');
    const btnReceita = document.getElementById('btnReceita');
    const categoriaSelect = document.getElementById('categoriaSelect');
    const valorInput = document.getElementById('valorInput');

    // Toggle tipo receita/despesa
    function setTipo(tipo) {
        tipoInput.value = tipo;

        // Atualizar botões
        btnDespesa.className = 'lk-tipo-btn' + (tipo === 'despesa' ? ' active-despesa' : '');
        btnReceita.className = 'lk-tipo-btn' + (tipo === 'receita' ? ' active-receita' : '');

        // Filtrar categorias
        const options = categoriaSelect.querySelectorAll('option[data-tipo]');
        categoriaSelect.value = ''; // Reset seleção

        options.forEach(opt => {
            if (opt.dataset.tipo === tipo) {
                opt.style.display = '';
                opt.disabled = false;
            } else {
                opt.style.display = 'none';
                opt.disabled = true;
            }
        });
    }

    btnDespesa.addEventListener('click', () => setTipo('despesa'));
    btnReceita.addEventListener('click', () => setTipo('receita'));

    // Máscara simples de valor (formato BR)
    valorInput.addEventListener('input', function(e) {
        let val = e.target.value.replace(/[^\d]/g, '');
        if (val === '') {
            e.target.value = '';
            return;
        }
        // Converter para centavos e formatar
        val = parseInt(val, 10);
        const formatted = (val / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g,
            '.');
        e.target.value = formatted;
    });
});
</script>