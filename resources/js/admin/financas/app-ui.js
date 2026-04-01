/**
 * Financas UI module
 * Rendering, tabs, modal helpers and money/form UI behavior.
 */

export function createFinancasUi({
    STATE,
    Utils,
    getCategoryIconColor,
    isDemoItem,
}) {
    function setupMoneyInputs() {
        const moneyFields = ['orcValor', 'metaValorAlvo', 'metaValorAtual', 'aporteValor'];
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

    function syncMetaAllocationField() {
        onMetaContaChange();
    }

    function onMetaContaChange() {
        const contaGroup = document.getElementById('metaContaId')?.closest('.fin-form-group');
        const contaId = document.getElementById('metaContaId')?.value;
        const valorAtualGroup = document.getElementById('metaValorAtual')?.closest('.fin-form-group');
        const valorAtualLabel = valorAtualGroup?.querySelector('.fin-label');
        const hint = document.getElementById('metaContaHint');
        const saldo = 0;
        if (contaId) {
            if (valorAtualGroup) valorAtualGroup.style.display = 'none';
            const conta = STATE.contas.find((item) => String(item.id) === String(contaId));
            const contaSaldo = conta?.saldoAtual ?? 0;
            if (hint) {
                hint.innerHTML = `<i data-lucide="info"></i> Saldo atual da conta: <strong>${Utils.formatCurrency(contaSaldo)}</strong> — sera usado como valor inicial. O progresso atualiza automaticamente.`;
                hint.style.display = '';
                if (window.lucide) lucide.createIcons();
            }
        } else {
            if (valorAtualGroup) valorAtualGroup.style.display = '';
            if (hint) {
                hint.style.display = 'none';
                hint.innerHTML = '';
            }
        }
        if (contaGroup) contaGroup.style.display = 'none';
        if (valorAtualGroup) valorAtualGroup.style.display = '';
        if (valorAtualLabel) valorAtualLabel.innerHTML = '<i data-lucide="coins"></i> Valor ja alocado';
        if (hint) {
            hint.style.display = 'none';
            hint.innerHTML = '';
        }
        return saldo;
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
        const met = data.metas || {};

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
            ringFill.classList.remove('score-good', 'score-warn', 'score-bad');
            if (score >= 70) ringFill.classList.add('score-good');
            else if (score >= 40) ringFill.classList.add('score-warn');
            else ringFill.classList.add('score-bad');
        }
        if (scoreEl) scoreEl.textContent = score;
        if (labelEl) {
            if (score >= 80) labelEl.textContent = 'Excelente!';
            else if (score >= 60) labelEl.textContent = 'Bom';
            else if (score >= 40) labelEl.textContent = 'Atencao';
            else labelEl.textContent = 'Critico';

            labelEl.className = 'summary-status';
            if (score >= 70) labelEl.classList.add('status-good');
            else if (score >= 40) labelEl.classList.add('status-warn');
            else labelEl.classList.add('status-bad');
        }

        const totalOrcado = orc.total_limite ?? orc.total_orcado ?? 0;
        const totalGasto = orc.total_gasto ?? 0;
        Utils.setText('totalOrcado', Utils.formatCurrency(totalOrcado));
        Utils.setText('totalGasto', Utils.formatCurrency(totalGasto));
        Utils.setText('totalDisponivel', Utils.formatCurrency(totalOrcado - totalGasto));

        const totalMetas = met.total_metas ?? met.ativas ?? 0;
        const totalAtual = met.total_atual ?? 0;
        const totalAlvo = met.total_alvo ?? 0;
        const progressoGeral = met.progresso_geral ?? 0;
        const atrasadas = met.atrasadas ?? 0;

        Utils.setText('metasAtivas', `${totalMetas} ativa${totalMetas !== 1 ? 's' : ''}`);
        Utils.setText('metasTotalAtual', Utils.formatCurrency(totalAtual));
        Utils.setText('metasTotalAlvo', Utils.formatCurrency(totalAlvo));

        const metasRingFill = document.getElementById('metasProgressRingFill');
        const metasScore = document.getElementById('metasProgressScore');
        const metasLabel = document.getElementById('metasProgressLabel');

        if (metasRingFill) {
            metasRingFill.style.strokeDasharray = `${progressoGeral}, 100`;
            metasRingFill.classList.remove('score-good', 'score-warn', 'score-bad');
            if (progressoGeral >= 60) metasRingFill.classList.add('score-good');
            else if (progressoGeral >= 30) metasRingFill.classList.add('score-warn');
            else metasRingFill.classList.add('score-bad');
        }
        if (metasScore) metasScore.textContent = `${Math.round(progressoGeral)}%`;
        if (metasLabel) {
            if (atrasadas > 0) {
                metasLabel.textContent = `${atrasadas} atrasada${atrasadas > 1 ? 's' : ''}`;
                metasLabel.className = 'summary-status status-bad';
            } else if (totalMetas === 0) {
                metasLabel.textContent = 'Nenhuma meta';
                metasLabel.className = 'summary-status';
            } else if (progressoGeral >= 80) {
                metasLabel.textContent = 'Quase la!';
                metasLabel.className = 'summary-status status-good';
            } else {
                metasLabel.textContent = 'Em progresso';
                metasLabel.className = 'summary-status status-good';
            }
        }
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

        grid.innerHTML = STATE.orcamentos.map((orc) => {
            const pct = orc.percentual || 0;
            const isDemo = isDemoItem(orc);
            const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
            const catNome = orc.categoria?.nome || orc.categoria_nome || 'Categoria';
            const catIcone = orc.categoria?.icone || 'tag';
            const gasto = orc.gasto_real || 0;
            const limite = orc.valor_limite || 0;
            const disponivel = limite - gasto;
            const rolloverTag = orc.rollover && orc.rollover_valor > 0
                ? `<span class="orc-badge rollover" title="Inclui R$ ${Utils.formatNumber(orc.rollover_valor)} do mes anterior">+${Utils.formatNumber(orc.rollover_valor)} rollover</span>`
                : '';
            const actionsMarkup = isDemo
                ? '<span class="orc-badge rollover" title="Dado de exemplo">Exemplo</span>'
                : `
                    <button class="orc-action-btn" onclick="financasManager.openOrcamentoModal(${orc.id})" title="Editar">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="orc-action-btn danger" onclick="financasManager.deleteOrcamento(${orc.id})" title="Excluir">
                        <i data-lucide="trash-2"></i>
                    </button>
                `;

            return `
            <div class="orc-card ${statusClass}" data-aos="fade-up">
                <div class="orc-header">
                    <div class="orc-cat">
                        <span class="orc-icon"><i data-lucide="${catIcone}" style="color:${getCategoryIconColor(catIcone)}"></i></span>
                        <span class="orc-name">${Utils.escHtml(catNome)}</span>
                    </div>
                    <div class="orc-actions">
                        ${actionsMarkup}
                    </div>
                </div>
                <div class="orc-progress">
                    <div class="orc-progress-bar">
                        <div class="orc-progress-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                    </div>
                    <div class="orc-progress-info">
                        <span class="orc-gasto">${Utils.formatCurrency(gasto)}</span>
                        <span class="orc-limite">de ${Utils.formatCurrency(limite)}</span>
                    </div>
                </div>
                <div class="orc-footer">
                    <span class="orc-pct ${statusClass}">${pct.toFixed(0)}%</span>
                    <span class="orc-disponivel ${disponivel < 0 ? 'negative' : ''}">
                        ${disponivel >= 0 ? 'Resta' : 'Excedido'} ${Utils.formatCurrency(Math.abs(disponivel))}
                    </span>
                    ${rolloverTag}
                </div>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    function renderMetas() {
        const grid = document.getElementById('metasGrid');
        const empty = document.getElementById('metasEmpty');
        if (!grid || !empty) return;

        if (!STATE.metas.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';

        grid.innerHTML = STATE.metas.map((meta) => {
            const progresso = meta.progresso || 0;
            const isDemo = isDemoItem(meta);
            const cor = meta.cor || '#6366f1';
            const tipoEmoji = Utils.getTipoEmoji(meta.tipo);
            const prioridadeTag = Utils.getPrioridadeTag(meta.prioridade);
            const statusTag = meta.status !== 'ativa'
                ? `<span class="meta-status-badge ${meta.status}">${Utils.capitalize(meta.status)}</span>`
                : '';
            const demoTag = isDemo ? '<span class="meta-status-badge active">Exemplo</span>' : '';
            const diasRestantes = meta.dias_restantes;
            const prazoInfo = diasRestantes !== null && diasRestantes !== undefined
                ? (diasRestantes > 0
                    ? `<span class="meta-prazo">${diasRestantes} dias restantes</span>`
                    : '<span class="meta-prazo atrasada">Prazo vencido!</span>')
                : '';
            const aporteSugerido = meta.aporte_mensal_sugerido > 0
                ? `<span class="meta-aporte-hint">${Utils.formatCurrency(meta.aporte_mensal_sugerido)}/mes sugerido</span>`
                : '';
            const contaBadge = meta.conta_id
                ? `<span class="meta-conta-badge"><i data-lucide="landmark"></i> ${Utils.escHtml(meta.conta_nome || 'Conta vinculada')}</span>`
                : '';

            return `
            <div class="meta-card" style="--meta-color: ${cor}" data-aos="fade-up">
                <div class="meta-header">
                    <div class="meta-title-row">
                        <span class="meta-emoji">${tipoEmoji}</span>
                        <h4 class="meta-titulo">${Utils.escHtml(meta.titulo)}</h4>
                        ${statusTag}
                        ${demoTag}
                    </div>
                    <div class="meta-actions">
                        ${!isDemo ? `<button class="meta-action-btn" onclick="financasManager.openMetaModal(${meta.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="meta-action-btn danger" onclick="financasManager.deleteMeta(${meta.id})" title="Excluir">
                            <i data-lucide="trash-2"></i>
                        </button>` : ''}
                    </div>
                </div>
                <div class="meta-progress-section">
                    <div class="meta-progress-bar">
                        <div class="meta-progress-fill" style="width: ${Math.min(progresso, 100)}%; background: ${cor}"></div>
                    </div>
                    <div class="meta-progress-info">
                        <span class="meta-valor-atual">${Utils.formatCurrency(meta.valor_atual || 0)}</span>
                        <span class="meta-progresso">${progresso.toFixed(1)}%</span>
                        <span class="meta-valor-alvo">${Utils.formatCurrency(meta.valor_alvo)}</span>
                    </div>
                </div>
                <div class="meta-footer">
                    ${prioridadeTag}
                    ${prazoInfo}
                    ${contaBadge}
                    ${aporteSugerido}
                </div>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    function renderInsights(insights) {
        const section = document.getElementById('insightsSection');
        const grid = document.getElementById('insightsGrid');
        if (!grid) return;

        if (!insights.length) {
            if (section) section.style.display = 'none';
            return;
        }

        if (section) section.style.display = '';

        grid.innerHTML = insights.map((insight) => {
            const icon = Utils.getInsightIcon(insight.tipo);
            const level = insight.nivel || 'info';
            return `
            <div class="insight-card ${level}" data-aos="fade-up">
                <div class="insight-icon ${level}">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="insight-content">
                    <span class="insight-title">${Utils.escHtml(insight.titulo || '')}</span>
                    <p class="insight-text">${Utils.escHtml(insight.mensagem || '')}</p>
                </div>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    function switchTab(tabName) {
        STATE.currentTab = tabName;

        document.querySelectorAll('.fin-tab').forEach((tab) => {
            const isActive = tab.dataset.tab === tabName;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('.fin-tab-content').forEach((content) => {
            content.classList.toggle('active', content.id === `tab-${tabName}`);
        });

        const sumOrc = document.getElementById('summaryOrcamentos');
        const sumMeta = document.getElementById('summaryMetas');
        if (sumOrc) {
            sumOrc.style.opacity = tabName === 'orcamentos' ? '1' : '0';
            sumOrc.style.display = tabName === 'orcamentos' ? '' : 'none';
        }
        if (sumMeta) {
            sumMeta.style.display = tabName === 'metas' ? '' : 'none';
            sumMeta.style.opacity = tabName === 'metas' ? '1' : '0';
        }

        try {
            localStorage.setItem('financas_tab', tabName);
        } catch {
            // ignore
        }
        history.replaceState(null, '', `#${tabName}`);
    }

    function openModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
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
        syncMetaAllocationField,
        onMetaContaChange,
        populateCategoriaSelect,
        renderResumo,
        renderOrcamentos,
        renderMetas,
        renderInsights,
        switchTab,
        openModal,
        closeModal,
        updateAporteSugerido,
    };
}
