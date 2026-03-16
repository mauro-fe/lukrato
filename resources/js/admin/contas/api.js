/**
 * ============================================================================
 * LUKRATO - Contas / API
 * ============================================================================
 * All API calls: CRUD for contas and instituicoes used by the Contas screen.
 * Keep launch modal logic in the shared lancamento-global module.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { refreshIcons } from '../shared/ui.js';

export const ContasAPI = {
    setReloadState(isBusy) {
        const btnReload = document.getElementById('btnReload');
        if (!btnReload) return;

        btnReload.disabled = isBusy;
        btnReload.classList.toggle('is-busy', isBusy);
        btnReload.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    },

    async handlePlanLimitError(response, result) {
        if (response.status !== 403 || !result?.errors?.limit_reached || typeof Swal === 'undefined') {
            return false;
        }

        const decision = await Swal.fire({
            icon: 'info',
            title: 'Limite do plano atingido',
            text: result.message || 'Voc\u00ea atingiu o limite de contas do seu plano.',
            showCancelButton: true,
            confirmButtonText: 'Ver planos',
            cancelButtonText: 'Fechar',
        });

        if (decision.isConfirmed && result.errors?.upgrade_url) {
            window.location.href = result.errors.upgrade_url;
        }

        return true;
    },

    async loadInstituicoes() {
        try {
            let data;

            if (window.lkFetch) {
                const result = await window.lkFetch.get(`${CONFIG.API_URL}/instituicoes`, {
                    timeout: 15000,
                    maxRetries: 2,
                    showLoading: false
                });
                data = result.data;
            } else {
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

                if (!response.ok) {
                    throw new Error('Erro ao carregar instituicoes');
                }

                data = await response.json();
            }

            STATE.instituicoes = Array.isArray(data) ? data : (data.data || []);
            Modules.Render.renderInstituicoesSelect();
        } catch (error) {
            console.error('Erro ao carregar instituicoes:', error);

            let message = 'Erro ao carregar institui\u00e7\u00f5es financeiras';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conex\u00e3o est\u00e1 lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conex\u00e3o com a internet';
            }

            Utils.showToast(message, 'error');
        }
    },

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

            if (!response.ok || !result.success) {
                const handledPlanLimit = await ContasAPI.handlePlanLimitError(response, result);
                if (handledPlanLimit) {
                    return null;
                }

                throw new Error(result.message || 'Erro ao criar instituicao');
            }

            return result;
        } catch (error) {
            console.error('Erro ao criar instituicao:', error);
            throw error;
        }
    },

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
            if (!result?.data) {
                return;
            }

            STATE.instituicoes.push(result.data);
            Modules.Render.renderInstituicoesSelect();

            const select = document.getElementById('instituicaoFinanceiraSelect');
            if (select) {
                select.value = result.data.id;
            }

            Modules.Modal.closeNovaInstituicaoModal();
            Utils.showToast('Institui\u00e7\u00e3o criada com sucesso!', 'success');
        } catch (error) {
            Utils.showToast(error.message, 'error');
        }
    },

    async loadContas(options = {}) {
        const { silent = false } = options;
        const grid = document.getElementById('accountsGrid');

        try {
            STATE.isLoadingContas = true;
            STATE.lastLoadError = null;
            ContasAPI.setReloadState(true);

            if (!silent && (!STATE.contas || STATE.contas.length === 0)) {
                Modules.Render.showLoading(true);
            } else if (grid) {
                grid.setAttribute('aria-busy', 'true');
            }

            const params = new URLSearchParams({
                with_balances: '1',
                only_active: '1'
            });

            const url = `${CONFIG.API_URL}/contas?${params}`;
            let data;

            if (window.lkFetch) {
                const result = await window.lkFetch.get(url, {
                    timeout: 20000,
                    maxRetries: 2,
                    showLoading: !silent,
                    loadingTarget: '#accountsGrid'
                });
                data = result.data;
            } else {
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
                    console.error('Erro na resposta ao carregar contas:', errorText);
                    throw new Error(`Erro ao carregar contas: ${response.status}`);
                }

                data = await response.json();
            }

            STATE.contas = Array.isArray(data) ? data : (data.data || data.contas || []);
            STATE.lastLoadedAt = new Date();

            Modules.Render.updateStats();
            Modules.Render.renderContas();
        } catch (error) {
            console.error('Erro ao carregar contas:', error);

            let message = 'Erro ao carregar contas';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conex\u00e3o est\u00e1 lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conex\u00e3o com a internet';
            }

            STATE.lastLoadError = message;

            if (STATE.contas.length === 0) {
                Modules.Render.renderContas();
            } else {
                Utils.showToast(message, 'error');
                Modules.Render.updatePageContext(Modules.Render.getFilteredContas());
                Modules.Render.updateFilterSummary(Modules.Render.getFilteredContas());
                refreshIcons();
            }
        } finally {
            STATE.isLoadingContas = false;
            ContasAPI.setReloadState(false);
            if (grid) {
                grid.setAttribute('aria-busy', 'false');
            }
        }
    },

    async createConta(data) {
        const requestId = `req_${Date.now()}_${Math.random().toString(36).slice(2, 11)}`;

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

            if (!response.ok || !result.success) {
                const handledPlanLimit = await ContasAPI.handlePlanLimitError(response, result);
                if (handledPlanLimit) {
                    return;
                }

                console.error('Erro ao criar conta:', {
                    requestId,
                    status: response.status,
                    result
                });
                throw new Error(result.message || 'Erro ao criar conta');
            }

            if (result.csrf_token) {
                Utils.updateCSRFToken(result.csrf_token);
            }

            Utils.showToast('Conta criada com sucesso!', 'success');
            Modules.Modal.closeModal();
            await ContasAPI.loadContas({ silent: true });

            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 400);
        } catch (error) {
            console.error('Falha ao criar conta:', { requestId, error });
            Utils.showToast(error.message, 'error');
        } finally {
            STATE.isSubmitting = false;
        }
    },

    async editConta(contaId) {
        const conta = STATE.contas.find((item) => item.id === contaId);
        if (!conta) {
            console.error('Conta nao encontrada:', contaId);
            return;
        }

        Modules.Modal.openModal('edit', conta);
    },

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

            const responseText = await response.text();

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Erro ao fazer parse do JSON:', parseError);
                console.error('Resposta recebida:', responseText);
                throw new Error('Resposta inv\u00e1lida do servidor. Verifique o console.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erro ao atualizar conta');
            }

            if (result.csrf_token) {
                Utils.updateCSRFToken(result.csrf_token);
            }

            Utils.showToast('Conta atualizada com sucesso!', 'success');
            await ContasAPI.loadContas({ silent: true });
            Modules.Modal.closeModal();
        } catch (error) {
            console.error('Erro ao atualizar conta:', error);
            Utils.showToast(error.message, 'error');
        } finally {
            STATE.isSubmitting = false;
        }
    },

    async archiveConta(contaId) {
        const conta = STATE.contas.find((item) => item.id === contaId);
        const nomeConta = conta ? conta.nome : 'esta conta';

        const result = await Swal.fire({
            title: 'Arquivar conta?',
            html: `Deseja realmente arquivar <strong>${nomeConta}</strong>?<br><small class="text-muted">A conta ficar\u00e1 oculta, mas pode ser restaurada depois.</small>`,
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
            didOpen: () => {
                refreshIcons();
            }
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

            await ContasAPI.loadContas({ silent: true });
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

    async deleteConta(contaId) {
        const conta = STATE.contas.find((item) => item.id === contaId);
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

                if (!result.success && result.errors?.requires_confirmation) {
                    ContasAPI.showDeleteConfirmation(
                        `${nomeConta} (tem lancamentos vinculados)`,
                        async () => {
                            await ContasAPI.forceDeleteConta(contaId);
                        },
                        'Esta conta possui lancamentos vinculados. Ao exclui-la, todos os lancamentos tambem serao removidos. Deseja continuar?'
                    );
                    return;
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Erro ao excluir conta');
                }

                Utils.showToast('Conta excluida com sucesso!', 'success');
                await ContasAPI.loadContas({ silent: true });
            } catch (error) {
                console.error('Erro ao excluir conta:', error);
                Utils.showToast(error.message, 'error');
            }
        });
    },

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

            Utils.showToast('Conta e lancamentos excluidos com sucesso!', 'success');
            await ContasAPI.loadContas({ silent: true });
        } catch (error) {
            console.error('Erro ao excluir conta:', error);
            Utils.showToast(error.message, 'error');
        }
    },

    showDeleteConfirmation(nomeConta, onConfirm, customMessage = null) {
        const overlay = document.getElementById('confirmDeleteOverlay');
        const messageEl = document.getElementById('confirmDeleteMessage');
        const btnConfirm = document.getElementById('btnConfirmDelete');
        const btnCancel = document.getElementById('btnCancelDelete');

        if (!overlay || !messageEl || !btnConfirm || !btnCancel) {
            return;
        }

        if (customMessage) {
            messageEl.textContent = customMessage;
        } else {
            messageEl.innerHTML = `Tem certeza que deseja excluir <strong>${nomeConta}</strong>?<br>Esta acao nao pode ser desfeita.`;
        }

        overlay.style.display = 'flex';

        const closeModal = () => {
            overlay.style.display = 'none';
            btnConfirm.onclick = null;
            btnCancel.onclick = null;
            overlay.onclick = null;
        };

        btnCancel.onclick = closeModal;
        overlay.onclick = (event) => {
            if (event.target === overlay) {
                closeModal();
            }
        };

        btnConfirm.onclick = async () => {
            closeModal();
            await onConfirm();
        };

        const escHandler = (event) => {
            if (event.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        };

        document.addEventListener('keydown', escHandler);
    }
};

Modules.API = ContasAPI;
