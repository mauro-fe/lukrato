import { apiGet, getBaseUrl } from '../shared/api.js';
import { resolveFinanceSummaryEndpoint } from '../api/endpoints/finance.js';
import { Utils } from './state.js';

/**
 * Finance Overview Component
 * Usa o resumo financeiro para renderizar metas, limites e alertas.
 */

class FinanceOverview {
  constructor(containerId = 'financeOverviewContainer') {
    this.container = document.getElementById(containerId);
    this.baseURL = getBaseUrl();
  }

  render() {
    if (!this.container) return;

    this.container.innerHTML = `
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
    `;
  }

  async load() {
    try {
      const { mes, ano } = this.getSelectedPeriod();
      const result = await apiGet(resolveFinanceSummaryEndpoint(), { mes, ano });

      if (result.success && result.data) {
        this.renderAlerts(result.data);
        this.renderMetas(result.data.metas);
        this.renderOrcamento(result.data.orcamento);
      } else {
        this.renderAlerts();
        this.renderMetasEmpty();
        this.renderOrcamentoEmpty();
      }
    } catch (error) {
      console.error('Error loading finance overview:', error);
      this.renderAlerts();
      this.renderMetasEmpty();
      this.renderOrcamentoEmpty();
    }

    if (!this._listening) {
      this._listening = true;
      document.addEventListener('lukrato:data-changed', () => this.load());
      document.addEventListener('lukrato:month-changed', () => this.load());
    }
  }

  renderAlerts(data = null) {
    const container = document.getElementById('dashboardAlertsBudget');
    if (!container) return;

    const budgets = Array.isArray(data?.orcamento?.orcamentos) ? data.orcamento.orcamentos.slice() : [];
    const estourados = budgets
      .filter((item) => item.status === 'estourado')
      .sort((a, b) => Number(b.excedido || 0) - Number(a.excedido || 0));
    const alertas = budgets
      .filter((item) => item.status === 'alerta')
      .sort((a, b) => Number(b.percentual || 0) - Number(a.percentual || 0));

    const alerts = [];

    estourados.slice(0, 2).forEach((item) => {
      alerts.push({
        variant: 'danger',
        title: `Você já passou do limite em ${item.categoria_nome}`,
        message: `Excedido em ${this.money(item.excedido || 0)}.`,
      });
    });

    if (alerts.length < 2) {
      alertas.slice(0, 2 - alerts.length).forEach((item) => {
        alerts.push({
          variant: 'warning',
          title: `${item.categoria_nome} já consumiu ${Math.round(item.percentual || 0)}% do limite`,
          message: `Restam ${this.money(item.disponivel || 0)} nessa categoria.`,
        });
      });
    }

    if (alerts.length === 0) {
      container.innerHTML = '';
      this.toggleAlertsSection();
      return;
    }

    container.innerHTML = alerts.map((alert) => `
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${alert.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${alert.variant === 'danger' ? 'triangle-alert' : 'circle-alert'}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${alert.title}</strong>
          <span>${alert.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join('');

    this.toggleAlertsSection();
    this.refreshIcons();
  }

  renderOrcamento(data) {
    const el = document.getElementById('foOrcamento');
    if (!el) return;

    if (!data || data.total_categorias === 0) {
      this.renderOrcamentoEmpty();
      return;
    }

    const pct = Math.round(data.percentual_geral || 0);
    const barColor = this.getBarColor(pct);
    const top3 = (data.orcamentos || [])
      .slice()
      .sort((a, b) => Number(b.percentual || 0) - Number(a.percentual || 0))
      .slice(0, 3);

    const itemsHtml = top3.map((orc) => {
      const progress = Math.min(Number(orc.percentual || 0), 100);
      const itemColor = this.getBarColor(orc.percentual);
      return `
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${orc.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${itemColor};">${Math.round(orc.percentual || 0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${progress}%; background:${itemColor};"></div>
          </div>
        </div>
      `;
    }).join('');

    let badgeText = 'No controle';
    if ((data.estourados || 0) > 0) {
      badgeText = `${data.estourados} acima do limite`;
    } else if ((data.em_alerta || 0) > 0) {
      badgeText = `${data.em_alerta} em atencao`;
    }

    el.innerHTML = `
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${barColor}; background:${barColor}18;">${badgeText}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(data.total_gasto || 0)} usados de ${this.money(data.total_limite || 0)}</span>
        <span class="fo-summary-status">Saude: ${data.saude_financeira?.label || 'Boa'}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(pct, 100)}%; background:${barColor};"></div>
      </div>

      ${itemsHtml ? `<div class="fo-orc-list">${itemsHtml}</div>` : ''}

      <a href="${this.baseURL}financas#orcamentos" class="fo-link">Ver limites <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
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
          Limites do mes
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu limites para acompanhar categorias.</p>
        <a href="${this.baseURL}financas#orcamentos" class="fo-cta">Definir limite</a>
      </div>
    `;

    this.refreshIcons();
  }

  renderMetas(data) {
    const el = document.getElementById('foMetas');
    if (!el) return;

    if (!data || data.total_metas === 0) {
      this.renderMetasEmpty();
      return;
    }

    const proxima = data.proxima_concluir;
    const pctGeral = Math.round(data.progresso_geral || 0);

    if (!proxima) {
      this.updateGoalsHeadline('você tem metas ativas, mas nenhuma esta proxima de concluir.');
      el.innerHTML = `
        <div class="fo-card-header">
          <a href="${this.baseURL}financas#metas" class="fo-card-title">
            <i data-lucide="target" style="width:16px;height:16px;"></i>
            Metas
          </a>
          <span class="fo-badge">${data.total_metas} ativa${data.total_metas !== 1 ? 's' : ''}</span>
        </div>
        <div class="fo-metas-summary">
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value">${pctGeral}%</span>
            <span class="fo-metas-stat-label">progresso geral</span>
          </div>
        </div>
        <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
      `;
      this.refreshIcons();
      return;
    }

    const cor = proxima.cor || 'var(--color-primary)';
    const iconName = this.normalizeIconName(proxima.icone);
    const pct = Math.round(proxima.progresso || 0);
    const faltam = Math.max(Number(proxima.valor_alvo || 0) - Number(proxima.valor_atual || 0), 0);

    this.updateGoalsHeadline(`Faltam ${this.money(faltam)} para alcancar sua meta.`);

    el.innerHTML = `
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${data.total_metas} ativa${data.total_metas !== 1 ? 's' : ''}</span>
      </div>

      <div class="fo-meta-destaque">
        <div class="fo-meta-icon" style="color:${cor}; background:${cor}18;">
          <i data-lucide="${iconName}" style="width:16px;height:16px;"></i>
        </div>
        <div class="fo-meta-info">
          <span class="fo-meta-titulo">${proxima.titulo}</span>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${Math.min(pct, 100)}%; background:${cor};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(proxima.valor_atual || 0)} de ${this.money(proxima.valor_alvo || 0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${cor};">${pct}%</span>
      </div>

      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${this.money(faltam)}</span>
          <span class="fo-metas-stat-label">faltam para concluir</span>
        </div>
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${pctGeral}%</span>
          <span class="fo-metas-stat-label">progresso geral</span>
        </div>
      </div>

      <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `;

    this.refreshIcons();
  }

  renderMetasEmpty() {
    const el = document.getElementById('foMetas');
    if (!el) return;

    this.updateGoalsHeadline('Defina uma meta para transformar sua sobra em um objetivo claro.');

    el.innerHTML = `
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
    `;

    this.refreshIcons();
  }

  updateGoalsHeadline(text) {
    const headline = document.getElementById('foGoalsHeadline');
    if (headline) {
      headline.textContent = text;
    }
  }

  toggleAlertsSection() {
    const section = document.getElementById('dashboardAlertsSection');
    const overview = document.getElementById('dashboardAlertsOverview');
    const budget = document.getElementById('dashboardAlertsBudget');

    if (!section) return;

    const hasOverview = overview && overview.innerHTML.trim() !== '';
    const hasBudget = budget && budget.innerHTML.trim() !== '';
    section.style.display = hasOverview || hasBudget ? 'block' : 'none';
  }

  getSelectedPeriod() {
    const month = Utils.getCurrentMonth ? Utils.getCurrentMonth() : new Date().toISOString().slice(0, 7);
    const match = String(month).match(/^(\d{4})-(\d{2})$/);
    if (match) {
      return {
        ano: Number(match[1]),
        mes: Number(match[2]),
      };
    }

    const now = new Date();
    return {
      mes: now.getMonth() + 1,
      ano: now.getFullYear(),
    };
  }

  getBarColor(pct) {
    if (pct >= 100) return '#ef4444';
    if (pct >= 80) return '#f59e0b';
    return '#10b981';
  }

  normalizeIconName(icon) {
    const value = String(icon || '').trim();
    if (!value) return 'target';

    const faToLucide = {
      'fa-bullseye': 'target',
      'fa-target': 'target',
      'fa-wallet': 'wallet',
      'fa-university': 'landmark',
      'fa-plane': 'plane',
      'fa-car': 'car',
      'fa-home': 'house',
      'fa-heart': 'heart',
      'fa-briefcase': 'briefcase-business',
      'fa-piggy-bank': 'piggy-bank',
      'fa-shield': 'shield',
      'fa-graduation-cap': 'graduation-cap',
      'fa-store': 'store',
      'fa-baby': 'baby',
      'fa-hand-holding-usd': 'hand-coins',
    };

    return faToLucide[value] || value.replace(/^fa-/, '') || 'target';
  }

  money(value) {
    return Number(value || 0).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    });
  }

  refreshIcons() {
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }
}

window.FinanceOverview = FinanceOverview;
