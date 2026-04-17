import { apiGet, getBaseUrl } from './api.js';
import { resolveLancamentosUsageEndpoint } from '../api/endpoints/lancamentos.js';
import { escapeHtml } from './utils.js';

(() => {
    const root = document.getElementById('lk-usage-banner-root');
    if (!root) return;

    const baseUrl = getBaseUrl();
    const now = new Date();
    const ym = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    const dismissedKey = `lk_usage_banner_dismissed_${ym}`;

    if (localStorage.getItem(dismissedKey) === '1') return;

    void apiGet(resolveLancamentosUsageEndpoint(), { month: ym })
        .then((json) => {
            const data = json?.data ?? json;
            const msg = data?.ui_message;
            const usage = data?.usage;
            const upgradeCta = data?.upgrade_cta;

            if (!msg || !usage || usage.plan !== 'free' || !usage.should_warn) {
                return;
            }

            const percentage = usage.percentage || 0;
            const isCritical = percentage >= 90;
            const bannerClass = isCritical ? 'lk-usage-banner lk-usage-banner--critical' : 'lk-usage-banner';
            const icon = isCritical ? '🔴' : '⚠️';

            root.innerHTML = `
                <div class="${bannerClass}" role="alert" aria-live="polite">
                    <div class="lk-usage-banner__left">
                        <div class="lk-usage-banner__icon">${icon}</div>
                        <div class="lk-usage-banner__content">
                            <div class="lk-usage-banner__text">${msg}</div>
                            <div class="lk-usage-banner__progress">
                                <div class="lk-usage-banner__progress-bar"
                                     style="width: ${percentage}%"
                                     role="progressbar"
                                     aria-valuenow="${percentage}"
                                     aria-valuemin="0"
                                     aria-valuemax="100"></div>
                            </div>
                            <div class="lk-usage-banner__stats">
                                <span class="lk-usage-banner__stat">
                                    <span class="lk-usage-banner__stat-value">${usage.used}</span>
                                    <span>utilizados</span>
                                </span>
                                <span class="lk-usage-banner__stat">
                                    <span class="lk-usage-banner__stat-value">${usage.remaining}</span>
                                    <span>restantes</span>
                                </span>
                                <span class="lk-usage-banner__stat">
                                    <span class="lk-usage-banner__stat-value">${percentage}%</span>
                                    <span>usado</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="lk-usage-banner__actions">
                        <a class="lk-usage-banner__link" href="${baseUrl}billing" title="${escapeHtml(upgradeCta || 'Assinar plano Pro')}">
                            <i data-lucide="crown"></i>
                            <span>Assinar Pro</span>
                        </a>
                        <button class="lk-usage-banner__btn" type="button" data-close title="Dispensar aviso este mes">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
            `;

            const closeBtn = root.querySelector('[data-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    localStorage.setItem(dismissedKey, '1');
                    root.style.animation = 'lk-slideInDown 0.3s ease reverse';
                    setTimeout(() => {
                        root.innerHTML = '';
                    }, 300);
                });
            }
        })
        .catch((error) => {
            console.warn('Erro ao carregar aviso de limite:', error);
        });
})();
