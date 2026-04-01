/**
 * ============================================================================
 * LUKRATO - Categorias / Feedback Helpers
 * ============================================================================
 * Toast/error helpers and empty-state renderer.
 * ============================================================================
 */

export function createCategoriasFeedback({
    STATE,
    escapeHtml,
    toastSuccess,
    toastError,
}) {
    function renderListState({ tipoLabel, totalCount, query }) {
        if (STATE.lastLoadError && STATE.categorias.length === 0) {
            return `
                <div class="category-state error">
                    <i data-lucide="triangle-alert"></i>
                    <p>${escapeHtml(STATE.lastLoadError)}</p>
                    <button type="button" class="category-state-btn" data-action="refresh-categorias">Tentar novamente</button>
                </div>
            `;
        }

        if (query) {
            return `
                <div class="category-state empty">
                    <i data-lucide="search-x"></i>
                    <p>Nenhuma categoria de ${escapeHtml(tipoLabel)} corresponde a busca atual.</p>
                    <button type="button" class="category-state-btn" data-action="clear-categoria-search">Limpar busca</button>
                </div>
            `;
        }

        if (totalCount === 0) {
            return `
                <div class="category-state empty">
                    <i data-lucide="inbox"></i>
                    <p>Nenhuma categoria de ${escapeHtml(tipoLabel)} cadastrada.</p>
                </div>
            `;
        }

        return `
            <div class="category-state empty">
                <i data-lucide="inbox"></i>
                <p>Nenhuma categoria de ${escapeHtml(tipoLabel)} disponivel neste momento.</p>
            </div>
        `;
    }

    function showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
            });
        } else {
            toastSuccess(message);
        }
    }

    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: message,
                confirmButtonText: 'OK',
            });
        } else {
            toastError(message);
        }
    }

    return {
        renderListState,
        showSuccess,
        showError,
    };
}
