import { l as x, d as oe, e as J, j as ve, i as ye, c as ee } from "./api-Dkfcp6ON.js"; import { a as ne, i as ie } from "./api-store-OncwIV5O.js"; import { e as T } from "./utils-Bj4jxwhy.js"; const y = { BASE_URL: (() => { let e = document.querySelector('meta[name="base-url"]')?.content || ""; if (!e) { const t = location.pathname.match(/^(.*\/public\/)/); e = t ? location.origin + t[1] : location.origin + "/" } if (e && !/\/public\/?$/.test(e)) { const t = location.pathname.match(/^(.*\/public\/)/); t && (e = location.origin + t[1]) } return e.replace(/\/?$/, "/") })(), TRANSACTIONS_LIMIT: 5, CHART_MONTHS: 6, ANIMATION_DELAY: 300 }; y.API_URL = `${y.BASE_URL}api/`; const c = { saldoValue: document.getElementById("saldoValue"), receitasValue: document.getElementById("receitasValue"), despesasValue: document.getElementById("despesasValue"), saldoMesValue: document.getElementById("saldoMesValue"), chartContainer: document.getElementById("evolutionChart"), chartLoading: document.getElementById("chartLoading"), tableBody: document.getElementById("transactionsTableBody"), table: document.getElementById("transactionsTable"), cardsContainer: document.getElementById("transactionsCards"), emptyState: document.getElementById("emptyState"), monthLabel: document.getElementById("currentMonthText"), streakDays: document.getElementById("streakDays"), badgesGrid: document.getElementById("badgesGrid"), userLevel: document.getElementById("userLevel"), totalLancamentos: document.getElementById("totalLancamentos"), totalCategorias: document.getElementById("totalCategorias"), mesesAtivos: document.getElementById("mesesAtivos"), pontosTotal: document.getElementById("pontosTotal") }, v = { chartInstance: null, currentMonth: null, isLoading: !1 }, h = { money: s => { try { return Number(s || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" }) } catch { return "R$ 0,00" } }, dateBR: s => { if (!s) return "-"; try { const t = String(s).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/); return t ? `${t[3]}/${t[2]}/${t[1]}` : "-" } catch { return "-" } }, formatMonth: s => { try { const [e, t] = String(s).split("-").map(Number); return new Date(e, t - 1, 1).toLocaleDateString("pt-BR", { month: "long", year: "numeric" }) } catch { return "-" } }, formatMonthShort: s => { try { const [e, t] = String(s).split("-").map(Number); return new Date(e, t - 1, 1).toLocaleDateString("pt-BR", { month: "short" }) } catch { return "-" } }, getCurrentMonth: () => window.LukratoHeader?.getMonth?.() || new Date().toISOString().slice(0, 7), getPreviousMonths: (s, e) => { const t = [], [a, o] = s.split("-").map(Number); for (let n = e - 1; n >= 0; n--) { const r = new Date(a, o - 1 - n, 1), i = r.getFullYear(), d = String(r.getMonth() + 1).padStart(2, "0"); t.push(`${i}-${d}`) } return t }, getCssVar: (s, e = "") => { try { return (getComputedStyle(document.documentElement).getPropertyValue(s) || "").trim() || e } catch { return e } }, isLightTheme: () => { try { return (document.documentElement?.getAttribute("data-theme") || "dark") === "light" } catch { return !1 } }, getContaLabel: s => { if (typeof s.conta == "string" && s.conta.trim()) return s.conta.trim(); const e = s.conta_instituicao ?? s.conta_nome ?? s.conta?.instituicao ?? s.conta?.nome ?? null, t = s.conta_destino_instituicao ?? s.conta_destino_nome ?? s.conta_destino?.instituicao ?? s.conta_destino?.nome ?? null; return s.eh_transferencia && (e || t) ? `${e || "-"}${t || "-"}` : s.conta_label && String(s.conta_label).trim() ? String(s.conta_label).trim() : e || "-" }, getTipoClass: s => { const e = String(s || "").toLowerCase(); return e === "receita" ? "receita" : e.includes("despesa") ? "despesa" : e.includes("transferencia") ? "transferencia" : "" }, removeLoadingClass: () => { setTimeout(() => { document.querySelectorAll(".kpi-value.loading").forEach(s => { s.classList.remove("loading") }) }, y.ANIMATION_DELAY) } }, be = () => { const s = (document.documentElement.getAttribute("data-theme") || "").toLowerCase() === "light" || h.isLightTheme?.(); return { isLightTheme: s, axisColor: s ? h.getCssVar("--color-primary", "#e67e22") || "#e67e22" : "rgba(255, 255, 255, 0.6)", yTickColor: s ? "#000" : "#fff", xTickColor: s ? h.getCssVar("--color-text-muted", "#6c757d") || "#6c757d" : "rgba(255, 255, 255, 0.6)", gridColor: s ? "rgba(0, 0, 0, 0.08)" : "rgba(255, 255, 255, 0.05)", tooltipBg: s ? "rgba(255, 255, 255, 0.92)" : "rgba(0, 0, 0, 0.85)", tooltipColor: s ? "#0f172a" : "#f8fafc", labelColor: s ? "#0f172a" : "#f8fafc" } }, we = 3e4, Ee = 15e3, re = window.__LK_CONFIG?.userId ?? "anon"; function xe(s, e) { return `dashboard:overview:${s}:${e}` } function H(s = h.getCurrentMonth(), { limit: e = y.TRANSACTIONS_LIMIT, force: t = !1 } = {}) { return ne(`${y.API_URL}dashboard/overview`, { month: s, limit: e }, { cacheKey: xe(s, e), ttlMs: we, force: t }) } function k(s = null) { const e = s ? `dashboard:overview:${s}:` : "dashboard:overview:"; ie(e) } function ke({ force: s = !1 } = {}) { return ne(`${y.BASE_URL}api/onboarding/checklist`, {}, { cacheKey: `dashboard:onboarding:checklist:${re}`, ttlMs: Ee, force: s }) } function Ce() { ie(`dashboard:onboarding:checklist:${re}`) } class Ie {
  constructor(e = "greetingContainer") { this.container = document.getElementById(e); const t = window.__LK_CONFIG?.username || "Usuario"; this.userName = t.split(" ")[0], this._listeningDataChanged = !1 } render() {
    if (!this.container) return; const e = this.getGreeting(), a = new Date().toLocaleDateString("pt-BR", { weekday: "long", day: "numeric", month: "long" }); this.container.innerHTML = `
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${a}</p>
        <p class="greeting-title">${e.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `, this.loadInsight()
  } getGreeting() { const e = new Date().getHours(); return e >= 5 && e < 12 ? { title: `Bom dia, ${this.userName}.` } : e >= 12 && e < 18 ? { title: `Boa tarde, ${this.userName}.` } : e >= 18 && e < 24 ? { title: `Boa noite, ${this.userName}.` } : { title: `Boa madrugada, ${this.userName}.` } } async loadInsight({ force: e = !1 } = {}) { try { const t = await H(void 0, { force: e }), a = t?.data ?? t; a?.greeting_insight ? this.displayInsight(a.greeting_insight) : this.displayFallbackInsight() } catch (t) { x("Error loading greeting insight", t, "Falha ao carregar insight"), this.displayFallbackInsight() } this._listeningDataChanged || (this._listeningDataChanged = !0, document.addEventListener("lukrato:data-changed", () => { k(), this.loadInsight({ force: !0 }) }), document.addEventListener("lukrato:month-changed", () => { k(), this.loadInsight({ force: !0 }) })) } displayInsight(e) {
    const t = document.getElementById("greetingInsight"); if (!t) return; const { message: a, icon: o, color: n } = e; t.innerHTML = `
      <div class="insight-content">
        <div class="insight-icon" style="color: ${n || "var(--color-primary)"};">
          <i data-lucide="${o || "sparkles"}" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">${a}</p>
      </div>
    `, typeof window.lucide < "u" && window.lucide.createIcons()
  } displayFallbackInsight() {
    const e = document.getElementById("greetingInsight"); e && (e.innerHTML = `
      <div class="insight-content">
        <div class="insight-icon">
          <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">Seu resumo financeiro do mes aparece logo abaixo.</p>
      </div>
    `, typeof window.lucide < "u" && window.lucide.createIcons())
  }
} window.DashboardGreeting = Ie; class Le {
  constructor(e = "healthScoreContainer") { this.container = document.getElementById(e), this.healthScore = 0, this.maxScore = 100, this.animationDuration = 1200 } render() {
    if (!this.container) return; const e = 339.29; this.container.innerHTML = `
      <div class="health-score-widget lk-health-score" data-aos="fade-up" data-aos-duration="500">
        <div class="hs-header">
          <div class="hs-header-copy">
            <span class="dashboard-section-eyebrow">Saude financeira</span>
            <h2 class="hs-summary-title" id="healthSummaryTitle">Saude financeira: carregando</h2>
            <p class="hs-summary-text" id="healthSummaryText">Assim que os dados carregarem, o Lukrato resume sua situacao do mes aqui.</p>
          </div>
          <div class="hs-badge" id="healthIndicator">
            <span class="hs-badge-dot"></span>
            <span class="hs-badge-text">Carregando</span>
          </div>
        </div>

        <div class="hs-main">
          <div class="hs-gauge-area">
            <svg class="hs-gauge" viewBox="0 0 120 120">
              <defs>
                <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#10b981"/>
                  <stop offset="100%" stop-color="#3b82f6"/>
                </linearGradient>
              </defs>
              <circle cx="60" cy="60" r="54" class="hs-gauge-track"/>
              <circle cx="60" cy="60" r="54" class="hs-gauge-fill"
                id="gaugeCircle"
                stroke-dasharray="${e}"
                stroke-dashoffset="${e}"
              />
              <text x="60" y="56" class="hs-gauge-value" id="gaugeValue">0</text>
              <text x="60" y="72" class="hs-gauge-label">de 100</text>
            </svg>
          </div>

          <div class="hs-info">
            <div class="hs-breakdown">
              <div class="hs-metric">
                <span class="hs-metric-label">Seus registros</span>
                <span class="hs-metric-value" id="hsLancamentos">--</span>
              </div>
              <div class="hs-metric-divider"></div>
              <div class="hs-metric">
                <span class="hs-metric-label">Limites</span>
                <span class="hs-metric-value" id="hsOrcamento">--</span>
              </div>
              <div class="hs-metric-divider"></div>
              <div class="hs-metric">
                <span class="hs-metric-label">Metas</span>
                <span class="hs-metric-value" id="hsMetas">--</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `, this.circumference = e, this.updateIcons()
  } async load({ force: e = !1 } = {}) { try { const t = await H(void 0, { force: e }), a = t?.data ?? t; a?.health_score && this.updateScore(a.health_score) } catch (t) { x("Error loading health score", t, "Falha ao carregar health score"), this.showError() } this._listeningDataChanged || (this._listeningDataChanged = !0, document.addEventListener("lukrato:data-changed", () => { k(), this.load({ force: !0 }) }), document.addEventListener("lukrato:month-changed", () => { k(), this.load({ force: !0 }) })) } updateScore(e) { const { score: t = 0 } = e; this.animateGauge(t), this.updateBreakdown(e), this.updateStatusIndicator(t) } animateGauge(e) { const t = document.getElementById("gaugeCircle"), a = document.getElementById("gaugeValue"); if (!t || !a) return; const o = this.circumference || 339.29; let n = 0; const r = e / (this.animationDuration / 16), i = () => { n += r, n >= e && (n = e); const d = o - o * n / this.maxScore; t.setAttribute("stroke-dashoffset", d), a.textContent = Math.round(n), n < e && requestAnimationFrame(i) }; i() } updateBreakdown(e) { const t = document.getElementById("hsLancamentos"), a = document.getElementById("hsOrcamento"), o = document.getElementById("hsMetas"); if (t) { const n = e.lancamentos ?? 0; t.textContent = `${n} neste mes`, n >= 10 ? t.className = "hs-metric-value color-success" : n >= 5 ? t.className = "hs-metric-value color-warning" : t.className = "hs-metric-value color-muted" } if (a) { const n = e.orcamentos ?? 0, r = e.orcamentos_ok ?? 0; n === 0 ? (a.textContent = "Nao definido", a.className = "hs-metric-value color-muted") : (a.textContent = `${r}/${n} no limite`, r === n ? a.className = "hs-metric-value color-success" : r >= n / 2 ? a.className = "hs-metric-value color-warning" : a.className = "hs-metric-value color-danger") } if (o) { const n = e.metas_ativas ?? 0, r = e.metas_concluidas ?? 0; n === 0 ? (o.textContent = "Nenhuma", o.className = "hs-metric-value color-muted") : r > 0 ? (o.textContent = `${n} ativa${n !== 1 ? "s" : ""} e ${r} concluida${r !== 1 ? "s" : ""}`, o.className = "hs-metric-value color-success") : (o.textContent = `${n} ativa${n !== 1 ? "s" : ""}`, o.className = "hs-metric-value color-warning") } } updateStatusIndicator(e) {
    const t = document.getElementById("healthIndicator"), a = document.getElementById("healthSummaryTitle"), o = document.getElementById("healthSummaryText"); if (!t || !a || !o) return; let n = "critical", r = "CRITICA", i = "Seu mes pede ajustes rapidos para evitar aperto financeiro."; e >= 70 ? (n = "excellent", r = "BOA", i = "você esta em uma faixa saudavel neste mes e segue no controle.") : e >= 50 ? (n = "good", r = "ESTAVEL", i = "Seu controle esta funcionando, mas ainda ha espaco para melhorar.") : e >= 30 && (n = "warning", r = "EM ATENCAO", i = "Alguns sinais pedem cuidado agora para o mes nao apertar."), t.className = `hs-badge hs-badge--${n}`, t.innerHTML = `
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${r}</span>
    `, a.textContent = `Saude financeira: ${r}`, o.textContent = i
  } updateIcons() { typeof window.lucide < "u" && window.lucide.createIcons() } showError() {
    const e = document.getElementById("healthIndicator"), t = document.getElementById("healthSummaryTitle"), a = document.getElementById("healthSummaryText"); e && (e.className = "hs-badge hs-badge--error", e.innerHTML = `
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `), t && (t.textContent = "Saude financeira: indisponivel"), a && (a.textContent = "Nao foi possivel resumir sua saude financeira agora.")
  }
} window.HealthScoreWidget = Le; class Se {
  constructor(e = "healthScoreInsights") { this.container = document.getElementById(e), this.baseURL = window.BASE_URL || "/", this.init() } init() { this.container && (this._initialized || (this._initialized = !0, this.renderSkeleton(), this.loadInsights(), this._intervalId = setInterval(() => this.loadInsights({ force: !0 }), 3e5), document.addEventListener("lukrato:data-changed", () => { k(), this.loadInsights({ force: !0 }) }), document.addEventListener("lukrato:month-changed", () => { k(), this.loadInsights({ force: !0 }) }))) } renderSkeleton() {
    this.container.innerHTML = `
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `} async loadInsights({ force: e = !1 } = {}) { try { const t = await H(void 0, { force: e }), a = t?.data ?? t; a?.health_score_insights ? this.renderInsights(a.health_score_insights) : this.renderEmpty() } catch (t) { x("Error loading health score insights", t, "Falha ao carregar insights"), this.renderEmpty() } } renderInsights(e) {
    const t = Array.isArray(e) ? e : e?.insights || [], a = Array.isArray(e) ? "" : e?.total_possible_improvement || ""; if (t.length === 0) { this.renderEmpty(); return } const o = t.map((n, r) => {
      const i = this.normalizeInsight(n); return `
      <a href="${this.baseURL}${i.action.url}" class="hsi-card hsi-card--${i.priority}" style="animation-delay: ${r * 80}ms;">
        <div class="hsi-card-icon hsi-icon--${i.priority}">
          <i data-lucide="${this.getIconForType(i.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${i.title}</span>
          <span class="hsi-card-desc">${i.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${i.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `}).join(""); this.container.innerHTML = `
      <div class="hsi-list">${o}</div>
      ${a ? `
        <div class="hsi-summary">
          <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
          <span>Potencial: <strong>${a}</strong></span>
        </div>
      `: ""}
    `, typeof window.lucide < "u" && window.lucide.createIcons()
  } normalizeInsight(e) { const a = { negative_balance: { title: "Seu saldo ficou negativo", impact: "Aja agora", action: { url: "lancamentos?tipo=despesa" } }, low_activity: { title: "Registre mais movimentacoes", impact: "Mais controle", action: { url: "lancamentos" } }, low_categories: { title: "Use mais categorias", impact: "Mais clareza", action: { url: "categorias" } }, no_goals: { title: "Defina uma meta financeira", impact: "Mais direcao", action: { url: "financas#metas" } } }[e.type] || { title: "Insight do mes", impact: "Ver detalhe", action: { url: "dashboard" } }; return { priority: e.priority || "medium", type: e.type || "generic", title: e.title || a.title, message: e.message || "", impact: e.impact || a.impact, action: e.action || a.action } } renderEmpty() { this.container.innerHTML = "" } getIconForType(e) { return { savings_rate: "piggy-bank", consistency: "calendar-check", diversification: "layers", negative_balance: "alert-triangle", low_balance: "wallet", no_income: "alert-circle", no_goals: "target" }[e] || "lightbulb" }
} window.HealthScoreInsights = Se; class _e {
  constructor(e = "financeOverviewContainer") { this.container = document.getElementById(e), this.baseURL = window.BASE_URL || "/" } render() {
    this.container && (this.container.innerHTML = `
      <section class="finance-overview-section" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
          <div>
            <span class="dashboard-section-eyebrow">Metas</span>
            <h2 class="dashboard-section-title">Seu proximo objetivo</h2>
            <p class="dashboard-section-copy" id="foGoalsHeadline">Faltam R$ 0,00 para alcancar sua meta.</p>
          </div>
          <a href="${this.baseURL}financas#metas" class="dashboard-section-link">Criar metas</a>
        </div>

        <div class="fo-grid">
          <div class="fo-card fo-card--goal" id="foMetas">
            <div class="fo-skeleton"></div>
          </div>
          <div class="fo-card fo-card--budget" id="foOrcamento">
            <div class="fo-skeleton"></div>
          </div>
        </div>
      </section>
    `)
  } async load() { try { const { mes: e, ano: t } = this.getSelectedPeriod(), a = await oe(`${this.baseURL}api/financas/resumo`, { mes: e, ano: t }); a.success && a.data ? (this.renderAlerts(a.data), this.renderMetas(a.data.metas), this.renderOrcamento(a.data.orcamento)) : (this.renderAlerts(), this.renderMetasEmpty(), this.renderOrcamentoEmpty()) } catch (e) { console.error("Error loading finance overview:", e), this.renderAlerts(), this.renderMetasEmpty(), this.renderOrcamentoEmpty() } this._listening || (this._listening = !0, document.addEventListener("lukrato:data-changed", () => this.load()), document.addEventListener("lukrato:month-changed", () => this.load())) } renderAlerts(e = null) {
    const t = document.getElementById("dashboardAlertsBudget"); if (!t) return; const a = Array.isArray(e?.orcamento?.orcamentos) ? e.orcamento.orcamentos.slice() : [], o = a.filter(i => i.status === "estourado").sort((i, d) => Number(d.excedido || 0) - Number(i.excedido || 0)), n = a.filter(i => i.status === "alerta").sort((i, d) => Number(d.percentual || 0) - Number(i.percentual || 0)), r = []; if (o.slice(0, 2).forEach(i => { r.push({ variant: "danger", title: `você ja passou do limite em ${i.categoria_nome}`, message: `Excedido em ${this.money(i.excedido || 0)}.` }) }), r.length < 2 && n.slice(0, 2 - r.length).forEach(i => { r.push({ variant: "warning", title: `${i.categoria_nome} ja consumiu ${Math.round(i.percentual || 0)}% do limite`, message: `Restam ${this.money(i.disponivel || 0)} nessa categoria.` }) }), r.length === 0) { t.innerHTML = "", this.toggleAlertsSection(); return } t.innerHTML = r.map(i => `
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${i.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${i.variant === "danger" ? "triangle-alert" : "circle-alert"}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${i.title}</strong>
          <span>${i.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join(""), this.toggleAlertsSection(), this.refreshIcons()
  } renderOrcamento(e) {
    const t = document.getElementById("foOrcamento"); if (!t) return; if (!e || e.total_categorias === 0) { this.renderOrcamentoEmpty(); return } const a = Math.round(e.percentual_geral || 0), o = this.getBarColor(a), r = (e.orcamentos || []).slice().sort((d, u) => Number(u.percentual || 0) - Number(d.percentual || 0)).slice(0, 3).map(d => {
      const u = Math.min(Number(d.percentual || 0), 100), p = this.getBarColor(d.percentual); return `
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${d.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${p};">${Math.round(d.percentual || 0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${u}%; background:${p};"></div>
          </div>
        </div>
      `}).join(""); let i = "No controle"; (e.estourados || 0) > 0 ? i = `${e.estourados} acima do limite` : (e.em_alerta || 0) > 0 && (i = `${e.em_alerta} em atencao`), t.innerHTML = `
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${o}; background:${o}18;">${i}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(e.total_gasto || 0)} usados de ${this.money(e.total_limite || 0)}</span>
        <span class="fo-summary-status">Saude: ${e.saude_financeira?.label || "Boa"}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(a, 100)}%; background:${o};"></div>
      </div>

      ${r ? `<div class="fo-orc-list">${r}</div>` : ""}

      <a href="${this.baseURL}financas#orcamentos" class="fo-link">Ver limites <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `, this.refreshIcons()
  } renderOrcamentoEmpty() {
    const e = document.getElementById("foOrcamento"); e && (e.innerHTML = `
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu limites para acompanhar categorias.</p>
        <a href="${this.baseURL}financas#orcamentos" class="fo-cta">Definir limite</a>
      </div>
    `, this.refreshIcons())
  } renderMetas(e) {
    const t = document.getElementById("foMetas"); if (!t) return; if (!e || e.total_metas === 0) { this.renderMetasEmpty(); return } const a = e.proxima_concluir, o = Math.round(e.progresso_geral || 0); if (!a) {
      this.updateGoalsHeadline("você tem metas ativas, mas nenhuma esta proxima de concluir."), t.innerHTML = `
        <div class="fo-card-header">
          <a href="${this.baseURL}financas#metas" class="fo-card-title">
            <i data-lucide="target" style="width:16px;height:16px;"></i>
            Metas
          </a>
          <span class="fo-badge">${e.total_metas} ativa${e.total_metas !== 1 ? "s" : ""}</span>
        </div>
        <div class="fo-metas-summary">
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value">${o}%</span>
            <span class="fo-metas-stat-label">progresso geral</span>
          </div>
        </div>
        <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
      `, this.refreshIcons(); return
    } const n = a.cor || "var(--color-primary)", r = this.normalizeIconName(a.icone), i = Math.round(a.progresso || 0), d = Math.max(Number(a.valor_alvo || 0) - Number(a.valor_atual || 0), 0); this.updateGoalsHeadline(`Faltam ${this.money(d)} para alcancar sua meta.`), t.innerHTML = `
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${e.total_metas} ativa${e.total_metas !== 1 ? "s" : ""}</span>
      </div>

      <div class="fo-meta-destaque">
        <div class="fo-meta-icon" style="color:${n}; background:${n}18;">
          <i data-lucide="${r}" style="width:16px;height:16px;"></i>
        </div>
        <div class="fo-meta-info">
          <span class="fo-meta-titulo">${a.titulo}</span>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${Math.min(i, 100)}%; background:${n};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(a.valor_atual || 0)} de ${this.money(a.valor_alvo || 0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${n};">${i}%</span>
      </div>

      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${this.money(d)}</span>
          <span class="fo-metas-stat-label">faltam para concluir</span>
        </div>
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${o}%</span>
          <span class="fo-metas-stat-label">progresso geral</span>
        </div>
      </div>

      <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `, this.refreshIcons()
  } renderMetasEmpty() {
    const e = document.getElementById("foMetas"); e && (this.updateGoalsHeadline("Defina uma meta para transformar sua sobra em um objetivo claro."), e.innerHTML = `
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu uma meta ativa.</p>
        <a href="${this.baseURL}financas#metas" class="fo-cta">Criar meta</a>
      </div>
    `, this.refreshIcons())
  } updateGoalsHeadline(e) { const t = document.getElementById("foGoalsHeadline"); t && (t.textContent = e) } toggleAlertsSection() { const e = document.getElementById("dashboardAlertsSection"), t = document.getElementById("dashboardAlertsOverview"), a = document.getElementById("dashboardAlertsBudget"); if (!e) return; const o = t && t.innerHTML.trim() !== "", n = a && a.innerHTML.trim() !== ""; e.style.display = o || n ? "block" : "none" } getSelectedPeriod() { const e = h.getCurrentMonth ? h.getCurrentMonth() : new Date().toISOString().slice(0, 7), t = String(e).match(/^(\d{4})-(\d{2})$/); if (t) return { ano: Number(t[1]), mes: Number(t[2]) }; const a = new Date; return { mes: a.getMonth() + 1, ano: a.getFullYear() } } getBarColor(e) { return e >= 100 ? "#ef4444" : e >= 80 ? "#f59e0b" : "#10b981" } normalizeIconName(e) { const t = String(e || "").trim(); return t && ({ "fa-bullseye": "target", "fa-target": "target", "fa-wallet": "wallet", "fa-university": "landmark", "fa-plane": "plane", "fa-car": "car", "fa-home": "house", "fa-heart": "heart", "fa-briefcase": "briefcase-business", "fa-piggy-bank": "piggy-bank", "fa-shield": "shield", "fa-graduation-cap": "graduation-cap", "fa-store": "store", "fa-baby": "baby", "fa-hand-holding-usd": "hand-coins" }[t] || t.replace(/^fa-/, "")) || "target" } money(e) { return Number(e || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" }) } refreshIcons() { typeof window.lucide < "u" && window.lucide.createIcons() }
} window.FinanceOverview = _e; const $e = `lk_user_${window.__LK_CONFIG?.userId ?? "anon"}_`; function Te(s) { return $e + s } class ce { constructor(e = {}) { this.config = { isFirstTime: e.isFirstTime || window.__lkFirstVisit || !1, minTransactionsToUnlock: e.minTransactionsToUnlock || 1, ...e }, this.state = { transactionCount: 0, hiddenSections: [] }, this.HIDDEN_SECTIONS_KEY = "lk_hidden_sections_shown", this.init() } init() { this.config.isFirstTime && (this.checkTransactionCount(), document.addEventListener("lukrato:transaction-added", () => { this.onTransactionAdded() }), document.addEventListener("lukrato:data-changed", e => { e.detail?.resource === "transactions" && (k(), this.checkTransactionCount({ force: !0 })) })) } async checkTransactionCount({ force: e = !1 } = {}) { try { const t = await H(this.getCurrentMonth(), { force: e }), a = (t?.data ?? t)?.metrics || null; a && typeof a.count < "u" && (this.state.transactionCount = parseInt(a.count) || 0, this.updateHiddenSections()) } catch (t) { x("Error checking transaction count", t, "Falha ao verificar transações") } } updateHiddenSections() { [{ id: "provisaoSection", threshold: 0, label: "Previsão" }, { id: "chart-section", threshold: 2, label: "Gráfico" }, { id: "table-section", threshold: 0, label: "Transações" }].forEach(t => { const a = document.querySelector(`#${t.id}, .${t.id}`); if (!a) return; const o = this.state.transactionCount >= t.threshold; o && a.classList.contains("progressive-hidden") ? this.revealSection(a, t.label) : !o && !a.classList.contains("progressive-hidden") && (a.classList.add("progressive-hidden"), a.style.opacity = "0.5", a.style.pointerEvents = "none") }) } revealSection(e, t) { e.classList.remove("progressive-hidden"), e.style.opacity = "0", e.style.transform = "translateY(20px)", e.style.transition = "all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)", e.style.pointerEvents = "auto", setTimeout(() => { e.style.opacity = "1", e.style.transform = "translateY(0)" }, 100), window.LK?.toast && window.LK.toast.success(`✨ Nova seção desbloqueada: ${t}`); const a = JSON.parse(localStorage.getItem(this.HIDDEN_SECTIONS_KEY) || "[]"); a.includes(t) || (a.push(t), localStorage.setItem(this.HIDDEN_SECTIONS_KEY, JSON.stringify(a))) } onTransactionAdded() { this.checkTransactionCount() } getCurrentMonth() { const e = new Date, t = e.getFullYear(), a = String(e.getMonth() + 1).padStart(2, "0"); return `${t}-${a}` } revealAll() { document.querySelectorAll(".progressive-hidden").forEach((t, a) => { setTimeout(() => { t.classList.remove("progressive-hidden"), t.style.opacity = "1", t.style.pointerEvents = "auto" }, a * 100) }), localStorage.setItem(this.HIDDEN_SECTIONS_KEY, JSON.stringify(["Previsão", "Gráfico", "Transações"])) } } window.ProgressiveDisclosure = ce; document.addEventListener("DOMContentLoaded", () => { (window.__lkFirstVisit || localStorage.getItem(Te("lukrato_onboarding_completed")) !== "true") && (window.progressiveDisclosure = new ce({ isFirstTime: !0 })) }); class le {
  constructor() { this.initialized = !1, this.init() } init() { this.setupEventListeners(), this.initialized = !0 } setupEventListeners() { document.addEventListener("lukrato:transaction-added", () => { this.playAddedAnimation() }), document.addEventListener("lukrato:level-up", e => { this.playLevelUpAnimation(e.detail?.level) }), document.addEventListener("lukrato:streak-milestone", e => { this.playStreakAnimation(e.detail?.days) }), document.addEventListener("lukrato:goal-completed", e => { this.playGoalAnimation(e.detail?.goalName) }), document.addEventListener("lukrato:achievement-unlocked", e => { this.playAchievementAnimation(e.detail?.name, e.detail?.icon) }) } playAddedAnimation() { window.fab && window.fab.celebrate(), window.LK?.toast && window.LK.toast.success("✅ Lançamento adicionado!"), this.fireConfetti("small", .9, .9) } playLevelUpAnimation(e) { this.showCelebrationToast({ title: `⭐ Nível ${e}!`, subtitle: "Parabéns! Você subiu de nível", icon: "star", duration: 3e3 }), this.fireConfetti("large", .5, .3), this.screenFlash("#f59e0b", .3, 2), window.fab?.container && (window.fab.container.style.animation = "spin 0.8s ease-out", setTimeout(() => { window.fab.container.style.animation = "" }, 800)) } playStreakAnimation(e) { const a = { 7: { title: "🔥 Semana Perfeita!", subtitle: "7 dias de série!" }, 14: { title: "🌟 Duas Semanas!", subtitle: "14 dias de série!" }, 30: { title: "👑 Mês Épico!", subtitle: "30 dias de série!" }, 100: { title: "🚀 Lendário!", subtitle: "100 dias de série!" } }[e] || { title: `🔥 ${e} dias!`, subtitle: "Série em alta!" }; this.showCelebrationModal(a.title, a.subtitle), this.fireConfetti("extreme", .5, .2) } playGoalAnimation(e) { this.showCelebrationToast({ title: "🎯 Meta Atingida!", subtitle: `Você completou: ${e}`, icon: "target", duration: 3500 }), this.fireConfetti("large", .5, .4), this.screenFlash("#10b981", .4, 1.5) } playAchievementAnimation(e, t) {
    const a = document.createElement("div"); a.className = "achievement-popup", a.innerHTML = `
      <div class="achievement-card">
        <div class="achievement-icon">${t || "🏆"}</div>
        <div class="achievement-title">Conquista Desbloqueada!</div>
        <div class="achievement-name">${e}</div>
      </div>
    `, document.body.appendChild(a), setTimeout(() => { a.classList.add("show") }, 10), setTimeout(() => { a.classList.remove("show"), setTimeout(() => a.remove(), 300) }, 3500), this.fireConfetti("medium", .5, .6)
  } showCelebrationToast(e) {
    const { title: t = "🎉 Parabéns!", subtitle: a = "Você fez progresso!", icon: o = "party-popper", duration: n = 3e3 } = e; window.LK?.toast && window.LK.toast.success(`${t}
${a}`)
  } showCelebrationModal(e, t) { typeof Swal > "u" || Swal.fire({ title: e, text: t, icon: "success", confirmButtonText: "🎉 Incrível!", confirmButtonColor: "var(--color-primary)", allowOutsideClick: !1, didOpen: () => { this.fireConfetti("extreme", .5, .2) } }) } screenFlash(e = "#10b981", t = .3, a = 1) {
    const o = document.createElement("div"); o.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: ${e};
      opacity: 0;
      z-index: 99999;
      pointer-events: none;
      transition: none;
    `, document.body.appendChild(o), setTimeout(() => { o.style.transition = `opacity ${a / 2}ms ease-out`, o.style.opacity = t }, 10), setTimeout(() => { o.style.transition = `opacity ${a / 2}ms ease-in`, o.style.opacity = "0" }, a / 2), setTimeout(() => o.remove(), a)
  } fireConfetti(e = "medium", t = .5, a = .5) { if (typeof confetti != "function") return; const o = { small: { particleCount: 30, spread: 40 }, medium: { particleCount: 60, spread: 60 }, large: { particleCount: 100, spread: 90 }, extreme: { particleCount: 150, spread: 120 } }, n = o[e] || o.medium; confetti({ ...n, origin: { x: t, y: a }, gravity: .8, decay: .95, zIndex: 99999 }) }
} window.CelebrationSystem = le; document.addEventListener("DOMContentLoaded", () => { window.celebrationSystem || (window.celebrationSystem = new le) }); function Be() { const s = window.BASE_URL || window.__LK_CONFIG?.baseUrl || "/";["assets/css/pages/admin-dashboard/health-score.css", "assets/css/pages/admin-dashboard/greeting.css", "assets/css/pages/admin-dashboard/health-score-insights.css"].forEach(t => { const a = s + t; if (!document.querySelector(`link[href="${a}"]`)) { const o = document.createElement("link"); o.rel = "stylesheet", o.href = a, o.type = "text/css", document.head.appendChild(o) } }) } function Ae() { return new Promise(s => { let e = 0; const t = setInterval(() => { window.HealthScoreWidget && window.DashboardGreeting && window.HealthScoreInsights && window.FinanceOverview && (clearInterval(t), s()), e++ > 50 && (clearInterval(t), s()) }, 100) }) } function K(s, e) { const t = document.getElementById(s); return t || e() } async function Me() { Be(), await Ae(), document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", te) : te() } function te() { const s = document.querySelector(".modern-dashboard"); if (s) { if (typeof window.DashboardGreeting < "u" && (K("greetingContainer", () => { const t = document.createElement("div"); return t.id = "greetingContainer", s.insertBefore(t, s.firstChild), t }), new window.DashboardGreeting().render()), typeof window.HealthScoreWidget < "u") { const e = K("healthScoreContainer", () => { const a = document.createElement("div"); a.id = "healthScoreContainer"; const o = s.querySelector(".kpi-grid"); return o ? o.insertAdjacentElement("afterend", a) : s.insertBefore(a, s.children[1] || null), a }), t = new window.HealthScoreWidget; t.render(), t.load(), typeof window.HealthScoreInsights < "u" && (K("healthScoreInsights", () => { const a = document.createElement("div"); return a.id = "healthScoreInsights", a.className = "health-score-insights-section", e.insertAdjacentElement("afterend", a), a }), window.healthScoreInsights = new window.HealthScoreInsights) } if (typeof window.FinanceOverview < "u") { K("financeOverviewContainer", () => { const t = document.createElement("div"); t.id = "financeOverviewContainer"; const a = s.querySelector(".provisao-section"); return a ? a.insertAdjacentElement("afterend", t) : s.appendChild(t), t }); const e = new window.FinanceOverview; e.render(), e.load() } typeof window.lucide < "u" && window.lucide.createIcons() } } Me(); window.__LK_CONFIG?.baseUrl; const U = "lk_dashboard_tour_completed", O = [{ target: ".lk-health-score", title: "Sua Saúde Financeira", content: "Este número mostra como estão suas finanças. Quanto mais alto, melhor!", position: "bottom", icon: "heart-pulse" }, { target: ".lk-kpi-cards", title: "Resumo Financeiro", content: "Veja seu saldo, receitas e despesas do mês em um só lugar.", position: "bottom", icon: "bar-chart-3" }, { target: '#addTransactionBtn, .fab, [data-action="add-transaction"]', title: "Adicionar Lançamentos", content: "Use este botão para registrar suas receitas e despesas.", position: "top", icon: "plus-circle", highlight: !0 }, { target: '.sidebar .nav-item[href*="lancamentos"]', title: "Todos os Lançamentos", content: "Aqui você vê o histórico completo de tudo que entrou e saiu.", position: "right", icon: "layers" }, { target: '.sidebar .nav-item[href*="relatorios"]', title: "Relatórios", content: "Descubra para onde vai seu dinheiro com gráficos detalhados.", position: "right", icon: "pie-chart" }]; class de {
  constructor() { this.currentStep = 0, this.isActive = !1, this.overlay = null, this.tooltip = null } shouldShowTour() { const e = new URLSearchParams(window.location.search).get("first_visit") === "1", t = window.__lkFirstVisit === !0, a = localStorage.getItem(U) === "true"; return (e || t) && !a } start() { if (this.isActive) return; if (!document.querySelector(O[0].target)) { console.warn("[Tour] First target not found, skipping tour"); return } this.isActive = !0, this.currentStep = 0, this.createOverlay(), this.showStep(0), this.trackEvent("tour_started") } createOverlay() {
    this.overlay = document.createElement("div"), this.overlay.className = "lk-tour-overlay", this.overlay.innerHTML = `
            <div class="lk-tour-backdrop"></div>
        `, document.body.appendChild(this.overlay), this.overlay.querySelector(".lk-tour-backdrop").addEventListener("click", () => { this.askToSkip() })
  } showStep(e) { if (e >= O.length) { this.complete(); return } const t = O[e], a = document.querySelector(t.target); if (!a) { this.showStep(e + 1); return } this.currentStep = e, this.tooltip && this.tooltip.remove(), this.highlightElement(a, t.highlight), this.createTooltip(a, t, e), this.scrollToElement(a) } highlightElement(e, t = !1) {
    document.querySelectorAll(".lk-tour-highlighted").forEach(n => { n.classList.remove("lk-tour-highlighted") }), e.classList.add("lk-tour-highlighted"), t && e.classList.add("lk-tour-pulse"); const a = e.getBoundingClientRect(), o = this.overlay.querySelector(".lk-tour-spotlight") || document.createElement("div"); o.className = "lk-tour-spotlight", o.style.cssText = `
            position: fixed;
            top: ${a.top - 8}px;
            left: ${a.left - 8}px;
            width: ${a.width + 16}px;
            height: ${a.height + 16}px;
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);
            pointer-events: none;
            z-index: 10000;
            transition: all 0.3s ease;
        `, o.parentElement || this.overlay.appendChild(o)
  } createTooltip(e, t, a) {
    const o = e.getBoundingClientRect(), n = a === O.length - 1; this.tooltip = document.createElement("div"), this.tooltip.className = `lk-tour-tooltip lk-tour-tooltip-${t.position}`, this.tooltip.innerHTML = `
            <div class="lk-tour-tooltip-content">
                <div class="lk-tour-tooltip-header">
                    <div class="lk-tour-tooltip-icon">
                        <i data-lucide="${t.icon}"></i>
                    </div>
                    <div class="lk-tour-tooltip-title">${t.title}</div>
                </div>
                <p class="lk-tour-tooltip-text">${t.content}</p>
                <div class="lk-tour-tooltip-footer">
                    <div class="lk-tour-tooltip-progress">
                        ${a + 1} de ${O.length}
                    </div>
                    <div class="lk-tour-tooltip-actions">
                        <button class="lk-tour-btn-skip" type="button">Pular</button>
                        <button class="lk-tour-btn-next" type="button">
                            ${n ? "Concluir" : "Próximo"}
                            <i data-lucide="${n ? "check" : "arrow-right"}"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="lk-tour-tooltip-arrow"></div>
        `, document.body.appendChild(this.tooltip), this.positionTooltip(o, t.position), window.lucide && lucide.createIcons({ icons: this.tooltip }), this.tooltip.querySelector(".lk-tour-btn-next").addEventListener("click", () => { this.next() }), this.tooltip.querySelector(".lk-tour-btn-skip").addEventListener("click", () => { this.askToSkip() }), requestAnimationFrame(() => { this.tooltip.classList.add("visible") })
  } positionTooltip(e, t) { const a = this.tooltip, o = a.getBoundingClientRect(); let n, r; switch (t) { case "top": n = e.top - o.height - 16, r = e.left + e.width / 2 - o.width / 2; break; case "bottom": n = e.bottom + 16, r = e.left + e.width / 2 - o.width / 2; break; case "left": n = e.top + e.height / 2 - o.height / 2, r = e.left - o.width - 16; break; case "right": n = e.top + e.height / 2 - o.height / 2, r = e.right + 16; break }const i = 16; r = Math.max(i, Math.min(r, window.innerWidth - o.width - i)), n = Math.max(i, Math.min(n, window.innerHeight - o.height - i)), a.style.top = `${n}px`, a.style.left = `${r}px` } scrollToElement(e) { const t = e.getBoundingClientRect(); t.top >= 0 && t.bottom <= window.innerHeight && t.left >= 0 && t.right <= window.innerWidth || e.scrollIntoView({ behavior: "smooth", block: "center" }) } next() { this.trackEvent("tour_step_completed", { step: this.currentStep }), this.showStep(this.currentStep + 1) } askToSkip() { typeof Swal < "u" ? Swal.fire({ title: "Pular o tour?", text: "Você pode acessar o tour novamente pelo menu de ajuda.", icon: "question", showCancelButton: !0, confirmButtonColor: "var(--color-primary, #e67e22)", cancelButtonColor: "#6c757d", confirmButtonText: "Sim, pular", cancelButtonText: "Continuar tour" }).then(e => { e.isConfirmed && this.skip() }) : confirm("Pular o tour? Você pode acessá-lo novamente pelo menu de ajuda.") && this.skip() } skip() { this.trackEvent("tour_skipped", { step: this.currentStep }), this.cleanup(), localStorage.setItem(U, "true") } complete() { this.trackEvent("tour_completed"), this.cleanup(), localStorage.setItem(U, "true"), typeof Swal < "u" && Swal.fire({ title: "Tour concluído! 🎉", text: "Agora você conhece o básico do Lukrato. Bora organizar suas finanças!", icon: "success", confirmButtonColor: "var(--color-primary, #e67e22)", confirmButtonText: "Vamos lá!" }) } cleanup() { this.isActive = !1, this.overlay && (this.overlay.remove(), this.overlay = null), this.tooltip && (this.tooltip.remove(), this.tooltip = null), document.querySelectorAll(".lk-tour-highlighted, .lk-tour-pulse").forEach(e => { e.classList.remove("lk-tour-highlighted", "lk-tour-pulse") }) } trackEvent(e, t = {}) { console.log("[Tour]", e, t) }
} function ue() {
  if (document.getElementById("lk-tour-styles")) return; const s = document.createElement("style"); s.id = "lk-tour-styles", s.textContent = `
        .lk-tour-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
        }

        .lk-tour-backdrop {
            position: absolute;
            inset: 0;
            pointer-events: auto;
        }

        .lk-tour-highlighted {
            position: relative;
            z-index: 10001 !important;
        }

        .lk-tour-pulse {
            animation: lk-tour-pulse 2s ease-in-out infinite;
        }

        @keyframes lk-tour-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(230, 126, 34, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(230, 126, 34, 0); }
        }

        .lk-tour-tooltip {
            position: fixed;
            z-index: 10002;
            max-width: 320px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .lk-tour-tooltip.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .lk-tour-tooltip-content {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .lk-tour-tooltip-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .lk-tour-tooltip-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--color-primary), #d35400);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .lk-tour-tooltip-icon svg {
            width: 20px;
            height: 20px;
            color: white;
        }

        .lk-tour-tooltip-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .lk-tour-tooltip-text {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .lk-tour-tooltip-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .lk-tour-tooltip-progress {
            font-size: 0.75rem;
            color: var(--color-text-muted);
        }

        .lk-tour-tooltip-actions {
            display: flex;
            gap: 8px;
        }

        .lk-tour-btn-skip {
            padding: 8px 16px;
            background: transparent;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--color-text-muted);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .lk-tour-btn-skip:hover {
            color: var(--color-text);
            border-color: var(--color-text-muted);
        }

        .lk-tour-btn-next {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--color-primary);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .lk-tour-btn-next:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        }

        .lk-tour-btn-next svg {
            width: 14px;
            height: 14px;
        }

        .lk-tour-tooltip-arrow {
            position: absolute;
            width: 12px;
            height: 12px;
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            transform: rotate(45deg);
        }

        .lk-tour-tooltip-top .lk-tour-tooltip-arrow {
            bottom: -7px;
            left: 50%;
            margin-left: -6px;
            border-top: none;
            border-left: none;
        }

        .lk-tour-tooltip-bottom .lk-tour-tooltip-arrow {
            top: -7px;
            left: 50%;
            margin-left: -6px;
            border-bottom: none;
            border-right: none;
        }

        .lk-tour-tooltip-left .lk-tour-tooltip-arrow {
            right: -7px;
            top: 50%;
            margin-top: -6px;
            border-left: none;
            border-bottom: none;
        }

        .lk-tour-tooltip-right .lk-tour-tooltip-arrow {
            left: -7px;
            top: 50%;
            margin-top: -6px;
            border-right: none;
            border-top: none;
        }

        @media (max-width: 600px) {
            .lk-tour-tooltip {
                max-width: calc(100vw - 32px);
                left: 16px !important;
                right: 16px;
            }

            .lk-tour-tooltip-content {
                padding: 16px;
            }
        }
    `, document.head.appendChild(s)
} let L = null; function Ne() { return ue(), L || (L = new de), setTimeout(() => { L.shouldShowTour() && L.start() }, 1500), L } function De() { L || (L = new de, ue()), L.start() } function Oe() { localStorage.removeItem(U) } typeof window < "u" && (window.__LK_TOUR__ = { start: De, reset: Oe }); const w = { getOverview: async (s, e = {}) => { const t = await H(s, e); return ve(t, {}) }, fetch: async s => { if (window.LK?.api) { const t = await LK.api.get(s); if (!t.ok) throw new Error(t.message || "Erro na API"); return t.data } const e = await oe(s); if (e?.success === !1) throw new Error(J({ data: e }, "Erro na API")); return e?.data ?? e }, getMetrics: async s => (await w.getOverview(s)).metrics || {}, getAccountsBalances: async s => { const e = await w.getOverview(s); return Array.isArray(e.accounts_balances) ? e.accounts_balances : [] }, getTransactions: async (s, e) => { const t = await w.getOverview(s, { limit: e }); return Array.isArray(t.recent_transactions) ? t.recent_transactions : [] }, getChartData: async s => { const e = await w.getOverview(s); return Array.isArray(e.chart) ? e.chart : [] }, deleteTransaction: async s => { if (window.LK?.api) { const t = await LK.api.delete(`${y.API_URL}lancamentos/${s}`); if (t.ok) return t.data; throw new Error(t.message || "Erro ao excluir") } const e = [{ request: () => ye(`${y.API_URL}lancamentos/${s}`) }, { request: () => ee(`${y.API_URL}lancamentos/${s}/delete`, {}) }, { request: () => ee(`${y.API_URL}lancamentos/delete`, { id: s }) }]; for (const t of e) try { return await t.request() } catch (a) { if (a?.status !== 404) throw new Error(J(a, "Erro ao excluir")) } throw new Error("Endpoint de exclusão não encontrado.") } }, $ = { ensureSwal: async () => { window.Swal }, toast: (s, e) => { if (window.LK?.toast) return LK.toast[s]?.(e) || LK.toast.info(e); window.Swal?.fire({ toast: !0, position: "top-end", timer: 2500, timerProgressBar: !0, showConfirmButton: !1, icon: s, title: e }) }, loading: (s = "Processando...") => { if (window.LK?.loading) return LK.loading(s); window.Swal?.fire({ title: s, didOpen: () => window.Swal.showLoading(), allowOutsideClick: !1, showConfirmButton: !1 }) }, close: () => { if (window.LK?.hideLoading) return LK.hideLoading(); window.Swal?.close() }, confirm: async (s, e) => window.LK?.confirm ? LK.confirm({ title: s, text: e, confirmText: "Sim, confirmar", danger: !0 }) : (await window.Swal?.fire({ title: s, text: e, icon: "warning", showCancelButton: !0, confirmButtonText: "Sim, confirmar", cancelButtonText: "Cancelar", confirmButtonColor: "var(--color-danger)", cancelButtonColor: "var(--color-text-muted)" }))?.isConfirmed, error: (s, e) => { if (window.LK?.toast) return LK.toast.error(e || s); window.Swal?.fire({ icon: "error", title: s, text: e, confirmButtonColor: "var(--color-primary)" }) } }, b = {
  updateMonthLabel: s => { c.monthLabel && (c.monthLabel.textContent = h.formatMonth(s)) }, toggleAlertsSection: () => { const s = document.getElementById("dashboardAlertsSection"), e = document.getElementById("dashboardAlertsOverview"), t = document.getElementById("dashboardAlertsBudget"); if (!s) return; const a = e && e.innerHTML.trim() !== "", o = t && t.innerHTML.trim() !== ""; s.style.display = a || o ? "block" : "none" }, setSignedState: (s, e, t) => { const a = document.getElementById(s), o = document.getElementById(e); !a || !o || (a.classList.remove("is-positive", "is-negative", "income", "expense"), o.classList.remove("is-positive", "is-negative"), t > 0 ? (a.classList.add("is-positive"), o.classList.add("is-positive")) : t < 0 && (a.classList.add("is-negative"), o.classList.add("is-negative"))) }, renderHeroNarrative: ({ saldo: s, receitas: e, despesas: t, resultado: a }) => { const o = document.getElementById("dashboardHeroStatus"), n = document.getElementById("dashboardHeroMessage"); if (!(!o || !n)) { if (o.className = "dashboard-status-chip", n.className = "dashboard-hero-message", t > e) { o.classList.add("dashboard-status-chip--negative"), n.classList.add("dashboard-hero-message--negative"), o.textContent = "Mes em alerta", n.textContent = "Atencao: você gastou mais do que ganhou neste mes."; return } if (a > 0) { o.classList.add("dashboard-status-chip--positive"), n.classList.add("dashboard-hero-message--positive"), o.textContent = s >= 0 ? "Mes saudavel" : "Recuperando o mes", n.textContent = "você conseguiu guardar dinheiro esse mes."; return } if (a === 0) { o.classList.add("dashboard-status-chip--neutral"), o.textContent = "Mes equilibrado", n.textContent = "O que entrou foi praticamente o que saiu neste mes."; return } o.classList.add("dashboard-status-chip--negative"), n.classList.add("dashboard-hero-message--negative"), o.textContent = "Mes em alerta", n.textContent = "Seu saldo do mes ficou negativo e vale rever os gastos agora." } }, renderOverviewAlerts: ({ receitas: s, despesas: e }) => {
    const t = document.getElementById("dashboardAlertsOverview"); t && (e > s ? (t.innerHTML = `
                <a href="${y.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>você gastou mais do que ganhou</strong>
                        <span>Entraram ${h.money(s)} e sairam ${h.money(e)} neste mes.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `, typeof window.lucide < "u" && window.lucide.createIcons()) : t.innerHTML = "", b.toggleAlertsSection())
  }, renderChartInsight: (s, e) => { const t = document.getElementById("chartInsight"); if (!t) return; if (!Array.isArray(e) || e.length === 0 || e.every(r => Number(r) === 0)) { t.textContent = "Seu historico aparece aqui conforme você usa o Lukrato mais vezes."; return } let a = 0; e.forEach((r, i) => { Number(r) < Number(e[a]) && (a = i) }); const o = s[a], n = Number(e[a] || 0); if (n < 0) { t.textContent = `Seu pior mes foi ${h.formatMonth(o)} (${h.money(n)}).`; return } t.textContent = `Seu pior mes foi ${h.formatMonth(o)} e mesmo assim fechou em ${h.money(n)}.` }, renderKPIs: async s => { try { const [e, t] = await Promise.all([w.getMetrics(s), w.getAccountsBalances(s)]), a = { receitasValue: e.receitas || 0, despesasValue: e.despesas || 0, saldoMesValue: e.resultado || 0 }; Object.entries(a).forEach(([i, d]) => { const u = document.getElementById(i); u && (u.textContent = h.money(d)) }); const o = Number(e.saldoAcumulado ?? e.saldo ?? 0), n = (Array.isArray(t) ? t : []).reduce((i, d) => { const u = typeof d.saldoAtual == "number" ? d.saldoAtual : d.saldoInicial || 0; return i + (isFinite(u) ? Number(u) : 0) }, 0), r = Array.isArray(t) && t.length > 0 ? n : o; c.saldoValue && (c.saldoValue.textContent = h.money(r)), b.setSignedState("saldoValue", "saldoCard", r), b.setSignedState("saldoMesValue", "saldoMesCard", Number(e.resultado || 0)), b.renderHeroNarrative({ saldo: r, receitas: Number(e.receitas || 0), despesas: Number(e.despesas || 0), resultado: Number(e.resultado || 0) }), b.renderOverviewAlerts({ receitas: Number(e.receitas || 0), despesas: Number(e.despesas || 0) }), h.removeLoadingClass() } catch (e) { x("Erro ao renderizar KPIs", e, "Falha ao carregar indicadores"), ["saldoValue", "receitasValue", "despesasValue", "saldoMesValue"].forEach(t => { const a = document.getElementById(t); a && (a.textContent = "R$ 0,00", a.classList.remove("loading")) }) } }, renderTable: async s => {
    try {
      const e = await w.getTransactions(s, y.TRANSACTIONS_LIMIT); c.tableBody && (c.tableBody.innerHTML = ""), c.cardsContainer && (c.cardsContainer.innerHTML = ""); const t = Array.isArray(e) && e.length > 0; c.emptyState && (c.emptyState.style.display = t ? "none" : "block"), c.table && (c.table.style.display = t ? "table" : "none"), c.cardsContainer && (c.cardsContainer.style.display = t ? "flex" : "none"), t && e.forEach(a => {
        const o = String(a.tipo || "").toLowerCase(), n = h.getTipoClass(o), r = String(a.tipo || "").replace(/_/g, " "), i = a.categoria_nome ?? (typeof a.categoria == "string" ? a.categoria : a.categoria?.nome) ?? null, d = i ? T(i) : '<span class="categoria-empty">Sem categoria</span>', u = T(h.getContaLabel(a)), p = T(a.descricao || a.observacao || "--"), g = T(r), S = Number(a.valor) || 0, A = h.dateBR(a.data), C = document.createElement("tr"); if (C.setAttribute("data-id", a.id), C.innerHTML = `
              <td data-label="Data">${A}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${n}">${g}</span>
              </td>
              <td data-label="Categoria">${d}</td>
              <td data-label="Conta">${u}</td>
              <td data-label="Descrição">${p}</td>
              <td data-label="Valor" class="valor-cell ${n}">${h.money(S)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `, c.tableBody && c.tableBody.appendChild(C), c.cardsContainer) {
          const E = document.createElement("div"); E.className = "transaction-card", E.setAttribute("data-id", a.id), E.innerHTML = `
                <div class="transaction-card-header">
                  <span class="transaction-date">${A}</span>
                  <span class="transaction-value ${n}">${h.money(S)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${n}">${g}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${d}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${u}</span>
                  </div>
                  ${p !== "--" ? `
                  <div class="transaction-info-row">
                    <span class="transaction-label">Descrição</span>
                    <span class="transaction-description">${p}</span>
                  </div>
                  `: ""}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `, c.cardsContainer.appendChild(E)
        }
      })
    } catch (e) { x("Erro ao renderizar transações", e, "Falha ao carregar transações"), c.emptyState && (c.emptyState.style.display = "block"), c.table && (c.table.style.display = "none"), c.cardsContainer && (c.cardsContainer.style.display = "none") }
  }, renderChart: async s => { if (!(!c.chartContainer || typeof ApexCharts > "u")) { c.chartLoading && (c.chartLoading.style.display = "flex"); try { const e = h.getPreviousMonths(s, y.CHART_MONTHS), t = e.map(g => h.formatMonthShort(g)), a = await w.getChartData(s), o = new Map(a.map(g => [g.month, Number(g.resultado || 0)])), n = e.map(g => o.get(g) || 0); b.renderChartInsight(e, n); const { xTickColor: r, yTickColor: i, gridColor: d, isLightTheme: u } = be(), p = u ? "light" : "dark"; v.chartInstance && (v.chartInstance.destroy(), v.chartInstance = null), v.chartInstance = new ApexCharts(c.chartContainer, { chart: { type: "area", height: 300, width: "100%", toolbar: { show: !1 }, background: "transparent", fontFamily: "Inter, Arial, sans-serif" }, series: [{ name: "Resultado do Mês", data: n }], xaxis: { categories: t, labels: { style: { colors: r } }, axisBorder: { show: !1 }, axisTicks: { show: !1 } }, yaxis: { labels: { style: { colors: i }, formatter: g => h.money(g) } }, colors: ["#E67E22"], stroke: { curve: "smooth", width: 3 }, fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .05, stops: [0, 100] } }, markers: { size: 5, colors: ["#E67E22"], strokeColors: "#fff", strokeWidth: 2, hover: { size: 7 } }, grid: { borderColor: d, strokeDashArray: 4, xaxis: { lines: { show: !1 } } }, tooltip: { theme: p, y: { formatter: g => `Resultado: ${h.money(g)}` } }, legend: { show: !1 }, dataLabels: { enabled: !1 }, theme: { mode: p } }), v.chartInstance.render() } catch (e) { x("Erro ao renderizar gráfico", e, "Falha ao carregar gráfico") } finally { c.chartLoading && setTimeout(() => { c.chartLoading.style.display = "none" }, 300) } } }
}, ae = { delete: async (s, e) => { try { if (await $.ensureSwal(), !await $.confirm("Excluir lançamento?", "Esta ação não pode ser desfeita.")) return; $.loading("Excluindo..."), await w.deleteTransaction(Number(s)), $.close(), $.toast("success", "Lançamento excluído com sucesso!"), e && (e.style.opacity = "0", e.style.transform = "translateX(-20px)", setTimeout(() => { e.remove(), c.tableBody.children.length === 0 && (c.emptyState && (c.emptyState.style.display = "block"), c.table && (c.table.style.display = "none")) }, 300)), document.dispatchEvent(new CustomEvent("lukrato:data-changed", { detail: { resource: "transactions", action: "delete", id: Number(s) } })) } catch (t) { console.error("Erro ao excluir lançamento:", t), await $.ensureSwal(), $.error("Erro", J(t, "Falha ao excluir lançamento")) } } }, I = {
  isProUser: null, checkProStatus: async () => { try { const s = await w.getOverview(h.getCurrentMonth()); I.isProUser = s?.plan?.is_pro === !0 } catch { I.isProUser = !1 } return I.isProUser }, render: async s => { const e = document.getElementById("provisaoSection"); if (!e) return; await I.checkProStatus(); const t = document.getElementById("provisaoProOverlay"), a = I.isProUser; e.classList.remove("is-locked"), t && (t.style.display = "none"); try { const o = await w.getOverview(s); I.renderData(o.provisao || null, a) } catch (o) { x("Erro ao carregar provisão", o, "Falha ao carregar previsão") } }, renderData: (s, e = !0) => {
    if (!s) return; const t = s.provisao || {}, a = h.money, o = document.getElementById("provisaoTitle"), n = document.getElementById("provisaoHeadline"); o && (o.textContent = `Se continuar assim, você termina o mês com ${a(t.saldo_projetado || 0)}`), n && (n.textContent = (t.saldo_projetado || 0) >= 0 ? "A previsao abaixo considera seu saldo atual, o que ainda vai entrar e o que ainda vai sair." : "A previsao indica aperto no fim do mes se o ritmo atual continuar."); const r = document.getElementById("provisaoProximosTitle"), i = document.getElementById("provisaoVerTodos"); r && (r.innerHTML = e ? '<i data-lucide="clock"></i> Próximos Vencimentos' : '<i data-lucide="credit-card"></i> Próximas Faturas'), i && (i.href = e ? `${window.BASE_URL || "/"}lancamentos` : `${window.BASE_URL || "/"}faturas`); const d = document.getElementById("provisaoPagar"), u = document.getElementById("provisaoReceber"), p = document.getElementById("provisaoProjetado"), g = document.getElementById("provisaoPagarCount"), S = document.getElementById("provisaoReceberCount"), A = document.getElementById("provisaoProjetadoLabel"), C = u?.closest(".provisao-card"); if (d && (d.textContent = a(t.a_pagar || 0)), e ? (u && (u.textContent = a(t.a_receber || 0)), C && (C.style.opacity = "1")) : (u && (u.textContent = "R$ --"), C && (C.style.opacity = "0.5")), p && (p.textContent = a(t.saldo_projetado || 0), p.style.color = (t.saldo_projetado || 0) >= 0 ? "" : "var(--color-danger)"), g) { const m = t.count_pagar || 0, l = t.count_faturas || 0; if (e) { let f = `${m} pendente${m !== 1 ? "s" : ""}`; l > 0 && (f += ` • ${l} fatura${l !== 1 ? "s" : ""}`), g.textContent = f } else g.textContent = `${l} fatura${l !== 1 ? "s" : ""}` } e ? S && (S.textContent = `${t.count_receber || 0} pendente${(t.count_receber || 0) !== 1 ? "s" : ""}`) : S && (S.textContent = "Pro"), A && (A.textContent = `saldo atual: ${a(t.saldo_atual || 0)}`); const E = s.vencidos || {}, q = document.getElementById("provisaoAlertDespesas"); if (q) { const m = E.despesas || {}; if (e && (m.count || 0) > 0) { q.style.display = "flex"; const l = document.getElementById("provisaoAlertDespesasCount"), f = document.getElementById("provisaoAlertDespesasTotal"); l && (l.textContent = m.count), f && (f.textContent = a(m.total || 0)) } else q.style.display = "none" } const z = document.getElementById("provisaoAlertReceitas"); if (z) { const m = E.receitas || {}; if (e && (m.count || 0) > 0) { z.style.display = "flex"; const l = document.getElementById("provisaoAlertReceitasCount"), f = document.getElementById("provisaoAlertReceitasTotal"); l && (l.textContent = m.count), f && (f.textContent = a(m.total || 0)) } else z.style.display = "none" } const j = document.getElementById("provisaoAlertFaturas"); if (j) { const m = E.count_faturas || 0; if (m > 0) { j.style.display = "flex"; const l = document.getElementById("provisaoAlertFaturasCount"), f = document.getElementById("provisaoAlertFaturasTotal"); l && (l.textContent = m), f && (f.textContent = a(E.total_faturas || 0)) } else j.style.display = "none" } const M = document.getElementById("provisaoProximosList"), F = document.getElementById("provisaoEmpty"); let V = s.proximos || []; if (e || (V = V.filter(m => m.is_fatura === !0)), M) if (V.length === 0) { if (M.innerHTML = "", F) { const m = F.querySelector("span"); m && (m.textContent = e ? "Nenhum vencimento pendente" : "Nenhuma fatura pendente"), M.appendChild(F), F.style.display = "flex" } } else {
      M.innerHTML = ""; const m = new Date().toISOString().slice(0, 10); V.forEach(l => {
        const f = (l.tipo || "").toLowerCase(), P = l.is_fatura === !0, Q = (l.data_pagamento || "").split(/[T\s]/)[0], he = Q === m, me = I.formatDateShort(Q); let _ = ""; he && (_ += '<span class="provisao-item-badge vence-hoje">Hoje</span>'), P ? (_ += '<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>', l.cartao_ultimos_digitos && (_ += `<span>****${l.cartao_ultimos_digitos}</span>`)) : (l.eh_parcelado && l.numero_parcelas > 1 && (_ += `<span class="provisao-item-badge parcela">${l.parcela_atual}/${l.numero_parcelas}</span>`), l.recorrente && (_ += '<span class="provisao-item-badge recorrente">Recorrente</span>'), l.categoria && (_ += `<span>${T(l.categoria)}</span>`)); const Z = P ? "fatura" : f, D = document.createElement("div"); D.className = "provisao-item" + (P ? " is-fatura" : ""), D.innerHTML = `
                            <div class="provisao-item-dot ${Z}"></div>
                            <div class="provisao-item-info">
                                <div class="provisao-item-titulo">${T(l.titulo || "Sem título")}</div>
                                <div class="provisao-item-meta">${_}</div>
                            </div>
                            <span class="provisao-item-valor ${Z}">${a(l.valor || 0)}</span>
                            <span class="provisao-item-data">${me}</span>
                        `, P && l.cartao_id && (D.style.cursor = "pointer", D.addEventListener("click", () => { const pe = (l.data_pagamento || "").split(/[T\s]/)[0], [ge, fe] = pe.split("-"); window.location.href = `${window.BASE_URL || "/"}faturas?cartao_id=${l.cartao_id}&mes=${parseInt(fe)}&ano=${ge}` })), M.appendChild(D)
      })
    } const Y = document.getElementById("provisaoParcelas"), N = s.parcelas || {}; if (Y) if (e && (N.ativas || 0) > 0) { Y.style.display = "flex"; const m = document.getElementById("provisaoParcelasText"), l = document.getElementById("provisaoParcelasValor"); m && (m.textContent = `${N.ativas} parcelamento${N.ativas !== 1 ? "s" : ""} ativo${N.ativas !== 1 ? "s" : ""}`), l && (l.textContent = `${a(N.total_mensal || 0)}/mês`) } else Y.style.display = "none"
  }, formatDateShort: s => { if (!s) return "-"; try { const e = s.match(/^(\d{4})-(\d{2})-(\d{2})$/); return e ? `${e[3]}/${e[2]}` : "-" } catch { return "-" } }
}, B = { refresh: async ({ force: s = !1 } = {}) => { if (v.isLoading) return; v.isLoading = !0; const e = h.getCurrentMonth(); v.currentMonth = e, s && k(e); try { b.updateMonthLabel(e), await Promise.allSettled([b.renderKPIs(e), b.renderTable(e), b.renderChart(e), I.render(e)]) } catch (t) { x("Erro ao atualizar dashboard", t, "Falha ao atualizar dashboard") } finally { v.isLoading = !1 } }, init: async () => { await B.refresh({ force: !1 }) } }, Re = { init: () => { v.eventListenersInitialized || (v.eventListenersInitialized = !0, c.tableBody?.addEventListener("click", async s => { const e = s.target.closest(".btn-del"); if (!e) return; const t = s.target.closest("tr"), a = e.getAttribute("data-id"); a && (e.disabled = !0, await ae.delete(a, t), e.disabled = !1) }), c.cardsContainer?.addEventListener("click", async s => { const e = s.target.closest(".btn-del"); if (!e) return; const t = s.target.closest(".transaction-card"), a = e.getAttribute("data-id"); a && (e.disabled = !0, await ae.delete(a, t), e.disabled = !1) }), document.addEventListener("lukrato:data-changed", () => { k(v.currentMonth || h.getCurrentMonth()), B.refresh({ force: !1 }) }), document.addEventListener("lukrato:month-changed", () => { B.refresh({ force: !1 }) }), document.addEventListener("lukrato:theme-changed", () => { b.renderChart(v.currentMonth || h.getCurrentMonth()) }), Ne()) } }, se = window.__LK_CONFIG?.baseUrl || window.BASE_URL || "/", He = `lk_user_${window.__LK_CONFIG?.userId ?? "anon"}_`; function R(s) { return He + s } let G = null; function Fe() { if (window.__LK_ONBOARDING_CHECKLIST_INIT__) return; window.__LK_ONBOARDING_CHECKLIST_INIT__ = !0; const s = !!window.__lkFirstVisit, e = R("lk_checklist_skipped"), t = R("lk_checklist_state"), a = document.getElementById("onboardingChecklist"); if (!a || localStorage.getItem(e) === "1") return; G = localStorage.getItem(t) ? JSON.parse(localStorage.getItem(t)) : {}; const o = document.getElementById("checklistDismiss"); o && o.addEventListener("click", n => { n.preventDefault(), n.stopPropagation(), localStorage.setItem(e, "1"), X(a) }), W(a, s), setInterval(() => { W(a, s, { force: !0 }) }, 12e4), document.addEventListener("lukrato:data-changed", () => { Ce(), setTimeout(() => W(a, s, { force: !0 }), 500) }) } function W(s, e, { force: t = !1 } = {}) { if (localStorage.getItem(R("lk_checklist_skipped")) === "1") { X(s); return } ke({ force: t }).then(a => { if (!a.success) return; const o = a.data; if (o.all_complete && (localStorage.setItem(R("lukrato_onboarding_completed"), "true"), !e)) { X(s); return } Ve(o), s.style.display = "block", Ke(o), e && Ge() }).catch(() => { }) } function Ve(s, e) {
  const t = document.getElementById("checklistBadge"), a = document.getElementById("checklistProgressFill"), o = document.getElementById("checklistItems"), n = document.getElementById("checklistPrimaryLink"); if (t && (t.textContent = `${s.done_count}/${s.total}`), a) { const u = s.total > 0 ? s.done_count / s.total * 100 : 0; a.style.width = `${u}%` } const r = s.items.filter(u => !u.done).sort((u, p) => u.priority - p.priority), i = r.slice(0, 3), d = i[0] || r[0] || null; if (n && d && (n.href = se + d.href), !!o) {
    if (i.length === 0) {
      o.innerHTML = `
      <div class="lk-onboarding-widget-empty">
        <span class="lk-onboarding-widget-item-label">Seu setup esta completo.</span>
        <span class="lk-onboarding-widget-item-desc">Agora e so manter sua rotina financeira.</span>
      </div>
    `; return
    } o.innerHTML = i.map(u => {
      const p = Pe(u); return `
      <a href="${se}${u.href}" class="lk-onboarding-widget-item" data-item-key="${u.key}">
        <span class="lk-onboarding-widget-item-label">${p.label}</span>
        <span class="lk-onboarding-widget-item-desc">${p.description}</span>
      </a>
    `}).join("")
  }
} function Pe(s) { return { primeira_transacao: { label: "Registre seu primeiro gasto", description: "Comece a acompanhar o que entrou ou saiu." }, segunda_transacao: { label: "Registre mais uma movimentacao", description: "Dois registros ja deixam o mes mais claro." }, meta: { label: "Defina uma meta", description: "Escolha um objetivo para o dinheiro que sobra." }, conta_conectada: { label: "Adicione outra conta ou cartao", description: "Tenha uma visao mais completa do seu dinheiro." }, orcamento: { label: "Defina um limite de gastos", description: "Evite passar do ponto nas categorias principais." } }[s.key] || { label: s.label, description: s.description } } function Ke(s) { const e = {}; s.items.forEach(t => { e[t.key] = t.done }), G && s.items.forEach(t => { const a = !G[t.key], o = t.done; a && o && Ue(t) }), localStorage.setItem(R("lk_checklist_state"), JSON.stringify(e)), G = e } function Ue(s) { window.LK?.toast && window.LK.toast.success(`Item concluido: ${s.label}`), typeof confetti == "function" && confetti({ particleCount: 40, spread: 65, origin: { x: .5, y: .3 } }), document.dispatchEvent(new CustomEvent("lukrato:checklist-item-completed", { detail: { key: s.key, label: s.label, points: s.points, icon: s.icon } })) } function Ge() { typeof confetti == "function" && confetti({ particleCount: 60, spread: 80, origin: { x: .5, y: .25 } }) } function X(s) { s.style.opacity = "0", s.style.transform = "translateY(-12px)", s.style.transition = "all 0.25s ease", setTimeout(() => { s.style.display = "none" }, 250) } window.__LK_DASHBOARD_LOADER__ || (window.__LK_DASHBOARD_LOADER__ = !0, window.refreshDashboard = B.refresh, window.LK = window.LK || {}, window.LK.refreshDashboard = B.refresh, (() => { const e = () => { Re.init(), B.init() }; Fe(), document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", e) : e() })());
