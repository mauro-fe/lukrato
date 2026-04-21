import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import { DashboardSnapshot, DashboardTransaction } from '@/src/features/dashboard/types';

type RepositoryResult<T> = {
  source: 'remote';
  data: T;
  message?: string | null;
};

type RemoteMetrics = {
  saldo?: number;
  saldoAcumulado?: number;
  receitas?: number;
  despesas?: number;
  resultado?: number;
};

type RemoteTransaction = {
  id: number;
  data: string;
  tipo: string;
  valor: number;
  descricao?: string;
  categoria?: string | { nome?: string } | null;
  conta?: string;
};

type RemoteGreetingInsight = {
  message?: string;
};

type RemoteHealthScore = {
  score?: number;
  savingsRate?: number;
  consistency?: string;
  categories?: number;
  lancamentos?: number;
};

type RemoteProvisao = {
  month?: string;
  provisao?: {
    a_pagar?: number;
    a_receber?: number;
    saldo_projetado?: number;
    saldo_atual?: number;
    count_pagar?: number;
    count_receber?: number;
    count_faturas?: number;
    total_faturas?: number;
  };
  proximos?: {
    id?: number | string;
    titulo?: string;
    tipo?: string;
    valor?: number;
    data_pagamento?: string;
  }[];
  vencidos?: {
    count?: number;
    total?: number;
  };
  parcelas?: {
    ativas?: number;
    total_mensal?: number;
  };
};

function getCurrentMonthKey() {
  return new Date().toISOString().slice(0, 7);
}

function getMonthLabel(monthKey: string) {
  const [year, month] = monthKey.split('-').map(Number);
  return new Intl.DateTimeFormat('pt-BR', {
    month: 'long',
    year: 'numeric',
  }).format(new Date(year, month - 1, 1));
}

function buildQuickActions() {
  return [
    {
      id: 'expense',
      label: 'Registrar gasto',
      icon: 'remove-circle-outline',
      route: '/(app)/(tabs)/lancamentos' as const,
    },
    {
      id: 'income',
      label: 'Recebi',
      icon: 'add-circle-outline',
      route: '/(app)/(tabs)/lancamentos' as const,
    },
    {
      id: 'accounts',
      label: 'Ver contas',
      icon: 'wallet-outline',
      route: '/(app)/(tabs)/contas' as const,
    },
    {
      id: 'profile',
      label: 'Ajuda e perfil',
      icon: 'person-outline',
      route: '/(app)/(tabs)/perfil' as const,
    },
  ];
}

export function createEmptyDashboardSnapshot(
  userName = 'Usuario',
  monthKey = getCurrentMonthKey()
): DashboardSnapshot {
  return {
    monthLabel: getMonthLabel(monthKey),
    userName,
    balance: 0,
    income: 0,
    expenses: 0,
    reserved: 0,
    monthlyResult: 0,
    mainFocus: {
      title: 'Seu painel real aparece assim que a API responder',
      description:
        'Quando os dados chegarem, o app vai destacar primeiro o que pede atencao imediata.',
      amount: 0,
      supportText: 'Por enquanto, o fluxo continua preparado para mostrar saldo, vencimentos e proximos passos.',
    },
    guidedSteps: [
      {
        id: '1',
        title: 'Registre a primeira entrada do mes',
        description: 'Assim o saldo deixa de ser abstrato e passa a refletir o que realmente entrou.',
        cta: 'Adicionar receita',
      },
      {
        id: '2',
        title: 'Confirme os gastos fixos que ja sairam',
        description: 'Aluguel, internet, energia e contas recorrentes ficam mais claros quando entram cedo.',
        cta: 'Revisar lancamentos',
      },
      {
        id: '3',
        title: 'Revise o que ainda vence',
        description: 'Quando existir pendencia, ela sobe para o topo antes do resto.',
        cta: 'Ver vencimentos',
      },
    ],
    quickActions: buildQuickActions(),
    insights: [
      {
        id: 'welcome',
        title: 'O dashboard agora trabalha com dados reais.',
        description:
          'Se algo nao aparecer, o motivo vem do backend ou da sessao, nao de valores de demonstracao.',
      },
    ],
    transactions: [],
  };
}

function formatShortDate(date: string) {
  if (!date) {
    return '';
  }

  try {
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: 'short',
    }).format(new Date(date));
  } catch {
    return date;
  }
}

function mapTransaction(item: RemoteTransaction): DashboardTransaction {
  const kind =
    item.tipo === 'receita'
      ? 'income'
      : item.tipo === 'despesa'
        ? 'expense'
        : 'transfer';

  return {
    id: String(item.id),
    title: item.descricao?.trim() || 'Lancamento',
    category:
      typeof item.categoria === 'string'
        ? item.categoria || 'Sem categoria'
        : item.categoria?.nome || (kind === 'transfer' ? 'Transferencia' : 'Sem categoria'),
    account: item.conta || 'Conta nao definida',
    date: item.data,
    amount:
      kind === 'expense'
        ? -Math.abs(Number(item.valor || 0))
        : Math.abs(Number(item.valor || 0)),
    kind,
  };
}

function buildFocus(
  metrics: RemoteMetrics,
  provisao: RemoteProvisao | null
): DashboardSnapshot['mainFocus'] {
  const overdue = Number(provisao?.vencidos?.count ?? 0);
  const overdueTotal = Number(provisao?.vencidos?.total ?? 0);

  if (overdue > 0) {
    return {
      title: 'Existem pendencias atrasadas pedindo prioridade',
      description:
        'Quando algo ja venceu, o app traz isso para o topo porque essa e a decisao mais urgente do dia.',
      amount: overdueTotal,
      supportText: `${overdue} item(ns) atrasado(s) ainda precisam de acao.`,
    };
  }

  const nextDue = provisao?.proximos?.[0];
  if (nextDue) {
    return {
      title: 'Seu proximo vencimento ja esta claro',
      description:
        'O dashboard puxa o compromisso mais proximo para reduzir a chance de esquecer algo importante.',
      amount: Math.abs(Number(nextDue.valor ?? 0)),
      supportText: `${nextDue.titulo || 'Compromisso'} em ${formatShortDate(nextDue.data_pagamento || '')}.`,
    };
  }

  const totalFaturas = Number(provisao?.provisao?.total_faturas ?? 0);
  const countFaturas = Number(provisao?.provisao?.count_faturas ?? 0);
  if (countFaturas > 0) {
    return {
      title: 'As faturas do mes ja estao separadas do resto',
      description:
        'Em vez de misturar tudo no saldo, o app destaca as faturas para a revisão ficar direta.',
      amount: totalFaturas,
      supportText: `${countFaturas} fatura(s) pendente(s) neste mês.`,
    };
  }

  const monthlyResult = Number(metrics.resultado ?? 0);
  return {
    title: monthlyResult >= 0 ? 'Seu mês está positivo até aqui' : 'Seu mês pede ajuste',
    description:
      monthlyResult >= 0
        ? 'Sem pendências urgentes, o dashboard reforça o resultado do mês para você decidir o próximo passo.'
        : 'Sem vencidos urgentes, o resultado mensal vira o melhor ponto de partida para reorganizar o caixa.',
    amount: monthlyResult,
    supportText:
      monthlyResult >= 0
        ? 'Entrou mais dinheiro do que saiu no período atual.'
        : 'As saídas do mês passaram das entradas registradas.',
  };
}

function buildGuidedSteps(
  metrics: RemoteMetrics,
  provisao: RemoteProvisao | null,
  transactionCount: number
): DashboardSnapshot['guidedSteps'] {
  const income = Number(metrics.receitas ?? 0);
  const expenses = Number(metrics.despesas ?? 0);
  const overdue = Number(provisao?.vencidos?.count ?? 0);
  const nextDueCount = Array.isArray(provisao?.proximos) ? provisao?.proximos.length : 0;

  return [
    {
      id: '1',
      title: 'Registre primeiro o que entrou',
      description: 'Quando a renda entra cedo, o saldo do mês fica confiável para o resto das decisões.',
      cta: 'Adicionar receita',
      done: income > 0,
    },
    {
      id: '2',
      title: 'Depois confirme os gastos que já aconteceram',
      description: 'Isso evita saldo inflado e reduz a sensação de que falta contexto na tela.',
      cta: 'Revisar lançamentos',
      done: expenses > 0 || transactionCount >= 3,
    },
    {
      id: '3',
      title: 'Por último revise o que vence ou atrasou',
      description: 'O objetivo é agir primeiro no que pode gerar multa, juros ou confusão no caixa.',
      cta: 'Ver vencimentos',
      done: overdue === 0 && nextDueCount === 0,
    },
  ];
}

function buildInsights(
  greeting: RemoteGreetingInsight | null,
  health: RemoteHealthScore | null,
  provisao: RemoteProvisao | null
): DashboardSnapshot['insights'] {
  const insights = [];

  if (greeting?.message) {
    insights.push({
      id: 'greeting',
      title: greeting.message,
      description: 'Esse resumo vem do comportamento financeiro atual, não de texto fixo de demonstração.',
    });
  }

  if (health?.score !== undefined) {
    insights.push({
      id: 'health',
      title: `Seu Health Score está em ${Math.round(health.score)}/100.`,
      description: `Consistência ${health.consistency || 'regular'}, ${health.categories || 0} categoria(s) usada(s) e taxa de poupança de ${health.savingsRate || 0}%.`,
    });
  }

  const countReceber = Number(provisao?.provisao?.count_receber ?? 0);
  const amountReceber = Number(provisao?.provisao?.a_receber ?? 0);
  const countPagar = Number(provisao?.provisao?.count_pagar ?? 0);
  const amountPagar = Number(provisao?.provisao?.a_pagar ?? 0);

  if (countPagar > 0 || countReceber > 0) {
    insights.push({
      id: 'provisao',
      title: `Há ${countPagar} compromisso(s) a pagar e ${countReceber} a receber.`,
      description: `O painel separa ${amountPagar.toFixed(2)} para saídas e ${amountReceber.toFixed(2)} para entradas previstas no mês.`,
    });
  }

  if (!insights.length) {
    insights.push({
      id: 'fallback',
      title: 'Seu painel já está ligado na API.',
      description: 'Conforme os dados reais entrarem, os insights passam a refletir saldo, vencimentos e consistência.',
    });
  }

  return insights.slice(0, 3);
}

function getFallbackMessage(error: unknown) {
  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'Sua sessão não foi aceita pelo backend. Entre novamente para recarregar o dashboard.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API não foi configurada para este aparelho.';
    }

    return error.message;
  }

  return 'Não foi possível carregar o dashboard agora.';
}

class DashboardRepository {
  async getSnapshot(
    userName?: string,
    monthKey = getCurrentMonthKey()
  ): Promise<RepositoryResult<DashboardSnapshot>> {
    try {
      const warnings: string[] = [];

      const [metrics, transactions, greeting, health, provisao] = await Promise.all([
        httpClient.get<RemoteMetrics>('api/v1/dashboard/metrics', {
          month: monthKey,
          view: 'caixa',
        }),
        httpClient.get<RemoteTransaction[]>('api/v1/dashboard/transactions', {
          month: monthKey,
          limit: 5,
        }),
        httpClient.get<RemoteGreetingInsight>('api/v1/dashboard/greeting-insight').catch(() => {
          warnings.push('Insight dinâmico indisponível no momento.');
          return null;
        }),
        httpClient.get<RemoteHealthScore>('api/v1/dashboard/health-score').catch(() => {
          warnings.push('Health Score indisponível no momento.');
          return null;
        }),
        httpClient.get<RemoteProvisao>('api/v1/dashboard/provisao', {
          month: monthKey,
        }).catch(() => {
          warnings.push('Provisão financeira indisponível no momento.');
          return null;
        }),
      ]);

      const mappedTransactions = transactions.map(mapTransaction);

      return {
        source: 'remote',
        data: {
          monthLabel: getMonthLabel(monthKey),
          userName: userName || 'Usuario',
          balance: Number(metrics.saldoAcumulado ?? metrics.saldo ?? 0),
          income: Number(metrics.receitas ?? 0),
          expenses: Number(metrics.despesas ?? 0),
          reserved: Number(provisao?.provisao?.a_pagar ?? 0),
          monthlyResult: Number(metrics.resultado ?? 0),
          mainFocus: buildFocus(metrics, provisao),
          guidedSteps: buildGuidedSteps(metrics, provisao, mappedTransactions.length),
          quickActions: buildQuickActions(),
          insights: buildInsights(greeting, health, provisao),
          transactions: mappedTransactions,
        },
        message: warnings.length ? warnings.join(' ') : null,
      };
    } catch (error) {
      return {
        source: 'remote',
        data: createEmptyDashboardSnapshot(userName, monthKey),
        message: getFallbackMessage(error),
      };
    }
  }
}

export const dashboardRepository = new DashboardRepository();
