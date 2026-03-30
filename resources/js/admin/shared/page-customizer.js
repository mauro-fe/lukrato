/**
 * Shared UI page customizer.
 * Reusable engine for:
 * - first-run essential preset
 * - complete preset
 * - local cache + remote sync
 * - modal open/save flow
 */

const DEFAULT_MODAL_CONFIG = {
    overlayId: 'customizeModalOverlay',
    openButtonId: 'btnCustomizeDashboard',
    closeButtonId: 'btnCloseCustomize',
    saveButtonId: 'btnSaveCustomize',
    presetEssentialButtonId: 'btnPresetEssencial',
    presetCompleteButtonId: 'btnPresetCompleto'
};

function pickKnownPrefs(raw, toggleKeys) {
    if (!raw || typeof raw !== 'object') return {};

    const picked = {};
    toggleKeys.forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(raw, key)) {
            picked[key] = !!raw[key];
        }
    });
    return picked;
}

function hasKnownPrefs(raw, toggleKeys) {
    return Object.keys(pickKnownPrefs(raw, toggleKeys)).length > 0;
}

function mergeWithDefaults(raw, defaults, toggleKeys) {
    return {
        ...defaults,
        ...pickKnownPrefs(raw, toggleKeys)
    };
}

function safeRun(callback, fallback = null) {
    try {
        return callback();
    } catch {
        return fallback;
    }
}

export function createPageCustomizer(config) {
    const sectionMap = config?.sectionMap ?? {};
    const toggleKeys = Object.keys(sectionMap);

    if (toggleKeys.length === 0) {
        throw new Error('createPageCustomizer requires at least one toggle in sectionMap.');
    }

    const completeDefaults = mergeWithDefaults(config?.completeDefaults ?? {}, {}, toggleKeys);
    const essentialDefaults = mergeWithDefaults(config?.essentialDefaults ?? completeDefaults, completeDefaults, toggleKeys);
    const storageKey = String(config?.storageKey || 'lk_ui_page_prefs');
    const modal = { ...DEFAULT_MODAL_CONFIG, ...(config?.modal ?? {}) };
    const gridContainerId = config?.gridContainerId ? String(config.gridContainerId) : null;
    const gridToggleKeys = Array.isArray(config?.gridToggleKeys) && config.gridToggleKeys.length > 0
        ? config.gridToggleKeys
        : [];

    const state = {
        prefsVersion: 0
    };

    function getPreset(name) {
        return name === 'completo'
            ? { ...completeDefaults }
            : { ...essentialDefaults };
    }

    function loadLocalCacheRaw() {
        return safeRun(() => {
            const raw = localStorage.getItem(storageKey);
            if (!raw) return null;
            return JSON.parse(raw);
        }, null);
    }

    function loadLocalCache() {
        const raw = loadLocalCacheRaw();
        if (!raw) return null;
        return mergeWithDefaults(raw, completeDefaults, toggleKeys);
    }

    function hasSavedLocalPrefs() {
        return hasKnownPrefs(loadLocalCacheRaw(), toggleKeys);
    }

    function saveLocalCache(prefs) {
        safeRun(() => {
            localStorage.setItem(storageKey, JSON.stringify(prefs));
        });
    }

    async function loadPrefsFromRemote() {
        if (typeof config?.loadPreferences !== 'function') {
            return { hasRemotePrefs: null, prefs: null };
        }

        try {
            const rawPrefs = await config.loadPreferences();

            if (hasKnownPrefs(rawPrefs, toggleKeys)) {
                const merged = mergeWithDefaults(rawPrefs, completeDefaults, toggleKeys);
                saveLocalCache(merged);
                return { hasRemotePrefs: true, prefs: merged };
            }

            return { hasRemotePrefs: false, prefs: null };
        } catch {
            return { hasRemotePrefs: null, prefs: null };
        }
    }

    async function savePrefsToRemote(prefs) {
        saveLocalCache(prefs);

        if (typeof config?.savePreferences !== 'function') {
            return;
        }

        try {
            await config.savePreferences(prefs);
        } catch {
            // Keep local cache; sync can happen later.
        }
    }

    function loadPrefs() {
        return loadLocalCache() ?? { ...essentialDefaults };
    }

    function applyPrefs(prefs) {
        Object.entries(sectionMap).forEach(([checkboxId, sectionId]) => {
            const section = document.getElementById(sectionId);
            if (!section) return;
            section.style.display = prefs[checkboxId] ? '' : 'none';
        });

        if (gridContainerId) {
            const grid = document.getElementById(gridContainerId);
            if (grid) {
                const anyVisible = gridToggleKeys.some((key) => prefs[key]);
                grid.style.display = anyVisible ? '' : 'none';
            }
        }

        if (typeof config?.onApply === 'function') {
            config.onApply(prefs);
        }
    }

    function syncCheckboxes(prefs) {
        toggleKeys.forEach((checkboxId) => {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                checkbox.checked = !!prefs[checkboxId];
            }
        });
    }

    function collectPrefsFromModal() {
        const prefs = {};
        toggleKeys.forEach((checkboxId) => {
            const checkbox = document.getElementById(checkboxId);
            prefs[checkboxId] = checkbox ? checkbox.checked : !!completeDefaults[checkboxId];
        });
        return prefs;
    }

    function openModal() {
        const overlay = document.getElementById(modal.overlayId);
        if (!overlay) return;

        syncCheckboxes(loadPrefs());
        overlay.style.display = 'flex';

        const handler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handler);
            }
        };
        document.addEventListener('keydown', handler);
    }

    function closeModal() {
        const overlay = document.getElementById(modal.overlayId);
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function applyPresetToModal(presetName) {
        syncCheckboxes(getPreset(presetName));
    }

    function saveAndClose() {
        const prefs = collectPrefsFromModal();

        state.prefsVersion += 1;
        saveLocalCache(prefs);
        applyPrefs(prefs);
        closeModal();
        savePrefsToRemote(prefs);

        if (typeof config?.onSave === 'function') {
            config.onSave(prefs);
        }

        if (config?.refreshLucideIcons !== false && typeof window.lucide !== 'undefined') {
            window.lucide.createIcons();
        }
    }

    function bindPresetActions() {
        const btnPresetEssencial = document.getElementById(modal.presetEssentialButtonId);
        const btnPresetCompleto = document.getElementById(modal.presetCompleteButtonId);

        if (btnPresetEssencial) {
            btnPresetEssencial.addEventListener('click', () => applyPresetToModal('essencial'));
        }

        if (btnPresetCompleto) {
            btnPresetCompleto.addEventListener('click', () => applyPresetToModal('completo'));
        }
    }

    function bindModalActions() {
        const btnOpen = document.getElementById(modal.openButtonId);
        const btnClose = document.getElementById(modal.closeButtonId);
        const btnSave = document.getElementById(modal.saveButtonId);
        const overlay = document.getElementById(modal.overlayId);

        if (btnOpen) {
            btnOpen.addEventListener('click', openModal);
        }

        if (btnClose) {
            btnClose.addEventListener('click', closeModal);
        }

        if (btnSave) {
            btnSave.addEventListener('click', saveAndClose);
        }

        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeModal();
                }
            });
        }
    }

    function init() {
        const localPrefs = loadLocalCache();
        const hasLocalPrefs = hasSavedLocalPrefs();

        // Fast paint: use local cache; fallback to essential.
        applyPrefs(localPrefs ?? { ...essentialDefaults });

        const initialVersion = state.prefsVersion;
        loadPrefsFromRemote().then((remote) => {
            if (state.prefsVersion !== initialVersion) return;

            if (remote.hasRemotePrefs === true && remote.prefs) {
                applyPrefs(remote.prefs);
                return;
            }

            // First real access (no local and no remote): persist essential.
            if (remote.hasRemotePrefs === false && !hasLocalPrefs) {
                const essentialPrefs = getPreset('essencial');
                state.prefsVersion += 1;
                saveLocalCache(essentialPrefs);
                applyPrefs(essentialPrefs);
                savePrefsToRemote(essentialPrefs);
                return;
            }

            // Offline/local-only case: sync local once remote is reachable.
            if (remote.hasRemotePrefs === false && hasLocalPrefs && localPrefs) {
                savePrefsToRemote(localPrefs);
            }
        });

        bindModalActions();
        bindPresetActions();
    }

    return {
        init,
        open: openModal,
        close: closeModal,
        applyPreset: applyPresetToModal
    };
}

