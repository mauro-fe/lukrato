/**
 * Cartões Manager – API / Data layer
 * Extracted from cartoes-manager.js (monolith → modules)
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';

export const CartoesAPI = {
    /**
     * Carregar cartões do servidor
     */
    async loadCartoes() {
        const grid = document.getElementById('cartoesGrid');
        const emptyState = document.getElementById('emptyState');

        try {
            // Mostrar skeleton
            grid.innerHTML = `
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
            `;
            emptyState.style.display = 'none';

            const startTime = performance.now();

            // Usar lkFetch se disponível (com timeout, retry e indicadores)
            let data;
            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${CONFIG.API_URL}/cartoes`, {
                    timeout: 20000,      // 20 segundos
                    maxRetries: 2,       // 2 tentativas extras
                    showLoading: true,
                    loadingTarget: '#cartoesContainer'
                });
                data = result.data;
            } else {
                // Fallback para fetch simples com timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000);

                const response = await fetch(`${CONFIG.API_URL}/cartoes`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error('Erro ao carregar cartões');
                }

                data = await response.json();
            }

            const elapsed = performance.now() - startTime;
            STATE.cartoes = Array.isArray(data) ? data : (data.data || []);
            // Verificar faturas pendentes
            await CartoesAPI.verificarFaturasPendentes();

            STATE.filteredCartoes = [...STATE.cartoes];

            if (STATE.cartoes.length === 0) {
                grid.innerHTML = '';
                emptyState.style.display = 'block';
            } else {
                Modules.UI.renderCartoes();
                Modules.UI.updateStats();
            }

        } catch (error) {
            console.error('❌ [DEBUG] Erro ao carregar cartões:', error);
            console.error('❌ [DEBUG] Error name:', error.name);
            console.error('❌ [DEBUG] Error message:', error.message);
            console.error('❌ [DEBUG] Error stack:', error.stack);

            // Mensagem mais amigável para timeout
            let message = 'Erro ao carregar cartões';
            if (error.name === 'AbortError' || error.message.includes('demorou')) {
                message = 'A conexão está lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conexão com a internet';
            }

            Utils.showToast('error', message);
            grid.innerHTML = `
                <div class="error-state">
                    <i data-lucide="triangle-alert"></i>
                    <p class="error-message">${message}</p>
                    <button class="btn btn-primary btn-retry" onclick="window.cartoesManager.loadCartoes()">
                        <i data-lucide="refresh-cw"></i> Tentar novamente
                    </button>
                </div>
            `;
            refreshIcons();
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
                const response = await fetch(`${CONFIG.API_URL}/cartoes/${cartao.id}/faturas-pendentes`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    const meses = data.data || data || [];
                    cartao.temFaturaPendente = Array.isArray(meses) && meses.length > 0;
                } else {
                    cartao.temFaturaPendente = false;
                }
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
                data = result.data;
                STATE.alertas = data.alertas || [];
            } else {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);

                const response = await fetch(`${CONFIG.API_URL}/cartoes/alertas`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (response.ok) {
                    data = await response.json();
                    STATE.alertas = data.alertas || [];
                } else {
                    console.warn('Erro ao carregar alertas:', response.status);
                    STATE.alertas = [];
                }
            }

            CartoesAPI.renderAlertas();
        } catch (error) {
            console.warn('Erro ao carregar alertas:', error);
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

        let mensagem = '';
        if (alerta.tipo === 'vencimento_proximo') {
            mensagem = `Fatura de <strong>${alerta.nome_cartao}</strong> vence em <strong>${alerta.dias_faltando} dia(s)</strong> - ${Utils.formatMoney(alerta.valor_fatura)}`;
        } else if (alerta.tipo === 'limite_baixo') {
            mensagem = `Limite de <strong>${alerta.nome_cartao}</strong> em <strong>${alerta.percentual_disponivel.toFixed(1)}%</strong> - ${Utils.formatMoney(alerta.limite_disponivel)} disponível`;
        }

        return `
            <div class="alerta-item alerta-${alerta.gravidade}" data-tipo="${alerta.tipo}">
                <div class="alerta-icon" style="color: ${cores[alerta.gravidade]}">
                    <i data-lucide="${icones[alerta.tipo]}"></i>
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
        if (!select) {
            console.error('❌ Select contaVinculada não encontrado!');
            return;
        }

        try {
            const url = `${CONFIG.API_URL}/contas?only_active=0&with_balances=1`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Erro HTTP:', response.status, errorText);
                throw new Error('Erro ao carregar contas');
            }

            const data = await response.json();

            // Tentar diferentes estruturas de resposta
            let contas = [];
            if (Array.isArray(data)) {
                contas = data;
            } else if (data.data) {
                contas = Array.isArray(data.data) ? data.data : [];
            } else if (data.contas) {
                contas = Array.isArray(data.contas) ? data.contas : [];
            }

            if (contas.length === 0) {
                select.innerHTML = '<option value="">Nenhuma conta cadastrada</option>';
                console.warn('⚠️ Nenhuma conta encontrada');
                return;
            }

            const options = contas.map(conta => {
                // Pegar nome da instituição de diferentes estruturas possíveis
                const instituicao = conta.instituicao_financeira?.nome ||
                    conta.instituicao?.nome ||
                    conta.nome ||
                    'Sem instituição';
                const nome = Utils.escapeHtml(instituicao);
                // Tentar pegar o saldo de diferentes campos possíveis (saldoAtual é o campo retornado com with_balances=1)
                const saldoValue = parseFloat(conta.saldoAtual || conta.saldo_atual || conta.saldo || conta.saldo_inicial || 0);
                const saldo = Utils.formatMoney(saldoValue);
                return `<option value="${conta.id}">${nome} - ${saldo}</option>`;
            }).join('');

            select.innerHTML = '<option value="">Selecione a conta</option>' + options;
        } catch (error) {
            console.error('❌ Erro ao carregar contas:', error);
            console.error('Stack:', error.stack);
            select.innerHTML = '<option value="">Erro ao carregar contas</option>';
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

        const data = {
            nome_cartao: document.getElementById('nomeCartao').value,
            conta_id: document.getElementById('contaVinculada').value,
            bandeira: document.getElementById('bandeira').value,
            ultimos_digitos: document.getElementById('ultimosDigitos').value,
            limite_total: limiteParsed,
            dia_fechamento: document.getElementById('diaFechamento').value || null,
            dia_vencimento: document.getElementById('diaVencimento').value || null,
            lembrar_fatura_antes_segundos: lembreteAviso ? parseInt(lembreteAviso) : null,
            fatura_canal_inapp: lembreteAviso ? (document.getElementById('cartaoCanalInapp')?.checked ? 1 : 0) : 0,
            fatura_canal_email: lembreteAviso ? (document.getElementById('cartaoCanalEmail')?.checked ? 1 : 0) : 0,
            csrf_token: csrfToken
        };

        try {
            const url = isEdit
                ? `${CONFIG.API_URL}/cartoes/${cartaoId}`
                : `${CONFIG.API_URL}/cartoes`;

            const response = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Erro HTTP:', response.status);
                console.error('📄 Resposta completa:', errorText);

                let error;
                try {
                    error = JSON.parse(errorText);
                } catch (e) {
                    error = { message: errorText };
                }

                // Se for erro de CSRF, recarregar a página
                if (error.errors?.csrf_expired) {
                    Utils.showToast('error', 'Sessão expirada. Recarregando página...');
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                throw new Error(error.message || error.error || 'Erro ao salvar cartão');
            }

            const result = await response.json();

            // 🎮 GAMIFICAÇÃO: Exibir conquistas se houver
            if (result.gamification?.achievements && Array.isArray(result.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(result.gamification.achievements);
                } else {
                    console.error('❌ notifyMultipleAchievements não está disponível');
                }
            }

            Utils.showToast('success', isEdit ? 'Cartão atualizado com sucesso!' : 'Cartão criado com sucesso!');
            Modules.UI.closeModal();
            CartoesAPI.loadCartoes();
        } catch (error) {
            console.error('❌ Erro ao salvar cartão:', error);
            console.error('Stack:', error.stack);
            Utils.showToast('error', error.message || 'Erro ao salvar cartão');
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
            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(`${CONFIG.API_URL}/cartoes/${id}/archive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                throw new Error(result.message || 'Erro ao arquivar cartão');
            }

            Utils.showToast('success', 'Cartão arquivado com sucesso!');
            CartoesAPI.loadCartoes();

        } catch (error) {
            console.error('Erro ao arquivar:', error);
            Utils.showToast('error', error.message || 'Erro ao arquivar cartão');
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
        const response = await fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/fatura?mes=${mes}&ano=${ano}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            if (response.status === 404) {
                // Fatura não encontrada - retornar objeto vazio
                return { itens: [], total: 0, pago: 0, pendente: 0 };
            }
            throw new Error('Erro ao carregar fatura');
        }

        return await response.json();
    },

    /**
     * Carregar resumo de parcelamentos
     */
    async carregarParcelamentosResumo(cartaoId, mes, ano) {
        const response = await fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/parcelamentos-resumo?mes=${mes}&ano=${ano}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Erro ao carregar parcelamentos');
        }

        return await response.json();
    },

    /**
     * Carregar histórico de faturas pagas
     */
    async carregarHistoricoFaturas(cartaoId, limite = 12) {
        const csrfToken = await Utils.getCSRFToken();

        const response = await fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/faturas-historico?limite=${limite}`, {
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Erro ao carregar histórico');
        }

        return await response.json();
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

            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(`${CONFIG.API_URL}/cartoes/${cartaoId}/parcelas/pagar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    parcela_ids: parcelaIds,
                    mes: fatura.mes,
                    ano: fatura.ano
                })
            });

            const data = await response.json();

            if (response.ok) {
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
            Utils.showToast('error', error.message);
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
            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(
                `${CONFIG.API_URL}/cartoes/${cartaoId}/fatura/desfazer-pagamento`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mes, ano })
                }
            );

            const data = await response.json();

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
            Utils.showToast('error', error.message);
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
            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(
                `${CONFIG.API_URL}/cartoes/parcelas/${parcelaId}/desfazer-pagamento`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            const data = await response.json();

            if (response.ok && data.success) {
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
            Utils.showToast('error', error.message);
        }
    },
};

// Register in module system
Modules.API = CartoesAPI;
