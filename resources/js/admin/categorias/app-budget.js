/**
 * ============================================================================
 * LUKRATO - Categorias / Budget
 * ============================================================================
 * Monthly budget modal and API operations.
 * ============================================================================
 */

export function createCategoriasBudget({
    STATE,
    CONFIG,
    Utils,
    apiPost,
    apiDelete,
    getErrorMessage,
    showSuccess,
    showError,
    getOrcamento,
    loadOrcamentos,
    renderCategorias,
}) {
    async function salvarOrcamento(categoriaId, valorLimite) {
        try {
            const mes = STATE.mesSelecionado;
            const ano = STATE.anoSelecionado;

            await apiPost(`${CONFIG.API_URL}financas/orcamentos`, {
                categoria_id: categoriaId,
                valor_limite: valorLimite,
                mes,
                ano,
            });

            showSuccess('Limite atualizado!');
            await loadOrcamentos();
            renderCategorias();
        } catch (error) {
            console.error('Erro ao salvar orcamento:', error);
            showError(getErrorMessage(error, 'Erro ao salvar limite. Tente novamente.'));
        }
    }

    async function removerOrcamento(orcamentoId) {
        try {
            await apiDelete(`${CONFIG.API_URL}financas/orcamentos/${orcamentoId}`);

            showSuccess('Limite removido!');
            await loadOrcamentos();
            renderCategorias();
        } catch (error) {
            console.error('Erro ao remover orcamento:', error);
            showError(getErrorMessage(error, 'Erro ao remover limite. Tente novamente.'));
        }
    }

    function editarOrcamento(categoriaId, event) {
        if (event) event.stopPropagation();

        const categoria = STATE.categorias.find((cat) => cat.id === categoriaId);
        if (!categoria) return;

        const orcamento = getOrcamento(categoriaId);
        const currentValue = orcamento ? parseFloat(orcamento.valor_limite) : 0;

        document.getElementById('orcCategoriaNome').textContent = categoria.nome;
        const gastoEl = document.getElementById('orcGastoAtual');
        const gastoValorEl = document.getElementById('orcGastoValor');
        const btnRemover = document.getElementById('btnRemoverOrcamento');
        const btnText = document.getElementById('btnOrcText');
        const inputValor = document.getElementById('orcValorLimite');
        const alertEl = document.getElementById('orcAlertError');

        alertEl.classList.add('d-none');
        inputValor.value = currentValue > 0 ? Utils.formatOrcamentoInput(currentValue) : '';

        if (orcamento) {
            gastoEl.classList.remove('d-none');
            gastoValorEl.textContent = Utils.formatCurrency(orcamento.gasto_real);
            btnRemover.classList.remove('d-none');
            btnText.textContent = 'Atualizar';
        } else {
            gastoEl.classList.add('d-none');
            btnRemover.classList.add('d-none');
            btnText.textContent = 'Definir';
        }

        const form = document.getElementById('formOrcamento');
        form.dataset.categoriaId = categoriaId;

        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);

        const newInput = newForm.querySelector('#orcValorLimite');
        newInput.addEventListener('input', () => {
            Utils.applyCurrencyMask(newInput);
        });

        newForm.addEventListener('submit', async (submitEvent) => {
            submitEvent.preventDefault();
            const raw = document.getElementById('orcValorLimite').value;
            const val = Utils.parseCurrencyInput(raw);
            const errEl = document.getElementById('orcAlertError');
            if (!val || Number.isNaN(val) || val <= 0) {
                errEl.textContent = 'Informe um valor maior que zero';
                errEl.classList.remove('d-none');
                return;
            }
            errEl.classList.add('d-none');
            await salvarOrcamento(Number.parseInt(newForm.dataset.categoriaId, 10), val);
            bootstrap.Modal.getInstance(document.getElementById('modalOrcamento'))?.hide();
        });

        const newBtnRemover = document.getElementById('btnRemoverOrcamento');
        const clonedBtn = newBtnRemover.cloneNode(true);
        newBtnRemover.parentNode.replaceChild(clonedBtn, newBtnRemover);
        clonedBtn.addEventListener('click', async () => {
            if (orcamento) {
                await removerOrcamento(orcamento.id);
                bootstrap.Modal.getInstance(document.getElementById('modalOrcamento'))?.hide();
            }
        });

        document.getElementById('btnSalvarOrcamento').setAttribute('form', 'formOrcamento');

        const modal = new bootstrap.Modal(document.getElementById('modalOrcamento'));
        modal.show();

        document.getElementById('modalOrcamento').addEventListener('shown.bs.modal', () => {
            document.getElementById('orcValorLimite').focus();
        }, { once: true });
    }

    return {
        editarOrcamento,
        salvarOrcamento,
        removerOrcamento,
    };
}
