<div class="frontend-pilot-page">
    <section
        class="frontend-pilot-shell"
        data-frontend-pilot-root
        data-bootstrap-menu="perfil"
        data-bootstrap-view-id="admin-frontend-pilot-index"
        data-bootstrap-view-path="admin/frontend-pilot/index">
        <header class="frontend-pilot-hero">
            <div>
                <span class="frontend-pilot-kicker">Piloto isolado</span>
                <h1 class="frontend-pilot-title">Bootstrap autenticado via API v1</h1>
                <p class="frontend-pilot-subtitle">
                    Esta tela valida uma shell separada usando o bootstrap autenticado como fonte principal do estado global.
                </p>
            </div>

            <div class="frontend-pilot-actions">
                <button type="button" class="btn btn-outline-secondary" data-action="reload">
                    Recarregar bootstrap
                </button>
                <button type="button" class="btn btn-warning" data-action="renew" hidden>
                    Renovar sessão
                </button>
            </div>
        </header>

        <section class="frontend-pilot-flash" data-slot="flash" hidden></section>

        <section class="frontend-pilot-cards" aria-label="Resumo do bootstrap autenticado">
            <article class="frontend-pilot-card frontend-pilot-card--session">
                <div class="frontend-pilot-card-header">
                    <span class="frontend-pilot-card-label">Sessão</span>
                    <span class="frontend-pilot-badge" data-slot="session-tone">Aguardando</span>
                </div>
                <strong class="frontend-pilot-card-value" data-slot="session-label">Aguardando...</strong>
                <p class="frontend-pilot-card-meta" data-slot="session-detail">Preparando bootstrap inicial.</p>
            </article>

            <article class="frontend-pilot-card">
                <span class="frontend-pilot-card-label">Usuário</span>
                <strong class="frontend-pilot-card-value" data-slot="profile-name">-</strong>
                <p class="frontend-pilot-card-meta" data-slot="profile-meta">Sem bootstrap ainda.</p>
            </article>

            <article class="frontend-pilot-card">
                <span class="frontend-pilot-card-label">Tema</span>
                <strong class="frontend-pilot-card-value" data-slot="theme">-</strong>
                <p class="frontend-pilot-card-meta">Consumido de <span>/api/v1/user/bootstrap</span>.</p>
            </article>

            <article class="frontend-pilot-card">
                <span class="frontend-pilot-card-label">Ajuda</span>
                <strong class="frontend-pilot-card-value" data-slot="help-auto-offer">-</strong>
                <p class="frontend-pilot-card-meta" data-slot="help-meta">Preferências ainda não carregadas.</p>
            </article>

            <article class="frontend-pilot-card">
                <span class="frontend-pilot-card-label">Navegação</span>
                <strong class="frontend-pilot-card-value" data-slot="navigation-label">-</strong>
                <p class="frontend-pilot-card-meta" data-slot="navigation-meta">Navegação ainda não carregada.</p>
            </article>

            <article class="frontend-pilot-card">
                <span class="frontend-pilot-card-label">Dashboard</span>
                <strong class="frontend-pilot-card-value" data-slot="dashboard-count">0</strong>
                <p class="frontend-pilot-card-meta" data-slot="dashboard-meta">Preferências ainda não carregadas.</p>
            </article>

            <article class="frontend-pilot-card">
                <span class="frontend-pilot-card-label">Notificações</span>
                <strong class="frontend-pilot-card-value" data-slot="unread-count">0</strong>
                <p class="frontend-pilot-card-meta" data-slot="unread-meta">Contagem ainda não carregada.</p>
            </article>
        </section>

        <section class="frontend-pilot-runtime">
            <div class="frontend-pilot-runtime-item">
                <span class="frontend-pilot-runtime-label">Modo</span>
                <strong class="frontend-pilot-runtime-value" data-slot="mode-label">api/v1</strong>
            </div>
            <div class="frontend-pilot-runtime-item">
                <span class="frontend-pilot-runtime-label">Contexto</span>
                <strong class="frontend-pilot-runtime-value" data-slot="context-path">-</strong>
            </div>
            <div class="frontend-pilot-runtime-item">
                <span class="frontend-pilot-runtime-label">Última atualização</span>
                <strong class="frontend-pilot-runtime-value" data-slot="last-updated">ainda não carregado</strong>
            </div>
        </section>

        <section class="frontend-pilot-shell-preview" aria-label="Shell montada a partir do bootstrap autenticado">
            <aside class="frontend-pilot-shell-sidebar-panel">
                <div class="frontend-pilot-shell-panel-head">
                    <span class="frontend-pilot-card-label">Sidebar</span>
                    <strong class="frontend-pilot-shell-panel-title" data-slot="shell-current-menu">menu indefinido</strong>
                    <p class="frontend-pilot-card-meta">
                        Esta coluna já nasce de <span>pageContext.sidebar</span> e do <span>currentMenu</span> entregue pelo bootstrap.
                    </p>
                </div>

                <div class="frontend-pilot-shell-sidebar-content" data-slot="shell-sidebar"></div>

                <div class="frontend-pilot-shell-footer-panel">
                    <div class="frontend-pilot-shell-section-head">
                        <span class="frontend-pilot-card-label">Rodapé</span>
                        <strong class="frontend-pilot-shell-section-title">Atalhos utilitários</strong>
                    </div>

                    <div class="frontend-pilot-shell-footer-links" data-slot="shell-footer"></div>
                </div>
            </aside>

            <article class="frontend-pilot-shell-main-panel">
                <div class="frontend-pilot-shell-panel-head">
                    <span class="frontend-pilot-card-label">Conteúdo ativo</span>
                    <strong class="frontend-pilot-shell-panel-title" data-slot="shell-current-view">view indefinida</strong>
                    <p class="frontend-pilot-card-meta">
                        Os breadcrumbs abaixo também vêm do <span>pageContext</span>, sem depender de partial PHP para compor a moldura.
                    </p>
                </div>

                <nav class="frontend-pilot-shell-breadcrumbs" data-slot="shell-breadcrumbs" aria-label="Breadcrumbs do bootstrap"></nav>

                <div class="frontend-pilot-shell-stage-note">
                    <h2>Shell externa validada</h2>
                    <p>
                        O conteúdo principal ainda é um piloto controlado, mas a moldura acima já é montada só com o contrato autenticado de <span>/api/v1/user/bootstrap</span>.
                    </p>
                </div>
            </article>
        </section>

        <section class="frontend-pilot-controls" aria-label="Sondas de escrita controlada do piloto">
            <article class="frontend-pilot-control-card">
                <div class="frontend-pilot-control-head">
                    <div>
                        <span class="frontend-pilot-card-label">Write Probe</span>
                        <h2>Tema do usuário</h2>
                    </div>
                    <p>Escreve em <span>/api/v1/user/theme</span> e força um novo bootstrap da shell.</p>
                </div>

                <div class="frontend-pilot-theme-actions" data-role="theme-actions">
                    <button type="button" class="btn btn-outline-secondary" data-action="set-theme" data-theme-value="light">
                        Light
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-action="set-theme" data-theme-value="dark">
                        Dark
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-action="set-theme" data-theme-value="system">
                        System
                    </button>
                </div>
            </article>

            <article class="frontend-pilot-control-card">
                <div class="frontend-pilot-control-head">
                    <div>
                        <span class="frontend-pilot-card-label">Write Probe</span>
                        <h2>Preferências do dashboard</h2>
                    </div>
                    <p>Escreve em <span>/api/v1/perfil/dashboard-preferences</span> só com toggles de baixo risco.</p>
                </div>

                <form class="frontend-pilot-dashboard-form" data-role="dashboard-form">
                    <label class="frontend-pilot-toggle">
                        <input type="checkbox" name="toggleGrafico" data-dashboard-toggle="toggleGrafico">
                        <span>Exibir gráfico principal</span>
                    </label>
                    <label class="frontend-pilot-toggle">
                        <input type="checkbox" name="toggleMetas" data-dashboard-toggle="toggleMetas">
                        <span>Exibir bloco de metas</span>
                    </label>

                    <div class="frontend-pilot-dashboard-actions">
                        <button type="submit" class="btn btn-primary" data-action="save-dashboard">
                            Salvar preferências
                        </button>
                    </div>
                </form>
            </article>

            <article class="frontend-pilot-control-card">
                <div class="frontend-pilot-control-head">
                    <div>
                        <span class="frontend-pilot-card-label">Write Probe</span>
                        <h2>Nome de exibição</h2>
                    </div>
                    <p>Escreve em <span>/api/v1/user/display-name</span> e reidrata a shell pelo mesmo contrato de bootstrap.</p>
                </div>

                <form class="frontend-pilot-display-name-form" data-role="display-name-form">
                    <label class="frontend-pilot-field" for="frontend-pilot-display-name-input">
                        <span>Como o frontend separado prefere chamar o usuário</span>
                        <input
                            id="frontend-pilot-display-name-input"
                            type="text"
                            name="display_name"
                            maxlength="80"
                            autocomplete="nickname"
                            data-role="display-name-input">
                    </label>

                    <div class="frontend-pilot-dashboard-actions">
                        <button type="submit" class="btn btn-primary" data-action="save-display-name">
                            Salvar nome de exibição
                        </button>
                    </div>
                </form>
            </article>

            <article class="frontend-pilot-control-card">
                <div class="frontend-pilot-control-head">
                    <div>
                        <span class="frontend-pilot-card-label">Write Probe</span>
                        <h2>Preferências de ajuda</h2>
                    </div>
                    <p>Escreve em <span>/api/v1/user/help-preferences</span> só com a action estável <span>set_auto_offer</span> e revalida a shell.</p>
                </div>

                <form class="frontend-pilot-help-form" data-role="help-form">
                    <label class="frontend-pilot-toggle">
                        <input type="checkbox" name="auto_offer" data-help-toggle="auto_offer">
                        <span>Exibir ofertas automáticas de ajuda</span>
                    </label>

                    <div class="frontend-pilot-dashboard-actions">
                        <button type="submit" class="btn btn-primary" data-action="save-help-preferences">
                            Salvar preferências de ajuda
                        </button>
                    </div>
                </form>
            </article>
        </section>

        <section class="frontend-pilot-payloads" aria-label="Payloads brutos retornados pelo piloto">
            <article class="frontend-pilot-payload-card">
                <h2>Bootstrap</h2>
                <pre data-payload="bootstrap">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Session</h2>
                <pre data-payload="session">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Dashboard Preferences</h2>
                <pre data-payload="dashboard">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Notificações Unread</h2>
                <pre data-payload="unread">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Renew</h2>
                <pre data-payload="renew">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Theme Write</h2>
                <pre data-payload="theme-write">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Dashboard Write</h2>
                <pre data-payload="dashboard-write">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Display Name Write</h2>
                <pre data-payload="display-name-write">Aguardando...</pre>
            </article>

            <article class="frontend-pilot-payload-card">
                <h2>Help Preferences Write</h2>
                <pre data-payload="help-preferences-write">Aguardando...</pre>
            </article>
        </section>
    </section>
</div>