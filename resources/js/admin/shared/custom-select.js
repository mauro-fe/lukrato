import { normalizeText } from './utils.js';

const INSTANCES = new Map();
let GLOBAL_EVENTS_BOUND = false;

function closeAll(exceptId = null) {
    INSTANCES.forEach((instance, select) => {
        if (!select.isConnected) {
            instance.destroy();
            INSTANCES.delete(select);
            return;
        }

        if (instance.id !== exceptId) {
            instance.close();
        }
    });
}

function bindGlobalEvents() {
    if (GLOBAL_EVENTS_BOUND) return;
    GLOBAL_EVENTS_BOUND = true;

    document.addEventListener('click', (event) => {
        const container = event.target.closest('.lk-custom-select');
        if (!container) closeAll();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAll();
    });

    document.addEventListener('lk:custom-select-sync', (event) => {
        CustomSelectManager.syncAll(event.detail?.root || document);
    });
}

function resolveVariant(select) {
    if (select.dataset.lkCustomSelect) return select.dataset.lkCustomSelect;
    if (select.closest('.lk-filter-chip-select')) return 'chip';
    return 'form';
}

function resolveBoolean(value, fallback = false) {
    if (value === undefined) return fallback;
    if (value === '' || value === 'true' || value === '1') return true;
    if (value === 'false' || value === '0') return false;
    return fallback;
}

function getConfig(select) {
    const variant = resolveVariant(select);

    return {
        variant,
        searchable: resolveBoolean(
            select.dataset.lkSelectSearch ?? select.dataset.lkSearchable,
            false
        ),
        sortMode: String(select.dataset.lkSelectSort || '').trim().toLowerCase(),
        searchPlaceholder: select.dataset.lkSelectSearchPlaceholder || 'Pesquisar...',
        emptyText: select.dataset.lkSelectEmptyText || 'Nenhum resultado encontrado.',
        searchAriaLabel: select.dataset.lkSelectSearchLabel || 'Pesquisar opções'
    };
}

function getSelectedOption(select) {
    if (!select) return null;
    const selected = select.options[select.selectedIndex];
    return selected || null;
}

function getOptionEntries(select, config) {
    const entries = Array.from(select.options).map((opt, index) => ({
        index,
        value: String(opt.value ?? ''),
        text: String(opt.textContent || '').trim(),
        disabled: opt.disabled,
        selected: opt.selected,
        searchText: normalizeText(`${opt.textContent || ''} ${opt.dataset.lkSelectSearch || ''}`),
        pinned: opt.dataset.lkSelectPinned === 'true' || String(opt.value ?? '') === ''
    }));

    if (config.sortMode !== 'alpha') {
        return entries;
    }

    const pinned = [];
    const sortable = [];

    entries.forEach((entry) => {
        if (entry.pinned) pinned.push(entry);
        else sortable.push(entry);
    });

    sortable.sort((a, b) => a.text.localeCompare(b.text, 'pt-BR', { sensitivity: 'base' }));
    return [...pinned, ...sortable];
}

function createOptionButton(instance, entry) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'lk-custom-select-option';
    btn.dataset.index = String(entry.index);
    btn.dataset.search = entry.searchText;
    btn.setAttribute('role', 'option');
    btn.setAttribute('tabindex', '-1');
    btn.textContent = entry.text;

    if (entry.disabled) {
        btn.disabled = true;
    }

    if (entry.selected) {
        btn.classList.add('is-selected');
        btn.setAttribute('aria-selected', 'true');
    } else {
        btn.setAttribute('aria-selected', 'false');
    }

    btn.addEventListener('click', () => {
        if (btn.disabled) return;

        instance.select.selectedIndex = Number(btn.dataset.index);
        instance.select.dispatchEvent(new Event('change', { bubbles: true }));
        instance.syncFromSelect();
        instance.close();
        instance.trigger.focus();
    });

    btn.addEventListener('keydown', (event) => {
        const options = instance.getFocusableOptions();
        const currentIndex = options.indexOf(btn);

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            const next = options[currentIndex + 1] || options[0];
            next?.focus();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            const prev = options[currentIndex - 1] || options[options.length - 1];
            prev?.focus();
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            btn.click();
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            instance.close();
            instance.trigger.focus();
        }
    });

    return btn;
}

function resolveSelectTargets(root) {
    if (root instanceof HTMLSelectElement) return [root];
    if (root && typeof root.querySelectorAll === 'function') {
        return Array.from(root.querySelectorAll('select[data-lk-custom-select]'));
    }
    return Array.from(document.querySelectorAll('select[data-lk-custom-select]'));
}

function isInsideRoot(node, root) {
    if (!root || root === document) return true;
    if (node === root) return true;
    return typeof root.contains === 'function' ? root.contains(node) : false;
}

function createInstance(select) {
    const id = select.id || `lk-custom-${Math.random().toString(36).slice(2, 9)}`;
    if (!select.id) select.id = id;

    const config = getConfig(select);
    const container = document.createElement('div');
    container.className = `lk-custom-select lk-custom-select--${config.variant}`;
    container.dataset.for = id;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'lk-custom-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const label = document.createElement('span');
    label.className = 'lk-custom-select-label';

    const caret = document.createElement('span');
    caret.className = 'lk-custom-select-caret';
    caret.setAttribute('aria-hidden', 'true');

    trigger.appendChild(label);
    trigger.appendChild(caret);

    const menu = document.createElement('div');
    menu.className = 'lk-custom-select-menu';

    let searchInput = null;
    if (config.searchable) {
        const search = document.createElement('div');
        search.className = 'lk-custom-select-search';

        searchInput = document.createElement('input');
        searchInput.type = 'search';
        searchInput.className = 'lk-custom-select-search-input';
        searchInput.placeholder = config.searchPlaceholder;
        searchInput.setAttribute('aria-label', config.searchAriaLabel);
        searchInput.autocomplete = 'off';
        searchInput.spellcheck = false;

        search.appendChild(searchInput);
        menu.appendChild(search);
    }

    const optionsList = document.createElement('div');
    optionsList.className = 'lk-custom-select-options';
    optionsList.setAttribute('role', 'listbox');

    const emptyState = document.createElement('div');
    emptyState.className = 'lk-custom-select-empty';
    emptyState.hidden = true;
    emptyState.textContent = config.emptyText;

    menu.appendChild(optionsList);
    menu.appendChild(emptyState);

    container.appendChild(trigger);
    container.appendChild(menu);

    const instance = {
        id,
        select,
        config,
        container,
        trigger,
        label,
        menu,
        searchInput,
        optionsList,
        emptyState,
        observer: null,

        open() {
            if (this.select.disabled) return;
            closeAll(this.id);
            this.resetSearch();
            this.container.classList.add('is-open');
            this.trigger.setAttribute('aria-expanded', 'true');

            if (this.searchInput) {
                requestAnimationFrame(() => {
                    this.searchInput.focus();
                    this.searchInput.select();
                });
                return;
            }

            const selected = this.optionsList.querySelector('.lk-custom-select-option.is-selected:not([hidden]):not(:disabled)');
            const first = this.optionsList.querySelector('.lk-custom-select-option:not([hidden]):not(:disabled)');
            (selected || first)?.focus();
        },

        close() {
            this.container.classList.remove('is-open');
            this.trigger.setAttribute('aria-expanded', 'false');
            this.resetSearch();
        },

        getFocusableOptions() {
            return Array.from(this.optionsList.querySelectorAll('.lk-custom-select-option:not(:disabled):not([hidden])'));
        },

        updateEmptyState() {
            const hasVisibleOptions = this.getFocusableOptions().length > 0;
            this.emptyState.hidden = hasVisibleOptions;
        },

        applyFilter(query = '') {
            const normalizedQuery = normalizeText(query);
            const optionButtons = this.optionsList.querySelectorAll('.lk-custom-select-option');

            optionButtons.forEach((btn) => {
                const matches = !normalizedQuery || btn.dataset.search.includes(normalizedQuery);
                btn.hidden = !matches;
            });

            this.updateEmptyState();
        },

        resetSearch() {
            if (!this.searchInput) {
                this.updateEmptyState();
                return;
            }

            this.searchInput.value = '';
            this.applyFilter('');
        },

        renderOptions() {
            this.optionsList.innerHTML = '';
            const entries = getOptionEntries(this.select, this.config);

            entries.forEach((entry) => {
                const optionBtn = createOptionButton(this, entry);
                this.optionsList.appendChild(optionBtn);
            });

            this.syncFromSelect();
            this.applyFilter(this.searchInput?.value || '');
        },

        syncFromSelect() {
            const selected = getSelectedOption(this.select);
            const text = selected?.textContent?.trim() || '';
            const selectedIndex = this.select.selectedIndex;

            this.label.textContent = text;
            this.label.title = text;

            const hasValue = Boolean(this.select.value);
            this.container.classList.toggle('has-value', hasValue);
            this.container.classList.toggle('is-disabled', this.select.disabled);
            this.trigger.disabled = this.select.disabled;

            const optionButtons = this.optionsList.querySelectorAll('.lk-custom-select-option');
            optionButtons.forEach((btn) => {
                const selectedState = Number(btn.dataset.index) === selectedIndex;
                btn.classList.toggle('is-selected', selectedState);
                btn.setAttribute('aria-selected', selectedState ? 'true' : 'false');
            });

            if (this.select.closest('.lk-filter-chip-select')) {
                this.select.closest('.lk-filter-chip-select')?.classList.toggle('active', hasValue);
            }
        },

        bind() {
            this.trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                if (this.container.classList.contains('is-open')) {
                    this.close();
                } else {
                    this.open();
                }
            });

            this.trigger.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    this.open();
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    if (this.container.classList.contains('is-open')) this.close();
                    else this.open();
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    this.close();
                }
            });

            this.searchInput?.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    this.getFocusableOptions()[0]?.focus();
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    this.close();
                    this.trigger.focus();
                }
            });

            this.searchInput?.addEventListener('input', () => {
                this.applyFilter(this.searchInput.value);
            });

            this.select.addEventListener('change', () => {
                this.syncFromSelect();
            });

            this.observer = new MutationObserver(() => {
                this.renderOptions();
            });

            this.observer.observe(this.select, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled', 'label']
            });
        },

        destroy() {
            this.observer?.disconnect();
            this.container.remove();
            this.select.classList.remove('lk-custom-select-ready');
            this.select.closest('.lk-filter-chip-select')?.classList.remove('has-custom-select');
            this.select.closest('.lk-select-wrapper')?.classList.remove('has-custom-select');
        }
    };

    select.classList.add('lk-custom-select-ready');
    select.closest('.lk-filter-chip-select')?.classList.add('has-custom-select');
    select.closest('.lk-select-wrapper')?.classList.add('has-custom-select');
    select.insertAdjacentElement('afterend', container);

    instance.renderOptions();
    instance.bind();

    return instance;
}

export function syncCustomSelects(root = document) {
    document.dispatchEvent(new CustomEvent('lk:custom-select-sync', {
        detail: { root }
    }));
}

export const CustomSelectManager = {
    init(root = document) {
        bindGlobalEvents();

        const selects = resolveSelectTargets(root);
        selects.forEach((select) => {
            if (INSTANCES.has(select)) {
                INSTANCES.get(select)?.renderOptions();
                return;
            }

            const leaked = select.parentElement?.querySelector(`.lk-custom-select[data-for="${select.id}"]`);
            if (leaked) leaked.remove();

            const instance = createInstance(select);
            INSTANCES.set(select, instance);
        });
    },

    syncAll(root = document) {
        INSTANCES.forEach((instance, select) => {
            if (select.isConnected && isInsideRoot(select, root)) {
                instance.renderOptions();
            }
        });
    },

    destroyAll(root = document) {
        INSTANCES.forEach((instance, select) => {
            if (!select.isConnected || isInsideRoot(select, root)) {
                instance.destroy();
                INSTANCES.delete(select);
            }
        });
    }
};
