/**
 * Orcamento UI module
 * Rendering, modal helpers and UI-only budget helpers.
 */

export function createOrcamentoUi({
    STATE,
    Utils,
    getCategoryIconColor,
    isDemoItem,
}) {
    function setupMoneyInputs() {
        const moneyFields = ['orcValor'];
        moneyFields.forEach((id) => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', () => Utils.formatarDinheiro(input));
                input.addEventListener('focus', () => {
                    if (!input.value) input.value = '0,00';
                });
            }
        });
    }

    function populateCategoriaSelect() {
        const select = document.getElementById('orcCategoria');
        if (!select) return;
        select.innerHTML = '<option value="">Selecione uma categoria</option>';
        STATE.categorias.forEach((cat) => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.nome;
            select.appendChild(opt);
        });
    }

    function renderResumo(data) {
        if (!data) return;

        const orc = data.orcamento || data.orcamentos || {};

        const saude = orc.saude_financeira || {};
        const score = saude.score ?? orc.saude_score ?? 0;
        const ringFill = document.getElementById('saudeRingFill');
        const scoreEl = document.getElementById('saudeScore');
        const labelEl = document.getElementById('saudeLabel');
        const saudeContent = document.getElementById('saudeContent');
        const saudeCta = document.getElementById('saudeCta');

        const temOrcamentos = (orc.total_limite ?? orc.total_orcado ?? 0) > 0 || STATE.orcamentos.length > 0;

        if (!temOrcamentos && saudeContent && saudeCta) {
            saudeContent.style.display = 'none';
            saudeCta.style.display = '';
        } else if (saudeContent && saudeCta) {
            saudeContent.style.display = '';
            saudeCta.style.display = 'none';
        }

        if (ringFill) {
            ringFill.style.strokeDasharray = `${score}, 100`;
            ringFill.classList.remove('orc-score--good', 'orc-score--warn', 'orc-score--bad');
            if (score >= 70) ringFill.classList.add('orc-score--good');
            else if (score >= 40) ringFill.classList.add('orc-score--warn');
            else ringFill.classList.add('orc-score--bad');
        }
        if (scoreEl) scoreEl.textContent = score;
        if (labelEl) {
            if (score >= 80) labelEl.textContent = 'Excelente!';
            else if (score >= 60) labelEl.textContent = 'Bom';
            else if (score >= 40) labelEl.textContent = 'Atencao';
            else labelEl.textContent = 'Critico';

            labelEl.className = 'orc-summary-card__status';
            if (score >= 70) labelEl.classList.add('orc-status--good');
            else if (score >= 40) labelEl.classList.add('orc-status--warn');
            else labelEl.classList.add('orc-status--bad');
        }

        const totalOrcado = orc.total_limite ?? orc.total_orcado ?? 0;
        const totalGasto = orc.total_gasto ?? 0;
        const totalDisponivel = orc.total_disponivel ?? Math.max(0, totalOrcado - totalGasto);
        Utils.setText('totalOrcado', Utils.formatCurrency(totalOrcado));
        Utils.setText('totalGasto', Utils.formatCurrency(totalGasto));
        Utils.setText('totalDisponivel', Utils.formatCurrency(totalDisponivel));
    }

    function getPeriodBudgetContext() {
        const totalDays = new Date(STATE.currentYear, STATE.currentMonth, 0).getDate();
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;
        const selectedKey = (STATE.currentYear * 12) + STATE.currentMonth;
        const currentKey = (currentYear * 12) + currentMonth;

        if (selectedKey < currentKey) {
            return {
                phase: 'past',
                totalDays,
                remainingDays: 0,
                elapsedDays: totalDays,
            };
        }

        if (selectedKey > currentKey) {
            return {
                phase: 'future',
                totalDays,
                remainingDays: totalDays,
                elapsedDays: 0,
            };
        }

        const today = Math.min(now.getDate(), totalDays);
        return {
            phase: 'current',
            totalDays,
            remainingDays: Math.max(1, totalDays - today + 1),
            elapsedDays: today,
        };
    }

    function formatDayWindow(days, fallback = 'periodo') {
        if (!days || days <= 0) return fallback;
        return `${days} dia${days === 1 ? '' : 's'}`;
    }

    function getDailyBudgetInfo(orc) {
        const context = getPeriodBudgetContext();
        const gasto = Number(orc?.gasto_real || 0);
        const limiteBase = Number(orc?.valor_limite || 0);
        const limiteEfetivo = Number(orc?.limite_efetivo || limiteBase);
        const disponivel = Number(orc?.disponivel ?? Math.max(0, limiteEfetivo - gasto));
        const excedido = Number(orc?.excedido ?? Math.max(0, gasto - limiteEfetivo));

        if (context.phase === 'past') {
            const mediaReal = context.totalDays > 0 ? gasto / context.totalDays : 0;
            return {
                tone: 'neutral',
                label: 'Media real por dia',
                value: `${Utils.formatCurrency(mediaReal)}/dia`,
                hint: `Periodo encerrado em ${context.totalDays} dias.`,
            };
        }

        if (context.phase === 'future') {
            const planejado = context.totalDays > 0 ? limiteEfetivo / context.totalDays : 0;
            return {
                tone: 'info',
                label: 'Planejado por dia',
                value: `${Utils.formatCurrency(planejado)}/dia`,
                hint: `Distribuindo o limite pelos ${context.totalDays} dias do periodo.`,
            };
        }

        if (excedido > 0) {
            const corteDiario = context.remainingDays > 0 ? excedido / context.remainingDays : excedido;
            return {
                tone: 'danger',
                label: 'Corte necessario por dia',
                value: `${Utils.formatCurrency(corteDiario)}/dia`,
                hint: `Para compensar ${Utils.formatCurrency(excedido)} nos proximos ${formatDayWindow(context.remainingDays)}.`,
            };
        }

        const diarioDisponivel = context.remainingDays > 0 ? disponivel / context.remainingDays : 0;
        return {
            tone: diarioDisponivel > 0 ? 'success' : 'warning',
            label: 'Pode gastar por dia',
            value: `${Utils.formatCurrency(diarioDisponivel)}/dia`,
            hint: `Pelos proximos ${formatDayWindow(context.remainingDays)} sem estourar.`,
        };
    }

    function compareOrcamentos(a, b, sort) {
        const aRemaining = a.disponivel ?? 0;
        const bRemaining = b.disponivel ?? 0;
        const aExceeded = a.excedido ?? 0;
        const bExceeded = b.excedido ?? 0;

        switch (sort) {
            case 'exceeded':
                return bExceeded - aExceeded;
            case 'remaining':
                return bRemaining - aRemaining;
            case 'alpha':
                return (a.categoria?.nome || a.categoria_nome || '').localeCompare((b.categoria?.nome || b.categoria_nome || ''), 'pt-BR');
            case 'usage':
            default:
                return (b.percentual || 0) - (a.percentual || 0);
        }
    }

    function getFilteredOrcamentos() {
        const query = STATE.ui.query.trim().toLowerCase();

        return STATE.orcamentos.filter((orc) => {
            const catNome = (orc.categoria?.nome || orc.categoria_nome || '').toLowerCase();
            if (query && !catNome.includes(query)) {
                return false;
            }

            const pct = orc.percentual || 0;
            switch (STATE.ui.filter) {
                case 'over':
                    return pct > 100;
                case 'warn':
                    return pct >= 80 && pct <= 100;
                case 'ok':
                    return pct < 80;
                case 'rollover':
                    return !!orc.rollover && (orc.rollover_valor || 0) > 0;
                default:
                    return true;
            }
        }).sort((a, b) => compareOrcamentos(a, b, STATE.ui.sort));
    }

    function renderFocusPanel() {
        const focus = document.getElementById('orcFocusContent');
        const stats = document.getElementById('orcFocusStats');
        if (!focus || !stats) return;

        const periodContext = getPeriodBudgetContext();
        const resumo = STATE.resumo?.orcamento || STATE.resumo?.orcamentos || {};
        const emAlerta = resumo.em_alerta ?? STATE.orcamentos.filter((orc) => (orc.percentual || 0) >= 80 && (orc.percentual || 0) <= 100).length;
        const estourados = resumo.estourados ?? STATE.orcamentos.filter((orc) => (orc.percentual || 0) > 100).length;
        const usoGeral = resumo.percentual_geral ?? 0;
        const totalOrcado = resumo.total_limite ?? resumo.total_orcado ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.limite_efetivo || orc.valor_limite || 0), 0);
        const totalGasto = resumo.total_gasto ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.gasto_real || 0), 0);
        const disponivelTotal = resumo.total_disponivel ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.disponivel ?? 0), 0);
        const topPressure = [...STATE.orcamentos].sort((a, b) => (b.percentual || 0) - (a.percentual || 0))[0];
        const dailyTotal = periodContext.phase === 'past'
            ? (periodContext.totalDays > 0 ? totalGasto / periodContext.totalDays : 0)
            : (periodContext.phase === 'future'
                ? (periodContext.totalDays > 0 ? totalOrcado / periodContext.totalDays : 0)
                : (periodContext.remainingDays > 0 ? disponivelTotal / periodContext.remainingDays : 0));
        const dailyTotalLabel = periodContext.phase === 'past'
            ? 'Media diaria'
            : (periodContext.phase === 'future' ? 'Plano diario' : 'Folga por dia');

        stats.innerHTML = `
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Em alerta</span>
                <strong class="orc-focus-stat__value">${emAlerta}</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Estourados</span>
                <strong class="orc-focus-stat__value">${estourados}</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Uso geral</span>
                <strong class="orc-focus-stat__value">${Math.round(usoGeral)}%</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">${dailyTotalLabel}</span>
                <strong class="orc-focus-stat__value">${Utils.formatCurrency(dailyTotal)}/dia</strong>
            </div>
        `;

        if (!topPressure) {
            focus.innerHTML = `
                <div class="orc-focus-callout">
                    <div>
                        <h2 class="orc-focus-callout__title">Nenhum orcamento criado ainda.</h2>
                        <p class="orc-focus-callout__text">Comece com sugestoes automaticas ou crie manualmente as categorias que mais pesam no seu mes.</p>
                    </div>
                    <div class="orc-focus-callout__actions">
                        <button type="button" class="orc-action-btn orc-action-btn--primary" onclick="orcamentoManager.openSugestoes()">
                            <i data-lucide="wand-2"></i>
                            <span>Sugestao Inteligente</span>
                        </button>
                        <button type="button" class="orc-action-btn orc-action-btn--success" onclick="orcamentoManager.openOrcamentoModal()">
                            <i data-lucide="plus"></i>
                            <span>Novo Orcamento</span>
                        </button>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        const topName = topPressure.categoria?.nome || topPressure.categoria_nome || 'Categoria';
        const topDailyInfo = getDailyBudgetInfo(topPressure);
        const topRemaining = topPressure.percentual > 100
            ? `Excedido em <strong>${Utils.formatCurrency(topPressure.excedido || 0)}</strong>.`
            : `Restam <strong>${Utils.formatCurrency(topPressure.disponivel || 0)}</strong> nesta categoria.`;
        const helper = disponivelTotal > 0
            ? `Voce ainda tem ${Utils.formatCurrency(disponivelTotal)} de folga no total do periodo.`
            : 'Seu limite total do periodo ja foi consumido.';

        focus.innerHTML = `
            <div class="orc-focus-callout">
                <div>
                    <h2 class="orc-focus-callout__title">${Utils.escHtml(topName)}</h2>
                    <p class="orc-focus-callout__text">${topRemaining} ${helper} ${Utils.escHtml(topDailyInfo.hint)}</p>
                    <div class="orc-focus-callout__meta">
                        <span class="orc-focus-callout__pill">${Math.round(topPressure.percentual || 0)}% usado</span>
                        <span class="orc-focus-callout__pill">${Utils.escHtml(topDailyInfo.label)}: ${Utils.escHtml(topDailyInfo.value)}</span>
                        ${topPressure.rollover && (topPressure.rollover_valor || 0) > 0
                ? `<span class="orc-focus-callout__pill">Rollover de ${Utils.formatCurrency(topPressure.rollover_valor || 0)}</span>`
                : ''}
                    </div>
                </div>
                <div class="orc-focus-callout__actions">
                    <button type="button" class="orc-action-btn orc-action-btn--success" onclick="orcamentoManager.openOrcamentoModal(${topPressure.id})">
                        <i data-lucide="pencil"></i>
                        <span>Ajustar limite</span>
                    </button>
                    <button type="button" class="orc-action-btn" onclick="orcamentoManager.openSugestoes()">
                        <i data-lucide="wand-2"></i>
                        <span>Comparar sugestao</span>
                    </button>
                </div>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    }

    function buildDerivedInsights() {
        const periodContext = getPeriodBudgetContext();
        const resumo = STATE.resumo?.orcamento || STATE.resumo?.orcamentos || {};
        const totalGasto = resumo.total_gasto ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.gasto_real || 0), 0);
        const percentualGeral = resumo.percentual_geral ?? 0;
        const derived = [];

        if (percentualGeral >= 90 && totalGasto > 0) {
            derived.push({
                tipo: 'perigo',
                titulo: 'Mes apertado no consolidado',
                mensagem: `Voce ja consumiu ${Math.round(percentualGeral)}% do limite total deste periodo.`,
                icone: 'siren',
            });
        } else if (percentualGeral >= 70 && totalGasto > 0) {
            derived.push({
                tipo: 'alerta',
                titulo: 'Uso geral pede atencao',
                mensagem: `Seu uso geral esta em ${Math.round(percentualGeral)}%. Vale revisar as categorias mais pressionadas.`,
                icone: 'gauge',
            });
        }

        if (totalGasto > 0) {
            const topSpender = [...STATE.orcamentos].sort((a, b) => (b.gasto_real || 0) - (a.gasto_real || 0))[0];
            const share = topSpender ? ((topSpender.gasto_real || 0) / totalGasto) * 100 : 0;
            if (topSpender && share >= 40) {
                derived.push({
                    tipo: 'info',
                    titulo: `${topSpender.categoria?.nome || topSpender.categoria_nome} concentra boa parte do gasto`,
                    mensagem: `Essa categoria representa ${Math.round(share)}% do total gasto no periodo.`,
                    icone: 'pie-chart',
                    categoria_id: topSpender.categoria_id,
                });
            }
        }

        const topPressure = [...STATE.orcamentos].sort((a, b) => (b.percentual || 0) - (a.percentual || 0))[0];
        if (topPressure && periodContext.phase !== 'past') {
            const dailyInfo = getDailyBudgetInfo(topPressure);
            derived.push({
                tipo: topPressure.percentual > 100 ? 'perigo' : 'info',
                titulo: `${topPressure.categoria?.nome || topPressure.categoria_nome} pede ritmo diario claro`,
                mensagem: `${dailyInfo.label}: ${dailyInfo.value}. ${dailyInfo.hint}`,
                icone: topPressure.percentual > 100 ? 'triangle-alert' : 'calendar-range',
                categoria_id: topPressure.categoria_id,
            });
        }

        const rolloverWin = STATE.orcamentos.find((orc) => (orc.rollover_valor || 0) > 0 && (orc.percentual || 0) < 80);
        if (rolloverWin) {
            derived.push({
                tipo: 'positivo',
                titulo: `${rolloverWin.categoria?.nome || rolloverWin.categoria_nome} esta respirando melhor`,
                mensagem: `O rollover adicionou ${Utils.formatCurrency(rolloverWin.rollover_valor || 0)} de folga nesta categoria.`,
                icone: 'refresh-cw',
                categoria_id: rolloverWin.categoria_id,
            });
        }

        const slackCandidate = [...STATE.orcamentos]
            .filter((orc) => (orc.percentual || 0) <= 35 && (orc.disponivel || 0) > 0)
            .sort((a, b) => (b.disponivel || 0) - (a.disponivel || 0))[0];
        if (slackCandidate) {
            derived.push({
                tipo: 'positivo',
                titulo: `${slackCandidate.categoria?.nome || slackCandidate.categoria_nome} esta com folga relevante`,
                mensagem: `Ainda sobram ${Utils.formatCurrency(slackCandidate.disponivel || 0)}. Talvez o limite possa ser refinado no proximo ciclo.`,
                icone: 'sparkles',
                categoria_id: slackCandidate.categoria_id,
            });
        }

        return derived;
    }

    function renderOrcamentos() {
        const grid = document.getElementById('orcamentosGrid');
        const empty = document.getElementById('orcamentosEmpty');
        if (!grid || !empty) return;

        if (!STATE.orcamentos.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';
        const filteredOrcamentos = getFilteredOrcamentos();

        if (!filteredOrcamentos.length) {
            grid.innerHTML = `
                <div class="orc-soft-empty surface-card">
                    <i data-lucide="search-x"></i>
                    <p>Nenhum orcamento encontrado para os filtros atuais.</p>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        grid.innerHTML = filteredOrcamentos.map((orc) => {
            const pct = orc.percentual || 0;
            const isDemo = isDemoItem(orc);
            const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
            const catNome = orc.categoria?.nome || orc.categoria_nome || 'Categoria';
            const catIcone = orc.categoria?.icone || 'tag';
            const gasto = orc.gasto_real || 0;
            const limiteBase = orc.valor_limite || 0;
            const limiteEfetivo = orc.limite_efetivo || limiteBase;
            const disponivel = orc.disponivel ?? Math.max(0, limiteEfetivo - gasto);
            const excedido = orc.excedido ?? Math.max(0, gasto - limiteEfetivo);
            const rolloverTag = orc.rollover && orc.rollover_valor > 0
                ? `<span class="orc-card__badge" title="Inclui R$ ${Utils.formatNumber(orc.rollover_valor)} do mes anterior">+${Utils.formatNumber(orc.rollover_valor)} rollover</span>`
                : '';
            const limitHint = limiteEfetivo > limiteBase
                ? `<span class="orc-card__limit-hint">Base ${Utils.formatCurrency(limiteBase)} - Efetivo ${Utils.formatCurrency(limiteEfetivo)}</span>`
                : `<span class="orc-card__limit-hint">Limite do periodo ${Utils.formatCurrency(limiteBase)}</span>`;
            const dailyInfo = getDailyBudgetInfo(orc);
            const dailyIcon = dailyInfo.tone === 'danger'
                ? 'triangle-alert'
                : (dailyInfo.tone === 'info'
                    ? 'calendar-range'
                    : (dailyInfo.tone === 'neutral' ? 'receipt' : 'wallet'));
            const actionsMarkup = isDemo
                ? '<span class="orc-card__badge">Exemplo</span>'
                : `
                    <button class="orc-card__action-btn" onclick="orcamentoManager.openOrcamentoModal(${orc.id})" title="Editar">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="orc-card__action-btn orc-card__action-btn--danger" onclick="orcamentoManager.deleteOrcamento(${orc.id})" title="Excluir">
                        <i data-lucide="trash-2"></i>
                    </button>
                `;

            return `
            <div class="orc-card surface-card surface-card--interactive surface-card--clip ${statusClass}" data-aos="fade-up">
                <div class="orc-card__header">
                    <div class="orc-card__category">
                        <span class="orc-card__icon"><i data-lucide="${catIcone}" style="color:${getCategoryIconColor(catIcone)}"></i></span>
                        <span class="orc-card__name">${Utils.escHtml(catNome)}</span>
                    </div>
                    <div class="orc-card__actions">
                        ${actionsMarkup}
                    </div>
                </div>
                <div class="orc-card__progress">
                    <div class="orc-card__progress-bar">
                        <div class="orc-card__progress-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                    </div>
                    <div class="orc-card__progress-info">
                        <span class="orc-card__spent">${Utils.formatCurrency(gasto)}</span>
                        <span class="orc-card__limit">de ${Utils.formatCurrency(limiteEfetivo)}</span>
                    </div>
                    ${limitHint}
                    <div class="orc-card__daily orc-card__daily--${dailyInfo.tone}">
                        <div class="orc-card__daily-label">
                            <i data-lucide="${dailyIcon}"></i>
                            <span>${Utils.escHtml(dailyInfo.label)}</span>
                        </div>
                        <strong class="orc-card__daily-value">${Utils.escHtml(dailyInfo.value)}</strong>
                        <span class="orc-card__daily-hint">${Utils.escHtml(dailyInfo.hint)}</span>
                    </div>
                </div>
                <div class="orc-card__footer">
                    <span class="orc-card__pct ${statusClass}">${pct.toFixed(0)}%</span>
                    <span class="orc-card__remaining ${excedido > 0 ? 'orc-card__remaining--negative' : ''}">
                        ${excedido > 0 ? 'Excedido' : 'Resta'} ${Utils.formatCurrency(excedido > 0 ? excedido : disponivel)}
                    </span>
                    ${rolloverTag}
                </div>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    function renderInsights(insights) {
        const section = document.getElementById('insightsSection');
        const grid = document.getElementById('insightsGrid');
        if (!grid) return;
        const combinedInsights = [...(insights || []), ...buildDerivedInsights()];
        const dedupedInsights = Array.from(new Map(combinedInsights.map((item) => [`${item.tipo}:${item.titulo}`, item])).values()).slice(0, 8);

        if (!dedupedInsights.length) {
            if (section) section.style.display = 'none';
            return;
        }

        if (section) section.style.display = '';
        grid.innerHTML = dedupedInsights.map((insight) => {
            const icon = insight.icone || Utils.getInsightIcon(insight.tipo);
            const levelMap = { alerta: 'warning', perigo: 'danger', positivo: 'success', info: 'info' };
            const level = insight.nivel || levelMap[insight.tipo] || 'info';
            const cta = insight.categoria_id
                ? `<button type="button" class="orc-insight-action" onclick="orcamentoManager.openOrcamentoModalByCategoria(${insight.categoria_id})">
                        <i data-lucide="pencil"></i>
                        <span>Ajustar</span>
                   </button>`
                : '';
            return `
            <div class="orc-insight-card surface-card surface-card--interactive ${level}" data-aos="fade-up">
                <div class="orc-insight-icon ${level}">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="orc-insight-content">
                    <span class="orc-insight-title">${Utils.escHtml(insight.titulo || '')}</span>
                    <p class="orc-insight-text">${Utils.escHtml(insight.mensagem || '')}</p>
                </div>
                ${cta}
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    function openModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('active');
            if (!window.LK?.modalSystem) {
                document.body.style.overflow = 'hidden';
            }
        }
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('active');
            if (!window.LK?.modalSystem) {
                document.body.style.overflow = '';
            }
        }
    }

    return {
        setupMoneyInputs,
        populateCategoriaSelect,
        renderResumo,
        getPeriodBudgetContext,
        formatDayWindow,
        getDailyBudgetInfo,
        getFilteredOrcamentos,
        compareOrcamentos,
        renderFocusPanel,
        buildDerivedInsights,
        renderOrcamentos,
        renderInsights,
        openModal,
        closeModal,
    };
}
