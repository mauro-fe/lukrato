/**
 * ============================================================================
 * LUKRATO — Dashboard / Customize (Modal de Personalização)
 * ============================================================================
 * Gerencia o modal de personalização do dashboard:
 * - Abrir/fechar modal
 * - Checkboxes para seções opcionais
 * - Persistência em localStorage
 * - Toggle de visibilidade das seções
 * Preparado para integração com backend (POST /api/preferences).
 * ============================================================================
 */

import { Modules } from './state.js';

const STORAGE_KEY = 'lk_dashboard_prefs';

/** Mapeamento: checkbox ID → seção do dashboard */
const SECTION_MAP = {
    toggleGrafico: 'chart-section',
    toggleMetas: 'sectionMetas',
    toggleCartoes: 'sectionCartoes',
    toggleContas: 'sectionContas'
};

/** Preferências padrão */
const DEFAULTS = {
    toggleGrafico: true,
    toggleMetas: false,
    toggleCartoes: false,
    toggleContas: false
};

/* ─── Helpers ─────────────────────────────────────────────────────────── */

function loadPrefs() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return { ...DEFAULTS };
        return { ...DEFAULTS, ...JSON.parse(raw) };
    } catch {
        return { ...DEFAULTS };
    }
}

function savePrefs(prefs) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // localStorage cheio ou indisponível — falha silenciosa
    }
}

/* ─── Apply ───────────────────────────────────────────────────────────── */

function applyPrefs(prefs) {
    Object.entries(SECTION_MAP).forEach(([checkboxId, sectionId]) => {
        const section = document.getElementById(sectionId);
        if (!section) return;
        section.style.display = prefs[checkboxId] ? '' : 'none';
    });
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

    savePrefs(prefs);
    applyPrefs(prefs);
    closeModal();

    // Re-render ícones Lucide nas seções que podem ter sido reveladas
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}

/* ─── Init ────────────────────────────────────────────────────────────── */

export function initCustomize() {
    // Aplicar preferências salvas na carga da página
    const prefs = loadPrefs();
    applyPrefs(prefs);

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
