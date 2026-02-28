/**
 * ============================================================================
 * LUKRATO — Contas / Render
 * ============================================================================
 * Rendering helpers: account cards, institution selects, stats.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';

// ─── Render Module ───────────────────────────────────────────────────────────

export const ContasRender = {

    /**
     * Renderizar lista de contas
     */
    renderContas() {
        const container = document.getElementById('accountsGrid');
        if (!container) {
            console.error('❌ [DEBUG] accountsGrid NÃO encontrado!');
            return;
        }


        if (STATE.contas.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">
                        <i data-lucide="wallet"></i>
                    </div>
                    <h3>Nenhuma conta cadastrada</h3>
                    <p>Comece criando sua primeira conta bancária para gerenciar suas finanças</p>
                    <button class="btn btn-primary btn-lg" id="btnCriarPrimeiraConta">
                        <i data-lucide="plus"></i> Criar primeira conta
                    </button>
                </div>
            `;
            refreshIcons();
            // Anexar listener para o botão de criar primeira conta
            setTimeout(() => {
                const btnCriarPrimeira = document.getElementById('btnCriarPrimeiraConta');
                if (btnCriarPrimeira) {
                    btnCriarPrimeira.addEventListener('click', () => {
                        Modules.Modal?.openModal?.('create');
                    });
                }
            }, 100);
            return;
        }

        container.innerHTML = STATE.contas.map(conta => ContasRender.createContaCard(conta)).join('');
        refreshIcons();
        Modules.Events?.attachContaCardListeners?.();
    },

    /**
     * Criar card de conta
     */
    createContaCard(conta) {
        // Buscar instituição do objeto conta ou da lista
        let instituicao = conta.instituicao_financeira || Utils.getInstituicao(conta.instituicao_financeira_id);

        const logoUrl = instituicao?.logo_url || `${CONFIG.BASE_URL}assets/img/banks/default.svg`;
        const corPrimaria = instituicao?.cor_primaria || '#667eea';
        // Normalizar saldo: valores muito próximos de zero são tratados como zero
        let saldo = conta.saldo_atual || conta.saldoAtual || 0;
        if (Math.abs(saldo) < 0.01) saldo = 0;
        const saldoClass = saldo >= 0 ? 'positive' : 'negative';

        // Badge do tipo de conta para list view
        const tipoConta = conta.tipo_conta || conta.tipo || 'conta_corrente';
        const tipoLabel = Utils.formatTipoConta(tipoConta);
        const tipoClass = Utils.getTipoContaClass(tipoConta);

        return `
            <div class="account-card" data-account-id="${conta.id}">
                <div class="account-header" style="background: ${corPrimaria};">
                    <div class="account-logo">
                        <img src="${logoUrl}" alt="${conta.nome}" />
                    </div>
                    <div class="account-actions">
                     <button
                type="button"
                class="lk-info"
                data-lk-tooltip-title="Exclusão de contas"
                data-lk-tooltip="Para manter a integridade dos seus dados, contas só podem ser excluídas após serem arquivadas. Arquive a conta primeiro e depois realize a exclusão."
                aria-label="Ajuda: Exclusão de contas"
            >
                <i data-lucide="info" aria-hidden="true"></i>
            </button>
                        <button class="btn-icon" onclick="contasManager.editConta(${conta.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="btn-icon" onclick="contasManager.moreConta(${conta.id}, event)" title="Mais opções">
                            <i data-lucide="more-vertical"></i>
                        </button>
                    </div>
                </div>
                <div class="account-body">
                    <h3 class="account-name">${conta.nome}</h3>
                    <div class="account-institution">${instituicao ? instituicao.nome : 'Instituição não definida'}</div>
                    <span class="account-type-badge ${tipoClass}">${tipoLabel}</span>
                    <div class="account-balance ${saldoClass}">
                        ${Utils.formatCurrency(saldo)}
                    </div>
                    <div class="account-info">
                        <button class="btn-new-transaction" data-conta-id="${conta.id}" title="Novo Lançamento">
                            <i data-lucide="circle-plus"></i> Novo Lançamento
                        </button>
                    </div>
                    ${ContasRender.renderCartoesBadge(conta)}
                </div>
                <div class="account-list-actions">
                    <button class="btn-icon" onclick="contasManager.editConta(${conta.id})" title="Editar">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-icon" onclick="contasManager.moreConta(${conta.id}, event)" title="Mais opções">
                        <i data-lucide="more-vertical"></i>
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Renderizar badge de cartões vinculados
     */
    renderCartoesBadge(conta) {
        // TODO: Implementar contagem de cartões vinculados
        return '';
    },

    /**
     * Renderizar select de instituições
     */
    renderInstituicoesSelect() {
        const select = document.getElementById('instituicaoFinanceiraSelect');
        if (!select) return;

        const grupos = Utils.groupByTipo(STATE.instituicoes);

        select.innerHTML = '<option value="">Selecione uma instituição</option>';

        Object.keys(grupos).forEach(tipo => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = Utils.formatTipo(tipo);

            grupos[tipo].forEach(inst => {
                const option = document.createElement('option');
                option.value = inst.id;
                option.textContent = inst.nome;
                option.dataset.codigo = inst.codigo;
                option.dataset.cor = inst.cor_primaria;
                optgroup.appendChild(option);
            });

            select.appendChild(optgroup);
        });
    },

    /**
     * Atualizar estatísticas
     */
    updateStats() {
        const totalContas = STATE.contas.length;
        const saldoTotal = STATE.contas.reduce((sum, c) => sum + (c.saldoAtual || 0), 0);

        const totalContasEl = document.getElementById('totalContas');
        const saldoTotalEl = document.getElementById('saldoTotal');

        if (totalContasEl) totalContasEl.textContent = totalContas;
        if (saldoTotalEl) saldoTotalEl.textContent = Utils.formatCurrency(saldoTotal);
    },

    /**
     * Mostrar/ocultar loading
     */
    showLoading(show) {
        const grid = document.getElementById('accountsGrid');
        if (!grid) return;

        if (show) {
            grid.innerHTML = `
                <div class="acc-skeleton"></div>
                <div class="acc-skeleton"></div>
                <div class="acc-skeleton"></div>
            `;
        }
    }
};

// ─── Register in Modules ─────────────────────────────────────────────────────
Modules.Render = ContasRender;
