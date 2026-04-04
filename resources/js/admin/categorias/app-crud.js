/**
 * ============================================================================
 * LUKRATO - Categorias / CRUD
 * ============================================================================
 * Category create/edit/delete flows and related form helpers.
 * ============================================================================
 */

export function createCategoriasCrud({
    STATE,
    CONFIG,
    Utils,
    apiPost,
    apiPut,
    apiDelete,
    getErrorMessage,
    showSuccess,
    showError,
    setButtonBusy,
    resolveAppUrl,
    closeIconPicker,
    renderSuggestions,
    loadAll,
    highlightSelectedIcon,
}) {
    function getPageModalInstance(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return null;

        window.LK?.modalSystem?.prepareBootstrapModal(modalElement, { scope: 'page' });

        return bootstrap.Modal.getOrCreateInstance(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true,
        });
    }

    function resetCreateForm() {
        STATE.selectedIcon = '';
        const catIcone = document.getElementById('catIcone');
        if (catIcone) catIcone.value = '';

        const inner = document.querySelector('#iconPreviewRing .create-icon-inner');
        if (inner) {
            inner.innerHTML = '<i data-lucide="tag" class="create-main-icon" id="iconPreview"></i>';
        }

        closeIconPicker();
        renderSuggestions();
        Utils.processNewIcons();
    }

    async function handleNovaCategoria(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        try {
            setButtonBusy(submitButton, true, 'Adicionando...');
            const formData = new FormData(form);
            const data = {
                nome: formData.get('nome'),
                tipo: formData.get('tipo'),
                icone: formData.get('icone') || null,
            };

            const result = await apiPost(`${CONFIG.API_URL}categorias`, data);

            if (result?.success === false) {
                throw new Error(getErrorMessage({ data: result }, 'Erro ao criar categoria.'));
            }

            if (result.data?.gamification?.achievements && Array.isArray(result.data.gamification.achievements)) {
                if (typeof window.notifyMultipleAchievements === 'function') {
                    window.notifyMultipleAchievements(result.data.gamification.achievements);
                }
            }

            showSuccess('Categoria criada com sucesso!');
            form.reset();
            resetCreateForm();
            await loadAll();
        } catch (error) {
            const limitInfo = error?.data?.errors;

            if (error?.status === 403 && limitInfo?.limit_reached) {
                if (window.PlanLimits?.promptUpgrade) {
                    await window.PlanLimits.promptUpgrade({
                        context: 'categorias',
                        message: getErrorMessage(error, 'Limite do plano atingido.'),
                        upgradeUrl: limitInfo.upgrade_url,
                    });
                    return;
                }

                if (window.LKFeedback?.upgradePrompt) {
                    await window.LKFeedback.upgradePrompt({
                        context: 'categorias',
                        message: getErrorMessage(error, 'Limite do plano atingido.'),
                        upgradeUrl: limitInfo.upgrade_url,
                    });
                    return;
                }

                if (typeof Swal !== 'undefined') {
                    const decision = await Swal.fire({
                        icon: 'info',
                        title: 'Recurso Pro',
                        text: getErrorMessage(error, 'Limite do plano atingido.'),
                        showCancelButton: true,
                        confirmButtonText: 'Ver planos',
                        cancelButtonText: 'Agora nao',
                    });

                    if (decision.isConfirmed && limitInfo.upgrade_url) {
                        window.location.href = resolveAppUrl(limitInfo.upgrade_url);
                    }
                } else if (limitInfo.upgrade_url) {
                    window.location.href = resolveAppUrl(limitInfo.upgrade_url);
                }
                return;
            }

            console.error('Erro ao criar categoria:', error);
            showError(getErrorMessage(error, 'Erro ao criar categoria. Tente novamente.'));
        } finally {
            setButtonBusy(submitButton, false);
        }
    }

    function editarCategoria(id) {
        const categoria = STATE.categorias.find((cat) => cat.id === id);
        if (!categoria || !categoria.user_id || categoria.is_seeded) return;

        STATE.categoriaEmEdicao = categoria;

        document.getElementById('editCategoriaNome').value = categoria.nome;
        document.getElementById('editCategoriaTipo').value = categoria.tipo;

        const currentIcon = categoria.icone || 'tag';
        STATE.editSelectedIcon = currentIcon;
        document.getElementById('editCategoriaIcone').value = currentIcon;
        const editPreview = document.getElementById('editIconPreview');
        if (editPreview) {
            editPreview.innerHTML = `<i data-lucide="${currentIcon}"></i>`;
            Utils.processNewIcons();
        }

        const editIconPanel = document.getElementById('editIconPickerPanel');
        if (editIconPanel) editIconPanel.classList.add('d-none');
        highlightSelectedIcon('editIconPickerGrid', currentIcon);

        const modal = getPageModalInstance('modalEditCategoria');
        modal?.show();
    }

    async function handleEditarCategoria(form) {
        if (!STATE.categoriaEmEdicao) return;

        const submitButton = form.querySelector('button[type="submit"]') || document.querySelector('[form="formEditCategoria"]');
        try {
            setButtonBusy(submitButton, true, 'Salvando...');
            const formData = new FormData(form);
            const data = {
                nome: formData.get('nome'),
                tipo: formData.get('tipo'),
                icone: formData.get('icone') || null,
            };

            await apiPut(`${CONFIG.API_URL}categorias/${STATE.categoriaEmEdicao.id}`, data);

            showSuccess('Categoria atualizada com sucesso!');

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditCategoria'));
            modal.hide();

            await loadAll();
        } catch (error) {
            console.error('Erro ao editar categoria:', error);
            showError(getErrorMessage(error, 'Erro ao editar categoria. Tente novamente.'));
        } finally {
            setButtonBusy(submitButton, false);
        }
    }

    async function excluirCategoria(id) {
        const categoria = STATE.categorias.find((cat) => cat.id === id);
        if (!categoria || !categoria.user_id || categoria.is_seeded) return;

        const confirmacao = await Swal.fire({
            title: 'Confirmar exclusao',
            html: `Deseja realmente excluir a categoria <strong>${categoria.nome}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao.isConfirmed) return;

        try {
            await apiDelete(`${CONFIG.API_URL}categorias/${id}`, {});
        } catch (error) {
            if (error?.status === 422 && error?.data?.errors?.confirm_delete) {
                const counts = error.data.errors.counts || {};
                const forceDelete = await Swal.fire({
                    title: 'Categoria com vinculos',
                    html: `
                        <p>Esta categoria ainda possui itens vinculados.</p>
                        <ul class="swal2-html-container" style="text-align:left; margin:1rem 0 0;">
                            <li>${counts.subcategorias || 0} subcategoria(s)</li>
                            <li>${counts.lancamentos || 0} lancamento(s)</li>
                        </ul>
                        <p>Se continuar, as subcategorias serao removidas e os lancamentos ficarao sem categoria.</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Excluir mesmo assim',
                    cancelButtonText: 'Cancelar',
                });

                if (!forceDelete.isConfirmed) return;

                try {
                    await apiDelete(`${CONFIG.API_URL}categorias/${id}`, { force: true });
                } catch (forcedError) {
                    console.error('Erro ao excluir categoria:', forcedError);
                    showError(getErrorMessage(forcedError, 'Erro ao excluir categoria. Pode haver lancamentos vinculados.'));
                    return;
                }
            } else {
                console.error('Erro ao excluir categoria:', error);
                showError(getErrorMessage(error, 'Erro ao excluir categoria. Pode haver lancamentos vinculados.'));
                return;
            }
        }

        showSuccess('Categoria excluida com sucesso!');
        await loadAll();
    }

    return {
        resetCreateForm,
        handleNovaCategoria,
        editarCategoria,
        handleEditarCategoria,
        excluirCategoria,
    };
}
