<!-- ====== STEP 5: Categoria + Recorrência + Lembrete (pulável) ====== -->
<div class="lk-wizard-step" data-step="5" id="globalStep5">
    <div class="lk-wizard-question">
        <h3>
            <i data-lucide="sparkles"></i>
            Quer organizar melhor?
        </h3>
        <p>Categoria, recorrência e lembrete são opcionais</p>
    </div>

    <!-- Tipo de Agendamento (somente para agendamento) - LEGACY hidden -->
    <div class="lk-form-group" id="globalTipoAgendamentoGroup" style="display: none;">
        <label class="lk-label required">
            <i data-lucide="tag"></i>
            Tipo de Agendamento
        </label>
        <div class="lk-tipo-agendamento-btns">
            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-receita"
                onclick="lancamentoGlobalManager.selecionarTipoAgendamento('receita')">
                <i data-lucide="arrow-down"></i> Receita
            </button>
            <button type="button" class="lk-btn-tipo-ag lk-btn-tipo-despesa active"
                onclick="lancamentoGlobalManager.selecionarTipoAgendamento('despesa')">
                <i data-lucide="arrow-up"></i> Despesa
            </button>
        </div>
    </div>

    <!-- Categoria -->
    <div class="lk-form-group" id="globalCategoriaGroup">
        <label for="globalLancamentoCategoria" class="lk-label">
            <i data-lucide="tag"></i>
            Categoria
            <button type="button" class="lk-info" data-lk-tooltip-title="Categoria"
                data-lk-tooltip="Ajuda a organizar e visualizar para onde vai seu dinheiro nos relatórios."
                aria-label="Ajuda: Categoria">
                <i data-lucide="info" aria-hidden="true"></i>
            </button>
        </label>
        <div class="lk-ai-category-row">
            <div class="lk-select-wrapper" style="flex:1">
                <select id="globalLancamentoCategoria" name="categoria_id" class="lk-select"
                    data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                    data-lk-select-search-placeholder="Buscar categoria...">
                    <option value="">Sem categoria</option>
                </select>
                <i data-lucide="chevron-down" class="lk-select-icon"></i>
            </div>
            <button type="button" class="lk-btn-ai-suggest" id="btnGlobalAiSuggestCategoria"
                onclick="lancamentoGlobalManager.sugerirCategoriaIA()" title="Sugerir categoria com IA">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div class="lk-planning-alerts" id="globalCategoriaPlanningAlerts" hidden></div>

    <!-- Subcategoria -->
    <div class="lk-form-group subcategoria-select-group" id="globalSubcategoriaGroup">
        <label for="globalLancamentoSubcategoria" class="lk-label">
            <i data-lucide="tags"></i>
            Subcategoria
        </label>
        <div class="lk-select-wrapper">
            <select id="globalLancamentoSubcategoria" name="subcategoria_id" class="lk-select"
                data-lk-custom-select="modal" data-lk-select-search="true" data-lk-select-sort="alpha"
                data-lk-select-search-placeholder="Buscar subcategoria...">
                <option value="">Sem subcategoria</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>
    </div>

    <!-- Recorrência (para receita e despesa) -->
    <div class="lk-form-group" id="globalRecorrenciaGroup" style="display: none;">
        <div class="lk-checkbox-wrapper" style="margin-bottom: 0.5rem;">
            <label class="lk-checkbox-label">
                <input type="checkbox" id="globalLancamentoRecorrente" name="recorrente" value="1" class="lk-checkbox"
                    onchange="lancamentoGlobalManager.toggleRecorrencia()">
                <span class="lk-checkbox-custom"></span>
                <span class="lk-checkbox-text">
                    <i data-lucide="refresh-cw"></i>
                    Repetir este lançamento
                </span>
            </label>
        </div>
        <small class="lk-helper-text" style="margin-top: -0.25rem; margin-bottom: 0.5rem;">Cria
            automaticamente este lançamento nos próximos períodos.</small>

        <div id="globalRecorrenciaDetalhes" style="display: none;">
            <label class="lk-label">
                <i data-lucide="refresh-cw"></i>
                Frequência
            </label>
            <div class="lk-select-wrapper" style="margin-bottom: 0.75rem;">
                <select id="globalLancamentoRecorrenciaFreq" name="recorrencia_freq" class="lk-select"
                    data-lk-custom-select="modal" data-lk-select-search="true"
                    data-lk-select-search-placeholder="Buscar frequencia...">
                    <option value="semanal">Semanalmente</option>
                    <option value="quinzenal">Quinzenalmente</option>
                    <option value="mensal" selected>Mensalmente</option>
                    <option value="bimestral">Bimestralmente</option>
                    <option value="trimestral">Trimestralmente</option>
                    <option value="semestral">Semestralmente</option>
                    <option value="anual">Anualmente</option>
                </select>
                <i data-lucide="chevron-down" class="lk-select-icon"></i>
            </div>

            <label class="lk-label">
                <i data-lucide="flag"></i>
                Quando termina?
            </label>
            <div class="lk-radio-group" style="margin-bottom: 0.5rem;">
                <label class="lk-radio-label" id="globalRecorrenciaRadioInfinito">
                    <input type="radio" name="global_recorrencia_modo" value="infinito" class="lk-radio" checked
                        onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                    <span class="lk-radio-custom"></span>
                    <span class="lk-radio-text">Sem fim <small style="opacity:0.7">(ex: Spotify,
                            Netflix)</small></span>
                </label>
                <label class="lk-radio-label">
                    <input type="radio" name="global_recorrencia_modo" value="quantidade" class="lk-radio"
                        onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                    <span class="lk-radio-custom"></span>
                    <span class="lk-radio-text">Após um número de vezes</span>
                </label>
                <label class="lk-radio-label">
                    <input type="radio" name="global_recorrencia_modo" value="data" class="lk-radio"
                        onchange="lancamentoGlobalManager.toggleRecorrenciaFim()">
                    <span class="lk-radio-custom"></span>
                    <span class="lk-radio-text">Até uma data específica</span>
                </label>
            </div>

            <div id="globalRecorrenciaTotalGroup" style="display: none;">
                <div class="lk-input-group">
                    <input type="number" id="globalLancamentoRecorrenciaTotal" name="recorrencia_total" class="lk-input"
                        min="2" max="120" value="12" placeholder="12">
                    <span class="lk-input-suffix">vezes</span>
                </div>
                <small class="lk-helper-text">Total de repetições incluindo a primeira.</small>
            </div>

            <div id="globalRecorrenciaFimGroup" style="display: none;">
                <input type="date" id="globalLancamentoRecorrenciaFim" name="recorrencia_fim" class="lk-input">
                <small class="lk-helper-text">Data em que a repetição termina.</small>
            </div>
        </div>
    </div>

    <!-- Lembrete (para receita e despesa) -->
    <div class="lk-form-group" id="globalLembreteGroup" style="display: none;">
        <label for="globalLancamentoTempoAviso" class="lk-label">
            <i data-lucide="bell"></i>
            Lembrete
            <span class="lk-optional-badge">opcional</span>
        </label>
        <div class="lk-select-wrapper">
            <select id="globalLancamentoTempoAviso" name="lembrar_antes_segundos" class="lk-select"
                data-lk-custom-select="modal" data-lk-select-search="true"
                data-lk-select-search-placeholder="Buscar lembrete...">
                <option value="">Sem lembrete</option>
                <option value="86400">1 dia antes</option>
                <option value="172800">2 dias antes</option>
                <option value="259200">3 dias antes</option>
                <option value="604800">1 semana antes</option>
            </select>
            <i data-lucide="chevron-down" class="lk-select-icon"></i>
        </div>

        <div id="globalCanaisNotificacaoInline" style="display: none; margin-top: 0.5rem;">
            <div class="lk-checkbox-wrapper">
                <label class="lk-checkbox-label">
                    <input type="checkbox" id="globalCanalInapp" name="canal_inapp" value="1" class="lk-checkbox"
                        checked>
                    <span class="lk-checkbox-custom"></span>
                    <span class="lk-checkbox-text">
                        <i data-lucide="monitor"></i>
                        Aviso no sistema
                    </span>
                </label>
            </div>
            <div class="lk-checkbox-wrapper">
                <label class="lk-checkbox-label">
                    <input type="checkbox" id="globalCanalEmail" name="canal_email" value="1" class="lk-checkbox"
                        checked>
                    <span class="lk-checkbox-custom"></span>
                    <span class="lk-checkbox-text">
                        <i data-lucide="mail"></i>
                        E-mail
                    </span>
                </label>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <div class="lk-wizard-nav">
        <div class="lk-wizard-nav-left">
            <button type="button" class="lk-btn-voltar" onclick="lancamentoGlobalManager.prevStep()">
                <i data-lucide="arrow-left"></i>
                Voltar
            </button>
        </div>
        <div class="lk-wizard-nav-right">
            <button type="button" class="lk-btn-skip" onclick="lancamentoGlobalManager.skipAndSave()">
                <i data-lucide="fast-forward"></i>
                Pular e Salvar
            </button>
            <button type="submit" class="lk-btn lk-btn-primary" id="globalBtnSalvar">
                <i data-lucide="save"></i>
                Salvar
            </button>
        </div>
    </div>
</div>