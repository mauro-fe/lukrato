<!-- Tab Panel: Logs -->
<div class="sysadmin-tab-panel" id="panel-logs" role="tabpanel" aria-labelledby="tab-logs">
    <!-- =============================================== -->
    <!-- ERROR LOGS SECTION - Real-time Monitoring      -->
    <!-- =============================================== -->
    <div class="error-logs-section" id="errorLogsSection">
        <h2 class="section-title">
            <i data-lucide="shield-alert"></i>
            Logs de Erro em Tempo Real
            <div class="error-logs-title-actions">
                <label class="auto-refresh-toggle" title="Atualização automática">
                    <input type="checkbox" id="errorLogsAutoRefresh" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Auto</span>
                </label>
                <button class="btn-refresh-stats" data-action="loadErrorLogs" title="Atualizar logs">
                    <i data-lucide="refresh-cw" id="errorLogsRefreshIcon"></i>
                </button>
            </div>
        </h2>

        <!-- Error Level Summary Cards -->
        <div class="error-level-cards" id="errorLevelCards">
            <div class="error-level-card level-critical">
                <div class="error-level-icon"><i data-lucide="zap"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelCritical">0</span>
                    <span class="error-level-label">Critical</span>
                </div>
            </div>
            <div class="error-level-card level-error">
                <div class="error-level-icon"><i data-lucide="x-circle"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelError">0</span>
                    <span class="error-level-label">Error</span>
                </div>
            </div>
            <div class="error-level-card level-warning">
                <div class="error-level-icon"><i data-lucide="alert-triangle"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelWarning">0</span>
                    <span class="error-level-label">Warning</span>
                </div>
            </div>
            <div class="error-level-card level-info">
                <div class="error-level-icon"><i data-lucide="info"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelInfo">0</span>
                    <span class="error-level-label">Info</span>
                </div>
            </div>
            <div class="error-level-card level-unresolved">
                <div class="error-level-icon"><i data-lucide="clock"></i></div>
                <div class="error-level-content">
                    <span class="error-level-value" id="levelUnresolved">0</span>
                    <span class="error-level-label">Não Resolvidos</span>
                </div>
            </div>
        </div>

        <!-- Error Logs Filters -->
        <div class="error-logs-filters">
            <form id="errorLogsFilters" class="error-logs-filters-form">
                <input type="text" name="search" class="filter-input" placeholder="Buscar mensagem, classe, arquivo..."
                    id="errorLogSearch">
                <select name="level" class="filter-select" id="errorLogLevel">
                    <option value="">Todos os Níveis</option>
                </select>
                <select name="category" class="filter-select" id="errorLogCategory">
                    <option value="">Todas as Categorias</option>
                </select>
                <select name="resolved" class="filter-select" id="errorLogResolved">
                    <option value="">Todos</option>
                    <option value="0" selected>Não Resolvidos</option>
                    <option value="1">Resolvidos</option>
                </select>
                <select name="per_page" class="filter-select" id="errorLogPerPage">
                    <option value="15">15 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
                <button type="submit" class="btn-control primary"><i data-lucide="filter"></i> Filtrar</button>
                <button type="button" class="btn-control danger" data-action="confirmCleanupLogs"
                    title="Limpar logs antigos resolvidos">
                    <i data-lucide="trash-2"></i> Limpar
                </button>
            </form>
        </div>

        <!-- Error Logs Table -->
        <div class="error-logs-table-wrapper" id="errorLogsTableWrapper">
            <div class="lk-loading-state">
                <i data-lucide="loader-2"></i>
                <p>Carregando logs...</p>
            </div>
        </div>
    </div>
</div>