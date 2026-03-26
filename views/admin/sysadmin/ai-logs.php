
<div class="sysadmin-container logs-page">

    <div class="logs-header">
        <div class="header-content">
            <h1>
                <i data-lucide="file-text"></i>
                Logs da IA
            </h1>
            <p>Historico de interacoes, metricas de uso e custos estimados</p>
        </div>
        <div class="header-actions">
            <div class="period-control">
                <label for="summaryPeriod">Cards</label>
                <select id="summaryPeriod">
                    <option value="24">Ultimas 24h</option>
                    <option value="168">Ultimos 7 dias</option>
                    <option value="720">Ultimos 30 dias</option>
                </select>
            </div>
            <button type="button" class="btn-back btn-refresh" id="btnRefreshPage">
                <i data-lucide="refresh-cw" style="width:15px;height:15px;"></i>
                Atualizar
            </button>
            <a href="<?= BASE_URL ?>sysadmin/ai" class="btn-back">
                <i data-lucide="bot" style="width:15px;height:15px;"></i>
                Assistente IA
            </a>
            <a href="<?= BASE_URL ?>sysadmin" class="btn-back">
                <i data-lucide="arrow-left"></i>
                Voltar ao Painel
            </a>
        </div>
    </div>

    <div class="metrics-grid" id="metricsGrid">
        <div class="metric-card">
            <div class="metric-value" id="metricTotal">-</div>
            <div class="metric-label">Total</div>
        </div>
        <div class="metric-card success">
            <div class="metric-value" id="metricSuccess">-</div>
            <div class="metric-label">Taxa de sucesso</div>
        </div>
        <div class="metric-card">
            <div class="metric-value" id="metricTokens">-</div>
            <div class="metric-label">Tokens usados</div>
        </div>
        <div class="metric-card warning">
            <div class="metric-value" id="metricCost">-</div>
            <div class="metric-label">Custo estimado</div>
        </div>
        <div class="metric-card">
            <div class="metric-value" id="metricAvgTime">-</div>
            <div class="metric-label">Tempo medio</div>
        </div>
        <div class="metric-card danger">
            <div class="metric-value" id="metricErrors">-</div>
            <div class="metric-label">Erros</div>
        </div>
    </div>

    <div class="quality-section" id="qualitySection">
        <h2 class="section-title">
            <i data-lucide="activity"></i>
            Qualidade da IA
        </h2>
        <div class="metrics-grid">
            <div class="metric-card warning">
                <div class="metric-value" id="metricLowConf">-</div>
                <div class="metric-label">Baixa confianca</div>
            </div>
            <div class="metric-card warning">
                <div class="metric-value" id="metricFallback">-</div>
                <div class="metric-label">Fallback para chat</div>
            </div>
        </div>
        <div class="quality-panels">
            <div class="metric-card quality-panel">
                <div class="metric-label quality-panel-title">Distribuicao de intents</div>
                <div id="intentDistribution" class="quality-panel-list">
                    <span class="panel-loading">Carregando...</span>
                </div>
            </div>
            <div class="metric-card quality-panel">
                <div class="metric-label quality-panel-title">Erros por handler</div>
                <div id="errorsByHandler" class="quality-panel-list">
                    <span class="panel-loading">Carregando...</span>
                </div>
            </div>
            <div class="metric-card quality-panel">
                <div class="metric-label quality-panel-title">Origem da decisao</div>
                <div id="sourceDistribution" class="quality-panel-list">
                    <span class="panel-loading">Carregando...</span>
                </div>
            </div>
            <div class="metric-card quality-panel">
                <div class="metric-label quality-panel-title">Latencia por tipo</div>
                <div id="avgTimeByType" class="quality-panel-list">
                    <span class="panel-loading">Carregando...</span>
                </div>
            </div>
        </div>
    </div>

    <div class="quota-section" id="quotaSection">
        <h2 class="section-title">
            <i data-lucide="gauge"></i>
            Quota OpenAI
            <span class="quota-status-badge loading" id="quotaStatus">Verificando...</span>
        </h2>
        <div class="quota-grid" id="quotaGrid">
            <div class="quota-item">
                <div class="quota-label">Requisicoes</div>
                <div class="quota-bar-wrap">
                    <div class="quota-bar" id="quotaReqBar" style="width:0%;background:var(--blue-600);"></div>
                </div>
                <div class="quota-values"><strong id="quotaReqRemaining">-</strong> / <span id="quotaReqLimit">-</span></div>
                <div class="quota-hint" id="quotaReqHint">Disponivel agora</div>
            </div>
            <div class="quota-item">
                <div class="quota-label">Tokens</div>
                <div class="quota-bar-wrap">
                    <div class="quota-bar" id="quotaTokBar" style="width:0%;background:var(--color-success);"></div>
                </div>
                <div class="quota-values"><strong id="quotaTokRemaining">-</strong> / <span id="quotaTokLimit">-</span></div>
                <div class="quota-hint" id="quotaTokHint">Disponivel agora</div>
            </div>
        </div>
        <div class="quota-reset" id="quotaReset" style="display:none;"></div>
        <div class="quota-msg" id="quotaMsg" style="display:none;"></div>
    </div>

    <div class="filters-bar">
        <div class="filters-topline">
            <div class="filters-title-group">
                <div class="filters-title">Filtros da tabela</div>
                <div class="filters-subtitle" id="filterSummary">Sem filtros ativos</div>
            </div>
            <div class="filter-presets">
                <button type="button" class="btn-filter-chip" data-range="today">Hoje</button>
                <button type="button" class="btn-filter-chip" data-range="7d">7 dias</button>
                <button type="button" class="btn-filter-chip" data-range="30d">30 dias</button>
                <button type="button" class="btn-filter-chip ghost" id="btnClearFilters">Limpar filtros</button>
            </div>
        </div>
        <div class="filters-inputs">
            <select id="filterType">
                <option value="">Todos os tipos</option>
                <option value="chat">Chat</option>
                <option value="suggest_category">Sugestao de categoria</option>
                <option value="analyze_spending">Analise de gastos</option>
                <option value="categorize">Categorizacao</option>
                <option value="analyze">Analise (novo fluxo)</option>
                <option value="quick_query">Consulta rapida</option>
                <option value="extract_transaction">Extracao de transacao</option>
                <option value="create_entity">Criacao de entidade</option>
                <option value="confirm_action">Confirmacao</option>
                <option value="image_analysis">Analise de imagem</option>
                <option value="audio_transcription">Transcricao</option>
                <option value="pay_fatura">Pagamento de fatura</option>
            </select>
            <select id="filterChannel">
                <option value="">Todos os canais</option>
                <option value="web">Web Chat</option>
                <option value="telegram">Telegram</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="api">API</option>
                <option value="admin">Admin</option>
            </select>
            <select id="filterSuccess">
                <option value="">Todos os status</option>
                <option value="1">Sucesso</option>
                <option value="0">Erro</option>
            </select>
            <input type="date" id="filterDateFrom" title="Data inicial">
            <input type="date" id="filterDateTo" title="Data final">
            <input type="text" id="filterSearch" placeholder="Buscar no prompt, resposta ou erro..." style="min-width:180px;">
            <div class="filters-actions">
                <button class="btn-filter" id="btnFilter">
                    <i data-lucide="search" style="width:14px;height:14px;vertical-align:middle;margin-right:.2rem;"></i>
                    Aplicar filtros
                </button>
                <button class="btn-cleanup" id="btnCleanup" title="Limpar logs antigos">
                    <i data-lucide="trash-2" style="width:14px;height:14px;vertical-align:middle;margin-right:.2rem;"></i>
                    Limpar +90 dias
                </button>
            </div>
        </div>
    </div>

    <div class="logs-table-wrap">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Canal</th>
                    <th>Tipo</th>
                    <th>Prompt</th>
                    <th class="col-tokens">Tokens</th>
                    <th class="col-time">Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="logsBody">
                <tr>
                    <td colspan="7">
                        <div class="logs-empty"><i data-lucide="loader"></i> Carregando...</div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination" id="pagination"></div>
    </div>

</div>
