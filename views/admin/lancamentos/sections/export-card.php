<?php
$showLanExport = isset($showLanExport) ? (bool) $showLanExport : true;
$lancamentosCanAccessComplete = isset($lancamentosCanAccessComplete)
    ? (bool) $lancamentosCanAccessComplete
    : (bool) ($isPro ?? false);
?>

<div class="modern-card export-card surface-card surface-card--interactive"
    data-aos="fade-up" data-aos-delay="100" id="exportCard" <?= !$showLanExport ? ' style="display:none;"' : '' ?>>
    <div class="card-header-icon">
        <div class="icon-wrapper export">
            <i data-lucide="file-output"></i>
        </div>
        <div class="card-title-group">
            <h3 class="card-title">Exportar lancamentos</h3>
            <p class="card-subtitle">Exportação rapida em PDF ou Excel.</p>
        </div>
        <button type="button" class="card-collapse-btn" id="toggleExportCard" aria-expanded="false"
            aria-controls="exportCardBody" title="Expandir exportação">
            <i data-lucide="chevron-down"></i>
        </button>
    </div>

    <div class="export-card-toolbar">
        <div class="export-toolbar-copy">
            <span class="export-toolbar-label">Exportação</span>
            <p class="export-toolbar-text">Escolha o formato e exporte. Filtros avançados ficam recolhidos por
                padrão.</p>
        </div>
        <div class="export-actions-group">
            <select id="exportFormat" class="modern-select" data-lk-custom-select="export"
                aria-label="Formato de exportação" <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                <option value="pdf">PDF</option>
                <option value="excel">Excel (.xlsx)</option>
            </select>
            <button id="btnExportar" type="button" class="modern-btn primary" aria-label="Exportar lancamentos"
                <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                <i data-lucide="download"></i>
                <span>Exportar</span>
            </button>
        </div>
    </div>

    <div class="export-card-body" id="exportCardBody" hidden>
        <div class="export-controls <?= !$lancamentosCanAccessComplete ? 'disabled-blur' : '' ?>">
            <div class="date-range-group">
                <div class="input-group">
                    <label for="exportStart" class="input-label">
                        <i data-lucide="calendar-days"></i><span>Data inicial</span>
                    </label>
                    <input type="date" id="exportStart" class="modern-input" data-default-today="1"
                        aria-label="Data inicial" <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                </div>
                <div class="input-group">
                    <label for="exportEnd" class="input-label">
                        <i data-lucide="calendar-days"></i><span>Data final</span>
                    </label>
                    <input type="date" id="exportEnd" class="modern-input" data-default-today="1"
                        aria-label="Data final" <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                </div>
            </div>
            <div class="export-filters-row">
                <div class="export-filter-item">
                    <label for="exportConta" class="export-filter-label">Conta</label>
                    <select id="exportConta" class="modern-select export-select" data-lk-custom-select="export"
                        <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="export-filter-item">
                    <label for="exportCategoria" class="export-filter-label">Categoria</label>
                    <select id="exportCategoria" class="modern-select export-select" data-lk-custom-select="export"
                        <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="export-filter-item">
                    <label for="exportTipo" class="export-filter-label">Tipo</label>
                    <select id="exportTipo" class="modern-select export-select" data-lk-custom-select="export"
                        <?= !$lancamentosCanAccessComplete ? 'disabled' : '' ?>>
                        <option value="">Todos</option>
                        <option value="receita">Receitas</option>
                        <option value="despesa">Despesas</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>