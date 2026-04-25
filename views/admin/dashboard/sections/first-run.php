<section class="dash-first-run-stack" id="dashboardFirstRunStack" hidden>
    <section class="dash-quick-start surface-card surface-card--interactive" id="dashboardQuickStart" hidden>
        <div class="dash-quick-start__main">
            <span class="dash-first-run__eyebrow" id="dashboardQuickStartEyebrow">Passo 1</span>
            <h2 id="dashboardQuickStartTitle">Crie sua primeira conta</h2>
        </div>

        <div class="dash-quick-start__cta-panel">
            <button class="dash-btn dash-btn--primary dash-btn--lg" id="dashboardFirstTransactionCta" type="button">
                <i data-lucide="plus"></i> Criar primeira conta
            </button>
            <button class="dash-btn dash-btn--ghost dash-quick-start__tour-btn" id="dashboardOpenTourPrompt"
                type="button">
                Ver tour
            </button>
        </div>
    </section>

    <section class="dash-first-run-bar surface-card" id="dashboardDisplayNamePrompt" hidden>
        <div class="dash-first-run-bar__preview" id="dashboardPreviewNotice" hidden>
            <span class="dash-first-run-bar__icon" aria-hidden="true">
                <i data-lucide="sparkles"></i>
            </span>
            <div class="dash-first-run-bar__preview-copy">
                <span>Você está em uma prévia com dados de exemplo.</span>
                <button type="button" class="dash-first-run__inline-link" id="dashboardPreviewLearnMore">
                    Saiba mais
                </button>
            </div>
        </div>

        <form class="dash-first-run-bar__name" id="dashboardDisplayNameForm" hidden>
            <label class="dash-first-run-bar__label" for="dashboardDisplayNameInput">
                Como prefere ser chamado?
            </label>
            <input type="text" id="dashboardDisplayNameInput" class="lk-input" maxlength="80" placeholder="Ex.: Mauro"
                autocomplete="nickname">
            <button type="submit" class="dash-btn dash-btn--primary" id="dashboardDisplayNameSubmit">
                Salvar
            </button>
            <button type="button" class="dash-btn dash-btn--ghost" id="dashboardDisplayNameDismiss">
                Agora não
            </button>
        </form>

        <p class="dash-first-run-bar__feedback" id="dashboardDisplayNameFeedback" hidden aria-live="polite"></p>
    </section>
</section>
