/**
 * Cartões Manager – API / Data layer
 * Extracted from cartoes-manager.js (monolith → modules)
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { apiFetch, getApiPayload, getErrorMessage, logClientError, logClientWarning } from '../shared/api.js';
import { refreshIcons } from '../shared/ui.js';

function normalizeApiUrl(url) {
    if (typeof url !== 'string') {
        return url;
    }

    if (url.startsWith(CONFIG.BASE_URL)) {
        return url.slice(CONFIG.BASE_URL.length);
    }

    return url;
}

async function requestJson(url, { method = 'GET', data = null, headers = {}, timeout = 15000 } = {}) {
    return apiFetch(normalizeApiUrl(url), {
        method,
        headers,
        body: data,
    }, { timeout });
}

export const CartoesAPI = {
    /**
     * Carregar cartões do servidor
     */
    async loadCartoes() {
        const grid = document.getElementById('cartoesGrid');
        const emptyState = document.getElementById('emptyState');
        const container = document.getElementById('cartoesContainer');

        if (!grid || !emptyState) {
            return;
        }

        try {
            STATE.isLoading = true;
            grid.setAttribute('aria-busy', 'true');
            container?.setAttribute('aria-busy', 'true');
            // Mostrar skeleton
            grid.innerHTML = `
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
            `;
            emptyState.style.display = 'none';


            // Usar lkFetch se disponível (com timeout, retry e indicadores)
            let data;
            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${CONFIG.API_URL}/cartoes`, {
                    timeout: 20000,      // 20 segundos
                    maxRetries: 2,       // 2 tentativas extras
                    showLoading: true,
                    loadingTarget: '#cartoesContainer'
                });
                data = getApiPayload(result, []);
            } else {
                data = await requestJson(`${CONFIG.API_URL}/cartoes`);
            }

            STATE.cartoes = Array.isArray(data) ? data : getApiPayload(data, []);
            await CartoesAPI.verificarFaturasPendentes();
            STATE.lastLoadedAt = new Date().toISOString();

            Modules.UI.updateStats();
            Modules.UI.filterCartoes();
            await CartoesAPI.carregarAlertas();

        } catch (error) {
            logClientError('[Cartoes] Erro ao carregar cartões', error, 'Erro ao carregar cartões');

            // Mensagem mais amigável para timeout
            let message = getErrorMessage(error, 'Erro ao carregar cartoes');
            if (error.name === 'AbortError' || message.includes('demorou')) {
                message = 'A conexão está lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conexão com a internet';
            }

            Utils.showToast('error', message);
            grid.innerHTML = `
                <div class="error-state">
                    <i data-lucide="triangle-alert"></i>
                    <p class="error-message">${Utils.escapeHtml(message)}</p>
                    <button class="btn btn-primary btn-retry" onclick="window.cartoesManager.loadCartoes()">
                        <i data-lucide="refresh-cw"></i> Tentar novamente
                    </button>
                </div>
            `;
            refreshIcons();
        } finally {
            STATE.isLoading = false;
            grid.setAttribute('aria-busy', 'false');
            container?.setAttribute('aria-busy', 'false');
        }
    },

    /**
     * Verificar se cartões têm faturas pendentes
     * Usa o endpoint /faturas-pendentes que retorna apenas os meses pendentes (leve)
     */
    async verificarFaturasPendentes() {
        // Marcar todos os cartões como sem fatura pendente por padrão
        STATE.cartoes.forEach(cartao => {
            cartao.temFaturaPendente = false;
        });

        // Verificar para cada cartão se tem fatura pendente
        const promises = STATE.cartoes.map(async (cartao) => {
            try {
                const data = await requestJson(`${CONFIG.API_URL}/cartoes/${cartao.id}/faturas-pendentes`);
                const payload = getApiPayload(data, {});
                const meses = payload?.meses || getApiPayload(payload, []) || [];
                cartao.temFaturaPendente = Array.isArray(meses) && meses.length > 0;
            } catch (error) {
                cartao.temFaturaPendente = false;
            }
        });

        await Promise.all(promises);
    },

    /**
     * Carregar alertas de vencimentos e limites baixos
     */
    async carregarAlertas() {
        try {
            let data;
            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${CONFIG.API_URL}/cartoes/alertas`, {
                    timeout: 10000,
                    maxRetries: 1,
                    showLoading: false // Não mostrar loading global para alertas
                });
                data = getApiPayload(result, {});
                const payload = getApiPayload(data, {});
                STATE.alertas = payload?.alertas || [];
            } else {
                data = await requestJson(`${CONFIG.API_URL}/cartoes/alertas`, { timeout: 10000 });
                const payload = getApiPayload(data, {});
                STATE.alertas = payload?.alertas || [];
            }

            CartoesAPI.renderAlertas();
        } catch (error) {
            logClientWarning('[Cartoes] Erro ao carregar alertas', error, 'Erro ao carregar alertas');
            STATE.alertas = [];
            // Não mostra erro para o usuário, apenas oculta o container
            const container = document.getElementById('alertasContainer');
            if (container) {
                container.style.display = 'none';
            }
        }
    },

    /**
     * Renderizar alertas na interface
     */
    renderAlertas() {
        const container = document.getElementById('alertasContainer');
        if (!container) return;

        if (STATE.alertas.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        container.innerHTML = `
            <div class="alertas-list">
                ${STATE.alertas.map(alerta => CartoesAPI.criarAlertaHTML(alerta)).join('')}
            </div>
        `;
        refreshIcons();
    },

    /**
     * Criar HTML para um alerta específico
     */
    criarAlertaHTML(alerta) {
        const icones = {
            vencimento_proximo: 'calendar-x',
            limite_baixo: 'triangle-alert'
        };

        const cores = {
            critico: '#e74c3c',
            atencao: '#f39c12'
        };

        const tipo = Object.prototype.hasOwnProperty.call(icones, alerta?.tipo)
            ? alerta.tipo
            : 'limite_baixo';
        const gravidade = Object.prototype.hasOwnProperty.call(cores, alerta?.gravidade)
            ? alerta.gravidade
            : 'atencao';
        const nomeCartao = Utils.escapeHtml(String(alerta?.nome_cartao || 'Cartão'));
        const diasFaltando = Number(alerta?.dias_faltando || 0);
        const percentualDisponivel = Number(alerta?.percentual_disponivel || 0);
        const valorFatura = Number(alerta?.valor_fatura || 0);
        const limiteDisponivel = Number(alerta?.limite_disponivel || 0);

        let mensagem = '';
        if (tipo === 'vencimento_proximo') {
            mensagem = `Fatura de <strong>${nomeCartao}</strong> vence em <strong>${diasFaltando} dia(s)</strong> - ${Utils.formatMoney(valorFatura)}`;
        } else if (tipo === 'limite_baixo') {
            mensagem = `Limite de <strong>${nomeCartao}</strong> em <strong>${percentualDisponivel.toFixed(1)}%</strong> - ${Utils.formatMoney(limiteDisponivel)} disponível`;
        }

        return `
            <div class="alerta-item alerta-${gravidade}" data-tipo="${tipo}">
                <div class="alerta-icon" style="color: ${cores[gravidade]}">
                    <i data-lucide="${icones[tipo]}"></i>
                </div>
                <div class="alerta-content">
                    <p>${mensagem}</p>
                </div>
                <button class="alerta-dismiss" onclick="cartoesManager.dismissAlerta(this)" title="Dispensar">
                    <i data-lucide="x"></i>
                </button>
            </div>
        `;
    },

    /**
     * Dispensar alerta (apenas oculta na UI)
     */
    dismissAlerta(button) {
        const alertaItem = button.closest('.alerta-item');
        if (alertaItem) {
            alertaItem.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => {
                alertaItem.remove();
                const container = document.getElementById('alertasContainer');
                if (container && container.querySelectorAll('.alerta-item').length === 0) {
                    container.style.display = 'none';
                }
            }, 300);
        }
    },

    /**
     * Carregar contas no select
     */
    async loadContasSelect() {
        const select = document.getElementById('contaVinculada');
        const help = document.getElementById('contaVinculadaHelp');
        const emptyHint = document.getElementById('cartaoContaEmptyHint');
        if (!select) {
            console.error('❌ Select contaVinculada não encontrado!');
            return;
        }

        try {
            const url = `${CONFIG.API_URL}/contas?only_active=0&with_balances=1`;
            const data = await requestJson(url);

            // Tentar diferentes estruturas de resposta
            const payload = getApiPayload(data, {});
            let contas = [];
            if (Array.isArray(payload)) {
                contas = payload;
            } else if (Array.isArray(payload?.contas)) {
                contas = payload.contas;
            }

            if (contas.length === 0) {
                select.disabled = true;
                select.innerHTML = '<option value="">Nenhuma conta disponivel</option>';
                if (help) {
                    help.textContent = 'Crie uma conta antes de vincular um cartao.';
                }
                if (emptyHint) {
                    emptyHint.hidden = false;
                }
                console.warn('⚠️ Nenhuma conta encontrada');
                return 0;
            }

            const options = contas.map((conta) => {
                // Pegar nome da instituição de diferentes estruturas possíveis
                const instituicao = conta.instituicao_financeira?.nome ||
                    conta.instituicao?.nome ||
                    conta.nome ||
                    'Sem instituição';
                const nomeConta = Utils.escapeHtml(conta.nome || 'Conta sem nome');
                const nomeInstituicao = Utils.escapeHtml(instituicao);
                // Tentar pegar o saldo de diferentes campos possíveis (saldoAtual é o campo retornado com with_balances=1)
                const saldoValue = parseFloat(conta.saldoAtual || conta.saldo_atual || conta.saldo || conta.saldo_inicial || 0);
                const saldo = Utils.formatMoney(saldoValue);
                return `<option value="${conta.id}">${nomeConta} - ${nomeInstituicao} - ${saldo}</option>`;
            }).join('');

            select.disabled = false;
            select.innerHTML = '<option value="">Selecione a conta</option>' + options;
            if (help) {
                help.textContent = 'Conta onde o pagamento da fatura sera debitado.';
            }
            if (emptyHint) {
                emptyHint.hidden = true;
            }
            return contas.length;
        } catch (error) {
            logClientError('[Cartoes] Erro ao carregar contas', error, 'Erro ao carregar contas');
            select.disabled = true;
            select.innerHTML = '<option value="">Erro ao carregar contas</option>';
            if (help) {
                help.textContent = 'Nao foi possivel carregar as contas agora.';
            }
            if (emptyHint) {
                emptyHint.hidden = false;
            }
            return 0;
        }
    },

    /**
     * Salvar cartão
     */
    async saveCartao() {
        const form = document.getElementById('formCartao');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const cartaoId = document.getElementById('cartaoId').value;
        const isEdit = !!cartaoId;

        // Obter token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="csrf_token"]')?.value ||
            '';

        const limiteOriginal = document.getElementById('limiteTotal').value;
        const limiteParsed = Utils.parseMoney(limiteOriginal);

        // Campos de lembrete de fatura
        const lembreteAviso = document.getElementById('cartaoLembreteAviso')?.value || '';
        const contaSelect = document.getElementById('contaVinculada');
        const canalInapp = document.getElementById('cartaoCanalInapp');
        const canalEmail = document.getElementById('cartaoCanalEmail');

        if (contaSelect?.disabled) {
            Utils.showToast('error', 'Crie uma conta antes de cadastrar um cartao.');
            return;
        }

        if (lembreteAviso && !canalInapp?.checked && !canalEmail?.checked) {
            Utils.showToast('error', 'Selecione pelo menos um canal para o lembrete.');
            return;
        }

        const data = {
            nome_cartao: document.getElementById('nomeCartao').value.trim(),
            conta_id: contaSelect?.value ? parseInt(contaSelect.value, 10) : null,
            bandeira: document.getElementById('bandeira').value,
            ultimos_digitos: document.getElementById('ultimosDigitos').value.trim(),
            limite_total: limiteParsed,
            dia_fechamento: document.getElementById('diaFechamento').value || null,
            dia_vencimento: document.getElementById('diaVencimento').value || null,
            lembrar_fatura_antes_segundos: lembreteAviso ? parseInt(lembreteAviso) : null,
            fatura_canal_inapp: lembreteAviso ? (canalInapp?.checked ? 1 : 0) : 0,
            fatura_canal_email: lembreteAviso ? (canalEmail?.checked ? 1 : 0) : 0,
            csrf_token: csrfToken
        };

        try {
            const url = isEdit
                ? `${CONFIG.API_URL}/cartoes/${cartaoId}`
                : `${CONFIG.API_URL}/cartoes`;

            const result = await requestJson(url, {
                method: isEdit ? 'PUT' : 'POST',
                data,
            });

            const resultData = getApiPayload(result, null);

            // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
            if (resultData?.gamification?.achievements && Array.isArray(resultData.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(resultData.gamification.achievements);
                } else {
                    console.error('❌ notifyMultipleAchievements não está disponível');
                }
            }

            Utils.showToast('success', isEdit ? 'Cartão atualizado com sucesso!' : 'Cartão criado com sucesso!');
            Modules.UI.closeModal();
            await CartoesAPI.loadCartoes();
        } catch (error) {
            logClientError('[Cartoes] Erro ao salvar cartão', error, 'Erro ao salvar cartão');
            Utils.showToast('error', getErrorMessage(error, 'Erro ao salvar cartao'));
        }
    },

    /**
     * Editar cartão
     */
    async editCartao(id) {
        const cartao = STATE.cartoes.find(c => c.id === id);
        if (cartao) {
            Modules.UI.openModal('edit', cartao);
        }
    },

    /**
     * Arquivar cartão
     */
    async arquivarCartao(id) {
        const cartao = STATE.cartoes.find(c => c.id === id);
        if (!cartao) return;

        // Confirmação
        const confirmacao = await Utils.showConfirmDialog(
            'Arquivar Cartão',
            `Tem certeza que deseja arquivar o cartão "${cartao.nome_cartao}"? Você poderá restaurá-lo depois na página de Cartões Arquivados.`,
            'Arquivar'
        );

        if (!confirmacao) return;

        try {
            await requestJson(`${CONFIG.API_URL}/cartoes/${id}/archive`, {
                method: 'POST',
            });

            Utils.showToast('success', 'Cartão arquivado com sucesso!');
            CartoesAPI.loadCartoes();

        } catch (error) {
            logClientError('[Cartoes] Erro ao arquivar cartão', error, 'Erro ao arquivar cartão');
            Utils.showToast('error', getErrorMessage(error, 'Erro ao arquivar cartao'));
        }
    },

    /**
     * Deletar cartão (método antigo - mantido por compatibilidade)
     * @deprecated Use arquivarCartao() em vez disso
     */
    async deleteCartao(id) {
        // Redireciona para arquivar
        return CartoesAPI.arquivarCartao(id);
    },

    /**
     * Carregar dados da fatura
     */
    async carregarFatura(cartaoId, mes, ano) {
        try {
            const json = await requestJson(`${CONFIG.API_URL}/cartoes/${cartaoId}/fatura?mes=${mes}&ano=${ano}`);
            return getApiPayload(json, { itens: [], total: 0, pago: 0, pendente: 0 });
        } catch (error) {
            if (error?.status === 404) {
                return { itens: [], total: 0, pago: 0, pendente: 0 };
            }
            throw new Error(getErrorMessage(error, 'Erro ao carregar fatura'));
        }
    },

    /**
     * Carregar resumo de parcelamentos
     */
    async carregarParcelamentosResumo(cartaoId, mes, ano) {
        const json = await requestJson(`${CONFIG.API_URL}/cartoes/${cartaoId}/parcelamentos-resumo?mes=${mes}&ano=${ano}`);
        return getApiPayload(json, null);
    },

    /**
     * Carregar histórico de faturas pagas
     */
    async carregarHistoricoFaturas(cartaoId, limite = 12) {
        const json = await requestJson(`${CONFIG.API_URL}/cartoes/${cartaoId}/faturas-historico?limite=${limite}`);
        return getApiPayload(json, null);
    },

    /**
     * Pagar parcelas individuais
     */
    async pagarParcelasIndividuais(checkboxes, fatura) {
        try {
            const parcelaIds = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));

            // Obter cartao_id correto (pode estar em fatura.cartao_id ou fatura.cartao.id)
            const cartaoId = fatura.cartao_id || fatura.cartao?.id;

            if (!cartaoId) {
                throw new Error('ID do cartão não encontrado na fatura');
            }

            const data = await requestJson(`${CONFIG.API_URL}/cartoes/${cartaoId}/parcelas/pagar`, {
                method: 'POST',
                data: {
                    parcela_ids: parcelaIds,
                    mes: fatura.mes,
                    ano: fatura.ano
                }
            });

            if (data?.success !== false) {
                Utils.showToast('success', data.message || 'Parcelas pagas com sucesso!');

                // Fechar modal e recarregar
                const modal = document.querySelector('.modal-fatura-overlay');
                if (modal) {
                    Modules.Fatura.fecharModalFatura(modal);
                }

                await CartoesAPI.loadCartoes();
            } else {
                throw new Error(data.message || 'Erro ao pagar parcelas');
            }
        } catch (error) {
            Utils.showToast('error', getErrorMessage(error, 'Erro ao processar a operacao do cartao'));
        }
    },

    /**
     * Desfazer pagamento de fatura
     */
    async desfazerPagamento(cartaoId, mes, ano) {
        const confirmado = await Swal.fire({
            title: 'Desfazer pagamento?',
            html: `
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 300px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar as parcelas como não pagas</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Tem certeza?</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, desfazer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            reverseButtons: true
        });

        if (!confirmado.isConfirmed) return;

        try {
            const data = await requestJson(`${CONFIG.API_URL}/cartoes/${cartaoId}/fatura/desfazer-pagamento`, {
                method: 'POST',
                data: { mes, ano }
            });

            if (data.success) {
                Utils.showToast('success', data.message);

                // Fechar modal e recarregar
                const modal = document.querySelector('.modal-fatura-overlay');
                if (modal) {
                    Modules.Fatura.fecharModalFatura(modal);
                }

                await CartoesAPI.loadCartoes();
            } else {
                throw new Error(data.message || 'Erro ao desfazer pagamento');
            }
        } catch (error) {
            Utils.showToast('error', getErrorMessage(error, 'Erro ao processar a operacao do cartao'));
        }
    },

    /**
     * Desfazer pagamento de uma parcela individual
     */
    async desfazerPagamentoParcela(parcelaId) {
        const confirmado = await Swal.fire({
            title: 'Desfazer pagamento desta parcela?',
            html: `
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 320px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar esta parcela como não paga</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Deseja continuar?</strong></p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, desfazer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            reverseButtons: true
        });

        if (!confirmado.isConfirmed) return;

        try {
            const data = await requestJson(`${CONFIG.API_URL}/cartoes/parcelas/${parcelaId}/desfazer-pagamento`, {
                method: 'POST',
            });

            if (data.success) {
                Utils.showToast('success', data.message);

                // Fechar modal e recarregar
                const modal = document.querySelector('.modal-fatura-overlay');
                if (modal) {
                    Modules.Fatura.fecharModalFatura(modal);
                }

                await CartoesAPI.loadCartoes();
            } else {
                throw new Error(data.message || 'Erro ao desfazer pagamento');
            }
        } catch (error) {
            Utils.showToast('error', getErrorMessage(error, 'Erro ao processar a operacao do cartao'));
        }
    },
};

// Register in module system
Modules.API = CartoesAPI;




