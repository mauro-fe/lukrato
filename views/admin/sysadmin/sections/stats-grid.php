<!-- Stats Grid -->
<div class="stats-grid" id="sysStatsGrid">
    <!-- Total Users Card -->
    <div class="stat-card" data-aos="fade-up" data-aos-delay="0">
        <div class="stat-icon users">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value" id="total-users"><?= number_format($metrics['totalUsers'] ?? 0, 0, ',', '.') ?>
            </h3>
            <p class="stat-label">Usuários Totais</p>
            <span class="stat-badge positive">
                <i data-lucide="arrow-up"></i>
                +<?= number_format($metrics['newToday'] ?? 0, 0, ',', '.') ?> hoje
            </span>
        </div>
    </div>

    <!-- Admins Card -->
    <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-icon admins">
            <i data-lucide="shield-check"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value"><?= number_format($metrics['totalAdmins'] ?? 0, 0, ',', '.') ?></h3>
            <p class="stat-label">Admins Ativos</p>
            <span class="stat-badge success">
                <i data-lucide="circle-check"></i>
                Com permissões
            </span>
        </div>
    </div>

    <!-- Error Logs Card -->
    <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-icon errors">
            <i data-lucide="triangle-alert"></i>
        </div>
        <div class="stat-content">
            <h3 class="stat-value" id="stat-error-total">–</h3>
            <p class="stat-label">Logs de Erro</p>
            <span class="stat-badge warning" id="stat-error-badge">
                <i data-lucide="clock"></i>
                <span id="stat-error-unresolved">Carregando...</span>
            </span>
        </div>
        <a href="#" class="stat-link" data-action="switchTab" data-tab="logs">Ver Logs <i
                data-lucide="arrow-right"></i></a>
    </div>
</div>