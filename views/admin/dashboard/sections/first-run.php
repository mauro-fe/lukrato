<section class="dash-first-run-stack" id="dashboardFirstRunStack" hidden>
    <section class="dash-quick-start surface-card surface-card--interactive" id="dashboardQuickStart" hidden>
        <div class="dash-quick-start__journey" aria-label="Primeiros passos para configurar o dashboard">
            <article class="dash-journey-step" data-journey-step="create_account" data-state="active">
                <span class="dash-journey-step__index">1</span>
                <div class="dash-journey-step__copy">
                    <strong>Criar conta</strong>
                    <p>Adicione sua primeira conta bancária.</p>
                </div>
            </article>

            <article class="dash-journey-step" data-journey-step="create_transaction" data-state="pending">
                <span class="dash-journey-step__index">2</span>
                <div class="dash-journey-step__copy">
                    <strong>Importar ou registrar</strong>
                    <p>Traga seus dados para o Lukrato.</p>
                </div>
            </article>

            <article class="dash-journey-step" data-journey-step="done" data-state="pending">
                <span class="dash-journey-step__index">3</span>
                <div class="dash-journey-step__copy">
                    <strong>Pronto!</strong>
                    <p>Seu dashboard estará com seus dados reais.</p>
                </div>
            </article>
        </div>

        <div class="dash-quick-start__cta-panel">
            <button class="dash-btn dash-btn--primary dash-btn--lg" id="dashboardFirstTransactionCta" type="button">
                <i data-lucide="plus"></i> Criar primeira conta
            </button>
            <button class="dash-btn dash-btn--ghost dash-quick-start__tour-btn" id="dashboardOpenTourPrompt"
                type="button">
                Ver tour de 30s
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
