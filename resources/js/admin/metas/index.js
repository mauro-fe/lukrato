/**
 * Metas – Entry point
 * Bootstraps MetasApp and exposes window.metasManager
 */

import '../../../css/admin/metas/index.css';
import { MetasApp } from './app.js';
import { Utils } from './state.js';
import { initCustomize } from './customize.js';

// ── Global API ─────────────────────────────────────────────────

window.metasManager = {
    openMetaModal: (id) => MetasApp.openMetaModal(id),
    deleteMeta: (id) => MetasApp.deleteMeta(id),
    useTemplate: (tmpl) => MetasApp.useTemplate(tmpl),
    openTemplates: () => MetasApp.openTemplates(),
    loadAll: () => MetasApp.loadAll(),
    formatarDinheiro: (input) => Utils.formatarDinheiro(input),
};

// ── Bootstrap ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initCustomize();
    MetasApp.init();
});
