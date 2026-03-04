/**
 * LUKRATO - Custom selects for lancamentos filters/export
 * Keeps native <select> as source of truth and syncs a themed custom UI on top.
 */

const INSTANCES = new Map();
let GLOBAL_EVENTS_BOUND = false;

function closeAll(exceptId = null) {
    INSTANCES.forEach((instance, id) => {
        if (id !== exceptId) instance.close();
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

    document.addEventListener('lk:custom-select-sync', () => {
        CustomSelectManager.syncAll();
    });
}

function getVariant(select) {
    if (select.dataset.lkCustomSelect) return select.dataset.lkCustomSelect;
    if (select.closest('.lk-filter-chip-select')) return 'chip';
    return 'export';
}

function getSelectedOption(select) {
    if (!select) return null;
    const selected = select.options[select.selectedIndex];
    return selected || null;
}

function createOptionButton(instance, opt, index) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'lk-custom-select-option';
    btn.dataset.value = opt.value;
    btn.dataset.index = String(index);
    btn.setAttribute('role', 'option');
    btn.setAttribute('tabindex', '-1');
    btn.textContent = opt.textContent || '';

    if (opt.disabled) {
        btn.disabled = true;
    }

    if (opt.selected) {
        btn.classList.add('is-selected');
        btn.setAttribute('aria-selected', 'true');
    } else {
        btn.setAttribute('aria-selected', 'false');
    }

    btn.addEventListener('click', () => {
        if (btn.disabled) return;
        instance.select.value = opt.value;
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

function createInstance(select) {
    const id = select.id || `lk-custom-${Math.random().toString(36).slice(2, 9)}`;
    const variant = getVariant(select);

    const container = document.createElement('div');
    container.className = `lk-custom-select lk-custom-select--${variant}`;
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
    menu.setAttribute('role', 'listbox');

    container.appendChild(trigger);
    container.appendChild(menu);

    const instance = {
        id,
        select,
        container,
        trigger,
        menu,
        label,
        observer: null,

        open() {
            if (this.select.disabled) return;
            closeAll(this.id);
            this.container.classList.add('is-open');
            this.trigger.setAttribute('aria-expanded', 'true');
            const selected = this.menu.querySelector('.lk-custom-select-option.is-selected:not(:disabled)');
            const first = this.menu.querySelector('.lk-custom-select-option:not(:disabled)');
            (selected || first)?.focus();
        },

        close() {
            this.container.classList.remove('is-open');
            this.trigger.setAttribute('aria-expanded', 'false');
        },

        getFocusableOptions() {
            return Array.from(this.menu.querySelectorAll('.lk-custom-select-option:not(:disabled)'));
        },

        renderOptions() {
            this.menu.innerHTML = '';
            Array.from(this.select.options).forEach((opt, index) => {
                const optionBtn = createOptionButton(this, opt, index);
                this.menu.appendChild(optionBtn);
            });
            this.syncFromSelect();
        },

        syncFromSelect() {
            const selected = getSelectedOption(this.select);
            const text = selected?.textContent?.trim() || '';
            this.label.textContent = text;

            const hasValue = Boolean(this.select.value);
            this.container.classList.toggle('has-value', hasValue);
            this.container.classList.toggle('is-disabled', this.select.disabled);
            this.trigger.disabled = this.select.disabled;

            const optionButtons = this.menu.querySelectorAll('.lk-custom-select-option');
            optionButtons.forEach((btn) => {
                const selectedState = btn.dataset.value === this.select.value;
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
                attributeFilter: ['disabled']
            });
        },

        destroy() {
            this.observer?.disconnect();
            this.container.remove();
            this.select.classList.remove('lk-native-select-hidden');
            this.select.classList.remove('lk-custom-select-ready');
            if (this.select.closest('.lk-filter-chip-select')) {
                this.select.closest('.lk-filter-chip-select')?.classList.remove('has-custom-select');
            }
        }
    };

    select.classList.add('lk-native-select-hidden');
    select.classList.add('lk-custom-select-ready');
    if (select.closest('.lk-filter-chip-select')) {
        select.closest('.lk-filter-chip-select')?.classList.add('has-custom-select');
    }
    select.insertAdjacentElement('afterend', container);

    instance.renderOptions();
    instance.bind();

    return instance;
}

export const CustomSelectManager = {
    init(root = document) {
        bindGlobalEvents();

        const selects = root.querySelectorAll('select[data-lk-custom-select]');
        selects.forEach((select) => {
            if (INSTANCES.has(select)) {
                INSTANCES.get(select)?.syncFromSelect();
                return;
            }

            const leaked = select.parentElement?.querySelector(`.lk-custom-select[data-for="${select.id}"]`);
            if (leaked) {
                leaked.remove();
            }

            const instance = createInstance(select);
            INSTANCES.set(select, instance);
        });
    },

    syncAll() {
        INSTANCES.forEach((instance) => instance.syncFromSelect());
    },

    destroyAll() {
        INSTANCES.forEach((instance, select) => {
            instance.destroy();
            INSTANCES.delete(select);
        });
    }
};
