<section class="dash-first-run-stack" id="dashboardFirstRunStack">
    <section class="dash-display-name-card surface-card" id="dashboardDisplayNamePrompt" style="display:none;">
        <div class="dash-display-name-card__copy">
            <span class="dash-first-run__eyebrow">Personalização rápida</span>
            <h2>Como você prefere ser chamado?</h2>
            <p>Isso ajuda o Lukrato a deixar o dashboard, a IA e as mensagens com a sua cara.</p>
        </div>
        <form class="dash-display-name-card__form" id="dashboardDisplayNameForm">
            <label class="visually-hidden" for="dashboardDisplayNameInput">Como você prefere ser chamado?</label>
            <input type="text" id="dashboardDisplayNameInput" class="lk-input" maxlength="80" placeholder="Ex.: Mauro"
                autocomplete="nickname">
            <div class="dash-display-name-card__actions">
                <button type="submit" class="dash-btn dash-btn--primary" id="dashboardDisplayNameSubmit">
                    Salvar nome
                </button>
                <button type="button" class="dash-btn dash-btn--ghost" id="dashboardDisplayNameDismiss">
                    Agora não
                </button>
            </div>
            <p class="dash-display-name-card__feedback" id="dashboardDisplayNameFeedback" hidden></p>
        </form>
    </section>

    <section class="dash-quick-start surface-card surface-card--interactive" id="dashboardQuickStart"
        style="display:none;">
        <div class="dash-quick-start__header">
            <div>
                <span class="dash-first-run__eyebrow">Primeiro passo</span>
                <h2>Comece adicionando sua primeira transação</h2>
                <p>Enquanto você ainda não cadastrou nada, o Lukrato mostra um exemplo para você entender o fluxo.
                    Assim que chegar seu primeiro dado real, a demonstração some automaticamente.</p>
            </div>
            <div class="dash-quick-start__badge">
                <i data-lucide="sparkles"></i>
                <span>Menos de 1 minuto</span>
            </div>
        </div>

        <div class="dash-quick-start__actions">
            <button class="dash-btn dash-btn--primary dash-btn--lg" id="dashboardFirstTransactionCta" type="button">
                <i data-lucide="plus"></i> Adicionar agora
            </button>
            <button class="dash-btn dash-btn--ghost" id="dashboardOpenTourPrompt" type="button">
                Ver tour rápido
            </button>
        </div>

        <div class="dash-quick-start__notes">
            <span><i data-lucide="wallet"></i> O saldo começa a reagir imediatamente</span>
            <span><i data-lucide="pie-chart"></i> Compare o exemplo com seus dados reais depois</span>
            <span><i data-lucide="folder-kanban"></i> As categorias passam a refletir seu uso assim que você
                começar</span>
        </div>
    </section>
</section>