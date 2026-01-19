<style>
#lk-usage-banner-root {
    margin: 0 0 16px 0;
    animation: lk-slideInDown 0.4s ease-out;
    width: 100%;
}

@keyframes lk-slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes lk-pulse {

    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.05);
    }
}

.lk-usage-banner {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-radius: 12px;
    background: rgba(255, 193, 7, 0.12);
    border: 1px solid rgba(255, 193, 7, 0.30);
    color: var(--color-text, #e8edf3);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.lk-usage-banner:hover {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.lk-usage-banner--critical {
    background: rgba(244, 67, 54, 0.12);
    border-color: rgba(244, 67, 54, 0.30);
}

.lk-usage-banner--critical .lk-usage-banner__icon {
    background: rgba(244, 67, 54, 0.18);
    animation: lk-pulse 2s ease-in-out infinite;
}

.lk-usage-banner__left {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    min-width: 0;
    flex: 1;
}

.lk-usage-banner__icon {
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(255, 193, 7, 0.18);
    font-size: 16px;
}

.lk-usage-banner__content {
    flex: 1;
    min-width: 0;
}

.lk-usage-banner__text {
    margin-bottom: 4px;
    line-height: 1.5;
}

.lk-usage-banner__progress {
    margin-top: 8px;
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
    overflow: hidden;
}

.lk-usage-banner__progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #f39c12, #e67e22);
    border-radius: 999px;
    transition: width 0.6s ease;
}

.lk-usage-banner--critical .lk-usage-banner__progress-bar {
    background: linear-gradient(90deg, #f44336, #d32f2f);
}

.lk-usage-banner__stats {
    margin-top: 6px;
    font-size: 0.875rem;
    opacity: 0.85;
    display: flex;
    gap: 16px;
}

.lk-usage-banner__stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

.lk-usage-banner__stat-value {
    font-weight: 600;
}

.lk-usage-banner__actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex: 0 0 auto;
}

.lk-usage-banner__btn {
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: rgba(255, 255, 255, 0.06);
    color: inherit;
    padding: 8px 12px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.lk-usage-banner__btn:hover {
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
}

.lk-usage-banner__link {
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 10px;
    background: var(--color-primary, #f39c12);
    color: #0b141c;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lk-usage-banner__link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
}

.lk-usage-banner--critical .lk-usage-banner__link {
    background: #f44336;
    color: #fff;
}

.lk-usage-banner--critical .lk-usage-banner__link:hover {
    box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
}

@media (max-width: 768px) {
    .lk-usage-banner {
        flex-direction: column;
        align-items: stretch;
        padding: 12px 14px;
    }

    .lk-usage-banner__stats {
        flex-direction: column;
        gap: 4px;
    }

    .lk-usage-banner__actions {
        justify-content: flex-end;
        margin-top: 8px;
    }
}
</style>

<script>
(function() {
    const root = document.getElementById('lk-usage-banner-root');
    if (!root) return;

    // Evita spammar o usuário: guarda por mês
    const now = new Date();
    const ym = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
    const dismissedKey = `lk_usage_banner_dismissed_${ym}`;

    if (localStorage.getItem(dismissedKey) === '1') return;

    const endpoint = '<?= BASE_URL ?>api/lancamentos/usage?month=' + encodeURIComponent(ym);

    fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(json => {
            const data = json?.data ?? json;
            const msg = data?.ui_message;
            const usage = data?.usage;
            const upgradeCta = data?.upgrade_cta;

            if (!msg || !usage) return;
            if (usage.plan !== 'free') return;
            if (!usage.should_warn) return;

            const percentage = usage.percentage || 0;
            const isCritical = percentage >= 90;
            const bannerClass = isCritical ? 'lk-usage-banner lk-usage-banner--critical' : 'lk-usage-banner';
            const icon = isCritical ? '🔴' : '⚠️';

            // Render banner avançado
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
                        <a class="lk-usage-banner__link" href="<?= BASE_URL ?>planos" title="${escapeHtml(upgradeCta || 'Assinar plano Pro')}">
                            <i class="fas fa-crown"></i>
                            <span>Assinar Pro</span>
                        </a>
                        <button class="lk-usage-banner__btn" type="button" data-close title="Dispensar aviso este mês">
                            <i class="fas fa-times"></i>
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
        .catch(err => {
            console.warn('Erro ao carregar aviso de limite:', err);
        });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
</script>