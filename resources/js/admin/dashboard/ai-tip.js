import { logClientError } from '../shared/api.js';
import { getDashboardOverview, invalidateDashboardOverview } from './dashboard-data.js';

/**
 * AI Tip Card — Dica do Lukrato
 * Combines greeting insight + health score insights into actionable tips.
 */

class AiTipCard {
  constructor(containerId = 'aiTipContainer') {
    this.container = document.getElementById(containerId);
    this.baseURL = window.BASE_URL || '/';
  }

  init() {
    if (!this.container) return;
    if (this._initialized) return;
    this._initialized = true;

    this.render();
    this.load();

    document.addEventListener('lukrato:data-changed', () => {
      invalidateDashboardOverview();
      this.load({ force: true });
    });
    document.addEventListener('lukrato:month-changed', () => {
      invalidateDashboardOverview();
      this.load({ force: true });
    });
  }

  render() {
    this.container.innerHTML = `
      <div class="ai-tip-card" data-aos="fade-up" data-aos-duration="400" data-aos-delay="100">
        <div class="ai-tip-header">
          <i data-lucide="sparkles" class="ai-tip-header-icon"></i>
          <h2 class="ai-tip-title">Dica do Lukrato</h2>
        </div>
        <div class="ai-tip-list" id="aiTipList">
          <div class="ai-tip-skeleton"></div>
          <div class="ai-tip-skeleton"></div>
        </div>
      </div>
    `;
    this.updateIcons();
  }

  async load({ force = false } = {}) {
    try {
      const response = await getDashboardOverview(undefined, { force });
      const data = response?.data ?? response;

      const tips = this.buildTips(data);
      this.renderTips(tips);
    } catch (error) {
      logClientError('Error loading AI tips', error, 'Falha ao carregar dicas');
      this.renderEmpty();
    }
  }

  buildTips(data) {
    const tips = [];
    const hs = data?.health_score || {};
    const metrics = data?.metrics || {};
    const provisao = data?.provisao?.provisao || {};
    const vencidos = data?.provisao?.vencidos || {};
    const parcelas = data?.provisao?.parcelas || {};
    const chart = data?.chart || [];

    // 1. Health score insights (critical/high priority — show first)
    const insights = Array.isArray(data?.health_score_insights)
      ? data.health_score_insights
      : (data?.health_score_insights?.insights || []);

    const priorityOrder = { critical: 0, high: 1, medium: 2, low: 3 };

    insights
      .sort((a, b) => (priorityOrder[a.priority] ?? 9) - (priorityOrder[b.priority] ?? 9))
      .forEach(insight => {
        const norm = this.normalizeInsight(insight);
        tips.push({
          type: norm.type,
          priority: norm.priority,
          icon: norm.icon,
          title: norm.title,
          desc: norm.message,
          url: norm.url,
        });
      });

    // 2. Overdue items (vencidos)
    if (vencidos.count > 0) {
      const total = (vencidos.total || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      tips.push({
        type: 'overdue',
        priority: 'critical',
        icon: 'clock',
        title: `${vencidos.count} item(ns) vencido(s)`,
        desc: `Total de ${total} em atraso`,
        url: 'lancamentos?status=vencido',
      });
    }

    // 3. Greeting insight (month-over-month comparison)
    if (data?.greeting_insight) {
      const gi = data.greeting_insight;
      tips.push({
        type: 'greeting',
        priority: 'positive',
        icon: gi.icon || 'trending-up',
        title: gi.message || '',
        desc: '',
        url: null,
      });
    }

    // 4. Savings rate tip
    const savingsRate = hs.savingsRate ?? 0;
    const receitas = metrics.receitas ?? 0;
    if (receitas > 0) {
      if (savingsRate >= 20) {
        tips.push({
          type: 'savings',
          priority: 'positive',
          icon: 'piggy-bank',
          title: `Taxa de economia: ${savingsRate}%`,
          desc: 'Otimo! Voce esta guardando bem.',
          url: null,
        });
      } else if (savingsRate >= 0) {
        tips.push({
          type: 'savings',
          priority: 'medium',
          icon: 'piggy-bank',
          title: `Taxa de economia: ${savingsRate}%`,
          desc: 'Tente guardar pelo menos 20% da renda.',
          url: 'relatorios',
        });
      }
    }

    // 5. Budget status
    const orcamentos = hs.orcamentos ?? 0;
    const orcOk = hs.orcamentos_ok ?? 0;
    if (orcamentos > 0) {
      const exceeded = orcamentos - orcOk;
      if (exceeded > 0) {
        tips.push({
          type: 'budget',
          priority: 'high',
          icon: 'alert-circle',
          title: `${exceeded} orcamento(s) estourado(s)`,
          desc: `${orcOk} de ${orcamentos} dentro do limite`,
          url: 'financas',
        });
      } else {
        tips.push({
          type: 'budget',
          priority: 'positive',
          icon: 'check-circle',
          title: 'Todos os orcamentos no limite',
          desc: `${orcamentos} categoria(s) controlada(s)`,
          url: 'financas',
        });
      }
    }

    // 6. Goals progress
    const metasAtivas = hs.metas_ativas ?? 0;
    const metasConcluidas = hs.metas_concluidas ?? 0;
    if (metasConcluidas > 0) {
      tips.push({
        type: 'goals',
        priority: 'positive',
        icon: 'trophy',
        title: `${metasConcluidas} meta(s) concluida(s)!`,
        desc: metasAtivas > 0 ? `Ainda ${metasAtivas} em andamento` : 'Parabens pelo progresso!',
        url: 'financas#metas',
      });
    } else if (metasAtivas > 0) {
      tips.push({
        type: 'goals',
        priority: 'low',
        icon: 'target',
        title: `${metasAtivas} meta(s) em andamento`,
        desc: 'Continue focado nos seus objetivos.',
        url: 'financas#metas',
      });
    }

    // 7. Active installments
    if (parcelas.ativas > 0) {
      const mensal = (parcelas.total_mensal || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      tips.push({
        type: 'installments',
        priority: 'info',
        icon: 'layers',
        title: `${parcelas.ativas} parcelamento(s) ativo(s)`,
        desc: `${mensal}/mes comprometidos`,
        url: 'lancamentos',
      });
    }

    // 8. Upcoming bills (proximos)
    const proximos = data?.provisao?.proximos || [];
    if (proximos.length > 0) {
      const next = proximos[0];
      const dt = next.data_pagamento ? new Date(next.data_pagamento + 'T00:00:00') : null;
      const hoje = new Date();
      hoje.setHours(0, 0, 0, 0);
      if (dt) {
        const dias = Math.ceil((dt - hoje) / 86400000);
        if (dias >= 0 && dias <= 3) {
          const val = (next.valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
          tips.push({
            type: 'upcoming',
            priority: 'high',
            icon: 'calendar',
            title: dias === 0 ? 'Vencimento hoje!' : `Vencimento em ${dias} dia(s)`,
            desc: `${next.titulo}: ${val}`,
            url: 'lancamentos',
          });
        }
      }
    }

    // 9. Projected balance trend
    const projetado = provisao.saldo_projetado ?? 0;
    const saldoAtual = provisao.saldo_atual ?? 0;
    if (saldoAtual > 0 && projetado < 0) {
      tips.push({
        type: 'projection',
        priority: 'critical',
        icon: 'trending-down',
        title: 'Saldo projetado negativo',
        desc: 'Ate o fim do mes, seu saldo pode ficar negativo.',
        url: null,
      });
    } else if (projetado > saldoAtual && saldoAtual > 0) {
      tips.push({
        type: 'projection',
        priority: 'positive',
        icon: 'trending-up',
        title: 'Saldo projetado positivo',
        desc: 'Voce deve fechar o mes com saldo maior.',
        url: null,
      });
    }

    // 10. 6-month trend
    if (chart.length >= 3) {
      const recent = chart.slice(-3);
      const allPositive = recent.every(m => m.resultado > 0);
      const allNegative = recent.every(m => m.resultado < 0);
      if (allPositive) {
        tips.push({
          type: 'trend',
          priority: 'positive',
          icon: 'sparkles',
          title: '3 meses consecutivos positivos',
          desc: 'Otima sequencia! Mantenha o ritmo.',
          url: 'relatorios',
        });
      } else if (allNegative) {
        tips.push({
          type: 'trend',
          priority: 'high',
          icon: 'alert-triangle',
          title: '3 meses consecutivos negativos',
          desc: 'Hora de revisar seus gastos.',
          url: 'relatorios',
        });
      }
    }

    // Deduplicate: if we have a health_score_insight and a data-derived tip of the same type, keep only one
    const seen = new Set();
    const unique = tips.filter(t => {
      if (seen.has(t.type)) return false;
      seen.add(t.type);
      return true;
    });

    // Sort: critical first, then high, medium, low, positive, info
    const order = { critical: 0, high: 1, medium: 2, low: 3, positive: 4, info: 5 };
    unique.sort((a, b) => (order[a.priority] ?? 9) - (order[b.priority] ?? 9));

    return unique.slice(0, 4);
  }

  normalizeInsight(insight) {
    const defaults = {
      negative_balance: {
        title: 'Saldo negativo neste mes',
        icon: 'alert-triangle',
        url: 'lancamentos?tipo=despesa',
      },
      low_activity: {
        title: 'Registre mais movimentacoes',
        icon: 'edit-3',
        url: 'lancamentos',
      },
      low_categories: {
        title: 'Use mais categorias',
        icon: 'layers',
        url: 'categorias',
      },
      no_goals: {
        title: 'Defina uma meta financeira',
        icon: 'target',
        url: 'financas#metas',
      },
    };

    const preset = defaults[insight.type] || {
      title: 'Insight do mes',
      icon: 'lightbulb',
      url: 'dashboard',
    };

    return {
      type: insight.type || 'generic',
      priority: insight.priority || 'medium',
      title: insight.title || preset.title,
      message: insight.message || '',
      icon: preset.icon,
      url: preset.url,
    };
  }

  renderTips(tips) {
    const listEl = document.getElementById('aiTipList');
    if (!listEl) return;

    if (tips.length === 0) {
      this.renderEmpty();
      return;
    }

    const html = tips.map((tip, i) => {
      const iconClass = this.getIconClass(tip.priority);
      const tag = tip.url ? 'a' : 'div';
      const href = tip.url ? ` href="${this.baseURL}${tip.url}"` : '';

      return `
        <${tag}${href} class="ai-tip-item" style="animation-delay: ${i * 80}ms;">
          <div class="ai-tip-item-icon ${iconClass}">
            <i data-lucide="${tip.icon}" style="width:14px;height:14px;"></i>
          </div>
          <div class="ai-tip-item-body">
            <span class="ai-tip-item-title">${tip.title}</span>
            ${tip.desc ? `<span class="ai-tip-item-desc">${tip.desc}</span>` : ''}
          </div>
          ${tip.url ? '<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>' : ''}
        </${tag}>
      `;
    }).join('');

    listEl.innerHTML = html;
    this.updateIcons();
  }

  renderEmpty() {
    const listEl = document.getElementById('aiTipList');
    if (!listEl) return;

    listEl.innerHTML = `
      <div class="ai-tip-empty">
        <i data-lucide="check-circle" class="ai-tip-empty-icon"></i>
        <p>Tudo certo! Suas financas estao no caminho certo.</p>
      </div>
    `;
    this.updateIcons();
  }

  getIconClass(priority) {
    const map = {
      critical: 'ai-tip-item-icon--critical',
      high: 'ai-tip-item-icon--high',
      medium: 'ai-tip-item-icon--medium',
      low: 'ai-tip-item-icon--low',
      positive: 'ai-tip-item-icon--positive',
    };
    return map[priority] || 'ai-tip-item-icon--info';
  }

  updateIcons() {
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }
}

window.AiTipCard = AiTipCard;
