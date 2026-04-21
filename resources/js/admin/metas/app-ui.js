/**
 * Metas UI module
 * Rendering, modal helpers and UI-only meta helpers.
 */

export function createMetasUi({
    STATE,
    Utils,
    isDemoItem,
}) {
    function setupMoneyInputs() {
        const moneyFields = ['metaValorAlvo', 'metaValorAtual'];
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

    function onMetaContaChange() {
        syncMetaAllocationField();
    }

    function syncMetaAllocationField() {
        const contaGroup = document.getElementById('metaContaId')?.closest('.fin-form-group');
        const valorAtualGroup = document.getElementById('metaValorAtual')?.closest('.fin-form-group');
        const valorAtualLabel = valorAtualGroup?.querySelector('.fin-label');
        const hint = document.getElementById('metaContaHint');
        const saldo = 0;

        if (contaGroup) {
            contaGroup.style.display = 'none';
            if (hint) {
                hint.innerHTML = `<i data-lucide="info"></i> Saldo atual da conta: <strong>${Utils.formatCurrency(saldo)}</strong> - sera usado como valor inicial. O progresso atualiza automaticamente.`;
                hint.style.display = 'none';
                if (window.lucide) lucide.createIcons();
            }
        } else {
            if (valorAtualGroup) valorAtualGroup.style.display = '';
            if (hint) {
                hint.style.display = 'none';
                hint.innerHTML = '';
            }
        }

        if (valorAtualGroup) valorAtualGroup.style.display = '';
        if (valorAtualLabel) valorAtualLabel.innerHTML = '<i data-lucide="coins"></i> Valor ja alocado';
        if (hint) {
            hint.style.display = 'none';
            hint.innerHTML = '';
        }
    }

    function renderResumo(data) {
        if (!data) return;

        const met = data.metas || {};
        const totalMetas = met.total_metas ?? met.ativas ?? 0;
        const totalAtual = met.total_atual ?? 0;
        const totalAlvo = met.total_alvo ?? 0;
        const progressoGeral = met.progresso_geral ?? 0;
        const atrasadas = met.atrasadas ?? 0;

        Utils.setText('metasAtivas', `${totalMetas} ativa${totalMetas !== 1 ? 's' : ''}`);
        Utils.setText('metasTotalAtual', Utils.formatCurrency(totalAtual));
        Utils.setText('metasTotalAlvo', Utils.formatCurrency(totalAlvo));

        const ringFill = document.getElementById('metasProgressRingFill');
        const scoreEl = document.getElementById('metasProgressScore');
        const labelEl = document.getElementById('metasProgressLabel');

        if (ringFill) {
            ringFill.style.strokeDasharray = `${progressoGeral}, 100`;
            ringFill.classList.remove('met-ring-fill--good', 'met-ring-fill--warn', 'met-ring-fill--bad');
            if (atrasadas > 0 || progressoGeral < 30) ringFill.classList.add('met-ring-fill--bad');
            else if (progressoGeral < 70) ringFill.classList.add('met-ring-fill--warn');
            else ringFill.classList.add('met-ring-fill--good');
        }

        if (scoreEl) scoreEl.textContent = `${Math.round(progressoGeral)}%`;
        if (labelEl) {
            labelEl.className = 'met-summary-card__status';
            if (atrasadas > 0) {
                labelEl.textContent = `${atrasadas} atrasada${atrasadas > 1 ? 's' : ''}`;
                labelEl.classList.add('met-status--bad');
            } else if (totalMetas === 0) {
                labelEl.textContent = 'Nenhuma meta';
            } else if (progressoGeral >= 80) {
                labelEl.textContent = 'Quase la';
                labelEl.classList.add('met-status--good');
            } else {
                labelEl.textContent = 'Em progresso';
                labelEl.classList.add('met-status--good');
            }
        }
    }

    function getFilteredMetas() {
        const query = STATE.ui.query.trim().toLowerCase();

        return STATE.metas.filter((meta) => {
            if (query && !(meta.titulo || '').toLowerCase().includes(query)) {
                return false;
            }

            switch (STATE.ui.filter) {
                case 'ativa':
                    return meta.status === 'ativa';
                case 'atrasada':
                    return meta.is_atrasada === true || ((meta.dias_restantes ?? 1) < 0 && meta.status === 'ativa');
                case 'concluida':
                    return meta.status === 'concluida';
                default:
                    return true;
            }
        }).sort((a, b) => compareMetas(a, b, STATE.ui.sort));
    }

    function compareMetas(a, b, sort) {
        const priorityWeight = { alta: 0, media: 1, baixa: 2 };

        switch (sort) {
            case 'progress':
                return (b.progresso || 0) - (a.progresso || 0);
            case 'remaining':
                return (b.valor_restante || 0) - (a.valor_restante || 0);
            case 'priority':
                return (priorityWeight[a.prioridade] ?? 99) - (priorityWeight[b.prioridade] ?? 99);
            case 'title':
                return (a.titulo || '').localeCompare(b.titulo || '', 'pt-BR');
            case 'deadline':
            default: {
                const aDeadline = a.dias_restantes ?? Number.POSITIVE_INFINITY;
                const bDeadline = b.dias_restantes ?? Number.POSITIVE_INFINITY;
                if (aDeadline !== bDeadline) return aDeadline - bDeadline;
                return (b.progresso || 0) - (a.progresso || 0);
            }
        }
    }

    function renderFocusPanel() {
        const focus = document.getElementById('metFocusContent');
        const stats = document.getElementById('metFocusStats');
        if (!focus || !stats) return;

        const activeMetas = STATE.metas.filter((meta) => meta.status === 'ativa');
        const overdueMetas = activeMetas.filter((meta) => meta.is_atrasada === true || (meta.dias_restantes ?? 1) < 0);
        const completedMetas = STATE.metas.filter((meta) => meta.status === 'concluida');
        const recommendedMonthly = activeMetas.reduce((sum, meta) => sum + (meta.aporte_mensal_sugerido || 0), 0);
        const nextMeta = [...activeMetas].sort((a, b) => {
            const progressDiff = (b.progresso || 0) - (a.progresso || 0);
            if (progressDiff !== 0) return progressDiff;
            return (a.valor_restante || 0) - (b.valor_restante || 0);
        })[0];

        stats.innerHTML = `
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Em risco</span>
                <strong class="met-focus-stat__value">${overdueMetas.length}</strong>
            </div>
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Aporte sugerido</span>
                <strong class="met-focus-stat__value">${recommendedMonthly > 0 ? Utils.formatCurrency(recommendedMonthly) : 'Sem prazo'}</strong>
            </div>
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Concluidas</span>
                <strong class="met-focus-stat__value">${completedMetas.length}</strong>
            </div>
        `;

        if (!nextMeta) {
            focus.innerHTML = `
                <div class="met-focus-callout">
                    <div>
                        <h2 class="met-focus-callout__title">Voce ainda nao tem uma meta ativa.</h2>
                        <p class="met-focus-callout__text">Use um template para sair do zero mais rapido ou crie uma meta com valor e prazo.</p>
                    </div>
                    <div class="met-focus-callout__actions">
                        <button type="button" class="met-action-btn met-action-btn--primary" onclick="metasManager.openMetaModal()">
                            <i data-lucide="plus"></i>
                            <span>Criar Meta</span>
                        </button>
                        <button type="button" class="met-action-btn" onclick="metasManager.openTemplates()">
                            <i data-lucide="wand-sparkles"></i>
                            <span>Usar Template</span>
                        </button>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        const monthlyHint = nextMeta.aporte_mensal_sugerido > 0
            ? `${Utils.formatCurrency(nextMeta.aporte_mensal_sugerido)}/mes sugeridos`
            : 'Sem prazo definido para calculo de aporte';
        const deadlineHint = nextMeta.dias_restantes == null
            ? 'Sem prazo definido.'
            : (nextMeta.dias_restantes < 0
                ? 'Prazo vencido.'
                : `${nextMeta.dias_restantes} dia${nextMeta.dias_restantes === 1 ? '' : 's'} restantes.`);
        const primaryAction = `<button type="button" class="met-action-btn met-action-btn--primary" onclick="metasManager.openMetaModal(${nextMeta.id})">
                    <i data-lucide="pencil"></i>
                    <span>Revisar meta</span>
               </button>`;

        focus.innerHTML = `
            <div class="met-focus-callout">
                <div>
                    <h2 class="met-focus-callout__title">${Utils.escHtml(nextMeta.titulo)}</h2>
                    <p class="met-focus-callout__text">
                        Faltam <strong>${Utils.formatCurrency(nextMeta.valor_restante || Math.max(0, (nextMeta.valor_alvo || 0) - (nextMeta.valor_atual || 0)))}</strong>
                        para concluir. ${deadlineHint}
                    </p>
                    <div class="met-focus-callout__meta">
                        <span class="met-focus-callout__pill">${(nextMeta.progresso || 0).toFixed(1)}% concluido</span>
                        <span class="met-focus-callout__pill">${monthlyHint}</span>
                    </div>
                </div>
                <div class="met-focus-callout__actions">
                    ${primaryAction}
                    <button type="button" class="met-action-btn" onclick="metasManager.openMetaModal(${nextMeta.id})">
                        <i data-lucide="sliders-horizontal"></i>
                        <span>Ajustar meta</span>
                    </button>
                </div>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    }

    function buildInsights() {
        const activeMetas = STATE.metas.filter((meta) => meta.status === 'ativa');
        const completedMetas = STATE.metas.filter((meta) => meta.status === 'concluida');
        const overdueMetas = activeMetas.filter((meta) => meta.is_atrasada === true || (meta.dias_restantes ?? 1) < 0);
        const highestPriority = [...activeMetas]
            .filter((meta) => meta.prioridade === 'alta')
            .sort((a, b) => (a.progresso || 0) - (b.progresso || 0))[0];
        const closestMeta = [...activeMetas]
            .sort((a, b) => (a.valor_restante || 0) - (b.valor_restante || 0))[0];
        const monthlyRequired = activeMetas.reduce((sum, meta) => sum + (meta.aporte_mensal_sugerido || 0), 0);
        const insights = [];

        if (monthlyRequired > 0) {
            insights.push({
                tipo: 'info',
                titulo: 'Ritmo mensal das metas',
                mensagem: `Para cumprir os prazos atuais, reserve cerca de ${Utils.formatCurrency(monthlyRequired)} por mes.`,
                icon: 'calendar-range',
            });
        }

        if (overdueMetas.length > 0) {
            const overdue = overdueMetas[0];
            insights.push({
                tipo: 'danger',
                titulo: `${overdue.titulo} esta atrasada`,
                mensagem: overdue.aporte_mensal_sugerido > 0
                    ? `Para recuperar o prazo, tente reforcar em ${Utils.formatCurrency(overdue.aporte_mensal_sugerido)} por mes.`
                    : `Faltam ${Utils.formatCurrency(overdue.valor_restante || 0)} para concluir esta meta.`,
                icon: 'triangle-alert',
                metaId: overdue.id,
            });
        }

        if (closestMeta) {
            insights.push({
                tipo: 'success',
                titulo: `${closestMeta.titulo} esta mais perto de sair do papel`,
                mensagem: `Faltam ${Utils.formatCurrency(closestMeta.valor_restante || 0)} para concluir.`,
                icon: 'target',
                metaId: closestMeta.id,
            });
        }

        if (highestPriority) {
            insights.push({
                tipo: 'warning',
                titulo: 'Sua meta de alta prioridade pede atencao',
                mensagem: `${highestPriority.titulo} ainda esta em ${(highestPriority.progresso || 0).toFixed(1)}% de progresso.`,
                icon: 'flag',
                metaId: highestPriority.id,
            });
        }

        if (completedMetas.length > 0) {
            insights.push({
                tipo: 'success',
                titulo: `Voce ja concluiu ${completedMetas.length} meta${completedMetas.length > 1 ? 's' : ''}`,
                mensagem: 'Vale usar esse embalo para abrir o proximo objetivo e manter a consistencia.',
                icon: 'party-popper',
            });
        }

        return insights.slice(0, 5);
    }

    function renderInsights() {
        const section = document.getElementById('metInsightsSection');
        const grid = document.getElementById('metInsightsGrid');
        const insightCount = document.getElementById('metInsightCount');
        if (!section || !grid) return;

        const insights = buildInsights();
        if (!insights.length) {
            section.style.display = '';
            if (insightCount) insightCount.textContent = '0 insights';
            grid.innerHTML = `
                <div class="met-insights-empty">
                    <i data-lucide="sparkles"></i>
                    <p>Os insights aparecem quando houver metas suficientes para comparar ritmo, proximidade e prioridade.</p>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        section.style.display = '';
        if (insightCount) {
            insightCount.textContent = `${insights.length} ${insights.length === 1 ? 'insight' : 'insights'}`;
        }
        grid.innerHTML = insights.map((insight) => {
            const action = insight.metaId
                ? `<button type="button" class="met-insight-action" onclick="metasManager.openMetaModal(${insight.metaId})">
                        <i data-lucide="pencil"></i>
                        <span>Revisar</span>
                   </button>`
                : '';
            return `
                <div class="met-insight-card surface-card surface-card--interactive ${insight.tipo}">
                    <div class="met-insight-icon ${insight.tipo}">
                        <i data-lucide="${insight.icon}"></i>
                    </div>
                    <div class="met-insight-content">
                        <span class="met-insight-title">${Utils.escHtml(insight.titulo)}</span>
                        <p class="met-insight-text">${Utils.escHtml(insight.mensagem)}</p>
                    </div>
                    ${action}
                </div>
            `;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    function renderMetas() {
        const grid = document.getElementById('metasGrid');
        const empty = document.getElementById('metasEmpty');
        const visibleCount = document.getElementById('metVisibleCount');
        if (!grid || !empty) return;

        if (!STATE.metas.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            if (visibleCount) visibleCount.textContent = '0';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';
        const filteredMetas = getFilteredMetas();

        if (!filteredMetas.length) {
            if (visibleCount) visibleCount.textContent = '0';
            grid.innerHTML = `
                <div class="met-soft-empty surface-card">
                    <i data-lucide="search-x"></i>
                    <p>Nenhuma meta encontrada para os filtros atuais.</p>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        if (visibleCount) {
            visibleCount.textContent = String(filteredMetas.length);
        }

        grid.innerHTML = filteredMetas.map((meta) => {
            const progresso = meta.progresso || 0;
            const isDemo = isDemoItem(meta);
            const cor = meta.cor || '#8b5cf6';
            const tipoEmoji = Utils.getTipoEmoji(meta.tipo);
            const prioridadeTag = Utils.getPrioridadeTag(meta.prioridade);
            const isCompleted = meta.status === 'concluida';
            const statusTag = meta.status !== 'ativa'
                ? `<span class="met-card__badge met-card__badge--${meta.status === 'concluida' ? 'completed' : 'active'}">${Utils.capitalize(meta.status)}</span>`
                : '<span class="met-card__badge met-card__badge--active">Ativa</span>';
            const demoTag = isDemo ? '<span class="met-card__badge met-card__badge--demo">Exemplo</span>' : '';
            const diasRestantes = meta.dias_restantes;
            const prazoInfo = diasRestantes !== null && diasRestantes !== undefined
                ? (diasRestantes > 0
                    ? `<span class="met-card__deadline">${diasRestantes} dias restantes</span>`
                    : '<span class="met-card__deadline" style="color:#ef4444">Prazo vencido!</span>')
                : '';
            const aporteInfo = meta.aporte_mensal_sugerido > 0
                ? `<span class="met-card__hint met-card__hint--aporte"><i data-lucide="calendar-range" style="width:12px;height:12px"></i> ${Utils.formatCurrency(meta.aporte_mensal_sugerido)}/mes sugeridos</span>`
                : '';
            const contaBadge = meta.conta_id
                ? `<span class="met-card__hint met-card__hint--account"><i data-lucide="landmark" style="width:12px;height:12px"></i> ${Utils.escHtml(meta.conta_nome || 'Conta vinculada')} - saldo sincronizado</span>`
                : '';
            const topMetaRow = [prazoInfo, aporteInfo].filter(Boolean).join('');
            const contextRow = [contaBadge, demoTag].filter(Boolean).join('');

            return `
            <div class="met-card surface-card surface-card--interactive ${isCompleted ? 'met-card--completed' : ''}" style="--met-card-color: ${cor}" data-aos="fade-up">
                <div class="met-card__header">
                    <div class="met-card__title-group">
                        <span class="met-card__icon">${tipoEmoji}</span>
                        <div class="met-card__info">
                            <div class="met-card__title-row">
                                <span class="met-card__name">${Utils.escHtml(meta.titulo)}</span>
                            </div>
                            ${topMetaRow ? `<div class="met-card__meta-row">${topMetaRow}</div>` : ''}
                            ${contextRow ? `<div class="met-card__context-row">${contextRow}</div>` : ''}
                        </div>
                    </div>
                    <div class="met-card__actions">
                        ${!isDemo ? `<button class="met-card__action-btn" onclick="metasManager.openMetaModal(${meta.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="met-card__action-btn met-card__action-btn--danger" onclick="metasManager.deleteMeta(${meta.id})" title="Excluir">
                            <i data-lucide="trash-2"></i>
                        </button>` : ''}
                    </div>
                </div>
                <div class="met-card__progress">
                    <div class="met-card__progress-bar">
                        <div class="met-card__progress-fill" style="width: ${Math.min(progresso, 100)}%"></div>
                    </div>
                    <div class="met-card__progress-info">
                        <span class="met-card__current">${Utils.formatCurrency(meta.valor_atual || 0)}</span>
                        <span class="met-card__target">de ${Utils.formatCurrency(meta.valor_alvo)}</span>
                    </div>
                </div>
                <div class="met-card__footer">
                    <div class="met-card__footer-meta">
                        <span class="met-card__pct">${progresso.toFixed(1)}%</span>
                        ${prioridadeTag}
                        ${statusTag}
                    </div>
                    <span class="met-card__remaining-value">Faltam ${Utils.formatCurrency(Math.max(0, (meta.valor_alvo || 0) - (meta.valor_atual || 0)))}</span>
                </div>
                ${isCompleted ? '<div class="met-celebrate"><i data-lucide="party-popper" style="width:14px;height:14px"></i> Meta atingida!</div>' : ''}
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

    function updateAporteSugerido() {
        const hint = document.getElementById('metaAporteSugerido');
        if (!hint) return;

        const valorAlvo = Utils.parseMoney(document.getElementById('metaValorAlvo')?.value || '0');
        const prazo = document.getElementById('metaPrazo')?.value;

        let valorAtual = 0;
        const contaId = document.getElementById('metaContaId')?.value;
        if (contaId) {
            const conta = STATE.contas.find((item) => String(item.id) === String(contaId));
            valorAtual = conta?.saldoAtual ?? 0;
        } else {
            valorAtual = Utils.parseMoney(document.getElementById('metaValorAtual')?.value || '0');
        }

        if (!prazo || valorAlvo <= 0) {
            hint.textContent = '';
            return;
        }

        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        const dataPrazo = new Date(`${prazo}T00:00:00`);

        if (dataPrazo <= hoje) {
            hint.textContent = 'Esse prazo ja passou. Ajuste para uma data futura.';
            return;
        }

        const restante = valorAlvo - valorAtual;
        if (restante <= 0) {
            hint.textContent = 'Valor ja atingido!';
            return;
        }

        const diffDias = Math.ceil((dataPrazo - hoje) / (1000 * 60 * 60 * 24));
        const mesesRestantes = Math.max(1, Math.ceil(diffDias / 30.44));
        const aporteMensal = restante / mesesRestantes;
        const plural = mesesRestantes === 1 ? 'mes' : 'meses';
        hint.textContent = `Para atingir no prazo: ${Utils.formatCurrency(aporteMensal)} por mes (${mesesRestantes} ${plural})`;
    }

    return {
        setupMoneyInputs,
        onMetaContaChange,
        syncMetaAllocationField,
        renderResumo,
        getFilteredMetas,
        compareMetas,
        renderFocusPanel,
        buildInsights,
        renderInsights,
        renderMetas,
        openModal,
        closeModal,
        updateAporteSugerido,
    };
}
