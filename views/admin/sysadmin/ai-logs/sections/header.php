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
