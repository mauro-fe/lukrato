import {
    formatAmount,
    hasCategory,
    parsePositiveInt,
} from './helpers.js';

function resolveSourceKey(row) {
    if (!row) {
        return 'pending';
    }

    if (row.categoriaEditada && row.categoriaId) {
        return 'manual';
    }

    const normalized = String(row.categoriaSource || '').trim().toLowerCase();
    if (normalized === 'user_rule' || normalized === 'rule' || normalized === 'manual') {
        return normalized;
    }

    return row.categoriaId ? 'manual' : 'pending';
}

function resolveSourceLabel(row) {
    switch (resolveSourceKey(row)) {
        case 'user_rule':
            return 'Regra do usuário';
        case 'rule':
            return 'Regra global';
        case 'manual':
            return 'Manual';
        default:
            return 'Pendente';
    }
}

function resolveReviewState(row, allowCategoryReview) {
    if (!allowCategoryReview) {
        return {
            key: 'ready',
            label: 'Validado',
        };
    }

    if (!hasCategory(row)) {
        return {
            key: 'pending',
            label: 'Sem categoria',
        };
    }

    if (row?.categoriaEditada) {
        return {
            key: 'reviewed',
            label: 'Revisado',
        };
    }

    return {
        key: 'ready',
        label: 'Pronto',
    };
}

function resolveTypeTone(row) {
    const normalizedType = String(row?.type || '').trim().toLowerCase();
    if (normalizedType === 'receita') {
        return 'receita';
    }

    if (normalizedType === 'despesa') {
        return 'despesa';
    }

    return 'neutral';
}

export function createImportacoesPreviewManager({
    previewRowsBody,
    isContaOfxPreviewActive,
    loadCategories,
    loadSubcategories,
    onPreviewRowUpdate,
}) {
    let categoryOptions = [];
    let categoryCatalogError = '';
    const subcategoryCache = new Map();

    const resolveCategoryName = (categoriaId) => {
        const normalizedId = parsePositiveInt(categoriaId);
        if (!normalizedId) {
            return '';
        }

        return String(
            categoryOptions.find((item) => String(item.id) === String(normalizedId))?.nome
            || ''
        );
    };

    const resolveSubcategoryName = (categoriaId, subcategoriaId) => {
        const normalizedCategoriaId = parsePositiveInt(categoriaId);
        const normalizedSubcategoriaId = parsePositiveInt(subcategoriaId);
        if (!normalizedCategoriaId || !normalizedSubcategoriaId) {
            return '';
        }

        const options = subcategoryCache.get(String(normalizedCategoriaId)) || [];
        return String(
            options.find((item) => String(item.id) === String(normalizedSubcategoriaId))?.nome
            || ''
        );
    };

    const syncPreviewRowState = (row) => {
        const categoriaId = parsePositiveInt(row?.categoriaId ?? null);
        const subcategoriaId = parsePositiveInt(row?.subcategoriaId ?? null);
        const categoriaSugeridaId = parsePositiveInt(row?.categoriaSugeridaId ?? null);
        const subcategoriaSugeridaId = parsePositiveInt(row?.subcategoriaSugeridaId ?? null);
        const categoriaEditada = categoriaId !== categoriaSugeridaId || subcategoriaId !== subcategoriaSugeridaId;
        const categoriaNome = String(row?.categoriaNome || resolveCategoryName(categoriaId) || '');
        const subcategoriaNome = String(row?.subcategoriaNome || resolveSubcategoryName(categoriaId, subcategoriaId) || '');

        return {
            ...row,
            categoriaId,
            subcategoriaId,
            categoriaSugeridaId,
            subcategoriaSugeridaId,
            categoriaNome,
            subcategoriaNome,
            categoriaEditada,
            status: categoriaId ? 'Pronto' : 'Pendente',
        };
    };

    const normalizePreviewRow = (row, index) => {
        const source = typeof row === 'object' && row !== null ? row : {};
        const amountValue = source.amount ?? source.valor ?? source.value ?? null;
        const type = String(source.type ?? source.entry_type ?? source.kind ?? '-').trim().toLowerCase();

        return syncPreviewRowState({
            rowKey: String(source.row_key || `preview-row-${index}`).trim(),
            date: String(source.date ?? source.occurred_on ?? source.posted_on ?? '-'),
            description: String(source.description ?? source.memo ?? source.historico ?? '-'),
            memo: String(source.memo ?? ''),
            amountValue: Number.isFinite(Number(amountValue)) ? Number(amountValue) : null,
            amountLabel: amountValue === null ? '-' : formatAmount(amountValue),
            type,
            typeLabel: String(type || '-').toUpperCase(),
            categoriaId: parsePositiveInt(source.categoria_id ?? null),
            subcategoriaId: parsePositiveInt(source.subcategoria_id ?? null),
            categoriaNome: String(source.categoria_nome ?? ''),
            subcategoriaNome: String(source.subcategoria_nome ?? ''),
            categoriaSugeridaId: parsePositiveInt(source.categoria_sugerida_id ?? source.categoria_id ?? null),
            subcategoriaSugeridaId: parsePositiveInt(source.subcategoria_sugerida_id ?? source.subcategoria_id ?? null),
            categoriaSugeridaNome: String(source.categoria_sugerida_nome ?? source.categoria_nome ?? ''),
            subcategoriaSugeridaNome: String(source.subcategoria_sugerida_nome ?? source.subcategoria_nome ?? ''),
            categoriaSource: String(source.categoria_source ?? '').trim().toLowerCase(),
            categoriaConfidence: String(source.categoria_confidence ?? '').trim().toLowerCase(),
            categoriaLearningSource: String(source.categoria_learning_source ?? '').trim().toLowerCase(),
            categoriaEditada: Boolean(source.categoria_editada === true),
            status: String(source.status ?? ''),
        });
    };

    const normalizeRows = (rows) => {
        if (!Array.isArray(rows)) {
            return [];
        }

        return rows.map((row, index) => normalizePreviewRow(row, index));
    };

    const getAvailableCategoriesForRow = (row) => {
        const bucketType = String(row?.type || '').trim().toLowerCase();
        if (!bucketType || !['receita', 'despesa'].includes(bucketType)) {
            return [...categoryOptions];
        }

        return categoryOptions.filter((item) => item.tipo === bucketType || item.tipo === 'ambas');
    };

    const ensureCategoryOptionsLoaded = async () => {
        if (categoryOptions.length > 0) {
            return categoryOptions;
        }

        const response = await loadCategories();
        const rawCategories = Array.isArray(response?.data)
            ? response.data
            : (Array.isArray(response?.data?.categorias) ? response.data.categorias : []);

        categoryOptions = rawCategories
            .map((item) => ({
                id: parsePositiveInt(item?.id ?? null),
                nome: String(item?.nome || '').trim(),
                tipo: String(item?.tipo || '').trim().toLowerCase(),
            }))
            .filter((item) => item.id && item.nome)
            .sort((left, right) => left.nome.localeCompare(right.nome, 'pt-BR', { sensitivity: 'base' }));

        categoryCatalogError = '';
        return categoryOptions;
    };

    const ensureSubcategoryOptionsLoaded = async (categoriaId) => {
        const normalizedCategoriaId = parsePositiveInt(categoriaId);
        if (!normalizedCategoriaId) {
            return [];
        }

        const cacheKey = String(normalizedCategoriaId);
        if (subcategoryCache.has(cacheKey)) {
            return subcategoryCache.get(cacheKey) || [];
        }

        const response = await loadSubcategories(normalizedCategoriaId);
        const rawSubcategories = Array.isArray(response?.data?.subcategorias)
            ? response.data.subcategorias
            : (Array.isArray(response?.data) ? response.data : []);

        const normalized = rawSubcategories
            .map((item) => ({
                id: parsePositiveInt(item?.id ?? null),
                nome: String(item?.nome || '').trim(),
            }))
            .filter((item) => item.id && item.nome)
            .sort((left, right) => left.nome.localeCompare(right.nome, 'pt-BR', { sensitivity: 'base' }));

        subcategoryCache.set(cacheKey, normalized);
        return normalized;
    };

    const prefetchSubcategoryOptions = async (rows) => {
        const categoryIds = Array.from(new Set(
            (Array.isArray(rows) ? rows : [])
                .map((row) => parsePositiveInt(row?.categoriaId ?? null))
                .filter(Boolean)
        ));

        await Promise.all(categoryIds.map((categoriaId) => ensureSubcategoryOptionsLoaded(categoriaId)));
    };

    const buildFallbackCell = (text) => {
        const span = document.createElement('span');
        span.className = 'imp-preview-cell-muted';
        span.textContent = String(text || '-');
        return span;
    };

    const buildStackCell = (title, copy = '') => {
        const wrapper = document.createElement('div');
        wrapper.className = 'imp-preview-cell-stack';

        const titleElement = document.createElement('span');
        titleElement.className = 'imp-preview-cell-title';
        titleElement.textContent = String(title || '-');
        wrapper.appendChild(titleElement);

        if (String(copy || '').trim() !== '') {
            const copyElement = document.createElement('span');
            copyElement.className = 'imp-preview-cell-copy imp-preview-cell-copy--truncate';
            copyElement.textContent = String(copy);
            wrapper.appendChild(copyElement);
        }

        return wrapper;
    };

    const buildAmountBadge = (row) => {
        const amount = document.createElement('span');
        amount.className = 'imp-preview-amount';
        amount.dataset.tone = resolveTypeTone(row);
        amount.textContent = String(row?.amountLabel || '-');
        return amount;
    };

    const buildTypeBadge = (row) => {
        const badge = document.createElement('span');
        badge.className = 'imp-preview-kind';
        badge.dataset.kind = resolveTypeTone(row);
        badge.textContent = String(row?.typeLabel || '-');
        return badge;
    };

    const buildStatusBadge = (row) => {
        const badge = document.createElement('span');
        const reviewState = resolveReviewState(row, isContaOfxPreviewActive());
        badge.className = 'imp-preview-status-pill';
        badge.dataset.review = reviewState.key;
        badge.textContent = reviewState.label;
        return badge;
    };

    const buildTableCell = (label, className, child) => {
        const cell = document.createElement('td');
        cell.className = `imp-preview-table__cell ${className}`;
        cell.dataset.label = label;
        cell.appendChild(child);
        return cell;
    };

    const buildCategorySelect = (row) => {
        if (!isContaOfxPreviewActive()) {
            return buildFallbackCell(row?.categoriaNome || '-');
        }

        if (categoryCatalogError || categoryOptions.length === 0) {
            return buildFallbackCell(row?.categoriaNome || 'Categorias indisponíveis');
        }

        const select = document.createElement('select');
        select.className = 'imp-preview-select';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Sem categoria';
        select.appendChild(emptyOption);

        const options = getAvailableCategoriesForRow(row);
        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.nome;
            select.appendChild(option);
        });

        const selectedValue = row?.categoriaId ? String(row.categoriaId) : '';
        if (selectedValue && !Array.from(select.options).some((option) => option.value === selectedValue)) {
            const fallbackOption = document.createElement('option');
            fallbackOption.value = selectedValue;
            fallbackOption.textContent = row?.categoriaNome || 'Categoria indisponível';
            select.appendChild(fallbackOption);
        }

        select.value = selectedValue;
        select.addEventListener('change', async (event) => {
            const categoriaId = parsePositiveInt(event.target.value);
            if (categoriaId) {
                try {
                    await ensureSubcategoryOptionsLoaded(categoriaId);
                } catch (error) {
                    categoryCatalogError = String(error?.message || 'Não foi possível carregar subcategorias.').trim();
                }
            }

            onPreviewRowUpdate(row.rowKey, (currentRow) => {
                const nextCategoriaId = parsePositiveInt(categoriaId);
                const nextSubcategories = nextCategoriaId
                    ? (subcategoryCache.get(String(nextCategoriaId)) || [])
                    : [];
                const keepCurrentSubcategory = nextSubcategories.some(
                    (item) => String(item.id) === String(currentRow.subcategoriaId || ''),
                );
                const nextSubcategoriaId = keepCurrentSubcategory ? currentRow.subcategoriaId : null;

                return {
                    categoriaId: nextCategoriaId,
                    categoriaNome: resolveCategoryName(nextCategoriaId),
                    subcategoriaId: nextSubcategoriaId,
                    subcategoriaNome: resolveSubcategoryName(nextCategoriaId, nextSubcategoriaId),
                };
            });
        });

        return select;
    };

    const buildSubcategorySelect = (row) => {
        if (!isContaOfxPreviewActive()) {
            return buildFallbackCell(row?.subcategoriaNome || '-');
        }

        if (categoryCatalogError) {
            return buildFallbackCell(row?.subcategoriaNome || 'Subcategorias indisponíveis');
        }

        if (!row?.categoriaId) {
            return buildFallbackCell('Sem subcategoria');
        }

        const options = subcategoryCache.get(String(row.categoriaId)) || [];
        if (options.length === 0) {
            return buildFallbackCell('Sem subcategoria');
        }

        const select = document.createElement('select');
        select.className = 'imp-preview-select';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Sem subcategoria';
        select.appendChild(emptyOption);

        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.nome;
            select.appendChild(option);
        });

        const selectedValue = row?.subcategoriaId ? String(row.subcategoriaId) : '';
        if (selectedValue && !Array.from(select.options).some((option) => option.value === selectedValue)) {
            const fallbackOption = document.createElement('option');
            fallbackOption.value = selectedValue;
            fallbackOption.textContent = row?.subcategoriaNome || 'Subcategoria indisponível';
            select.appendChild(fallbackOption);
        }

        select.value = selectedValue;
        select.addEventListener('change', (event) => {
            const subcategoriaId = parsePositiveInt(event.target.value);
            onPreviewRowUpdate(row.rowKey, {
                subcategoriaId,
                subcategoriaNome: resolveSubcategoryName(row.categoriaId, subcategoriaId),
            });
        });

        return select;
    };

    const clearPreviewRows = () => {
        if (!previewRowsBody) {
            return;
        }

        while (previewRowsBody.firstChild) {
            previewRowsBody.removeChild(previewRowsBody.firstChild);
        }
    };

    const renderPreviewRows = (rows) => {
        if (!previewRowsBody) {
            return;
        }

        clearPreviewRows();

        rows.forEach((row) => {
            const tr = document.createElement('tr');
            const reviewState = resolveReviewState(row, isContaOfxPreviewActive());
            tr.dataset.review = reviewState.key;
            tr.dataset.source = resolveSourceKey(row);

            const origemBadge = document.createElement('span');
            origemBadge.className = 'imp-preview-source';
            origemBadge.dataset.source = resolveSourceKey(row);
            origemBadge.textContent = resolveSourceLabel(row);

            tr.appendChild(buildTableCell('Data', 'imp-preview-table__cell--date', buildStackCell(row?.date || '-', '')));
            tr.appendChild(buildTableCell('Descrição', 'imp-preview-table__cell--description', buildStackCell(row?.description || '-', row?.memo || '')));
            tr.appendChild(buildTableCell('Valor', 'imp-preview-table__cell--amount', buildAmountBadge(row)));
            tr.appendChild(buildTableCell('Tipo', 'imp-preview-table__cell--type', buildTypeBadge(row)));
            tr.appendChild(buildTableCell('Categoria', 'imp-preview-table__cell--category', buildCategorySelect(row)));
            tr.appendChild(buildTableCell('Subcategoria', 'imp-preview-table__cell--subcategory', buildSubcategorySelect(row)));
            tr.appendChild(buildTableCell('Origem', 'imp-preview-table__cell--source', origemBadge));
            tr.appendChild(buildTableCell('Status', 'imp-preview-table__cell--status', buildStatusBadge(row)));

            previewRowsBody.appendChild(tr);
        });
    };

    const mergeSuggestedRowsIntoPreview = (currentRows, suggestedRows) => {
        if (!Array.isArray(currentRows) || currentRows.length === 0) {
            return Array.isArray(suggestedRows) ? suggestedRows : [];
        }

        const currentRowsByKey = new Map(
            currentRows
                .filter((row) => row?.rowKey)
                .map((row) => [String(row.rowKey), row])
        );

        return (Array.isArray(suggestedRows) ? suggestedRows : []).map((suggestedRow) => {
            const currentRow = currentRowsByKey.get(String(suggestedRow?.rowKey || ''));
            if (!currentRow || currentRow.categoriaEditada !== true) {
                return suggestedRow;
            }

            return {
                ...suggestedRow,
                categoriaId: currentRow.categoriaId,
                subcategoriaId: currentRow.subcategoriaId,
                categoriaNome: currentRow.categoriaNome,
                subcategoriaNome: currentRow.subcategoriaNome,
            };
        });
    };

    const buildRowOverrides = (rows) => {
        if (!isContaOfxPreviewActive()) {
            return {};
        }

        return rows.reduce((accumulator, row) => {
            if (!row?.rowKey) {
                return accumulator;
            }

            accumulator[row.rowKey] = {
                categoria_id: row.categoriaId || null,
                subcategoria_id: row.subcategoriaId || null,
                categoria_sugerida_id: row.categoriaSugeridaId || null,
                subcategoria_sugerida_id: row.subcategoriaSugeridaId || null,
                categoria_sugerida_nome: row.categoriaSugeridaNome || null,
                subcategoria_sugerida_nome: row.subcategoriaSugeridaNome || null,
                categoria_source: row.categoriaSource || null,
                categoria_confidence: row.categoriaConfidence || null,
                user_edited: row.categoriaEditada === true,
            };

            return accumulator;
        }, {});
    };

    return {
        syncPreviewRowState,
        normalizeRows,
        ensureCategoryOptionsLoaded,
        ensureSubcategoryOptionsLoaded,
        prefetchSubcategoryOptions,
        clearPreviewRows,
        renderPreviewRows,
        mergeSuggestedRowsIntoPreview,
        buildRowOverrides,
        getCategoryCatalogError() {
            return categoryCatalogError;
        },
        setCategoryCatalogError(message) {
            categoryCatalogError = String(message || '').trim();
        },
        clearCategoryCatalogError() {
            categoryCatalogError = '';
        },
        hasCategory,
    };
}