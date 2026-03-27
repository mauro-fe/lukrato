/**
 * Metas – Entry point
 * Bootstraps MetasApp and exposes window.metasManager
 */

import '../../../css/admin/metas/index.css';
import { MetasApp } from './app.js';
import { Utils } from './state.js';

// ── Global API ─────────────────────────────────────────────────

window.metasManager = {
    openMetaModal: (id) => MetasApp.openMetaModal(id),
    deleteMeta: (id) => MetasApp.deleteMeta(id),
    openAporteModal: (id) => MetasApp.openAporteModal(id),
    useTemplate: (tmpl) => MetasApp.useTemplate(tmpl),
    loadAll: () => MetasApp.loadAll(),
    formatarDinheiro: (input) => Utils.formatarDinheiro(input),
};

// ── Bootstrap ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => MetasApp.init());
