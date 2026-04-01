/**
 * ============================================================================
 * LUKRATO - Categorias / Actions
 * ============================================================================
 * Shared actions for refresh/search/reorder and button busy state.
 * ============================================================================
 */

export function createCategoriasActions({
    STATE,
    CONFIG,
    Utils,
    SubcategoriasModule,
    apiPut,
    getErrorMessage,
    toastError,
    showSuccess,
    updateRefreshButtons,
    loadAll,
    loadCategorias,
    renderCategorias,
    getQueryValue,
}) {
    function setButtonBusy(button, isBusy, busyLabel = 'Salvando...') {
        if (!button) return;

        if (isBusy) {
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            button.disabled = true;
            button.classList.add('is-busy');
            button.innerHTML = `<i data-lucide="loader-circle"></i><span>${busyLabel}</span>`;
            Utils.processNewIcons();
            return;
        }

        button.disabled = false;
        button.classList.remove('is-busy');
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
        Utils.processNewIcons();
    }

    function clearSearch() {
        const searchInput = document.getElementById('catSearchInput');
        const searchClear = document.getElementById('catSearchClear');

        if (searchInput) searchInput.value = '';
        STATE.filterQuery = '';
        if (searchClear) searchClear.classList.add('d-none');
        renderCategorias();
        SubcategoriasModule.initSubcategoriaEvents();
    }

    function resolveAppUrl(path) {
        if (!path) return CONFIG.BASE_URL;
        if (/^https?:\/\//i.test(path)) return path;
        return `${CONFIG.BASE_URL}${String(path).replace(/^\/+/, '')}`;
    }

    async function refreshCategorias(options = {}) {
        const { silent = false } = options;

        if (STATE.isRefreshing || STATE.isLoading) return;

        STATE.isRefreshing = true;
        updateRefreshButtons();

        try {
            await loadAll({ showErrorToast: !silent });
            if (!silent && !STATE.lastLoadError) {
                showSuccess('Categorias atualizadas.');
            }
        } finally {
            STATE.isRefreshing = false;
            updateRefreshButtons();
        }
    }

    async function moveCategoria(categoriaId, direction, bucketType = 'despesa') {
        const categoria = STATE.categorias.find((cat) => cat.id === categoriaId);
        if (!categoria || !categoria.user_id || categoria.is_seeded) return;

        if (getQueryValue()) {
            toastError('Limpe a busca para reordenar categorias.');
            return;
        }

        const orderedIds = STATE.categorias
            .filter((cat) => (cat.tipo === bucketType || cat.tipo === 'ambas') && cat.user_id && !cat.is_seeded)
            .map((cat) => Number(cat.id));

        const idx = orderedIds.indexOf(Number(categoriaId));
        if (idx < 0) return;
        if (direction === 'up' && idx === 0) return;
        if (direction === 'down' && idx === orderedIds.length - 1) return;

        const swapIdx = direction === 'up' ? idx - 1 : idx + 1;
        [orderedIds[idx], orderedIds[swapIdx]] = [orderedIds[swapIdx], orderedIds[idx]];

        try {
            await apiPut(`${CONFIG.API_URL}categorias/reorder`, { ids: orderedIds });
            await loadCategorias();
            renderCategorias();
            SubcategoriasModule.initSubcategoriaEvents();
        } catch (error) {
            toastError(getErrorMessage(error, 'Erro ao reordenar categorias.'));
        }
    }

    return {
        setButtonBusy,
        clearSearch,
        resolveAppUrl,
        refreshCategorias,
        moveCategoria,
    };
}
