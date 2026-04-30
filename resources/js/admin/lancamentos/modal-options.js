import {
    resolveAccountsEndpoint,
    resolveCategoriesEndpoint,
    resolveCategorySubcategoriesEndpoint,
} from '../api/endpoints/finance.js';

export function attachLancamentosModalOptions(OptionsManager, dependencies) {
    const {
        DOM,
        STATE,
        Utils,
        Modules,
        planningStore,
        apiGet,
        formatMetaOptionLabel,
    } = dependencies;

    const normalizePositiveId = (value) => Utils.parsePositiveId(value);

    Object.assign(OptionsManager, {
        populateCategoriaSelect: (select, tipo, selectedId) => {
            if (!select) return;

            const normalized = (tipo || '').toLowerCase();
            const currentId = normalizePositiveId(selectedId);
            const currentValue = currentId !== null ? String(currentId) : '';

            select.innerHTML = '<option value="">Sem categoria</option>';

            const items = STATE.categoriaOptions.filter((item) => {
                if (!normalized) return true;
                return item.tipo === normalized;
            });

            items.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = String(item.id);
                opt.textContent = item.nome;
                opt.dataset.tipo = item.tipo || '';
                if (currentValue && String(item.id) === currentValue) opt.selected = true;
                select.appendChild(opt);
            });

            if (currentValue && select.value !== currentValue) {
                const fallback = document.createElement('option');
                fallback.value = currentValue;
                fallback.textContent = 'Categoria indisponível';
                fallback.selected = true;
                select.appendChild(fallback);
            }

            if (!select.dataset.subcatListenerAttached) {
                select.dataset.subcatListenerAttached = '1';
                select.addEventListener('change', () => {
                    OptionsManager.populateSubcategoriaSelect(select.value);
                });
            }
        },

        populateSubcategoriaSelect: async (categoriaId, selectedSubcatId) => {
            const select = DOM.selectLancSubcategoria;
            const group = DOM.subcategoriaGroup;
            if (!select) return;

            const normalizedCategoriaId = normalizePositiveId(categoriaId);
            const normalizedSelectedSubcatId = normalizePositiveId(selectedSubcatId);

            if (normalizedCategoriaId === null) {
                select.innerHTML = '<option value="">Sem subcategoria</option>';
                if (group) group.classList.add('hidden');
                return;
            }
            try {
                const json = await apiGet(resolveCategorySubcategoriesEndpoint(normalizedCategoriaId));
                const subs = [...(json?.data?.subcategorias ?? (Array.isArray(json?.data) ? json.data : []))]
                    .sort((a, b) => String(a?.nome || '').localeCompare(String(b?.nome || ''), 'pt-BR', { sensitivity: 'base' }));

                const selectedVal = normalizedSelectedSubcatId !== null ? String(normalizedSelectedSubcatId) : '';
                select.innerHTML = '<option value="">Sem subcategoria</option>';
                subs.forEach((sub) => {
                    const opt = document.createElement('option');
                    opt.value = String(sub.id);
                    opt.textContent = sub.nome;
                    if (selectedVal && String(sub.id) === selectedVal) opt.selected = true;
                    select.appendChild(opt);
                });

                if (group) {
                    if (subs.length > 0) group.classList.remove('hidden');
                    else group.classList.add('hidden');
                }
            } catch {
                select.innerHTML = '<option value="">Sem subcategoria</option>';
                if (group) group.classList.add('hidden');
            }
        },

        populateContaSelect: (select, selectedId) => {
            if (!select) return;

            const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';

            select.innerHTML = '<option value="">Selecione</option>';

            STATE.contaOptions.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = String(item.id);
                opt.textContent = item.label;
                if (currentValue && String(item.id) === currentValue) opt.selected = true;
                select.appendChild(opt);
            });

            if (currentValue && select.value !== currentValue) {
                const fallback = document.createElement('option');
                fallback.value = currentValue;
                fallback.textContent = 'Conta indisponível';
                fallback.selected = true;
                select.appendChild(fallback);
            }
        },

        formatMetaOptionLabel: (meta) => formatMetaOptionLabel(meta, (value) => Utils.formatPercent(value)),

        populateMetaSelect: async (select, selectedId, options = {}) => {
            if (!select) return;

            const {
                emptyLabel = 'Nenhuma meta',
                fallbackLabel = 'Meta indisponivel'
            } = options;

            const currentValue = selectedId !== undefined && selectedId !== null ? String(selectedId) : '';
            const metas = await planningStore.ensureMetas();

            select.innerHTML = `<option value="">${emptyLabel}</option>`;

            (Array.isArray(metas) ? metas : []).forEach((meta) => {
                const metaId = Number(meta?.id ?? 0);
                if (!metaId) return;

                const opt = document.createElement('option');
                opt.value = String(metaId);
                opt.textContent = OptionsManager.formatMetaOptionLabel(meta);
                opt.dataset.status = String(meta?.status || '').trim().toLowerCase();

                if (currentValue && String(metaId) === currentValue) {
                    opt.selected = true;
                }

                select.appendChild(opt);
            });

            if (currentValue && select.value !== currentValue) {
                const fallback = document.createElement('option');
                fallback.value = currentValue;
                fallback.textContent = fallbackLabel;
                fallback.selected = true;
                select.appendChild(fallback);
            }
        },

        loadFilterOptions: async () => {
            const [categorias, contas] = await Promise.all([
                DOM.selectCategoria ? Modules.API.fetchJsonList(resolveCategoriesEndpoint()) : Promise.resolve([]),
                DOM.selectConta ? Modules.API.fetchJsonList(`${resolveAccountsEndpoint()}?only_active=1&with_balances=1`) : Promise.resolve([])
            ]);

            if (DOM.selectCategoria) {
                DOM.selectCategoria.innerHTML = '<option value="">Categoria</option><option value="none">Sem categoria</option>';
            }
            if (DOM.selectConta) {
                DOM.selectConta.innerHTML = '<option value="">Conta</option>';
            }

            if (DOM.selectCategoria && categorias.length) {
                STATE.categoriaOptions = categorias
                    .map((cat) => ({
                        id: Number(cat?.id ?? 0),
                        nome: String(cat?.nome ?? '').trim(),
                        tipo: String(cat?.tipo ?? '').trim().toLowerCase()
                    }))
                    .filter((cat) => Number.isFinite(cat.id) && cat.id > 0 && cat.nome)
                    .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' }));

                const options = STATE.categoriaOptions
                    .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                    .join('');
                DOM.selectCategoria.insertAdjacentHTML('beforeend', options);
            }

            if (DOM.selectConta && contas.length) {
                STATE.contaOptions = contas
                    .map((acc) => {
                        const id = Number(acc?.id ?? 0);
                        const nome = String(acc?.nome ?? '').trim();
                        const instituicao = String(acc?.instituicao ?? '').trim();
                        const label = nome || instituicao || `Conta #${id}`;
                        const saldo = Number(acc?.saldoAtual ?? acc?.saldo ?? acc?.saldo_inicial ?? 0);
                        return { id, label, nome, saldo };
                    })
                    .filter((acc) => Number.isFinite(acc.id) && acc.id > 0 && acc.label)
                    .sort((a, b) => a.label.localeCompare(b.label, 'pt-BR', { sensitivity: 'base' }));

                const options = STATE.contaOptions
                    .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                    .join('');
                DOM.selectConta.insertAdjacentHTML('beforeend', options);
            }

            if (DOM.exportConta) {
                DOM.exportConta.innerHTML = '<option value="">Todas</option>';
                if (STATE.contaOptions.length) {
                    const opts = STATE.contaOptions
                        .map((acc) => `<option value="${acc.id}">${Utils.escapeHtml(acc.label)}</option>`)
                        .join('');
                    DOM.exportConta.insertAdjacentHTML('beforeend', opts);
                }
            }
            if (DOM.exportCategoria) {
                DOM.exportCategoria.innerHTML = '<option value="">Todas</option>';
                if (STATE.categoriaOptions.length) {
                    const opts = STATE.categoriaOptions
                        .map((cat) => `<option value="${cat.id}">${Utils.escapeHtml(cat.nome)}</option>`)
                        .join('');
                    DOM.exportCategoria.insertAdjacentHTML('beforeend', opts);
                }
            }

            if (DOM.selectLancConta) {
                OptionsManager.populateContaSelect(DOM.selectLancConta, DOM.selectLancConta.value || null);
            }
            if (DOM.selectTransConta) {
                OptionsManager.populateContaSelect(DOM.selectTransConta, DOM.selectTransConta.value || null);
            }
            if (DOM.selectTransContaDestino) {
                OptionsManager.populateContaSelect(DOM.selectTransContaDestino, DOM.selectTransContaDestino.value || null);
            }
            if (DOM.selectLancCategoria) {
                OptionsManager.populateCategoriaSelect(
                    DOM.selectLancCategoria,
                    DOM.selectLancTipo?.value || '',
                    DOM.selectLancCategoria.value || null
                );
            }
        }
    });
}
