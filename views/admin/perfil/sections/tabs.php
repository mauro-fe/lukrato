    <!-- Tab Navigation -->
    <nav class="profile-tabs surface-card surface-card--clip" id="profileTabsSection" role="tablist" aria-label="Seções do perfil">
        <?php if ($isProfileView): ?>
            <button type="button" class="profile-tab surface-filter surface-filter--soft active" data-tab="dados" role="tab" aria-selected="true"
                aria-controls="panel-dados">
                <span class="tab-icon"><i data-lucide="user" style="color:#3b82f6"></i></span>
                <span class="tab-label">Dados Pessoais</span>
            </button>
            <button type="button" class="profile-tab surface-filter surface-filter--soft" data-tab="endereco" role="tab" aria-selected="false"
                aria-controls="panel-endereco">
                <span class="tab-icon"><i data-lucide="map-pin" style="color:#ef4444"></i></span>
                <span class="tab-label">Endereço</span>
            </button>
        <?php else: ?>
            <button type="button" class="profile-tab surface-filter surface-filter--soft active" data-tab="seguranca" role="tab" aria-selected="true"
                aria-controls="panel-seguranca">
                <span class="tab-icon"><i data-lucide="lock" style="color:#f59e0b"></i></span>
                <span class="tab-label">Segurança</span>
            </button>
            <button type="button" class="profile-tab surface-filter surface-filter--soft" data-tab="plano" role="tab" aria-selected="false"
                aria-controls="panel-plano">
                <span class="tab-icon"><i data-lucide="crown" style="color:#f59e0b"></i></span>
                <span class="tab-label">Plano & Indicação</span>
            </button>
            <button type="button" class="profile-tab surface-filter surface-filter--soft" data-tab="integracoes" role="tab" aria-selected="false"
                aria-controls="panel-integracoes">
                <span class="tab-icon"><i data-lucide="plug" style="color:#0ea5e9"></i></span>
                <span class="tab-label">Integrações</span>
            </button>
            <button type="button" class="profile-tab surface-filter surface-filter--soft tab-danger" data-tab="perigo" role="tab" aria-selected="false"
                aria-controls="panel-perigo">
                <span class="tab-icon"><i data-lucide="triangle-alert" style="color:#ef4444"></i></span>
                <span class="tab-label">Zona de Perigo</span>
            </button>
        <?php endif; ?>
    </nav>
