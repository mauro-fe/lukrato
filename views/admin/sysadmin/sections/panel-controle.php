<!-- Tab Panel: Control -->
<div class="sysadmin-tab-panel" id="panel-controle" role="tabpanel" aria-labelledby="tab-controle">
    <!-- Control Panel -->
    <div class="control-section">
        <h2 class="section-title">
            <i data-lucide="sliders-horizontal"></i>
            Controle Mestre
        </h2>

        <div class="control-grid">
            <!-- Maintenance Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="wrench"></i>
                    <div>
                        <h3>Manutenção e Limpeza</h3>
                        <p>Ferramentas para saúde do servidor</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" data-action="limparCache">
                        <i data-lucide="paintbrush"></i>
                        Limpar Cache do Sistema
                    </button>
                    <button class="btn-control danger" id="btnMaintenance" data-action="toggleMaintenance">
                        <i data-lucide="wrench" id="btnMaintenanceIcon"></i>
                        <span id="btnMaintenanceText">Verificando...</span>
                    </button>
                </div>
            </div>

            <!-- User Search Card -->


            <!-- Cupons de Desconto Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="ticket"></i>
                    <div>
                        <h3>Cupons de Desconto</h3>
                        <p>Gerenciar cupons promocionais</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" data-action="navigateTo"
                        data-href="<?= BASE_URL ?>sysadmin/cupons">
                        <i data-lucide="ticket"></i>
                        Gerenciar Cupons
                    </button>
                </div>
            </div>

            <!-- Comunicações Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="megaphone" style="color: #f59e0b;"></i>
                    <div>
                        <h3>Comunicações</h3>
                        <p>Envie mensagens e campanhas</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary btn-campanhas" data-action="navigateTo"
                        data-href="<?= BASE_URL ?>sysadmin/comunicacoes">
                        <i data-lucide="send"></i>
                        Gerenciar Campanhas
                    </button>
                </div>
            </div>

            <!-- Blog / Aprenda Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="book-open" style="color: #f97316;"></i>
                    <div>
                        <h3>Blog / Aprenda</h3>
                        <p>Gerencie artigos educacionais</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control primary" data-action="navigateTo"
                        data-href="<?= BASE_URL ?>sysadmin/blog">
                        <i data-lucide="pen-line"></i>
                        Gerenciar Blog
                    </button>
                </div>
            </div>

            <!-- Grant Access Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="gift"></i>
                    <div>
                        <h3>Liberar Acesso Premium</h3>
                        <p>Conceda acesso Pro ou Ultra temporário</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control success" data-action="openGrantAccessModal">
                        <i data-lucide="crown"></i>
                        Liberar Acesso
                    </button>
                </div>
            </div>

            <!-- Revoke Access Card -->
            <div class="control-card">
                <div class="control-header">
                    <i data-lucide="ban"></i>
                    <div>
                        <h3>Remover Acesso Premium</h3>
                        <p>Revogue o acesso Pro ou Ultra de um usuário</p>
                    </div>
                </div>
                <div class="control-actions">
                    <button class="btn-control danger" data-action="openRevokeAccessModal">
                        <i data-lucide="user-x"></i>
                        Remover Acesso
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>