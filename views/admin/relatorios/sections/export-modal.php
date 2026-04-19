<div class="rel-export-overlay" id="relExportModalOverlay" hidden>
    <div class="rel-export-modal surface-card" role="dialog" aria-modal="true"
        aria-labelledby="relExportModalTitle" aria-describedby="relExportModalDesc">
        <div class="rel-export-header">
            <div class="rel-export-title-wrap">
                <span class="rel-export-icon" aria-hidden="true">
                    <i data-lucide="download"></i>
                </span>
                <div>
                    <h3 class="rel-export-title" id="relExportModalTitle">Exportar relatório</h3>
                    <p class="rel-export-desc" id="relExportModalDesc">
                        Escolha o relatório e o formato do arquivo.
                    </p>
                </div>
            </div>
            <button class="rel-export-close" type="button" data-rel-export-close aria-label="Fechar exportação">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="rel-export-form" id="relExportForm">
            <div class="rel-export-body">
                <label class="rel-export-field" for="relExportType">
                    <span class="rel-export-label">
                        <i data-lucide="bar-chart-3"></i>
                        Tipo de relatório
                    </span>
                    <select id="relExportType" class="rel-export-select" name="type">
                        <option value="despesas_por_categoria">Despesas por categoria</option>
                        <option value="receitas_por_categoria">Receitas por categoria</option>
                        <option value="saldo_mensal">Saldo diário</option>
                        <option value="receitas_despesas_diario">Receitas x despesas diário</option>
                        <option value="evolucao_12m">Evolução 12 meses</option>
                        <option value="receitas_despesas_por_conta">Receitas x despesas por conta</option>
                        <option value="cartoes_credito">Relatório de cartões</option>
                        <option value="resumo_anual">Resumo anual</option>
                        <option value="despesas_anuais_por_categoria">Despesas anuais por categoria</option>
                        <option value="receitas_anuais_por_categoria">Receitas anuais por categoria</option>
                    </select>
                </label>

                <fieldset class="rel-export-format">
                    <legend class="rel-export-label">
                        <i data-lucide="file-output"></i>
                        Formato
                    </legend>
                    <div class="rel-export-format-grid">
                        <label class="rel-export-format-option">
                            <input type="radio" name="format" value="pdf" checked>
                            <span>
                                <i data-lucide="file-text"></i>
                                PDF
                            </span>
                        </label>
                        <label class="rel-export-format-option">
                            <input type="radio" name="format" value="excel">
                            <span>
                                <i data-lucide="table"></i>
                                Excel
                            </span>
                        </label>
                    </div>
                </fieldset>
            </div>

            <div class="rel-export-footer">
                <button class="rel-export-secondary" type="button" data-rel-export-close>Cancelar</button>
                <button class="rel-export-primary" type="submit">
                    <i data-lucide="download"></i>
                    Exportar
                </button>
            </div>
        </form>
    </div>
</div>
