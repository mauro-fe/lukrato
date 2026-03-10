/**
 * ============================================================================
 * LUKRATO — Shared AI Categorization
 * ============================================================================
 * Utility for AI-powered category suggestion used across all transaction modals.
 *
 * import { sugerirCategoriaIA } from '../shared/ai-categorization.js';
 * ============================================================================
 */

import { getBaseUrl, getCSRFToken } from './api.js';

/**
 * Request AI category suggestion and apply to the given selects.
 *
 * @param {Object} opts
 * @param {string} opts.descricaoInputId    - ID of the description input
 * @param {string} opts.categoriaSelectId   - ID of the category <select>
 * @param {string} opts.subcategoriaSelectId - ID of the subcategory <select> (optional)
 * @param {string} opts.subcategoriaGroupId - ID of the subcategory wrapper div (optional)
 * @param {string} opts.btnId              - ID of the AI suggest button
 * @param {Function} opts.notify           - Notification callback: (message, type) => void
 *                                           type: 'success' | 'warning' | 'error'
 */
export async function sugerirCategoriaIA(opts) {
    const {
        descricaoInputId,
        categoriaSelectId,
        subcategoriaSelectId,
        subcategoriaGroupId,
        btnId,
        notify,
    } = opts;

    // ── Validar descrição ────────────────────────────────────────────────
    const descricao = document.getElementById(descricaoInputId)?.value.trim() || '';
    if (descricao.length < 2) {
        notify('Digite uma descrição primeiro', 'warning');
        return;
    }

    // ── Loading state ────────────────────────────────────────────────────
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.disabled = true;
        btn.classList.add('loading');
    }

    try {
        const base = getBaseUrl();
        const csrf = getCSRFToken();
        const resp = await fetch(`${base}api/ai/suggest-category`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf,
            },
            body: JSON.stringify({ description: descricao }),
        });

        // ── Tratar erros de quota / upgrade ──────────────────────────────
        if (resp.status === 403) {
            notify('Faça upgrade do plano para usar sugestões de IA', 'warning');
            return;
        }
        if (resp.status === 429) {
            notify('Limite de uso da IA atingido. Tente novamente amanhã', 'warning');
            return;
        }

        const data = await resp.json();

        if (data.success && data.data?.category) {
            const select = document.getElementById(categoriaSelectId);
            if (!select) return;

            const categoryId   = data.data.category_id;
            const categoryName = data.data.category;
            let matched = false;

            // Tentar match por ID primeiro (mais confiável)
            if (categoryId) {
                const optById = select.querySelector(`option[value="${categoryId}"]`);
                if (optById) {
                    select.value = String(categoryId);
                    select.dispatchEvent(new Event('change'));
                    matched = true;
                }
            }

            // Fallback: match por nome (case-insensitive)
            if (!matched) {
                for (const opt of select.options) {
                    if (opt.text.trim().toLowerCase() === categoryName.toLowerCase()) {
                        select.value = opt.value;
                        select.dispatchEvent(new Event('change'));
                        matched = true;
                        break;
                    }
                }
            }

            if (matched) {
                notify(`Categoria sugerida: ${categoryName}`, 'success');

                // Auto-selecionar subcategoria se retornada pelo backend
                const subcatId = data.data.subcategory_id;
                if (subcatId && subcategoriaSelectId) {
                    _applySub(subcategoriaSelectId, subcategoriaGroupId, subcatId);
                }
            } else {
                notify(`IA sugeriu "${categoryName}", mas não encontrada nas suas categorias`, 'warning');
            }
        } else {
            notify('Não foi possível sugerir uma categoria', 'warning');
        }
    } catch (_e) {
        notify('Erro ao sugerir categoria', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Aguarda o population do select de subcategoria (que acontece via change event)
 * e então seleciona a subcategoria pelo ID.
 */
function _applySub(subcategoriaSelectId, subcategoriaGroupId, subcatId) {
    // O change no select de categoria dispara a population assíncrona das subcategorias.
    // Aguardamos um curto intervalo para que o DOM seja atualizado.
    setTimeout(() => {
        const subSelect = document.getElementById(subcategoriaSelectId);
        if (!subSelect) return;

        const optById = subSelect.querySelector(`option[value="${subcatId}"]`);
        if (optById) {
            subSelect.value = String(subcatId);
            subSelect.dispatchEvent(new Event('change'));
        }

        // Garantir que o grupo de subcategoria esteja visível
        if (subcategoriaGroupId) {
            const group = document.getElementById(subcategoriaGroupId);
            if (group) {
                group.style.display = '';
                group.classList.remove('d-none');
            }
        }
    }, 400);
}
