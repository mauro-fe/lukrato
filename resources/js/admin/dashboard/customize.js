/**
 * ============================================================================
 * LUKRATO — Dashboard / Customize (Modal de Personalização)
 * ============================================================================
 * Gerencia o modal de personalização do dashboard:
 * - Abrir/fechar modal
 * - Checkboxes para seções opcionais
 * - Persistência no banco via API (com localStorage como cache/fallback)
 * - Toggle de visibilidade das seções
 * ============================================================================
 */

import { CONFIG, Modules } from './state.js';
import { apiGet, apiPost } from '../shared/api.js';

const STORAGE_KEY = 'lk_dashboard_prefs';
let prefsVersion = 0;

/** Mapeamento: checkbox ID → seção do dashboard */
const SECTION_MAP = {
    // Principais
    toggleHealthScore: 'sectionHealthScore',
    toggleAlertas: 'sectionAlertas',
    toggleGrafico: 'chart-section',
    togglePrevisao: 'sectionPrevisao',
    // Extras (grid)
    toggleMetas: 'sectionMetas',
    toggleCartoes: 'sectionCartoes',
    toggleContas: 'sectionContas',
    toggleOrcamentos: 'sectionOrcamentos',
    toggleFaturas: 'sectionFaturas',
    // Standalone
    toggleGamificacao: 'sectionGamificacao'
};

/** Preferências padrão */
const DEFAULTS = {
    toggleHealthScore: true,
    toggleAlertas: true,
    toggleGrafico: true,
    togglePrevisao: true,
    toggleMetas: false,
    toggleCartoes: false,
    toggleContas: false,
    toggleOrcamentos: false,
    toggleFaturas: false,
    toggleGamificacao: false
};

/* ─── Persistence (API + localStorage cache) ──────────────────────────── */

function loadLocalCache() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        return { ...DEFAULTS, ...JSON.parse(raw) };
    } catch {
        return null;
    }
}

function saveLocalCache(prefs) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // silently ignore
    }
}

async function loadPrefsFromApi() {
    try {
        const res = await apiGet(`${CONFIG.API_URL}perfil/dashboard-preferences`);
        const data = res?.data ?? res;
        const prefs = data?.preferences;
        if (prefs && typeof prefs === 'object' && Object.keys(prefs).length > 0) {
            const merged = { ...DEFAULTS, ...prefs };
            saveLocalCache(merged);
            return merged;
        }
        // API responded but no prefs saved yet — apply defaults and clear stale cache
        saveLocalCache(DEFAULTS);
        return { ...DEFAULTS };
    } catch {
        // API unavailable — fall through to cache
    }
    return null;
}

async function savePrefsToApi(prefs) {
    saveLocalCache(prefs);
    try {
        await apiPost(`${CONFIG.API_URL}perfil/dashboard-preferences`, prefs);
    } catch {
        // Saved locally, will sync on next successful load
    }
}

function loadPrefs() {
    return loadLocalCache() ?? { ...DEFAULTS };
}

/* ─── Apply ───────────────────────────────────────────────────────────── */

function applyPrefs(prefs) {
    Object.entries(SECTION_MAP).forEach(([checkboxId, sectionId]) => {
        const section = document.getElementById(sectionId);
        if (!section) return;
        section.style.display = prefs[checkboxId] ? '' : 'none';
    });

    // Show/hide the optional grid container based on whether any grid section is visible
    const grid = document.getElementById('optionalGrid');
    if (grid) {
        const GRID_TOGGLES = ['toggleMetas', 'toggleCartoes', 'toggleContas', 'toggleOrcamentos', 'toggleFaturas'];
        const anyVisible = GRID_TOGGLES.some(k => prefs[k]);
        grid.style.display = anyVisible ? '' : 'none';
    }
}

function syncCheckboxes(prefs) {
    Object.keys(SECTION_MAP).forEach((checkboxId) => {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox) {
            checkbox.checked = !!prefs[checkboxId];
        }
    });
}

/* ─── Modal ───────────────────────────────────────────────────────────── */

function openModal() {
    const overlay = document.getElementById('customizeModalOverlay');
    if (!overlay) return;

    syncCheckboxes(loadPrefs());
    overlay.style.display = 'flex';

    // Focus trap: fechar com Escape
    const handler = (e) => {
        if (e.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', handler);
        }
    };
    document.addEventListener('keydown', handler);
}

function closeModal() {
    const overlay = document.getElementById('customizeModalOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function saveAndClose() {
    const prefs = {};
    Object.keys(SECTION_MAP).forEach((checkboxId) => {
        const checkbox = document.getElementById(checkboxId);
        prefs[checkboxId] = checkbox ? checkbox.checked : DEFAULTS[checkboxId];
    });

    prefsVersion += 1;
    saveLocalCache(prefs);
    applyPrefs(prefs);
    closeModal();
    savePrefsToApi(prefs);

    // Re-render ícones Lucide nas seções que podem ter sido reveladas
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}

/* ─── Init ────────────────────────────────────────────────────────────── */

export function initCustomize() {
    // 1) Aplicar cache local imediatamente para evitar flicker e preservar a última escolha do usuário
    applyPrefs(loadPrefs());

    // 2) Buscar do banco — se tiver preferências salvas, reaplicar
    const initialVersion = prefsVersion;
    loadPrefsFromApi().then((apiPrefs) => {
        if (apiPrefs && prefsVersion === initialVersion) {
            applyPrefs(apiPrefs);
        }
    });

    // Botão de abrir
    const btnOpen = document.getElementById('btnCustomizeDashboard');
    if (btnOpen) {
        btnOpen.addEventListener('click', openModal);
    }

    // Botão de fechar
    const btnClose = document.getElementById('btnCloseCustomize');
    if (btnClose) {
        btnClose.addEventListener('click', closeModal);
    }

    // Botão salvar
    const btnSave = document.getElementById('btnSaveCustomize');
    if (btnSave) {
        btnSave.addEventListener('click', saveAndClose);
    }

    // Fechar ao clicar no overlay
    const overlay = document.getElementById('customizeModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal();
            }
        });
    }
}

// ─── Register module ─────────────────────────────────────────────────────
Modules.Customize = { init: initCustomize, open: openModal, close: closeModal };
