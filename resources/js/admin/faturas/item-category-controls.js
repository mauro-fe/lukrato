import { STATE, Modules } from './state.js';
import { getApiPayload } from '../shared/api.js';

function parsePositiveId(value) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function normalizeCategoriaOptions(response) {
    const payload = getApiPayload(response, []);
    const rawItems = Array.isArray(payload)
        ? payload
        : (Array.isArray(payload?.categorias) ? payload.categorias : []);

    return rawItems
        .map((item) => ({
            id: parsePositiveId(item?.id ?? null),
            nome: String(item?.nome || '').trim(),
            tipo: String(item?.tipo || '').trim().toLowerCase(),
            parentId: parsePositiveId(item?.parent_id ?? null),
        }))
        .filter((item) => item.id && item.nome && !item.parentId)
        .filter((item) => item.tipo === 'despesa' || item.tipo === 'ambas' || item.tipo === '')
        .sort((left, right) => left.nome.localeCompare(right.nome, 'pt-BR', { sensitivity: 'base' }));
}

function normalizeSubcategoriaOptions(response) {
    const payload = getApiPayload(response, []);
    const rawItems = Array.isArray(payload?.subcategorias)
        ? payload.subcategorias
        : (Array.isArray(payload) ? payload : []);

    return rawItems
        .map((item) => ({
            id: parsePositiveId(item?.id ?? null),
            nome: String(item?.nome || '').trim(),
        }))
        .filter((item) => item.id && item.nome)
        .sort((left, right) => left.nome.localeCompare(right.nome, 'pt-BR', { sensitivity: 'base' }));
}

function getEditCategoriaElements() {
    return {
        categoriaSelect: document.getElementById('editItemCategoria'),
        subcategoriaSelect: document.getElementById('editItemSubcategoria'),
        subcategoriaGroup: document.getElementById('editItemSubcategoriaGroup'),
    };
}

async function ensureCategoriasLoaded() {
    if (Array.isArray(STATE.categorias) && STATE.categorias.length > 0) {
        return STATE.categorias;
    }

    const response = await Modules.API.listarCategorias();
    STATE.categorias = normalizeCategoriaOptions(response);
    return STATE.categorias;
}

async function ensureSubcategoriasLoaded(categoriaId) {
    const normalizedCategoriaId = parsePositiveId(categoriaId);
    if (!normalizedCategoriaId) {
        return [];
    }

    const cacheKey = String(normalizedCategoriaId);
    if (STATE.subcategoriasCache.has(cacheKey)) {
        return STATE.subcategoriasCache.get(cacheKey) || [];
    }

    const response = await Modules.API.listarSubcategorias(normalizedCategoriaId);
    const subcategorias = normalizeSubcategoriaOptions(response);
    STATE.subcategoriasCache.set(cacheKey, subcategorias);
    return subcategorias;
}

function populateOptions(select, items, emptyLabel, selectedId) {
    if (!select) {
        return;
    }

    select.innerHTML = '';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = emptyLabel;
    select.appendChild(emptyOption);

    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = item.nome;
        select.appendChild(option);
    });

    const normalizedSelectedId = parsePositiveId(selectedId);
    select.value = normalizedSelectedId ? String(normalizedSelectedId) : '';
}

export async function populateEditCategoriaControls(selectedCategoriaId = null, selectedSubcategoriaId = null) {
    const { categoriaSelect, subcategoriaSelect, subcategoriaGroup } = getEditCategoriaElements();
    if (!categoriaSelect || !subcategoriaSelect) {
        return;
    }

    const categorias = await ensureCategoriasLoaded();
    populateOptions(categoriaSelect, categorias, 'Sem categoria', selectedCategoriaId);

    const categoriaId = parsePositiveId(categoriaSelect.value);
    const subcategorias = categoriaId ? await ensureSubcategoriasLoaded(categoriaId) : [];
    populateOptions(subcategoriaSelect, subcategorias, 'Sem subcategoria', selectedSubcategoriaId);

    if (subcategoriaGroup) {
        subcategoriaGroup.style.display = subcategorias.length > 0 ? 'block' : 'none';
    }
}

export function bindEditCategoriaChange() {
    const { categoriaSelect, subcategoriaSelect, subcategoriaGroup } = getEditCategoriaElements();
    if (!categoriaSelect || categoriaSelect.dataset.boundFaturaCategoria === '1') {
        return;
    }

    categoriaSelect.dataset.boundFaturaCategoria = '1';
    categoriaSelect.addEventListener('change', async () => {
        const categoriaId = parsePositiveId(categoriaSelect.value);
        const subcategorias = categoriaId ? await ensureSubcategoriasLoaded(categoriaId) : [];
        populateOptions(subcategoriaSelect, subcategorias, 'Sem subcategoria', null);

        if (subcategoriaGroup) {
            subcategoriaGroup.style.display = subcategorias.length > 0 ? 'block' : 'none';
        }
    });
}

export function getSelectedCategoriaPayload() {
    return {
        categoriaId: parsePositiveId(document.getElementById('editItemCategoria')?.value || null),
        subcategoriaId: parsePositiveId(document.getElementById('editItemSubcategoria')?.value || null),
    };
}
