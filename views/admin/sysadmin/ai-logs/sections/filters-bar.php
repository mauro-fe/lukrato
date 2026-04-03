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
