/**
 * FinancasManager - Gerenciador de Finanças (Orçamentos + Metas + Insights)
 * @version 1.0.0
 */
class FinancasManager {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.currentMonth = new Date().getMonth() + 1;
        this.currentYear = new Date().getFullYear();
        this.currentTab = 'orcamentos';
        this.orcamentos = [];
        this.metas = [];
        this.categorias = [];
        this.contas = [];
        this.sugestoes = [];
        this.editingOrcamentoId = null;
        this.editingMetaId = null;

        this.init();
    }

    // ==================== INICIALIZAÇÃO ====================

    async init() {
        this.syncFromHeader();
        this.attachEventListeners();
        this.setupMoneyInputs();
        await Promise.all([this.loadCategorias(), this.loadContas()]);

        // Restore tab from hash or localStorage
        const hash = location.hash.replace('#', '');
        const validTabs = ['orcamentos', 'metas'];
        let initialTab = 'orcamentos';
        if (hash && validTabs.includes(hash)) {
            initialTab = hash;
        } else {
            try {
                const stored = localStorage.getItem('financas_tab');
                if (stored && validTabs.includes(stored)) initialTab = stored;
            } catch(e) {}
        }
        if (initialTab !== 'orcamentos') this.switchTab(initialTab);

        await this.loadAll();
    }

    /**
     * Sincronizar mês/ano com o header global (LukratoHeader)
     */
    syncFromHeader() {
        const ym = window.LukratoHeader?.getMonth?.() || sessionStorage.getItem('lkMes');
        if (ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
            const [y, m] = ym.split('-').map(Number);
            this.currentYear = y;
            this.currentMonth = m;
        }
    }

    getBaseUrl() {
        if (window.LK && typeof window.LK.getBase === 'function') return window.LK.getBase();
        const meta = document.querySelector('meta[name="base-url"]');
        if (meta?.content) return meta.content;
        if (window.BASE_URL) return window.BASE_URL.endsWith('/') ? window.BASE_URL : window.BASE_URL + '/';
        return '/lukrato/public/';
    }

    getCsrfToken() {
        const input = document.querySelector('input[name="csrf_token"]');
        if (input?.value) return input.value;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta?.content) return meta.content;
        if (window.LK?.getCSRF) return window.LK.getCSRF();
        return window.CSRF || '';
    }

    // ==================== EVENT LISTENERS ====================

    attachEventListeners() {
        // Navegação de mês via header global
        document.addEventListener('lukrato:month-changed', (e) => {
            const ym = e.detail?.month;
            if (ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
                const [y, m] = ym.split('-').map(Number);
                this.currentYear = y;
                this.currentMonth = m;
                this.loadAll();
            }
        });

        // Tabs + keyboard nav
        const tabButtons = document.querySelectorAll('.fin-tab');
        const tabNames = [...tabButtons].map(t => t.dataset.tab);
        tabButtons.forEach(tab => {
            tab.addEventListener('click', () => this.switchTab(tab.dataset.tab));
            tab.addEventListener('keydown', (e) => {
                const idx = tabNames.indexOf(tab.dataset.tab);
                let next = -1;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (idx + 1) % tabNames.length;
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (idx - 1 + tabNames.length) % tabNames.length;
                if (next >= 0) {
                    e.preventDefault();
                    tabButtons[next].focus();
                    this.switchTab(tabNames[next]);
                }
            });
        });

        // Orçamentos
        document.getElementById('btnAutoSugerir')?.addEventListener('click', () => this.openSugestoes());
        document.getElementById('btnAutoSugerirEmpty')?.addEventListener('click', () => this.openSugestoes());
        document.getElementById('btnCopiarMes')?.addEventListener('click', () => this.copiarMesAnterior());
        document.getElementById('btnNovoOrcamento')?.addEventListener('click', () => this.openOrcamentoModal());
        document.getElementById('formOrcamento')?.addEventListener('submit', (e) => this.handleOrcamentoSubmit(e));
        document.getElementById('btnAplicarSugestoes')?.addEventListener('click', () => this.aplicarSugestoes());

        // Metas
        document.getElementById('btnNovaMeta')?.addEventListener('click', () => this.openMetaModal());
        document.getElementById('btnTemplates')?.addEventListener('click', () => this.openTemplates());
        document.getElementById('btnTemplatesEmpty')?.addEventListener('click', () => this.openTemplates());
        document.getElementById('formMeta')?.addEventListener('submit', (e) => this.handleMetaSubmit(e));
        document.getElementById('formAporte')?.addEventListener('submit', (e) => this.handleAporteSubmit(e));

        // Color picker
        document.querySelectorAll('#metaCorPicker .color-dot').forEach(dot => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => d.classList.remove('active'));
                dot.classList.add('active');
                document.getElementById('metaCor').value = dot.dataset.color;
            });
        });

        // Modal overlays (close on backdrop click)
        document.querySelectorAll('.fin-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.closeModal(overlay.id);
            });
        });

        // Close buttons
        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal(btn.dataset.closeModal));
        });

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fin-modal-overlay.active').forEach(m => this.closeModal(m.id));
            }
        });

        // Auto-suggest hint on category change
        document.getElementById('orcCategoria')?.addEventListener('change', (e) => this.loadCategorySuggestion(e.target.value));

        // Meta deadline change → show suggested monthly contribution
        document.getElementById('metaPrazo')?.addEventListener('change', () => this.updateAporteSugerido());
        document.getElementById('metaValorAlvo')?.addEventListener('input', () => this.updateAporteSugerido());
        document.getElementById('metaValorAtual')?.addEventListener('input', () => this.updateAporteSugerido());

        // Conta vinculada → toggle valor_atual field
        document.getElementById('metaContaId')?.addEventListener('change', () => this.onMetaContaChange());
    }

    setupMoneyInputs() {
        const moneyFields = ['orcValor', 'metaValorAlvo', 'metaValorAtual', 'aporteValor'];
        moneyFields.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', () => this.formatarDinheiro(input));
                input.addEventListener('focus', () => { if (!input.value) input.value = '0,00'; });
            }
        });
    }

    // ==================== NAVEGAÇÃO DE MÊS ====================

    // ==================== CARREGAMENTO DE DADOS ====================

    async loadAll() {
        try {
            await Promise.all([
                this.loadResumo(),
                this.loadOrcamentos(),
                this.loadMetas(),
                this.loadInsights()
            ]);
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    }

    async loadCategorias() {
        try {
            const res = await this.apiGet('api/categorias');
            if (res.success !== false && res.data) {
                // Filter only expense categories (despesa)
                this.categorias = (res.data || []).filter(c => c.tipo === 'despesa');
                this.populateCategoriaSelect();
            }
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    }

    async loadContas() {
        try {
            const res = await this.apiGet('api/contas?only_active=1&with_balances=1');
            // A API de contas retorna array direto (sem wrapper {success, data})
            if (Array.isArray(res)) {
                this.contas = res;
            } else if (res.success !== false && Array.isArray(res.data)) {
                this.contas = res.data;
            }
        } catch (e) {
            console.error('Erro ao carregar contas:', e);
        }
    }

    onMetaContaChange() {
        const contaId = document.getElementById('metaContaId')?.value;
        const valorAtualGroup = document.getElementById('metaValorAtual')?.closest('.fin-form-group');
        const hint = document.getElementById('metaContaHint');
        if (contaId) {
            // Esconder campo manual de valor atual
            if (valorAtualGroup) valorAtualGroup.style.display = 'none';
            // Mostrar hint com saldo atual da conta
            const conta = this.contas.find(c => String(c.id) === String(contaId));
            const saldo = conta?.saldoAtual ?? 0;
            if (hint) {
                hint.innerHTML = `<i data-lucide="info"></i> Saldo atual da conta: <strong>${this.formatCurrency(saldo)}</strong> — será usado como valor inicial. O progresso atualiza automaticamente.`;
                hint.style.display = '';
                if(window.lucide) lucide.createIcons();
            }
        } else {
            if (valorAtualGroup) valorAtualGroup.style.display = '';
            if (hint) {
                hint.style.display = 'none';
                hint.innerHTML = '';
            }
        }
    }

    populateCategoriaSelect() {
        const select = document.getElementById('orcCategoria');
        if (!select) return;
        select.innerHTML = '<option value="">Selecione uma categoria</option>';
        this.categorias.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = `${cat.icone || '📁'} ${cat.nome}`;
            select.appendChild(opt);
        });
    }

    async loadResumo() {
        try {
            const res = await this.apiGet(`api/financas/resumo?mes=${this.currentMonth}&ano=${this.currentYear}`);
            if (res.success !== false) this.renderResumo(res.data);
        } catch (e) {
            console.error('Erro ao carregar resumo:', e);
        }
    }

    async loadOrcamentos() {
        try {
            const res = await this.apiGet(`api/financas/orcamentos?mes=${this.currentMonth}&ano=${this.currentYear}`);
            if (res.success !== false) {
                this.orcamentos = res.data || [];
                this.renderOrcamentos();
            }
        } catch (e) {
            console.error('Erro ao carregar orçamentos:', e);
        }
    }

    async loadMetas() {
        try {
            const res = await this.apiGet('api/financas/metas');
            if (res.success !== false) {
                this.metas = res.data || [];
                this.renderMetas();
            }
        } catch (e) {
            console.error('Erro ao carregar metas:', e);
        }
    }

    async loadInsights() {
        try {
            const res = await this.apiGet(`api/financas/insights?mes=${this.currentMonth}&ano=${this.currentYear}`);
            if (res.success !== false) {
                this.renderInsights(res.data || []);
            }
        } catch (e) {
            console.error('Erro ao carregar insights:', e);
        }
    }

    // ==================== RENDER: RESUMO ====================

    renderResumo(data) {
        if (!data) return;

        const orc = data.orcamento || data.orcamentos || {};
        const met = data.metas || {};

        // Saúde financeira
        const saude = orc.saude_financeira || {};
        const score = saude.score ?? orc.saude_score ?? 0;
        const ringFill = document.getElementById('saudeRingFill');
        const scoreEl = document.getElementById('saudeScore');
        const labelEl = document.getElementById('saudeLabel');
        const saudeContent = document.getElementById('saudeContent');
        const saudeCta = document.getElementById('saudeCta');

        // Verificar se há orçamentos configurados
        const temOrcamentos = (orc.total_limite ?? orc.total_orcado ?? 0) > 0 || this.orcamentos.length > 0;

        if (!temOrcamentos && saudeContent && saudeCta) {
            // Sem orçamentos: mostrar CTA
            saudeContent.style.display = 'none';
            saudeCta.style.display = '';
        } else if (saudeContent && saudeCta) {
            // Com orçamentos: mostrar score real
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
            else if (score >= 40) labelEl.textContent = 'Atenção';
            else labelEl.textContent = 'Crítico';

            labelEl.className = 'summary-status';
            if (score >= 70) labelEl.classList.add('status-good');
            else if (score >= 40) labelEl.classList.add('status-warn');
            else labelEl.classList.add('status-bad');
        }

        // Values
        const totalOrcado = orc.total_limite ?? orc.total_orcado ?? 0;
        const totalGasto = orc.total_gasto ?? 0;
        this.setText('totalOrcado', this.formatCurrency(totalOrcado));
        this.setText('totalGasto', this.formatCurrency(totalGasto));
        this.setText('totalDisponivel', this.formatCurrency(totalOrcado - totalGasto));

        // Metas summary cards
        const totalMetas = met.total_metas ?? met.ativas ?? 0;
        const totalAtual = met.total_atual ?? 0;
        const totalAlvo  = met.total_alvo ?? 0;
        const progressoGeral = met.progresso_geral ?? 0;
        const atrasadas = met.atrasadas ?? 0;

        this.setText('metasAtivas', `${totalMetas} ativa${totalMetas !== 1 ? 's' : ''}`);
        this.setText('metasTotalAtual', this.formatCurrency(totalAtual));
        this.setText('metasTotalAlvo', this.formatCurrency(totalAlvo));

        // Metas progress ring
        const metasRingFill = document.getElementById('metasProgressRingFill');
        const metasScore    = document.getElementById('metasProgressScore');
        const metasLabel    = document.getElementById('metasProgressLabel');

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
                metasLabel.textContent = 'Quase lá!';
                metasLabel.className = 'summary-status status-good';
            } else {
                metasLabel.textContent = 'Em progresso';
                metasLabel.className = 'summary-status status-good';
            }
        }
    }

    // ==================== RENDER: ORÇAMENTOS ====================

    renderOrcamentos() {
        const grid = document.getElementById('orcamentosGrid');
        const empty = document.getElementById('orcamentosEmpty');
        if (!grid || !empty) return;

        if (!this.orcamentos.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';

        grid.innerHTML = this.orcamentos.map(orc => {
            const pct = orc.percentual || 0;
            const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
            const catNome = orc.categoria?.nome || orc.categoria_nome || 'Categoria';
            const catIcone = orc.categoria?.icone || '📁';
            const gasto = orc.gasto_real || 0;
            const limite = orc.valor_limite || 0;
            const disponivel = limite - gasto;
            const rolloverTag = orc.rollover && orc.rollover_valor > 0
                ? `<span class="orc-badge rollover" title="Inclui R$ ${this.formatNumber(orc.rollover_valor)} do mês anterior">+${this.formatNumber(orc.rollover_valor)} rollover</span>`
                : '';

            return `
            <div class="orc-card ${statusClass}" data-aos="fade-up">
                <div class="orc-header">
                    <div class="orc-cat">
                        <span class="orc-icon">${catIcone}</span>
                        <span class="orc-name">${this.escHtml(catNome)}</span>
                    </div>
                    <div class="orc-actions">
                        <button class="orc-action-btn" onclick="financasManager.openOrcamentoModal(${orc.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="orc-action-btn danger" onclick="financasManager.deleteOrcamento(${orc.id})" title="Excluir">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
                <div class="orc-progress">
                    <div class="orc-progress-bar">
                        <div class="orc-progress-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                    </div>
                    <div class="orc-progress-info">
                        <span class="orc-gasto">${this.formatCurrency(gasto)}</span>
                        <span class="orc-limite">de ${this.formatCurrency(limite)}</span>
                    </div>
                </div>
                <div class="orc-footer">
                    <span class="orc-pct ${statusClass}">${pct.toFixed(0)}%</span>
                    <span class="orc-disponivel ${disponivel < 0 ? 'negative' : ''}">
                        ${disponivel >= 0 ? 'Resta' : 'Excedido'} ${this.formatCurrency(Math.abs(disponivel))}
                    </span>
                    ${rolloverTag}
                </div>
            </div>`;
        }).join('');
        if(window.lucide) lucide.createIcons();
    }

    // ==================== RENDER: METAS ====================

    renderMetas() {
        const grid = document.getElementById('metasGrid');
        const empty = document.getElementById('metasEmpty');
        if (!grid || !empty) return;

        if (!this.metas.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';

        grid.innerHTML = this.metas.map(meta => {
            const progresso = meta.progresso || 0;
            const cor = meta.cor || '#6366f1';
            const tipoEmoji = this.getTipoEmoji(meta.tipo);
            const prioridadeTag = this.getPrioridadeTag(meta.prioridade);
            const statusTag = meta.status !== 'ativa'
                ? `<span class="meta-status-badge ${meta.status}">${this.capitalize(meta.status)}</span>`
                : '';
            const diasRestantes = meta.dias_restantes;
            const prazoInfo = diasRestantes !== null && diasRestantes !== undefined
                ? (diasRestantes > 0
                    ? `<span class="meta-prazo">${diasRestantes} dias restantes</span>`
                    : `<span class="meta-prazo atrasada">Prazo vencido!</span>`)
                : '';
            const aporteSugerido = meta.aporte_mensal_sugerido > 0
                ? `<span class="meta-aporte-hint">${this.formatCurrency(meta.aporte_mensal_sugerido)}/mês sugerido</span>`
                : '';
            const contaBadge = meta.conta_id
                ? `<span class="meta-conta-badge"><i data-lucide="landmark"></i> ${this.escHtml(meta.conta_nome || 'Conta vinculada')}</span>`
                : '';

            return `
            <div class="meta-card" style="--meta-color: ${cor}" data-aos="fade-up">
                <div class="meta-header">
                    <div class="meta-title-row">
                        <span class="meta-emoji">${tipoEmoji}</span>
                        <h4 class="meta-titulo">${this.escHtml(meta.titulo)}</h4>
                        ${statusTag}
                    </div>
                    <div class="meta-actions">
                        ${meta.status === 'ativa' && !meta.conta_id ? `
                        <button class="meta-action-btn" onclick="financasManager.openAporteModal(${meta.id})" title="Adicionar aporte">
                            <i data-lucide="circle-plus"></i>
                        </button>` : ''}
                        <button class="meta-action-btn" onclick="financasManager.openMetaModal(${meta.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="meta-action-btn danger" onclick="financasManager.deleteMeta(${meta.id})" title="Excluir">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
                <div class="meta-progress-section">
                    <div class="meta-progress-bar">
                        <div class="meta-progress-fill" style="width: ${Math.min(progresso, 100)}%; background: ${cor}"></div>
                    </div>
                    <div class="meta-progress-info">
                        <span class="meta-valor-atual">${this.formatCurrency(meta.valor_atual || 0)}</span>
                        <span class="meta-progresso">${progresso.toFixed(1)}%</span>
                        <span class="meta-valor-alvo">${this.formatCurrency(meta.valor_alvo)}</span>
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
        if(window.lucide) lucide.createIcons();
    }

    // ==================== RENDER: INSIGHTS ====================

    renderInsights(insights) {
        const section = document.getElementById('insightsSection');
        const grid = document.getElementById('insightsGrid');
        if (!grid) return;

        if (!insights.length) {
            if (section) section.style.display = 'none';
            return;
        }

        if (section) section.style.display = '';

        grid.innerHTML = insights.map(insight => {
            const icon = this.getInsightIcon(insight.tipo);
            const level = insight.nivel || 'info';
            return `
            <div class="insight-card ${level}" data-aos="fade-up">
                <div class="insight-icon ${level}">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="insight-content">
                    <span class="insight-title">${this.escHtml(insight.titulo || '')}</span>
                    <p class="insight-text">${this.escHtml(insight.mensagem || '')}</p>
                </div>
            </div>`;
        }).join('');
        if(window.lucide) lucide.createIcons();
    }

    // ==================== TABS ====================

    switchTab(tabName) {
        this.currentTab = tabName;

        // Update tab buttons + ARIA
        document.querySelectorAll('.fin-tab').forEach(t => {
            const isActive = t.dataset.tab === tabName;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        // Update tab panels
        document.querySelectorAll('.fin-tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${tabName}`));

        // Crossfade summary card groups
        const sumOrc  = document.getElementById('summaryOrcamentos');
        const sumMeta = document.getElementById('summaryMetas');
        if (sumOrc) {
            sumOrc.style.opacity = tabName === 'orcamentos' ? '1' : '0';
            sumOrc.style.display = tabName === 'orcamentos' ? '' : 'none';
        }
        if (sumMeta) {
            sumMeta.style.display = tabName === 'metas' ? '' : 'none';
            sumMeta.style.opacity = tabName === 'metas' ? '1' : '0';
        }

        // Persist tab choice
        try { localStorage.setItem('financas_tab', tabName); } catch(e) {}
        history.replaceState(null, '', `#${tabName}`);
    }

    // ==================== MODAIS ====================

    openModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // ==================== ORÇAMENTO: CRUD ====================

    openOrcamentoModal(orcId = null) {
        this.editingOrcamentoId = orcId;
        const title = document.getElementById('modalOrcamentoTitle');
        const form = document.getElementById('formOrcamento');

        if (orcId) {
            const orc = this.orcamentos.find(o => o.id === orcId);
            if (!orc) return;
            if (title) title.textContent = 'Editar Orçamento';
            document.getElementById('orcCategoria').value = orc.categoria_id;
            document.getElementById('orcCategoria').disabled = true;
            document.getElementById('orcValor').value = this.formatNumber(orc.valor_limite);
            document.getElementById('orcRollover').checked = !!orc.rollover;
            document.getElementById('orcAlerta80').checked = orc.alerta_80 !== false && orc.alerta_80 !== 0;
            document.getElementById('orcAlerta100').checked = orc.alerta_100 !== false && orc.alerta_100 !== 0;
        } else {
            if (title) title.textContent = 'Novo Orçamento';
            form?.reset();
            document.getElementById('orcCategoria').disabled = false;
            document.getElementById('orcAlerta80').checked = true;
            document.getElementById('orcAlerta100').checked = true;
        }

        document.getElementById('orcSugestao').textContent = '';
        this.openModal('modalOrcamento');
    }

    async handleOrcamentoSubmit(e) {
        e.preventDefault();

        const categoriaId = document.getElementById('orcCategoria').value;
        const valorLimite = this.parseMoney(document.getElementById('orcValor').value);
        const rollover = document.getElementById('orcRollover').checked;
        const alerta80 = document.getElementById('orcAlerta80').checked;
        const alerta100 = document.getElementById('orcAlerta100').checked;

        if (!categoriaId) return this.showToast('Selecione uma categoria', 'error');
        if (valorLimite <= 0) return this.showToast('Informe um valor válido', 'error');

        try {
            const res = await this.apiPost('api/financas/orcamentos', {
                categoria_id: parseInt(categoriaId),
                valor_limite: valorLimite,
                mes: this.currentMonth,
                ano: this.currentYear,
                rollover: rollover,
                alerta_80: alerta80,
                alerta_100: alerta100
            });

            // Verificar se é erro de limite do plano
            if (this.handleLimitError(res)) return;

            if (res.success !== false && res.status !== 'error') {
                this.closeModal('modalOrcamento');
                this.showToast('Orçamento salvo!', 'success');
                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao salvar', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao salvar orçamento', 'error');
        }
    }

    async deleteOrcamento(id) {
        const orc = this.orcamentos.find(o => o.id === id);
        const nome = orc?.categoria?.nome || 'este orçamento';

        const result = await Swal.fire({
            title: 'Excluir orçamento',
            html: `Deseja excluir o orçamento de <strong>${this.escHtml(nome)}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await this.apiDelete(`api/financas/orcamentos/${id}`);
            if (res.success !== false) {
                this.showToast('Orçamento excluído', 'success');
                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao excluir', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao excluir', 'error');
        }
    }

    // ==================== ORÇAMENTO: SUGESTÕES ====================

    async openSugestoes() {
        this.openModal('modalSugestoes');
        const list = document.getElementById('sugestoesList');
        list.innerHTML = '<div class="loading-state"><i data-lucide="loader-2" class="icon-spin"></i><p>Analisando seu histórico...</p></div>';
        if(window.lucide) lucide.createIcons();

        try {
            const res = await this.apiGet(`api/financas/orcamentos/sugestoes?mes=${this.currentMonth}&ano=${this.currentYear}`);
            if (res.success !== false && res.data?.length) {
                this.sugestoes = res.data;
                this.renderSugestoes();
            } else {
                list.innerHTML = '<div class="fin-empty-state"><p>Não encontramos dados suficientes para gerar sugestões.<br>Continue registrando suas despesas!</p></div>';
            }
        } catch (e) {
            list.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar sugestões.</p></div>';
        }
    }

    renderSugestoes() {
        const list = document.getElementById('sugestoesList');
        list.innerHTML = this.sugestoes.map((sug, idx) => {
            const trendIcon = sug.tendencia === 'subindo' ? 'arrow-up' : sug.tendencia === 'descendo' ? 'arrow-down' : 'minus';
            const trendClass = sug.tendencia === 'subindo' ? 'up' : sug.tendencia === 'descendo' ? 'down' : 'stable';
            const catNome = sug.categoria?.nome || sug.categoria_nome || 'Categoria';
            const catIcone = sug.categoria?.icone || '📁';
            const mediaGastos = sug.media_gastos || sug.media_3_meses || 0;
            const economiaTag = sug.economia_sugerida > 0
                ? `<span class="sugestao-economia">economia de ${this.formatCurrency(sug.economia_sugerida)}/mês</span>`
                : '';
            return `
            <div class="sugestao-item">
                <div class="sugestao-info">
                    <span class="sugestao-icon">${catIcone}</span>
                    <div class="sugestao-detail">
                        <span class="sugestao-nome">${this.escHtml(catNome)}</span>
                        <span class="sugestao-media">Média: ${this.formatCurrency(mediaGastos)}
                            <i data-lucide="${trendIcon}" class="trend-${trendClass}"></i>
                        </span>
                        ${economiaTag}
                    </div>
                </div>
                <div class="sugestao-valor">
                    <input type="text" class="fin-input sugestao-input" id="sug_${idx}"
                           value="${this.formatNumber(sug.valor_sugerido || 0)}"
                           oninput="financasManager.formatarDinheiro(this)">
                </div>
                <label class="sugestao-check">
                    <input type="checkbox" checked data-sug-idx="${idx}">
                    <span class="checkmark"><i data-lucide="check"></i></span>
                </label>
            </div>`;
        }).join('');
        if(window.lucide) lucide.createIcons();
    }

    async aplicarSugestoes() {
        const orcamentos = [];
        this.sugestoes.forEach((sug, idx) => {
            const checkbox = document.querySelector(`[data-sug-idx="${idx}"]`);
            if (checkbox?.checked) {
                const input = document.getElementById(`sug_${idx}`);
                const valor = this.parseMoney(input?.value || '0');
                if (valor > 0 && sug.categoria_id) {
                    orcamentos.push({
                        categoria_id: sug.categoria_id,
                        valor_limite: valor,
                        alerta_80: true,
                        alerta_100: true
                    });
                }
            }
        });

        if (!orcamentos.length) return this.showToast('Selecione ao menos uma categoria', 'error');

        try {
            const res = await this.apiPost('api/financas/orcamentos/aplicar-sugestoes', {
                mes: this.currentMonth,
                ano: this.currentYear,
                orcamentos: orcamentos
            });

            // Verificar se é erro de limite do plano
            if (this.handleLimitError(res)) return;

            if (res.success !== false && res.status !== 'error') {
                this.closeModal('modalSugestoes');
                this.showToast(`${res.data?.aplicados || orcamentos.length} orçamentos configurados!`, 'success');
                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao aplicar', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao aplicar sugestões', 'error');
        }
    }

    async copiarMesAnterior() {
        let mesAnt = this.currentMonth - 1;
        let anoAnt = this.currentYear;
        if (mesAnt < 1) { mesAnt = 12; anoAnt--; }

        const meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        const result = await Swal.fire({
            title: 'Copiar mês anterior',
            html: `Deseja copiar os orçamentos de <strong>${meses[mesAnt]}/${anoAnt}</strong> para o mês atual?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, copiar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await this.apiPost('api/financas/orcamentos/copiar-mes', {
                mes_origem: mesAnt,
                ano_origem: anoAnt,
                mes_destino: this.currentMonth,
                ano_destino: this.currentYear
            });

            // Verificar se é erro de limite do plano
            if (this.handleLimitError(res)) return;

            if (res.success !== false && res.status !== 'error') {
                this.showToast(`${res.data?.copiados || 0} orçamentos copiados!`, 'success');
                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao copiar', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao copiar mês', 'error');
        }
    }

    async loadCategorySuggestion(categoriaId) {
        const hint = document.getElementById('orcSugestao');
        if (!hint || !categoriaId) { if (hint) hint.textContent = ''; return; }

        try {
            const res = await this.apiGet(`api/financas/orcamentos/sugestoes?mes=${this.currentMonth}&ano=${this.currentYear}`);
            if (res.success !== false && res.data?.length) {
                const sug = res.data.find(s => s.categoria_id == categoriaId);
                if (sug) {
                    hint.textContent = `💡 Sugestão: ${this.formatCurrency(sug.valor_sugerido)} (média: ${this.formatCurrency(sug.media_gastos)})`;
                } else {
                    hint.textContent = '';
                }
            }
        } catch (e) {
            hint.textContent = '';
        }
    }

    // ==================== METAS: CRUD ====================

    openMetaModal(metaId = null) {
        this.editingMetaId = metaId;
        const title = document.getElementById('modalMetaTitle');
        const form = document.getElementById('formMeta');

        // Populate conta select
        const contaSelect = document.getElementById('metaContaId');
        if (contaSelect) {
            contaSelect.innerHTML = '<option value="">— Sem vínculo (aporte manual) —</option>';
            this.contas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                const saldo = c.saldoAtual != null ? ` • ${this.formatCurrency(c.saldoAtual)}` : '';
                opt.textContent = c.nome + (c.instituicao ? ` (${c.instituicao})` : '') + saldo;
                contaSelect.appendChild(opt);
            });
        }

        if (metaId) {
            const meta = this.metas.find(m => m.id === metaId);
            if (!meta) return;
            if (title) title.textContent = 'Editar Meta';
            document.getElementById('metaTitulo').value = meta.titulo || '';
            document.getElementById('metaValorAlvo').value = this.formatNumber(meta.valor_alvo);
            document.getElementById('metaValorAtual').value = this.formatNumber(meta.valor_atual || 0);
            document.getElementById('metaTipo').value = meta.tipo || 'economia';
            document.getElementById('metaPrioridade').value = meta.prioridade || 'media';
            document.getElementById('metaPrazo').value = meta.data_prazo || '';
            document.getElementById('metaCor').value = meta.cor || '#6366f1';
            document.getElementById('metaId').value = meta.id;
            if (contaSelect) contaSelect.value = meta.conta_id || '';

            // Set active color
            document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                d.classList.toggle('active', d.dataset.color === (meta.cor || '#6366f1'));
            });
        } else {
            if (title) title.textContent = 'Nova Meta';
            form?.reset();
            document.getElementById('metaValorAtual').value = '0,00';
            document.getElementById('metaCor').value = '#6366f1';
            document.getElementById('metaId').value = '';
            if (contaSelect) contaSelect.value = '';
            document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                d.classList.toggle('active', d.dataset.color === '#6366f1');
            });
        }

        this.onMetaContaChange();
        this.updateAporteSugerido();
        this.openModal('modalMeta');
    }

    async handleMetaSubmit(e) {
        e.preventDefault();

        const metaId = document.getElementById('metaId').value;
        const contaIdRaw = document.getElementById('metaContaId')?.value;
        const data = {
            titulo: document.getElementById('metaTitulo').value.trim(),
            valor_alvo: this.parseMoney(document.getElementById('metaValorAlvo').value),
            valor_atual: contaIdRaw ? 0 : this.parseMoney(document.getElementById('metaValorAtual').value),
            tipo: document.getElementById('metaTipo').value,
            prioridade: document.getElementById('metaPrioridade').value,
            data_prazo: document.getElementById('metaPrazo').value || null,
            cor: document.getElementById('metaCor').value,
            conta_id: contaIdRaw ? parseInt(contaIdRaw) : null,
        };

        if (!data.titulo) return this.showToast('Informe o título da meta', 'error');
        if (data.valor_alvo <= 0) return this.showToast('Informe o valor da meta', 'error');

        try {
            let res;
            if (metaId) {
                res = await this.apiPut(`api/financas/metas/${metaId}`, data);
            } else {
                res = await this.apiPost('api/financas/metas', data);
            }

            // Verificar se é erro de limite do plano
            if (this.handleLimitError(res)) return;

            if (res.success !== false && res.status !== 'error') {
                // Processar conquistas desbloqueadas
                const responseData = res.data;
                const gamification = responseData?.gamification || res.gamification;
                this.processGamification(gamification);

                this.closeModal('modalMeta');
                this.showToast(metaId ? 'Meta atualizada!' : 'Meta criada!', 'success');
                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao salvar', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao salvar meta', 'error');
        }
    }

    async deleteMeta(id) {
        const meta = this.metas.find(m => m.id === id);
        const result = await Swal.fire({
            title: 'Excluir meta',
            html: `Deseja excluir a meta <strong>${this.escHtml(meta?.titulo || '')}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await this.apiDelete(`api/financas/metas/${id}`);
            if (res.success !== false) {
                this.showToast('Meta excluída', 'success');
                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao excluir', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao excluir', 'error');
        }
    }

    // ==================== METAS: APORTE ====================

    openAporteModal(metaId) {
        const meta = this.metas.find(m => m.id === metaId);
        if (!meta) return;

        document.getElementById('aporteMetaId').value = metaId;
        document.getElementById('aporteValor').value = '';
        const info = document.getElementById('aporteMetaInfo');
        if (info) {
            const restante = (meta.valor_alvo || 0) - (meta.valor_atual || 0);
            info.innerHTML = `<strong>${this.escHtml(meta.titulo)}</strong><br>
                Faltam ${this.formatCurrency(Math.max(0, restante))} para a meta de ${this.formatCurrency(meta.valor_alvo)}`;
        }
        this.openModal('modalAporte');
        setTimeout(() => document.getElementById('aporteValor')?.focus(), 300);
    }

    async handleAporteSubmit(e) {
        e.preventDefault();

        const metaId = document.getElementById('aporteMetaId').value;
        const valor = this.parseMoney(document.getElementById('aporteValor').value);

        if (valor <= 0) return this.showToast('Informe um valor válido', 'error');

        try {
            const res = await this.apiPost(`api/financas/metas/${metaId}/aporte`, { valor });

            if (res.success !== false) {
                this.closeModal('modalAporte');

                // Extrair meta e gamificação da resposta
                const responseData = res.data;
                const meta = responseData?.meta || responseData;
                const gamification = responseData?.gamification || res.gamification;

                if (meta?.status === 'concluida') {
                    await Swal.fire({
                        icon: 'success',
                        title: '🎉 Meta concluída!',
                        html: `Parabéns! Você atingiu <strong>${this.formatCurrency(meta.valor_alvo)}</strong> na meta <strong>${this.escHtml(meta.titulo)}</strong>!`,
                        confirmButtonText: 'Celebrar! 🎊'
                    });
                } else {
                    this.showToast('Aporte registrado!', 'success');
                }

                // Processar conquistas desbloqueadas (após o modal de conclusão)
                this.processGamification(gamification);

                await this.loadAll();
            } else {
                this.showToast(res.message || 'Erro ao registrar aporte', 'error');
            }
        } catch (e) {
            this.showToast('Erro ao registrar aporte', 'error');
        }
    }

    // ==================== METAS: TEMPLATES ====================

    /**
     * Processa conquistas desbloqueadas na resposta da API
     */
    processGamification(gamification) {
        if (!gamification) return;
        const achievements = gamification.achievements || [];
        if (achievements.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
            window.notifyMultipleAchievements(achievements);
        }
    }

    async openTemplates() {
        this.openModal('modalTemplates');
        const grid = document.getElementById('templatesGrid');
        grid.innerHTML = '<div class="loading-state"><i data-lucide="loader-2" class="icon-spin"></i><p>Carregando templates...</p></div>';
        if(window.lucide) lucide.createIcons();

        try {
            const res = await this.apiGet('api/financas/metas/templates');
            if (res.success !== false && res.data?.length) {
                const _faToLucide = {
                    'fa-arrow-down':'arrow-down','fa-arrow-up':'arrow-up','fa-calendar-alt':'calendar-days',
                    'fa-check':'check','fa-check-circle':'circle-check','fa-chevron-right':'chevron-right',
                    'fa-credit-card':'credit-card','fa-exclamation-circle':'circle-alert',
                    'fa-exclamation-triangle':'triangle-alert','fa-eye':'eye','fa-eye-slash':'eye-off',
                    'fa-info-circle':'info','fa-pencil':'pencil','fa-pencil-alt':'pencil',
                    'fa-plus':'plus','fa-plus-circle':'circle-plus','fa-redo':'refresh-cw',
                    'fa-shopping-cart':'shopping-cart','fa-sort':'arrow-up-down','fa-sort-down':'arrow-down',
                    'fa-sort-up':'arrow-up','fa-spinner':'loader-2','fa-times':'x','fa-trash':'trash-2',
                    'fa-undo':'undo-2','fa-university':'landmark','fa-wallet':'wallet'
                };
                grid.innerHTML = res.data.map(tmpl => {
                    // icone agora vem como nome Lucide direto (ex: 'shield', 'smartphone')
                    const iconeHtml = tmpl.icone
                        ? `<i data-lucide="${tmpl.icone}"></i>`
                        : '<i data-lucide="target"></i>';
                    return `
                    <div class="template-card" onclick="financasManager.useTemplate(${JSON.stringify(tmpl).replace(/"/g, '&quot;')})">
                        <span class="template-icon">${iconeHtml}</span>
                        <div class="template-info">
                            <strong>${this.escHtml(tmpl.titulo)}</strong>
                            <p>${this.escHtml(tmpl.descricao || '')}</p>
                            ${tmpl.valor_sugerido ? `<span class="template-valor">Sugestão: ${this.formatCurrency(tmpl.valor_sugerido)}</span>` : ''}
                        </div>
                        <i data-lucide="chevron-right" class="template-arrow"></i>
                    </div>`;
                }).join('');
                if(window.lucide) lucide.createIcons();
            } else {
                grid.innerHTML = '<div class="fin-empty-state"><p>Nenhum template disponível.</p></div>';
            }
        } catch (e) {
            grid.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar templates.</p></div>';
        }
    }

    useTemplate(tmpl) {
        this.closeModal('modalTemplates');
        this.openMetaModal();

        setTimeout(() => {
            if (tmpl.titulo) document.getElementById('metaTitulo').value = tmpl.titulo;
            if (tmpl.tipo) document.getElementById('metaTipo').value = tmpl.tipo;
            if (tmpl.valor_sugerido) document.getElementById('metaValorAlvo').value = this.formatNumber(tmpl.valor_sugerido);
            if (tmpl.prioridade) document.getElementById('metaPrioridade').value = tmpl.prioridade;
            if (tmpl.cor) {
                document.getElementById('metaCor').value = tmpl.cor;
                document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                    d.classList.toggle('active', d.dataset.color === tmpl.cor);
                });
            }
            this.updateAporteSugerido();
        }, 100);
    }

    updateAporteSugerido() {
        const hint = document.getElementById('metaAporteSugerido');
        if (!hint) return;

        const valorAlvo = this.parseMoney(document.getElementById('metaValorAlvo')?.value || '0');
        const prazo = document.getElementById('metaPrazo')?.value;

        // Determinar valor atual: se conta vinculada, usar saldo da conta
        let valorAtual = 0;
        const contaId = document.getElementById('metaContaId')?.value;
        if (contaId) {
            const conta = this.contas.find(c => String(c.id) === String(contaId));
            valorAtual = conta?.saldoAtual ?? 0;
        } else {
            valorAtual = this.parseMoney(document.getElementById('metaValorAtual')?.value || '0');
        }

        if (!prazo || valorAlvo <= 0) { hint.textContent = ''; return; }

        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        const dataPrazo = new Date(prazo + 'T00:00:00');

        if (dataPrazo <= hoje) {
            hint.textContent = '⚠️ Esse prazo já passou. Ajuste para uma data futura.';
            return;
        }

        const restante = valorAlvo - valorAtual;
        if (restante <= 0) { hint.textContent = '🎉 Valor já atingido!'; return; }

        const diffDias = Math.ceil((dataPrazo - hoje) / (1000 * 60 * 60 * 24));
        const mesesRestantes = Math.max(1, Math.ceil(diffDias / 30.44));
        const aporteMensal = restante / mesesRestantes;

        const plural = mesesRestantes === 1 ? 'mês' : 'meses';
        hint.textContent = `💡 Para atingir no prazo: ${this.formatCurrency(aporteMensal)} por mês (${mesesRestantes} ${plural})`;
    }

    // ==================== API HELPERS ====================

    async apiGet(endpoint) {
        const url = `${this.baseUrl}${endpoint}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.getCsrfToken() }
        });
        return await response.json();
    }

    async apiPost(endpoint, data) {
        const url = `${this.baseUrl}${endpoint}`;
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken()
            },
            body: JSON.stringify(data)
        });
        return await response.json();
    }

    async apiPut(endpoint, data) {
        const url = `${this.baseUrl}${endpoint}`;
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken()
            },
            body: JSON.stringify(data)
        });
        return await response.json();
    }

    async apiDelete(endpoint) {
        const url = `${this.baseUrl}${endpoint}`;
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken()
            }
        });
        return await response.json();
    }

    /**
     * Verifica se a resposta da API indica erro de limite do plano
     * e exibe modal de upgrade se necessário
     * @returns true se foi erro de limite tratado, false caso contrário
     */
    handleLimitError(res) {
        const isError = res.status === 'error' || res.success === false;
        if (!isError) return false;

        const msg = res.message || '';
        const isLimitError = /limite|plano gratuito|upgrade|faça upgrade/i.test(msg);

        if (isLimitError) {
            Swal.fire({
                icon: 'warning',
                title: '🚀 Limite Atingido',
                html: `
                    <p>${msg}</p>
                    <p style="margin-top: 12px; color: #6c757d; font-size: 0.9em;">
                        Desbloqueie metas e orçamentos ilimitados com o plano Pro!
                    </p>
                `,
                showCancelButton: true,
                confirmButtonText: '✨ Ver Plano Pro',
                cancelButtonText: 'Depois',
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.goToBilling();
                }
            });
            return true;
        }

        return false;
    }

    /**
     * Redireciona para a página de billing/assinatura
     */
    goToBilling() {
        if (typeof openBillingModal === 'function') {
            openBillingModal();
        } else {
            window.location.href = `${this.baseUrl}billing`;
        }
    }

    // ==================== UTILS ====================

    formatCurrency(value) {
        if (Math.abs(value) < 0.01) value = 0;
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    }

    formatNumber(value) {
        return parseFloat(value || 0).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    }

    parseMoney(str) {
        if (!str) return 0;
        return parseFloat(String(str).replace(/[R$\s]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
    }

    formatarDinheiro(input) {
        let valor = input.value.replace(/\D/g, '');
        if (!valor) { input.value = ''; return; }
        valor = (parseInt(valor) / 100).toFixed(2);
        valor = valor.replace('.', ',');
        valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = valor;
    }

    escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    setText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    getTipoEmoji(tipo) {
        const map = {
            economia: '💰', compra: '🛒', quitacao: '💳', emergencia: '🛡️', investimento: '📈',
            viagem: '✈️', educacao: '🎓', moradia: '🏠', veiculo: '🚗', saude: '🏥',
            negocio: '🏪', aposentadoria: '🏖️', outro: '🎯'
        };
        return map[tipo] || '🎯';
    }

    getPrioridadeTag(prioridade) {
        const map = {
            baixa: '<span class="meta-prio baixa">🟢 Baixa</span>',
            media: '<span class="meta-prio media">🟡 Média</span>',
            alta: '<span class="meta-prio alta">🔴 Alta</span>'
        };
        return map[prioridade] || '';
    }

    getInsightIcon(tipo) {
        const map = {
            alerta_80: 'triangle-alert',
            alerta_100: 'circle-alert',
            economia: 'thumbs-up',
            tendencia_alta: 'trending-up',
            tendencia_baixa: 'trending-down',
            comparativo: 'scale',
            sem_orcamento: 'circle-help',
            meta_atrasada: 'clock'
        };
        return map[tipo] || 'lightbulb';
    }

    showToast(message, type = 'info') {
        const iconMap = { success: 'success', error: 'error', info: 'info', warning: 'warning' };
        Swal.fire({
            icon: iconMap[type] || 'info',
            title: type === 'error' ? 'Erro!' : (type === 'success' ? 'Sucesso!' : 'Aviso'),
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
}

// ==================== INIT ====================
let financasManager;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { financasManager = new FinancasManager(); });
} else {
    financasManager = new FinancasManager();
}
