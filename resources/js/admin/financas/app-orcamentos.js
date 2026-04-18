/**
 * Financas - Orcamentos module
 * Orcamento CRUD and suggestion workflows.
 */

import {
    resolveFinanceBudgetApplySuggestionsEndpoint,
    resolveFinanceBudgetCopyMonthEndpoint,
    resolveFinanceBudgetEndpoint,
    resolveFinanceBudgetSuggestionsEndpoint,
    resolveFinanceBudgetsEndpoint,
} from '../api/endpoints/finance.js';

export function createFinancasOrcamentos({
    STATE,
    Utils,
    apiGet,
    apiPost,
    apiDelete,
    handleLimitError,
    requestErrorMessage,
    preventDemoAction,
    getCategoryIconColor,
    openModal,
    closeModal,
    loadAll,
}) {
    function openOrcamentoModal(orcId = null) {
        STATE.editingOrcamentoId = orcId;
        const title = document.getElementById('modalOrcamentoTitle');
        const form = document.getElementById('formOrcamento');

        if (orcId) {
            const orc = STATE.orcamentos.find((item) => item.id === orcId);
            if (!orc) return;
            if (preventDemoAction(orc)) return;
            if (title) title.textContent = 'Editar Orcamento';
            document.getElementById('orcCategoria').value = orc.categoria_id;
            document.getElementById('orcCategoria').disabled = true;
            document.getElementById('orcValor').value = Utils.formatNumber(orc.valor_limite);
            document.getElementById('orcRollover').checked = !!orc.rollover;
            document.getElementById('orcAlerta80').checked = orc.alerta_80 !== false && orc.alerta_80 !== 0;
            document.getElementById('orcAlerta100').checked = orc.alerta_100 !== false && orc.alerta_100 !== 0;
        } else {
            if (title) title.textContent = 'Novo Orcamento';
            form?.reset();
            document.getElementById('orcCategoria').disabled = false;
            document.getElementById('orcAlerta80').checked = true;
            document.getElementById('orcAlerta100').checked = true;
        }

        document.getElementById('orcSugestao').textContent = '';
        openModal('modalOrcamento');
    }

    async function handleOrcamentoSubmit(event) {
        event.preventDefault();

        const categoriaId = document.getElementById('orcCategoria').value;
        const valorLimite = Utils.parseMoney(document.getElementById('orcValor').value);
        const rollover = document.getElementById('orcRollover').checked;
        const alerta80 = document.getElementById('orcAlerta80').checked;
        const alerta100 = document.getElementById('orcAlerta100').checked;

        if (!categoriaId) return Utils.showToast('Selecione uma categoria', 'error');
        if (valorLimite <= 0) return Utils.showToast('Informe um valor valido', 'error');

        try {
            const res = await apiPost(resolveFinanceBudgetsEndpoint(), {
                categoria_id: Number.parseInt(categoriaId, 10),
                valor_limite: valorLimite,
                mes: STATE.currentMonth,
                ano: STATE.currentYear,
                rollover,
                alerta_80: alerta80,
                alerta_100: alerta100,
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                closeModal('modalOrcamento');
                Utils.showToast('Orcamento salvo!', 'success');
                await loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao salvar', 'error');
            }
        } catch (error) {
            Utils.showToast(requestErrorMessage(error, 'Erro ao salvar orcamento'), 'error');
        }
    }

    async function deleteOrcamento(id) {
        const orc = STATE.orcamentos.find((item) => item.id === id);
        if (preventDemoAction(orc)) return;
        const nome = orc?.categoria?.nome || 'este orcamento';

        const result = await Swal.fire({
            title: 'Excluir orcamento',
            html: `Deseja excluir o orcamento de <strong>${Utils.escHtml(nome)}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) return;

        try {
            const res = await apiDelete(resolveFinanceBudgetEndpoint(id));
            if (res.success !== false) {
                Utils.showToast('Orcamento excluido', 'success');
                await loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao excluir', 'error');
            }
        } catch (error) {
            Utils.showToast(requestErrorMessage(error, 'Erro ao excluir'), 'error');
        }
    }

    async function openSugestoes() {
        openModal('modalSugestoes');
        const list = document.getElementById('sugestoesList');
        list.innerHTML = '<div class="lk-loading-state"><i data-lucide="loader-2"></i><p>Analisando seu historico...</p></div>';
        if (window.lucide) lucide.createIcons();

        try {
            const res = await apiGet(resolveFinanceBudgetSuggestionsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
            if (res.success !== false && res.data?.length) {
                STATE.sugestoes = res.data;
                renderSugestoes();
            } else {
                list.innerHTML = '<div class="fin-empty-state"><p>Nao encontramos dados suficientes para gerar sugestoes.<br>Continue registrando suas despesas!</p></div>';
            }
        } catch {
            list.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar sugestoes.</p></div>';
        }
    }

    function renderSugestoes() {
        const list = document.getElementById('sugestoesList');
        list.innerHTML = STATE.sugestoes.map((sug, idx) => {
            const trendIcon = sug.tendencia === 'subindo' ? 'arrow-up' : sug.tendencia === 'descendo' ? 'arrow-down' : 'minus';
            const trendClass = sug.tendencia === 'subindo' ? 'up' : sug.tendencia === 'descendo' ? 'down' : 'stable';
            const catNome = sug.categoria?.nome || sug.categoria_nome || 'Categoria';
            const catIcone = sug.categoria?.icone || 'tag';
            const mediaGastos = sug.media_gastos || sug.media_3_meses || 0;
            const economiaTag = sug.economia_sugerida > 0
                ? `<span class="sugestao-economia"><i data-lucide="banknote" aria-hidden="true"></i> economia de ${Utils.formatCurrency(sug.economia_sugerida)}/mes</span>`
                : '';
            return `
            <div class="sugestao-item surface-card">
                <div class="sugestao-info">
                    <span class="sugestao-icon"><i data-lucide="${catIcone}" style="color:${getCategoryIconColor(catIcone)}"></i></span>
                    <div class="sugestao-detail">
                        <span class="sugestao-nome">${Utils.escHtml(catNome)}</span>
                        <span class="sugestao-media">Media: ${Utils.formatCurrency(mediaGastos)}
                            <i data-lucide="${trendIcon}" class="trend-${trendClass}"></i>
                        </span>
                        ${economiaTag}
                    </div>
                </div>
                <div class="sugestao-valor">
                    <input type="text" class="fin-input sugestao-input" id="sug_${idx}"
                           value="${Utils.formatNumber(sug.valor_sugerido || 0)}"
                           oninput="financasManager.formatarDinheiro(this)">
                </div>
                <label class="sugestao-check">
                    <input type="checkbox" checked data-sug-idx="${idx}">
                    <span class="checkmark"><i data-lucide="check"></i></span>
                </label>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    async function aplicarSugestoes() {
        const orcamentos = [];
        STATE.sugestoes.forEach((sug, idx) => {
            const checkbox = document.querySelector(`[data-sug-idx="${idx}"]`);
            if (checkbox?.checked) {
                const input = document.getElementById(`sug_${idx}`);
                const valor = Utils.parseMoney(input?.value || '0');
                if (valor > 0 && sug.categoria_id) {
                    orcamentos.push({
                        categoria_id: sug.categoria_id,
                        valor_limite: valor,
                        alerta_80: true,
                        alerta_100: true,
                    });
                }
            }
        });

        if (!orcamentos.length) return Utils.showToast('Selecione ao menos uma categoria', 'error');

        try {
            const res = await apiPost(resolveFinanceBudgetApplySuggestionsEndpoint(), {
                mes: STATE.currentMonth,
                ano: STATE.currentYear,
                orcamentos,
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                closeModal('modalSugestoes');
                Utils.showToast(`${res.data?.aplicados || orcamentos.length} orcamentos configurados!`, 'success');
                await loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao aplicar', 'error');
            }
        } catch (error) {
            Utils.showToast(requestErrorMessage(error, 'Erro ao aplicar sugestoes'), 'error');
        }
    }

    async function copiarMesAnterior() {
        let mesAnt = STATE.currentMonth - 1;
        let anoAnt = STATE.currentYear;
        if (mesAnt < 1) {
            mesAnt = 12;
            anoAnt--;
        }

        const meses = ['', 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        const result = await Swal.fire({
            title: 'Copiar mes anterior',
            html: `Deseja copiar os orcamentos de <strong>${meses[mesAnt]}/${anoAnt}</strong> para o mes atual?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, copiar',
            cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) return;

        try {
            const res = await apiPost(resolveFinanceBudgetCopyMonthEndpoint(), {
                mes_origem: mesAnt,
                ano_origem: anoAnt,
                mes_destino: STATE.currentMonth,
                ano_destino: STATE.currentYear,
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                Utils.showToast(`${res.data?.copiados || 0} orcamentos copiados!`, 'success');
                await loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao copiar', 'error');
            }
        } catch (error) {
            Utils.showToast(requestErrorMessage(error, 'Erro ao copiar mes'), 'error');
        }
    }

    async function loadCategorySuggestion(categoriaId) {
        const hint = document.getElementById('orcSugestao');
        if (!hint || !categoriaId) {
            if (hint) hint.textContent = '';
            return;
        }

        try {
            const res = await apiGet(resolveFinanceBudgetSuggestionsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
            if (res.success !== false && res.data?.length) {
                const sug = res.data.find((item) => item.categoria_id == categoriaId);
                if (sug) {
                    hint.textContent = `Sugestao: ${Utils.formatCurrency(sug.valor_sugerido)} (media: ${Utils.formatCurrency(sug.media_gastos)})`;
                } else {
                    hint.textContent = '';
                }
            }
        } catch {
            hint.textContent = '';
        }
    }

    return {
        openOrcamentoModal,
        handleOrcamentoSubmit,
        deleteOrcamento,
        openSugestoes,
        renderSugestoes,
        aplicarSugestoes,
        copiarMesAnterior,
        loadCategorySuggestion,
    };
}
