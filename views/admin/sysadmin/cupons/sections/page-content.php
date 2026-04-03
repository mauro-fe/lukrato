<div class="main-content">
    <div class="cupons-container">
        <!-- Botão Voltar -->
        <a href="<?= BASE_URL ?>sysadmin" class="btn-voltar">
            <i data-lucide="arrow-left"></i>
            <span>Voltar ao Painel</span>
        </a>

        <!-- Header -->
        <div class="cupons-header">
            <div class="cupons-header-title">
                <div class="cupons-header-icon">
                    <i data-lucide="ticket"></i>
                </div>
                <div>
                    <h1>Gerenciar Cupons de Desconto</h1>
                    <p>Crie e gerencie cupons promocionais para seus clientes</p>
                </div>
            </div>
            <button class="btn-criar-cupom" data-action="abrirModalCriarCupom">
                <i data-lucide="circle-plus"></i>
                Criar Novo Cupom
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="cupons-stats" id="cuponsStats" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i data-lucide="ticket"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalCupons">0</h3>
                    <p>Total de Cupons</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i data-lucide="circle-check"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statCuponsAtivos">0</h3>
                    <p>Cupons Ativos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i data-lucide="line-chart"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalUsos">0</h3>
                    <p>Total de Usos</p>
                </div>
            </div>
        </div>

        <!-- Tabela de Cupons -->
        <div class="cupons-table-container">
            <div class="table-header">
                <h2><i data-lucide="list"></i> Lista de Cupons</h2>
            </div>
            <div id="loading" class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Carregando cupons...</p>
            </div>
            <table class="cupons-table" id="cuponsTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Desconto</th>
                        <th>Tipo</th>
                        <th>Validade</th>
                        <th>Uso</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="cuponsTableBody">
                    <!-- Preenchido via JavaScript -->
                </tbody>
            </table>
            <div id="emptyState" class="empty-state" style="display: none;">
                <i data-lucide="ticket"></i>
                <h3>Nenhum cupom cadastrado</h3>
                <p>Crie seu primeiro cupom de desconto para começar</p>
            </div>
        </div>
    </div>
</div>
