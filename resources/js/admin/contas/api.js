/**
 * ============================================================================
 * LUKRATO - Contas / API
 * ============================================================================
 * All API calls: CRUD for contas and instituicoes used by the Contas screen.
 * Keep launch modal logic in the shared lancamento-global module.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { apiFetch, getApiPayload, getErrorMessage } from '../shared/api.js';
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

async function requestJson(url, { method = 'GET', data = null, headers = {}, timeout = 20000 } = {}) {
    return apiFetch(normalizeApiUrl(url), {
        method,
        headers,
        body: data,
    }, { timeout });
}

function applyPreviewMeta(meta) {
    STATE.previewMeta = meta?.is_demo ? meta : null;

    if (STATE.previewMeta) {
        window.LKDemoPreviewBanner?.show(STATE.previewMeta);
        return;
    }

    window.LKDemoPreviewBanner?.hide();
}

export const ContasAPI = {
    setReloadState(isBusy) {
        const btnReload = document.getElementById('btnReload');
        if (!btnReload) return;

        btnReload.disabled = isBusy;
        btnReload.classList.toggle('is-busy', isBusy);
        btnReload.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    },

    async handlePlanLimitError(status, result) {
        if (status !== 403 || !result?.errors?.limit_reached || typeof Swal === 'undefined') {
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
                data = getApiPayload(result, []);
            } else {
                data = await requestJson(`${CONFIG.API_URL}/instituicoes`, { timeout: 15000 });
            }

            STATE.instituicoes = Array.isArray(data) ? data : getApiPayload(data, []);
            Modules.Render.renderInstituicoesSelect();
        } catch (error) {
            console.error('Erro ao carregar instituicoes:', error);

            let message = 'Erro ao carregar institui\u00e7\u00f5es financeiras';
            if (error.name === 'AbortError' || error.message?.includes('demorou')) {
                message = 'A conex\u00e3o est\u00e1 lenta. Tente novamente.';
            } else if (!navigator.onLine) {
                message = 'Sem conex\u00e3o com a internet';
            }

            Utils.showToast(getErrorMessage(error, message), 'error');
        }
    },

    async createInstituicao(data) {
        try {
            return await requestJson(`${CONFIG.API_URL}/instituicoes`, {
                method: 'POST',
                data,
            });
        } catch (error) {
            const handledPlanLimit = await ContasAPI.handlePlanLimitError(error?.status ?? 0, error?.data);
            if (handledPlanLimit) {
                return null;
            }

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
            const instituicao = getApiPayload(result, null);
            if (!instituicao) {
                return;
            }

            STATE.instituicoes.push(instituicao);
            Modules.Render.renderInstituicoesSelect();

            const select = document.getElementById('instituicaoFinanceiraSelect');
            if (select) {
                select.value = instituicao.id;
            }

            Modules.Modal.closeNovaInstituicaoModal();
            Utils.showToast('Institui\u00e7\u00e3o criada com sucesso!', 'success');
        } catch (error) {
            Utils.showToast(getErrorMessage(error, 'Erro ao criar instituicao'), 'error');
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
                only_active: '1',
                preview: '1',
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
                data = getApiPayload(result, []);
            } else {
                data = await requestJson(url, { timeout: 20000 });
            }

            const payload = getApiPayload(data, {});
            applyPreviewMeta(payload?.meta);
            STATE.contas = Array.isArray(payload) ? payload : (payload?.contas || []);
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
                Utils.showToast(getErrorMessage(error, message), 'error');
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
            const result = await requestJson(`${CONFIG.API_URL}/contas`, {
                method: 'POST',
                data,
            });

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
            const handledPlanLimit = await ContasAPI.handlePlanLimitError(error?.status ?? 0, error?.data);
            if (handledPlanLimit) {
                return;
            }

            console.error('Falha ao criar conta:', { requestId, error });
            Utils.showToast(getErrorMessage(error, 'Erro ao criar conta'), 'error');
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
            const result = await requestJson(`${CONFIG.API_URL}/contas/${contaId}`, {
                method: 'POST',
                data,
                headers: {
                    'X-HTTP-Method-Override': 'PUT'
                }
            });

            if (result.csrf_token) {
                Utils.updateCSRFToken(result.csrf_token);
            }

            Utils.showToast('Conta atualizada com sucesso!', 'success');
            await ContasAPI.loadContas({ silent: true });
            Modules.Modal.closeModal();
        } catch (error) {
            console.error('Erro ao atualizar conta:', error);
            Utils.showToast(getErrorMessage(error, 'Erro ao atualizar conta'), 'error');
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
            await requestJson(`${CONFIG.API_URL}/contas/${contaId}/archive`, {
                method: 'POST'
            });

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
                text: getErrorMessage(error, 'Erro ao arquivar conta'),
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
                const result = await requestJson(`${CONFIG.API_URL}/contas/${contaId}`, {
                    method: 'POST',
                    headers: {
                        'X-HTTP-Method-Override': 'DELETE'
                    }
                });

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

                if (!result.success) {
                    throw new Error(result.message || 'Erro ao excluir conta');
                }

                Utils.showToast('Conta excluida com sucesso!', 'success');
                await ContasAPI.loadContas({ silent: true });
            } catch (error) {
                console.error('Erro ao excluir conta:', error);
                Utils.showToast(getErrorMessage(error, 'Erro ao excluir conta'), 'error');
            }
        });
    },

    async forceDeleteConta(contaId) {
        try {
            await requestJson(`${CONFIG.API_URL}/contas/${contaId}?force=1`, {
                method: 'POST',
                headers: {
                    'X-HTTP-Method-Override': 'DELETE'
                }
            });

            Utils.showToast('Conta e lancamentos excluidos com sucesso!', 'success');
            await ContasAPI.loadContas({ silent: true });
        } catch (error) {
            console.error('Erro ao excluir conta:', error);
            Utils.showToast(getErrorMessage(error, 'Erro ao excluir conta'), 'error');
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
