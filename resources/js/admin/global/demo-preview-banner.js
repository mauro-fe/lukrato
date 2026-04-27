import '../../../css/admin/modules/demo-preview-banner.css';
import { escapeHtml } from '../shared/utils.js';
import { openPrimaryAction, resolvePrimaryActionMeta } from '../shared/primary-actions.js';

const GENERIC_TITLE = 'Dados de exemplo';
const GENERIC_MESSAGE = 'Esses dados existem só para mostrar como o Lukrato funciona. Assim que você criar seus primeiros registros reais, a demonstração desaparece automaticamente.';
const DEFAULT_EYEBROW = 'Configuração inicial';
const DEFAULT_TITLE = 'Cadastre sua primeira conta';
const DEFAULT_MESSAGE = 'Comece pela base do seu fluxo financeiro. Assim que a conta for criada, o painel passa a refletir a sua operação.';
const DEFAULT_PREVIEW_BADGE = 'Modo de demonstração';
const DEFAULT_PREVIEW_TEXT = 'Você está usando dados de exemplo enquanto finaliza a configuração inicial.';
const DEFAULT_INFO_TITLE = 'Sobre a prévia';
const DEFAULT_INFO_MESSAGE = 'Os dados de exemplo servem só para você conhecer o fluxo antes do primeiro cadastro real.';
const DEFAULT_STORAGE_NOTE = 'Não salva nada no banco';
const DEFAULT_EXIT_NOTE = 'Sai automaticamente ao criar dado real';

function refreshIcons() {
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}

function buildViewModel(meta = {}) {
    const primaryAction = resolvePrimaryActionMeta(meta, {
        actionType: 'create_account',
        ctaUrl: 'contas',
        ctaLabel: 'Criar primeira conta',
    });

    const rawTitle = String(meta.title || '').trim();
    const rawMessage = String(meta.message || '').trim();

    return {
        primaryAction,
        context: String(meta.context || 'preview').trim() || 'preview',
        eyebrow: String(meta.eyebrow || DEFAULT_EYEBROW).trim() || DEFAULT_EYEBROW,
        title: rawTitle === '' || rawTitle === GENERIC_TITLE ? DEFAULT_TITLE : rawTitle,
        message: rawMessage === '' || rawMessage === GENERIC_MESSAGE ? DEFAULT_MESSAGE : rawMessage,
        previewBadge: String(meta.highlight_label || meta.preview_label || DEFAULT_PREVIEW_BADGE).trim() || DEFAULT_PREVIEW_BADGE,
        previewText: String(meta.preview_text || DEFAULT_PREVIEW_TEXT).trim() || DEFAULT_PREVIEW_TEXT,
        infoTitle: String(meta.info_title || DEFAULT_INFO_TITLE).trim() || DEFAULT_INFO_TITLE,
        infoMessage: String(meta.info_message || DEFAULT_INFO_MESSAGE).trim() || DEFAULT_INFO_MESSAGE,
        storageNote: String(meta.storage_note || DEFAULT_STORAGE_NOTE).trim() || DEFAULT_STORAGE_NOTE,
        exitNote: String(meta.exit_note || DEFAULT_EXIT_NOTE).trim() || DEFAULT_EXIT_NOTE,
    };
}

function createMarkup(viewModel) {
    const eyebrow = escapeHtml(viewModel.eyebrow);
    const title = escapeHtml(viewModel.title);
    const message = escapeHtml(viewModel.message);
    const previewBadge = escapeHtml(viewModel.previewBadge);
    const previewText = escapeHtml(viewModel.previewText);
    const ctaLabel = escapeHtml(viewModel.primaryAction.ctaLabel);
    const ctaAction = escapeHtml(viewModel.primaryAction.actionType);
    const ctaUrl = escapeHtml(viewModel.primaryAction.ctaUrl);
    const context = escapeHtml(viewModel.context);

    return `
        <div class="lk-demo-banner surface-card surface-card--interactive" role="status" aria-live="polite" data-demo-context="${context}">
            <div class="lk-demo-banner__main">
                <span class="lk-demo-banner__eyebrow">${eyebrow}</span>
                <strong class="lk-demo-banner__title">${title}</strong>
                <p class="lk-demo-banner__message">${message}</p>
                <div class="lk-demo-banner__meta" aria-label="Informações sobre o preview">
                    <span class="lk-demo-banner__meta-badge">
                        <i data-lucide="sparkles"></i>
                        <span>${previewBadge}</span>
                    </span>
                    <span class="lk-demo-banner__meta-text">${previewText}</span>
                    <button type="button" class="lk-demo-banner__inline-link" data-demo-preview-info>
                        Sobre a prévia
                    </button>
                </div>
            </div>
            <div class="lk-demo-banner__actions">
                <button
                    type="button"
                    class="lk-demo-banner__cta surface-button surface-button--primary"
                    data-demo-cta
                    data-demo-cta-action="${ctaAction}"
                    data-demo-cta-url="${ctaUrl}"
                >
                    <i data-lucide="plus"></i>
                    <span>${ctaLabel}</span>
                </button>
            </div>
        </div>
    `;
}

function showPreviewInfo(viewModel) {
    const detailLines = [viewModel.infoMessage, viewModel.storageNote, viewModel.exitNote].filter(Boolean);
    const detailText = detailLines.join(' ');

    if (typeof window.Swal !== 'undefined') {
        window.Swal.fire({
            icon: 'info',
            title: viewModel.infoTitle,
            text: detailText,
            confirmButtonText: 'Entendi',
        });
        return;
    }

    if (window.LKFeedback?.info) {
        window.LKFeedback.info(detailText);
        return;
    }

    window.alert(detailText);
}

function bindActions(root, meta, viewModel) {
    root.querySelector('[data-demo-cta]')?.addEventListener('click', (event) => {
        event.preventDefault();

        openPrimaryAction(
            {
                ...meta,
                cta_action: event.currentTarget?.dataset?.demoCtaAction || meta.cta_action || meta.primary_action,
                cta_url: event.currentTarget?.dataset?.demoCtaUrl || meta.cta_url,
            },
            {
                actionType: 'create_account',
                ctaUrl: 'contas',
                ctaLabel: viewModel.primaryAction.ctaLabel,
            }
        );
    });

    root.querySelector('[data-demo-preview-info]')?.addEventListener('click', () => {
        showPreviewInfo(viewModel);
    });
}

const DemoPreviewBanner = {
    root: null,
    currentKey: null,

    ensureRoot() {
        if (!this.root) {
            this.root = document.getElementById('lk-demo-banner-root');
        }

        return this.root;
    },

    show(meta = {}) {
        if (!meta || meta.is_demo !== true) {
            this.hide();
            return;
        }

        const root = this.ensureRoot();
        if (!root) {
            return;
        }

        const viewModel = buildViewModel(meta);
        const nextKey = JSON.stringify({
            ...viewModel,
            primaryAction: viewModel.primaryAction,
            real_account_count: meta.real_account_count || 0,
        });

        if (this.currentKey === nextKey) {
            return;
        }

        this.currentKey = nextKey;
        root.innerHTML = createMarkup(viewModel);
        root.hidden = false;
        bindActions(root, meta, viewModel);
        refreshIcons();
    },

    hide() {
        const root = this.ensureRoot();
        if (!root) {
            return;
        }

        this.currentKey = null;
        root.innerHTML = '';
        root.hidden = true;
    },
};

window.LKDemoPreviewBanner = DemoPreviewBanner;

export { DemoPreviewBanner };
