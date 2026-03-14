/**
 * Finance Overview Component
 * Cards compactos de Orçamento e Metas no dashboard
 * USA: GET /api/financas/resumo (single call)
 */

class FinanceOverview {
  constructor(containerId = 'financeOverviewContainer') {
    this.container = document.getElementById(containerId);
    this.baseURL = window.BASE_URL || '/';
  }

  render() {
    if (!this.container) return;

    this.container.innerHTML = `
      <div class="fo-grid">
        <div class="fo-card" id="foOrcamento">
          <div class="fo-skeleton"></div>
        </div>
        <div class="fo-card" id="foMetas">
          <div class="fo-skeleton"></div>
        </div>
      </div>
    `;
  }

  async load() {
    try {
      const mes = new Date().getMonth() + 1;
      const ano = new Date().getFullYear();
      const response = await fetch(
        `${this.baseURL}api/financas/resumo?mes=${mes}&ano=${ano}`,
        { credentials: 'include', headers: { 'Accept': 'application/json' } }
      );

      if (!response.ok) throw new Error('Failed to fetch');
      const result = await response.json();

      if (result.success && result.data) {
        this.renderOrcamento(result.data.orcamento);
        this.renderMetas(result.data.metas);
      } else {
        this.renderOrcamentoEmpty();
        this.renderMetasEmpty();
      }
    } catch (error) {
      console.error('Error loading finance overview:', error);
      this.renderOrcamentoEmpty();
      this.renderMetasEmpty();
    }

    if (!this._listening) {
      this._listening = true;
      document.addEventListener('lukrato:data-changed', () => this.load());
    }
  }

  // ─── ORÇAMENTO ──────────────────────────────────────────────
  renderOrcamento(data) {
    const el = document.getElementById('foOrcamento');
    if (!el) return;

    if (!data || data.total_categorias === 0) {
      this.renderOrcamentoEmpty();
      return;
    }

    const pct = Math.round(data.percentual_geral || 0);
    const barColor = this.getBarColor(pct);
    const top3 = (data.orcamentos || []).slice(0, 3);

    const itemsHtml = top3.map(orc => {
      const oPct = Math.min(orc.percentual, 100);
      const oColor = this.getBarColor(orc.percentual);
      const icon = orc.categoria?.icone || 'tag';
      return `
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">
              <i data-lucide="${icon}" style="width:12px;height:12px;"></i>
              ${orc.categoria_nome}
            </span>
            <span class="fo-orc-item-pct" style="color: ${oColor};">${Math.round(orc.percentual)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width: ${oPct}%; background: ${oColor};"></div>
          </div>
        </div>
      `;
    }).join('');

    el.innerHTML = `
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Orçamento
        </a>
        <span class="fo-badge" style="color: ${barColor}; background: ${barColor}18;">${pct}% usado</span>
      </div>
      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width: ${Math.min(pct, 100)}%; background: ${barColor};"></div>
      </div>
      <div class="fo-orc-summary">
        <span>R$ ${this.formatMoney(data.total_gasto)} de R$ ${this.formatMoney(data.total_limite)}</span>
        ${data.estourados > 0 ? `<span class="fo-alert">${data.estourados} estourado${data.estourados > 1 ? 's' : ''}</span>` : ''}
      </div>
      ${itemsHtml ? `<div class="fo-orc-list">${itemsHtml}</div>` : ''}
      <a href="${this.baseURL}financas#orcamentos" class="fo-link">Ver detalhes <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `;

    this.refreshIcons();
  }

  renderOrcamentoEmpty() {
    const el = document.getElementById('foOrcamento');
    if (!el) return;

    el.innerHTML = `
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Orçamento
        </span>
      </div>
      <div class="fo-empty">
        <p>Nenhum orçamento definido para este mês</p>
        <a href="${this.baseURL}financas#orcamentos" class="fo-meta-destaque">Definir orçamento</a>
      </div>
    `;
    this.refreshIcons();
  }

  // ─── METAS ──────────────────────────────────────────────────
  renderMetas(data) {
    const el = document.getElementById('foMetas');
    if (!el) return;

    if (!data || data.total_metas === 0) {
      this.renderMetasEmpty();
      return;
    }

    const proxima = data.proxima_concluir;
    let proximaHtml = '';

    if (proxima) {
      const icon = proxima.icone || 'target';
      const cor = proxima.cor || 'var(--color-primary)';
      const pct = Math.round(proxima.progresso || 0);

      proximaHtml = `
        <div class="fo-meta-destaque">
          <div class="fo-meta-icon" style="color: ${cor}; background: ${cor}18;">
            <i data-lucide="${icon}" style="width:16px;height:16px;"></i>
          </div>
          <div class="fo-meta-info">
            <span class="fo-meta-titulo">${proxima.titulo}</span>
            <div class="fo-bar-track">
              <div class="fo-bar-fill" style="width: ${pct}%; background: ${cor};"></div>
            </div>
            <span class="fo-meta-detail">
              R$ ${this.formatMoney(proxima.valor_atual)} de R$ ${this.formatMoney(proxima.valor_alvo)}
              ${proxima.dias_restantes !== null ? ` · ${proxima.dias_restantes > 0 ? proxima.dias_restantes + 'd restantes' : 'Atrasada'}` : ''}
            </span>
          </div>
          <span class="fo-meta-pct" style="color: ${cor};">${pct}%</span>
        </div>
      `;
    }

    const pctGeral = Math.round(data.progresso_geral || 0);

    el.innerHTML = `
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${data.total_metas} ativa${data.total_metas !== 1 ? 's' : ''}</span>
      </div>
      ${proximaHtml}
      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${pctGeral}%</span>
          <span class="fo-metas-stat-label">progresso geral</span>
        </div>
        ${data.atrasadas > 0 ? `
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value fo-text-danger">${data.atrasadas}</span>
            <span class="fo-metas-stat-label">atrasada${data.atrasadas > 1 ? 's' : ''}</span>
          </div>
        ` : ''}
      </div>
      <a href="${this.baseURL}financas#metas" class="fo-link">Ver todas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `;

    this.refreshIcons();
  }

  renderMetasEmpty() {
    const el = document.getElementById('foMetas');
    if (!el) return;

    el.innerHTML = `
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </span>
      </div>
      <div class="fo-empty">
        <p>Nenhuma meta ativa no momento</p>
        <a href="${this.baseURL}financas#metas" class="fo-cta">Criar meta</a>
      </div>
    `;
    this.refreshIcons();
  }

  // ─── HELPERS ────────────────────────────────────────────────
  getBarColor(pct) {
    if (pct >= 100) return '#ef4444';
    if (pct >= 80) return '#f59e0b';
    return '#10b981';
  }

  formatMoney(val) {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  refreshIcons() {
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }
}

window.FinanceOverview = FinanceOverview;
