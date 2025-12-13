<style>
#lk-usage-banner-root {
    margin: 10px 0;
}

.lk-usage-banner {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255, 193, 7, 0.12);
    border: 1px solid rgba(255, 193, 7, 0.30);
    color: var(--color-text, #e8edf3);
}

.lk-usage-banner__left {
    display: flex;
    gap: 10px;
    align-items: center;
    min-width: 0;
}

.lk-usage-banner__icon {
    flex: 0 0 auto;
    width: 26px;
    height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(255, 193, 7, 0.18);
}

.lk-usage-banner__text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    padding: 7px 10px;
    border-radius: 10px;
    cursor: pointer;
}

.lk-usage-banner__btn:hover {
    background: rgba(255, 255, 255, 0.10);
}

.lk-usage-banner__link {
    text-decoration: none;
    padding: 7px 10px;
    border-radius: 10px;
    background: var(--color-primary, #f39c12);
    color: #0b141c;
    font-weight: 600;
}

.lk-usage-banner__link:hover {
    filter: brightness(1.05);
}

@media (max-width: 680px) {
    .lk-usage-banner {
        flex-direction: column;
        align-items: stretch;
    }

    .lk-usage-banner__text {
        white-space: normal;
    }

    .lk-usage-banner__actions {
        justify-content: flex-end;
    }
}
</style>

<div id="lk-usage-banner-root"></div>

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
            const msg = json?.data?.ui_message ?? json?.ui_message ?? null; // depende do seu Response::success
            const usage = json?.data?.usage ?? json?.usage ?? null;

            if (!msg || !usage) return;
            if (usage.plan !== 'free') return;
            if (!usage.should_warn) return;

            // Render banner
            root.innerHTML = `
        <div class="lk-usage-banner" role="status" aria-live="polite">
          <div class="lk-usage-banner__left">
            <div class="lk-usage-banner__icon">⚠️</div>
            <div class="lk-usage-banner__text">
              ${escapeHtml(msg)}
            </div>
          </div>
          <div class="lk-usage-banner__actions">
            <a class="lk-usage-banner__link" href="<?= BASE_URL ?>planos">Ver Pro</a>
            <button class="lk-usage-banner__btn" type="button" data-close>Fechar</button>
          </div>
        </div>
      `;

            const closeBtn = root.querySelector('[data-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    localStorage.setItem(dismissedKey, '1');
                    root.innerHTML = '';
                });
            }
        })
        .catch(() => {
            /* silencioso */
        });

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();
</script>