import { apiPost, getErrorMessage } from './api.js';

export async function sugerirCategoriaIA(opts) {
    const {
        descricaoInputId,
        categoriaSelectId,
        subcategoriaSelectId,
        subcategoriaGroupId,
        btnId,
        notify,
    } = opts;

    const descricao = document.getElementById(descricaoInputId)?.value.trim() || '';
    if (descricao.length < 2) {
        notify('Digite uma descricao primeiro', 'warning');
        return;
    }

    const btn = document.getElementById(btnId);
    if (btn) {
        btn.disabled = true;
        btn.classList.add('loading');
    }

    try {
        const data = await apiPost('api/ai/suggest-category', { description: descricao });

        if (data.success && data.data?.category) {
            const select = document.getElementById(categoriaSelectId);
            if (!select) return;

            const categoryId = data.data.category_id;
            const categoryName = data.data.category;
            let matched = false;

            if (categoryId) {
                const optionById = select.querySelector(`option[value="${categoryId}"]`);
                if (optionById) {
                    select.value = String(categoryId);
                    select.dispatchEvent(new Event('change'));
                    matched = true;
                }
            }

            if (!matched) {
                for (const option of select.options) {
                    if (option.text.trim().toLowerCase() === String(categoryName || '').toLowerCase()) {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change'));
                        matched = true;
                        break;
                    }
                }
            }

            if (matched) {
                notify(`Categoria sugerida: ${categoryName}`, 'success');

                const subcategoriaId = data.data.subcategory_id;
                if (subcategoriaId && subcategoriaSelectId) {
                    applySubcategoria(subcategoriaSelectId, subcategoriaGroupId, subcategoriaId);
                }
            } else {
                notify(`IA sugeriu "${categoryName}", mas nao encontrei essa categoria nas suas opcoes.`, 'warning');
            }
        } else {
            notify('Nao foi possivel sugerir uma categoria', 'warning');
        }
    } catch (error) {
        if (error?.status === 403) {
            notify('Faca upgrade do plano para usar sugestoes de IA', 'warning');
            return;
        }

        if (error?.status === 429) {
            notify('Voce usou suas sugestoes gratuitas de categoria neste mes. Faca upgrade para continuar.', 'warning');
            return;
        }

        notify(getErrorMessage(error, 'Erro ao sugerir categoria'), 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
    }
}

function applySubcategoria(subcategoriaSelectId, subcategoriaGroupId, subcategoriaId) {
    let attempts = 0;
    const maxAttempts = 30;
    const interval = setInterval(() => {
        attempts += 1;
        const subSelect = document.getElementById(subcategoriaSelectId);
        if (!subSelect) {
            clearInterval(interval);
            return;
        }

        const option = Array.from(subSelect.options).find((item) => String(item.value) === String(subcategoriaId));
        if (option || attempts >= maxAttempts) {
            clearInterval(interval);

            if (option) {
                subSelect.value = String(subcategoriaId);
                subSelect.dispatchEvent(new Event('change'));
            }

            if (subcategoriaGroupId) {
                const group = document.getElementById(subcategoriaGroupId);
                if (group) {
                    group.style.display = '';
                    group.classList.remove('d-none');
                }
            }
        }
    }, 100);
}
