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
    const tipCount = 4;
    this.container.innerHTML = `
      <div class="ai-tip-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400" data-aos-delay="100">
        <div class="ai-tip-header">
          <i data-lucide="sparkles" class="ai-tip-header-icon"></i>
          <h2 class="ai-tip-title">Dicas do Lukrato</h2>
          <span class="ai-tip-badge" id="aiTipBadge" style="display:none;"></span>
        </div>
        <div class="ai-tip-list" id="aiTipList">
          ${'<div class="ai-tip-skeleton"></div>'.repeat(tipCount)}
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

    // 1. Health score insights (from backend — enriched with metric/title)
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
          title: insight.title || norm.title,
          desc: insight.message || norm.message,
          url: norm.url,
          metric: insight.metric || null,
          metricLabel: insight.metric_label || null,
        });
      });

    // 2. Overdue items
    if (vencidos.count > 0) {
      const total = (vencidos.total || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      tips.push({
        type: 'overdue',
        priority: 'critical',
        icon: 'clock',
        title: `${vencidos.count} conta(s) em atraso`,
        desc: 'Regularize para evitar juros e manter o score saudável.',
        url: 'lancamentos?status=vencido',
        metric: total,
        metricLabel: 'em atraso',
      });
    }

    // 3. Upcoming bills (proximos — urgentes)
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
            title: dias === 0 ? 'Vence hoje!' : `Vence em ${dias} dia(s)`,
            desc: next.titulo || 'Conta próxima do vencimento',
            url: 'lancamentos',
            metric: val,
            metricLabel: dias === 0 ? 'hoje' : `${dias}d`,
          });
        }
      }
    }

    // 4. Greeting insight (month-over-month)
    if (data?.greeting_insight) {
      const gi = data.greeting_insight;
      tips.push({
        type: 'greeting',
        priority: 'positive',
        icon: gi.icon || 'trending-up',
        title: gi.message || 'Evolução do mês',
        desc: '',
        url: null,
        metric: null,
        metricLabel: null,
      });
    }

    // 5. Savings rate
    const savingsRate = hs.savingsRate ?? 0;
    const receitas = metrics.receitas ?? 0;
    if (receitas > 0 && savingsRate >= 20) {
      tips.push({
        type: 'savings',
        priority: 'positive',
        icon: 'piggy-bank',
        title: 'Ótima taxa de economia!',
        desc: 'Você está guardando acima dos 20% recomendados.',
        url: null,
        metric: savingsRate + '%',
        metricLabel: 'guardado',
      });
    }

    // 6. Budget status
    const orcamentos = hs.orcamentos ?? 0;
    const orcOk = hs.orcamentos_ok ?? 0;
    if (orcamentos > 0) {
      const exceeded = orcamentos - orcOk;
      if (exceeded > 0) {
        tips.push({
          type: 'budget',
          priority: 'high',
          icon: 'alert-circle',
          title: `${exceeded} orçamento(s) estourado(s)`,
          desc: 'Revise seus gastos para voltar ao controle.',
          url: 'financas',
          metric: `${orcOk}/${orcamentos}`,
          metricLabel: 'no limite',
        });
      } else {
        tips.push({
          type: 'budget',
          priority: 'positive',
          icon: 'check-circle',
          title: 'Orçamentos sob controle!',
          desc: `Todas as ${orcamentos} categoria(s) dentro do limite.`,
          url: 'financas',
          metric: `${orcamentos}/${orcamentos}`,
          metricLabel: 'ok',
        });
      }
    }

    // 7. Goals progress
    const metasAtivas = hs.metas_ativas ?? 0;
    const metasConcluidas = hs.metas_concluidas ?? 0;
    if (metasConcluidas > 0) {
      tips.push({
        type: 'goals',
        priority: 'positive',
        icon: 'trophy',
        title: `${metasConcluidas} meta(s) alcançada(s)!`,
        desc: metasAtivas > 0 ? `Continue! ${metasAtivas} ainda em progresso.` : 'Parabéns pelo progresso!',
        url: 'financas#metas',
        metric: String(metasConcluidas),
        metricLabel: 'concluída(s)',
      });
    } else if (metasAtivas > 0) {
      tips.push({
        type: 'goals',
        priority: 'low',
        icon: 'target',
        title: `${metasAtivas} meta(s) em progresso`,
        desc: 'Cada passo conta. Mantenha o foco!',
        url: 'financas#metas',
        metric: String(metasAtivas),
        metricLabel: 'ativa(s)',
      });
    }

    // 8. Installments
    if (parcelas.ativas > 0) {
      const mensal = (parcelas.total_mensal || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
      tips.push({
        type: 'installments',
        priority: 'info',
        icon: 'layers',
        title: `${parcelas.ativas} parcelamento(s) ativo(s)`,
        desc: `${mensal}/mês comprometidos com parcelas.`,
        url: 'lancamentos',
        metric: mensal,
        metricLabel: '/mês',
      });
    }

    // 9. Projected balance
    const projetado = provisao.saldo_projetado ?? 0;
    const saldoAtual = provisao.saldo_atual ?? 0;
    if (saldoAtual > 0 && projetado < 0) {
      tips.push({
        type: 'projection',
        priority: 'critical',
        icon: 'trending-down',
        title: 'Atenção: saldo projetado negativo',
        desc: 'Até o fim do mês, seu saldo pode ficar negativo. Reduza gastos.',
        url: null,
        metric: projetado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
        metricLabel: 'projetado',
      });
    } else if (projetado > saldoAtual && saldoAtual > 0) {
      tips.push({
        type: 'projection',
        priority: 'positive',
        icon: 'trending-up',
        title: 'Projeção positiva!',
        desc: 'Você deve fechar o mês com saldo maior.',
        url: null,
        metric: projetado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
        metricLabel: 'projetado',
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
          icon: 'flame',
          title: 'Sequência de 3 meses positivos!',
          desc: 'Ótima consistência. Mantenha o ritmo!',
          url: 'relatorios',
          metric: '3',
          metricLabel: 'meses',
        });
      } else if (allNegative) {
        tips.push({
          type: 'trend',
          priority: 'high',
          icon: 'alert-triangle',
          title: '3 meses no vermelho',
          desc: 'É hora de repensar seus gastos.',
          url: 'relatorios',
          metric: '3',
          metricLabel: 'meses',
        });
      }
    }

    // Deduplicate
    const seen = new Set();
    const unique = tips.filter(t => {
      if (seen.has(t.type)) return false;
      seen.add(t.type);
      return true;
    });

    // Sort: critical first, then high, medium, low, positive, info
    const order = { critical: 0, high: 1, medium: 2, low: 3, positive: 4, info: 5 };
    unique.sort((a, b) => (order[a.priority] ?? 9) - (order[b.priority] ?? 9));

    return unique.slice(0, 5);
  }

  normalizeInsight(insight) {
    const defaults = {
      negative_balance: {
        title: 'Saldo no vermelho',
        icon: 'alert-triangle',
        url: 'lancamentos?tipo=despesa',
      },
      overspending: {
        title: 'Gastos acima da receita',
        icon: 'trending-down',
        url: 'lancamentos?tipo=despesa',
      },
      low_savings: {
        title: 'Economia muito baixa',
        icon: 'piggy-bank',
        url: 'relatorios',
      },
      moderate_savings: {
        title: 'Aumente sua economia',
        icon: 'piggy-bank',
        url: 'relatorios',
      },
      low_activity: {
        title: 'Registre suas movimentações',
        icon: 'edit-3',
        url: 'lancamentos',
      },
      low_categories: {
        title: 'Organize por categorias',
        icon: 'layers',
        url: 'categorias',
      },
      no_goals: {
        title: 'Crie sua primeira meta',
        icon: 'target',
        url: 'financas#metas',
      },
      no_budgets: {
        title: 'Defina limites de gastos',
        icon: 'shield',
        url: 'financas',
      },
    };

    const preset = defaults[insight.type] || {
      title: 'Dica do mês',
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

    // Update badge
    const badge = document.getElementById('aiTipBadge');
    const hasCritical = tips.some(t => t.priority === 'critical' || t.priority === 'high');
    if (badge) {
      if (hasCritical) {
        badge.textContent = `${tips.filter(t => t.priority === 'critical' || t.priority === 'high').length} atenção`;
        badge.style.display = '';
        badge.style.background = 'rgba(239, 68, 68, 0.12)';
        badge.style.color = '#ef4444';
      } else {
        const positiveCount = tips.filter(t => t.priority === 'positive').length;
        if (positiveCount > 0) {
          badge.textContent = `${positiveCount} positivo(s)`;
          badge.style.display = '';
          badge.style.background = 'rgba(16, 185, 129, 0.12)';
          badge.style.color = '#10b981';
        } else {
          badge.style.display = 'none';
        }
      }
    }

    const html = tips.map((tip, i) => {
      const iconClass = this.getIconClass(tip.priority);
      const tag = tip.url ? 'a' : 'div';
      const href = tip.url ? ` href="${this.baseURL}${tip.url}"` : '';
      const accentClass = `ai-tip-accent--${tip.priority || 'info'}`;

      const metricHtml = tip.metric
        ? `<div class="ai-tip-metric">
            <span class="ai-tip-metric-value">${tip.metric}</span>
            ${tip.metricLabel ? `<span class="ai-tip-metric-label">${tip.metricLabel}</span>` : ''}
          </div>`
        : '';

      return `
        <${tag}${href} class="ai-tip-item" data-priority="${tip.priority}" style="animation-delay: ${i * 70}ms;">
          <div class="ai-tip-accent ${accentClass}"></div>
          <div class="ai-tip-content">
            <div class="ai-tip-item-icon ${iconClass}">
              <i data-lucide="${tip.icon}" style="width:16px;height:16px;"></i>
            </div>
            <div class="ai-tip-item-body">
              <span class="ai-tip-item-title">${tip.title}</span>
              ${tip.desc ? `<span class="ai-tip-item-desc">${tip.desc}</span>` : ''}
            </div>
            ${tip.url ? '<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>' : ''}
          </div>
          ${metricHtml}
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
        <p>Tudo certo por aqui! Suas finanças estão no caminho certo.</p>
      </div>
    `;

    const badge = document.getElementById('aiTipBadge');
    if (badge) {
      badge.textContent = 'Tudo ok';
      badge.style.display = '';
      badge.style.background = 'rgba(16, 185, 129, 0.12)';
      badge.style.color = '#10b981';
    }

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
