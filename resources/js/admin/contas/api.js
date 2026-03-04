/**
 * ============================================================================
 * LUKRATO — Contas / API
 * ============================================================================
 * All API calls: CRUD for contas, instituições, categorias, cartões, faturas,
 * and related helper methods (select population, recent history, etc.).
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules, showLoading, hideLoading } from './state.js';
import { refreshIcons } from '../shared/ui.js';

// ─── ContasAPI ───────────────────────────────────────────────────────────────

export const ContasAPI = {

    // =====================================================================
    //  Instituições
    // =====================================================================

    /**
     * Carregar instituições financeiras
     */
    async loadInstituicoes() {
        try {
            let data;
            const startTime = performance.now();

            // Usar lkFetch se disponível (com timeout e retry)
            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${CONFIG.API_URL}/instituicoes`, {
                    timeout: 15000,
                    maxRetries: 2,
                    showLoading: false
                });
                data = result.data;
            } else {
                // Fallback com timeout manual
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000);

                const response = await fetch(`${CONFIG.API_URL}/instituicoes`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) throw new Error('Erro ao carregar instituições');
                data = await response.json();
            }

            const elapsed = performance.now() - startTime;

            STATE.instituicoes = Array.isArray(data) ? data : (data.data || []);

            if (STATE.instituicoes.length > 0) {
                const nubank = STATE.instituicoes.find(i => i.codigo === 'nubank');
                if (nubank) {
                    // Nubank encontrado
                }
            }

            Modules.Render.renderInstituicoesSelect();
        } catch (error) {
            console.error('❌ [DEBUG] Erro ao carregar instituições:', error);
            console.error('❌ [DEBUG] Error name:', error.name);
            console.error('❌ [DEBUG] Error message:', error.message);
            console.error('❌ [DEBUG] Error stack:', error.stack);

            // Mensagem mais amigável para timeout
            let message = 'Erro ao carregar instituições financeiras';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conexão está lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conexão com a internet';
            }

            Utils.showToast(message, 'error');
        }
    },

    /**
     * Criar nova instituição
     */
    async createInstituicao(data) {
        try {
            const csrfToken = await Utils.getCSRFToken();
            const response = await fetch(`${CONFIG.API_URL}/instituicoes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Erro ao criar instituição');
            }

            return result;
        } catch (error) {
            console.error('Erro ao criar instituição:', error);
            throw error;
        }
    },

    /**
     * Handler do formulário de nova instituição
     */
    async handleNovaInstituicaoSubmit(form) {
        const formData = new FormData(form);
        const data = {
            nome: formData.get('nome'),
            tipo: formData.get('tipo'),
            cor_primaria: formData.get('cor_primaria'),
            cor_secundaria: '#FFFFFF'
        };

        try {
            const result = await ContasAPI.createInstituicao(data);

            // Adicionar a nova instituição à lista
            if (result.data) {
                STATE.instituicoes.push(result.data);
                Modules.Render.renderInstituicoesSelect();

                // Selecionar a nova instituição no select
                const select = document.getElementById('instituicaoFinanceiraSelect');
                if (select) {
                    select.value = result.data.id;
                }
            }

            Modules.Modal.closeNovaInstituicaoModal();
            Utils.showToast('Instituição criada com sucesso!', 'success');

        } catch (error) {
            Utils.showToast(error.message, 'error');
        }
    },

    // =====================================================================
    //  Contas — CRUD
    // =====================================================================

    /**
     * Carregar contas do usuário
     */
    async loadContas() {
        const grid = document.getElementById('accountsGrid');

        try {
            showLoading('Carregando contas...');

            const params = new URLSearchParams({
                with_balances: '1',
                only_active: '1'
            });

            const url = `${CONFIG.API_URL}/contas?${params}`;

            let data;
            const startTime = performance.now();

            // Usar lkFetch se disponível (com timeout, retry e indicadores)
            if (window.lkFetch) {
                const result = await window.lkFetch.get(url, {
                    timeout: 20000,
                    maxRetries: 2,
                    showLoading: true,
                    loadingTarget: '#accountsGrid'
                });
                data = result.data;
            } else {
                // Fallback com timeout manual
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 20000);

                const response = await fetch(url, {
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
                    const errorText = await response.text();
                    console.error('❌ [DEBUG] Erro na resposta:', errorText);
                    throw new Error(`Erro ao carregar contas: ${response.status}`);
                }

                data = await response.json();
            }

            const elapsed = performance.now() - startTime;

            // A resposta pode ser um array direto ou um objeto com data
            STATE.contas = Array.isArray(data) ? data : (data.data || data.contas || []);

            if (STATE.contas.length > 0) {
                // Contas carregadas
            }

            Modules.Render.renderContas();
            Modules.Render.updateStats();
        } catch (error) {
            console.error('❌ [DEBUG] Erro ao carregar contas:', error);
            console.error('❌ [DEBUG] Error name:', error.name);
            console.error('❌ [DEBUG] Error message:', error.message);
            console.error('❌ [DEBUG] Error stack:', error.stack);

            // Mensagem mais amigável para timeout
            let message = 'Erro ao carregar contas';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conexão está lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conexão com a internet';
            }

            Utils.showToast(message, 'error');

            // Mostrar estado de erro com botão de retry
            if (grid) {
                grid.innerHTML = `
                    <div class="error-state">
                        <i data-lucide="triangle-alert"></i>
                        <p class="error-message">${message}</p>
                        <button class="btn btn-primary btn-retry" onclick="window.ContasAPI?.loadContas?.()">
                            <i data-lucide="refresh-cw"></i> Tentar novamente
                        </button>
                    </div>
                `;
                refreshIcons();
            }
        } finally {
            hideLoading();
        }
    },

    /**
     * Criar nova conta
     */
    async createConta(data) {
        const requestId = 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        try {

            const csrfToken = await Utils.getCSRFToken();
            const response = await fetch(`${CONFIG.API_URL}/contas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok || (!result.ok && !result.success)) {
                console.error('❌ [' + requestId + '] Erro na resposta - Condição falhou');
                console.error('❌ [' + requestId + '] !response.ok:', !response.ok);
                console.error('❌ [' + requestId + '] !result.ok:', !result.ok);
                console.error('❌ [' + requestId + '] !result.success:', !result.success);
                throw new Error(result.message || 'Erro ao criar conta');
            }

            // Atualizar token CSRF para próxima requisição
            if (result.csrf_token) {
                Utils.updateCSRFToken(result.csrf_token);
            }

            Utils.showToast('Conta criada com sucesso!', 'success');
            Modules.Modal.closeModal();
            await ContasAPI.loadContas();

            // Scroll ao topo da página (modo seguro)
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        } catch (error) {
            console.error('💥 [' + requestId + '] EXCEPTION:', error);
            Utils.showToast(error.message, 'error');
        } finally {
            STATE.isSubmitting = false;
        }
    },

    /**
     * Editar conta (abre modal de edição)
     */
    async editConta(contaId) {
        const conta = STATE.contas.find(c => c.id === contaId);
        if (!conta) {
            console.error('Conta não encontrada:', contaId);
            return;
        }

        // Preencher formulário de edição
        Modules.Modal.openModal('edit', conta);
    },

    /**
     * Atualizar conta
     */
    async updateConta(contaId, data) {
        try {
            const csrfToken = await Utils.getCSRFToken();
            const response = await fetch(`${CONFIG.API_URL}/contas/${contaId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify(data)
            });

            // Capturar o texto da resposta primeiro
            const responseText = await response.text();

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Erro ao fazer parse do JSON:', parseError);
                console.error('📄 Resposta recebida:', responseText);
                throw new Error('Resposta inválida do servidor. Verifique o console.');
            }

            if (!response.ok || (!result.ok && !result.success)) {
                throw new Error(result.message || 'Erro ao atualizar conta');
            }

            // Atualizar token CSRF para próxima requisição
            if (result.csrf_token) {
                Utils.updateCSRFToken(result.csrf_token);
            }

            Utils.showToast('Conta atualizada com sucesso!', 'success');
            await ContasAPI.loadContas();
            Modules.Modal.closeModal();
        } catch (error) {
            console.error('Erro ao atualizar conta:', error);
            Utils.showToast(error.message, 'error');
        } finally {
            STATE.isSubmitting = false;
        }
    },

    /**
     * Arquivar conta
     */
    async archiveConta(contaId) {
        const conta = STATE.contas.find(c => c.id === contaId);
        const nomeConta = conta ? conta.nome : 'esta conta';

        const result = await Swal.fire({
            title: 'Arquivar conta?',
            html: `Deseja realmente arquivar <strong>${nomeConta}</strong>?<br><small class="text-muted">A conta ficará oculta mas pode ser restaurada depois.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e67e22',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i data-lucide="archive"></i> Sim, arquivar',
            cancelButtonText: '<i data-lucide="x"></i> Cancelar',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: true,
            customClass: {
                popup: 'swal-custom-popup',
                confirmButton: 'swal-confirm-btn',
                cancelButton: 'swal-cancel-btn'
            },
            didOpen: () => { refreshIcons(); }
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(`${CONFIG.API_URL}/contas/${contaId}/archive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const archiveResult = await response.json();

            if (!response.ok || !archiveResult.success) {
                throw new Error(archiveResult.message || 'Erro ao arquivar conta');
            }

            Swal.fire({
                title: 'Arquivada!',
                text: 'A conta foi arquivada com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            await ContasAPI.loadContas();
        } catch (error) {
            console.error('Erro ao arquivar conta:', error);
            Swal.fire({
                title: 'Erro!',
                text: error.message,
                icon: 'error',
                confirmButtonColor: '#e67e22'
            });
        }
    },

    /**
     * Excluir conta com modal moderno
     */
    async deleteConta(contaId) {
        const conta = STATE.contas.find(c => c.id === contaId);
        const nomeConta = conta ? conta.nome : 'esta conta';

        ContasAPI.showDeleteConfirmation(nomeConta, async () => {
            try {
                const csrfToken = await Utils.getCSRFToken();

                const response = await fetch(`${CONFIG.API_URL}/contas/${contaId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-HTTP-Method-Override': 'DELETE'
                    }
                });

                const result = await response.json();

                // Se precisa de confirmação de força (tem lançamentos)
                if (result.status === 'confirm_delete') {
                    ContasAPI.showDeleteConfirmation(
                        nomeConta + ' (tem lançamentos vinculados)',
                        async () => {
                            await ContasAPI.forceDeleteConta(contaId);
                        },
                        'Esta conta possui lançamentos vinculados. Ao excluí-la, todos os lançamentos também serão removidos. Deseja continuar?'
                    );
                    return;
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Erro ao excluir conta');
                }

                Utils.showToast('Conta excluída com sucesso!', 'success');
                await ContasAPI.loadContas();
            } catch (error) {
                console.error('Erro ao excluir conta:', error);
                Utils.showToast(error.message, 'error');
            }
        });
    },

    /**
     * Forçar exclusão de conta (com lançamentos)
     */
    async forceDeleteConta(contaId) {
        try {
            const csrfToken = await Utils.getCSRFToken();

            const response = await fetch(`${CONFIG.API_URL}/contas/${contaId}?force=1`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'DELETE'
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erro ao excluir conta');
            }

            Utils.showToast('Conta e lançamentos excluídos com sucesso!', 'success');
            await ContasAPI.loadContas();
        } catch (error) {
            console.error('Erro ao excluir conta:', error);
            Utils.showToast(error.message, 'error');
        }
    },

    /**
     * Mostrar modal de confirmação de exclusão
     */
    showDeleteConfirmation(nomeConta, onConfirm, customMessage = null) {
        const overlay = document.getElementById('confirmDeleteOverlay');
        const messageEl = document.getElementById('confirmDeleteMessage');
        const btnConfirm = document.getElementById('btnConfirmDelete');
        const btnCancel = document.getElementById('btnCancelDelete');

        // Atualizar mensagem
        if (customMessage) {
            messageEl.textContent = customMessage;
        } else {
            messageEl.innerHTML = `Tem certeza que deseja excluir <strong>${nomeConta}</strong>?<br>Esta ação não pode ser desfeita.`;
        }

        // Mostrar modal
        overlay.style.display = 'flex';

        // Handlers
        const closeModal = () => {
            overlay.style.display = 'none';
            btnConfirm.onclick = null;
            btnCancel.onclick = null;
            overlay.onclick = null;
        };

        btnCancel.onclick = closeModal;
        overlay.onclick = (e) => {
            if (e.target === overlay) closeModal();
        };

        btnConfirm.onclick = async () => {
            closeModal();
            await onConfirm();
        };

        // ESC para fechar
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    },

    // =====================================================================
    //  Categorias / Selects auxiliares
    // =====================================================================

    /**
     * Preencher categorias no formulário de lançamento
     */
    async preencherCategorias(tipo, selectEl, selectedId) {
        const select = selectEl || document.getElementById('lancamentoCategoria');
        if (!select) {
            console.error('❌ Select de categoria não encontrado');
            return;
        }


        try {
            // Se as categorias já foram carregadas, usar cache
            if (!STATE.categorias) {
                const url = `${CONFIG.BASE_URL}api/categorias`;

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();


                // A resposta vem como { status: 'success', data: [...] }
                if (result.status === 'success' && result.data) {
                    STATE.categorias = result.data;
                } else if (result.success && result.data) {
                    STATE.categorias = result.data;
                } else if (Array.isArray(result)) {
                    STATE.categorias = result;
                } else if (result.categorias) {
                    STATE.categorias = result.categorias;
                } else {
                    console.warn('⚠️ Formato de resposta inesperado:', result);
                    STATE.categorias = [];
                }

            }


            if (!STATE.categorias || STATE.categorias.length === 0) {
                console.warn('⚠️ Nenhuma categoria disponível');
                select.innerHTML = '<option value="">Nenhuma categoria cadastrada</option>';
                return;
            }

            // Filtrar categorias por tipo
            const categoriasFiltradas = STATE.categorias.filter(cat => {
                if (tipo === 'receita') return cat.tipo === 'receita';
                if (tipo === 'despesa') return cat.tipo === 'despesa';
                return true; // transferência pode usar qualquer
            });

            // Preencher select
            select.innerHTML = '<option value="">Selecione a categoria (opcional)</option>';

            categoriasFiltradas.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.nome;
                select.appendChild(option);
            });

            // Se um selectedId foi fornecido, marcar como selecionado
            if (selectedId) {
                select.value = selectedId;
            }

            // Listener cascata: ao trocar categoria → preencher subcategorias
            if (!select.dataset.subcatListenerAttached) {
                select.dataset.subcatListenerAttached = '1';
                select.addEventListener('change', () => {
                    Modules.API.preencherSubcategorias(select.value);
                });
            }

            // Se já tem categoria selecionada, carregar subcategorias
            if (selectedId) {
                Modules.API.preencherSubcategorias(selectedId);
            }

        } catch (error) {
            console.error('❌ Erro ao carregar categorias:', error);
            console.error('Stack:', error.stack);
            select.innerHTML = '<option value="">Erro ao carregar categorias</option>';

            // Mostrar erro visual para o usuário
            Swal.fire({
                icon: 'error',
                title: 'Erro ao carregar categorias',
                text: error.message || 'Não foi possível carregar as categorias. Tente novamente.',
                confirmButtonColor: '#3085d6'
            });
        }
    },

    /**
     * Preencher subcategorias com base na categoria selecionada
     */
    async preencherSubcategorias(categoriaId, selectedSubcatId) {
        const select = document.getElementById('lancamentoSubcategoria');
        const group = document.getElementById('subcategoriaGroup');
        if (!select) return;

        if (!categoriaId) {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.style.display = 'none';
            return;
        }

        try {
            const url = `${CONFIG.BASE_URL}api/categorias/${categoriaId}/subcategorias`;
            const response = await fetch(url);
            if (!response.ok) throw new Error();
            const result = await response.json();
            const subs = result?.data?.subcategorias ?? (Array.isArray(result?.data) ? result.data : []);

            select.innerHTML = '<option value="">Sem subcategoria</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.nome;
                if (selectedSubcatId && String(sub.id) === String(selectedSubcatId)) opt.selected = true;
                select.appendChild(opt);
            });

            if (group) group.style.display = subs.length > 0 ? 'block' : 'none';
        } catch {
            select.innerHTML = '<option value="">Sem subcategoria</option>';
            if (group) group.style.display = 'none';
        }
    },

    /**
     * Resetar select de subcategoria
     */
    resetSubcategoriaSelect() {
        const select = document.getElementById('lancamentoSubcategoria');
        if (select) select.innerHTML = '<option value="">Sem subcategoria</option>';
    },

    /**
     * Preencher select de contas destino (exceto a origem)
     */
    preencherContasDestino(selectEl, excludeId) {
        const select = selectEl || document.getElementById('lancamentoContaDestino');
        const contaOrigemId = excludeId || STATE.contaSelecionadaLancamento?.id;


        select.innerHTML = '<option value="">Selecione a conta de destino</option>';

        let contasAdicionadas = 0;
        STATE.contas.forEach(conta => {
            if (conta.id != contaOrigemId) {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = conta.nome;
                select.appendChild(option);
                contasAdicionadas++;
            }
        });

    },

    /**
     * Carregar cartões de crédito no select
     */
    async carregarCartoesCredito(selectEl) {
        const select = selectEl || document.getElementById('lancamentoCartaoCredito');
        if (!select) return;

        try {
            const url = `${CONFIG.BASE_URL}api/cartoes`;

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erro ao carregar cartões');

            const cartoes = await response.json();

            select.innerHTML = '<option value="">Não usar cartão (débito na conta)</option>';

            cartoes.forEach(cartao => {
                const option = document.createElement('option');
                option.value = cartao.id;
                option.textContent = `${cartao.nome_cartao} •••• ${cartao.ultimos_digitos}`;
                option.dataset.diaVencimento = cartao.dia_vencimento;
                select.appendChild(option);
            });

            // Adicionar listener para mudança
            select.addEventListener('change', () => Modules.Lancamento?.aoSelecionarCartao?.());

        } catch (error) {
            console.error('Erro ao carregar cartões:', error);
        }
    },

    /**
     * Carregar faturas disponíveis de um cartão
     */
    async carregarFaturasCartao(cartaoId, selectEl) {
        const select = selectEl || document.getElementById('lancamentoFaturaEstorno');
        if (!select) {
            console.error('[ESTORNO] Select lancamentoFaturaEstorno não encontrado');
            return;
        }

        // Gerar lista de meses diretamente (sem depender da API)
        const hoje = new Date();
        const mesAtual = hoje.getMonth() + 1;
        const anoAtual = hoje.getFullYear();

        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        select.innerHTML = '';

        // Gerar opções: mês atual e próximos 5 meses
        for (let i = 0; i < 6; i++) {
            let mes = mesAtual + i;
            let ano = anoAtual;
            if (mes > 12) {
                mes -= 12;
                ano++;
            }

            const option = document.createElement('option');
            option.value = `${ano}-${String(mes).padStart(2, '0')}`;
            option.textContent = i === 0
                ? `${meses[mes - 1]} / ${ano} (atual)`
                : `${meses[mes - 1]} / ${ano}`;

            // Marcar fatura atual como selecionada
            if (i === 0) {
                option.selected = true;
            }

            select.appendChild(option);
        }

        // Adicionar meses anteriores (últimos 3)
        for (let i = 1; i <= 3; i++) {
            let mes = mesAtual - i;
            let ano = anoAtual;
            if (mes < 1) {
                mes += 12;
                ano--;
            }

            const option = document.createElement('option');
            option.value = `${ano}-${String(mes).padStart(2, '0')}`;
            option.textContent = `${meses[mes - 1]} / ${ano} (anterior)`;
            select.appendChild(option);
        }
    },

    // =====================================================================
    //  Histórico recente
    // =====================================================================

    /**
     * Carregar histórico recente de movimentações
     */
    async carregarHistoricoRecente(contaId, containerEl) {
        const historicoContainer = containerEl || document.getElementById('lancamentoHistorico');

        try {
            // Buscar últimas 5 movimentações da conta
            const params = new URLSearchParams({
                account_id: contaId,
                limit: '5',
                month: new Date().toISOString().slice(0, 7) // Mês atual YYYY-MM
            });

            const response = await fetch(`${CONFIG.API_URL}/lancamentos?${params}`);
            if (!response.ok) {
                throw new Error('Erro ao carregar histórico');
            }

            const result = await response.json();

            // A resposta pode vir como array direto ou dentro de result.data
            const lancamentos = Array.isArray(result) ? result : (result.data || result.lancamentos || []);

            if (!lancamentos || lancamentos.length === 0) {
                historicoContainer.innerHTML = `
                    <div class="lk-historico-empty">
                        <i data-lucide="inbox"></i>
                        <p>Nenhuma movimentação recente</p>
                    </div>
                `;
                refreshIcons();
                return;
            }

            // Renderizar histórico
            historicoContainer.innerHTML = lancamentos.map(l => {
                const tipoClass = l.tipo === 'receita' ? 'receita' : l.tipo === 'despesa' ? 'despesa' : 'transferencia';
                const tipoIcon = l.tipo === 'receita' ? 'arrow-down' : l.tipo === 'despesa' ? 'arrow-up' : 'arrow-left-right';
                const sinal = l.tipo === 'receita' ? '+' : '-';
                const valorFormatado = Utils.formatCurrency(Math.abs(l.valor));
                const dataFormatada = new Date(l.data + 'T00:00:00').toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: 'short'
                });

                return `
                    <div class="lk-historico-item lk-historico-${tipoClass}">
                        <div class="lk-historico-icon">
                            <i data-lucide="${tipoIcon}"></i>
                        </div>
                        <div class="lk-historico-info">
                            <div class="lk-historico-desc">${l.descricao || 'Sem descrição'}</div>
                            <div class="lk-historico-cat">${l.categoria || 'Sem categoria'}</div>
                        </div>
                        <div class="lk-historico-right">
                            <div class="lk-historico-valor">${sinal} ${valorFormatado}</div>
                            <div class="lk-historico-data">${dataFormatada}</div>
                        </div>
                    </div>
                `;
            }).join('');
            refreshIcons();

        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
            historicoContainer.innerHTML = `
                <div class="lk-historico-empty">
                    <i data-lucide="circle-alert"></i>
                    <p>Erro ao carregar histórico</p>
                </div>
            `;
            refreshIcons();
        }
    }
};

// ─── Register in Modules registry ────────────────────────────────────────────
Modules.API = ContasAPI;
