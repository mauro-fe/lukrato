import '../../../css/admin/modules/demo-preview-banner.css';
import { escapeHtml } from '../shared/utils.js';
import { openPrimaryAction, resolvePrimaryActionMeta } from '../shared/primary-actions.js';

function refreshIcons() {
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}

function createMarkup(meta = {}) {
    const primaryAction = resolvePrimaryActionMeta(meta, {
        actionType: 'create_account',
        ctaUrl: 'contas',
        ctaLabel: 'Criar primeira conta',
    });
    const eyebrow = escapeHtml(String(meta.eyebrow || 'Preview guiado'));
    const title = escapeHtml(String(meta.title || 'Dados de exemplo'));
    const message = escapeHtml(String(
        meta.message
        || 'Esses dados existem só para mostrar como o app funciona. Assim que você criar seus primeiros registros reais, a demonstração desaparece automaticamente.'
    ));
    const context = escapeHtml(String(meta.context || 'preview'));
    const highlightLabel = escapeHtml(String(meta.highlight_label || 'Somente visualização'));
    const guideItems = [
        {
            icon: 'database',
            label: escapeHtml(String(meta.storage_note || 'Não salva nada no banco')),
        },
        {
            icon: 'refresh-cw',
            label: escapeHtml(String(meta.exit_note || 'Sai automaticamente ao criar dado real')),
        },
    ];

    return `
        <div class="lk-demo-banner surface-card surface-card--glass surface-card--clip" role="status" aria-live="polite" data-demo-context="${context}">
            <div class="lk-demo-banner__main">
                <div class="lk-demo-banner__icon" aria-hidden="true">
                    <i data-lucide="sparkles"></i>
                </div>
                <div class="lk-demo-banner__content">
                    <div class="lk-demo-banner__topline">
                        <span class="lk-demo-banner__eyebrow">
                            <i data-lucide="sparkles"></i>
                            ${eyebrow}
                        </span>
                        <span class="lk-demo-banner__tag">
                            <i data-lucide="eye"></i>
                            ${highlightLabel}
                        </span>
                    </div>
                    <strong class="lk-demo-banner__title">${title}</strong>
                    <p class="lk-demo-banner__message">${message}</p>
                    <div class="lk-demo-banner__meta" aria-label="Informações sobre o preview">
                        ${guideItems.map((item) => `
                            <span class="lk-demo-banner__meta-item">
                                <i data-lucide="${item.icon}"></i>
                                <span>${item.label}</span>
                            </span>
                        `).join('')}
                    </div>
                </div>
            </div>
            <div class="lk-demo-banner__actions">
                <a
                    class="lk-demo-banner__link surface-button surface-button--primary"
                    href="${escapeHtml(primaryAction.ctaUrl)}"
                    data-demo-cta-action="${escapeHtml(primaryAction.actionType)}"
                >
                    <i data-lucide="plus"></i>
                    <span>${escapeHtml(primaryAction.ctaLabel)}</span>
                </a>
            </div>
        </div>
    `;
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

        const nextKey = JSON.stringify({
            eyebrow: meta.eyebrow || '',
            title: meta.title || '',
            message: meta.message || '',
            cta_label: meta.cta_label || '',
            cta_url: meta.cta_url || '',
            cta_action: meta.cta_action || '',
            context: meta.context || '',
            highlight_label: meta.highlight_label || '',
            primary_action: meta.primary_action || '',
            real_account_count: meta.real_account_count || 0,
            storage_note: meta.storage_note || '',
            exit_note: meta.exit_note || '',
        });

        if (this.currentKey === nextKey) {
            return;
        }

        this.currentKey = nextKey;
        root.innerHTML = createMarkup(meta);
        root.hidden = false;
        root.querySelector('.lk-demo-banner__link')?.addEventListener('click', (event) => {
            event.preventDefault();

            openPrimaryAction(
                {
                    ...meta,
                    cta_action: event.currentTarget?.dataset?.demoCtaAction || meta.cta_action || meta.primary_action,
                    cta_url: event.currentTarget?.getAttribute('href') || meta.cta_url,
                },
                {
                    actionType: 'create_account',
                    ctaUrl: 'contas',
                    ctaLabel: 'Criar primeira conta',
                }
            );
        });
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
