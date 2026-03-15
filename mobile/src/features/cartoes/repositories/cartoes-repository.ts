import { appConfig } from '@/src/lib/config/app-config';
import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import { cartoesPreview } from '@/src/features/cartoes/data/cartoes-preview';
import { CartaoAlert, CartaoOverview, CartoesSnapshot } from '@/src/features/cartoes/types';

type RemoteCard = {
  id: number;
  nome_cartao: string;
  bandeira?: string | null;
  ultimos_digitos?: string | null;
  limite_total?: number | string | null;
  limite_disponivel?: number | string | null;
  limite_disponivel_real?: number | string | null;
  limite_utilizado?: number | string | null;
  percentual_uso?: number | string | null;
  dia_vencimento?: number | null;
  cor_cartao?: string | null;
  conta?: {
    nome?: string;
    instituicao_financeira?: {
      nome?: string;
      cor_primaria?: string;
    } | null;
  } | null;
};

type RemoteResumo = {
  total_cartoes: number;
  limite_total: number;
  limite_disponivel: number;
  limite_utilizado: number;
  percentual_uso: number;
};

type RemoteAlertsPayload = {
  total?: number;
  alertas?: RemoteAlert[];
};

type RemoteAlert = {
  cartao_id?: number;
  nome_cartao?: string;
  valor_fatura?: number;
  limite_disponivel?: number;
  percentual_disponivel?: number;
  dias_faltando?: number;
  tipo?: string;
  gravidade?: string;
};

type RemotePendingMonths = {
  meses?: { mes: number; ano: number }[];
};

type RemoteInvoice = {
  total?: number;
};

type RemoteInvoiceStatus = {
  pago?: boolean;
  valor?: number;
};

type RepositoryResult<T> = {
  source: 'preview' | 'remote';
  data: T;
  message?: string | null;
};

const BRAND_COLORS: Record<string, string> = {
  visa: '#2857c5',
  mastercard: '#f07c13',
  elo: '#3a79d9',
  amex: '#1f4f82',
};

function getCurrentMonthLabel() {
  return new Intl.DateTimeFormat('pt-BR', {
    month: 'long',
    year: 'numeric',
  }).format(new Date());
}

function formatDueDate(date: string) {
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: 'short',
  }).format(new Date(date));
}

function getNextDueDate(day?: number | null) {
  const today = new Date();
  const currentYear = today.getFullYear();
  const currentMonth = today.getMonth();
  const dueDay = Math.max(1, Math.min(day || 1, 31));

  const thisMonthLastDay = new Date(currentYear, currentMonth + 1, 0).getDate();
  const dueThisMonth = new Date(currentYear, currentMonth, Math.min(dueDay, thisMonthLastDay));

  if (today <= dueThisMonth) {
    return dueThisMonth;
  }

  const nextMonthDate = new Date(currentYear, currentMonth + 1, 1);
  const nextMonthLastDay = new Date(
    nextMonthDate.getFullYear(),
    nextMonthDate.getMonth() + 1,
    0
  ).getDate();

  return new Date(
    nextMonthDate.getFullYear(),
    nextMonthDate.getMonth(),
    Math.min(dueDay, nextMonthLastDay)
  );
}

function getDueDateForMonth(month: number, year: number, day?: number | null) {
  const dueDay = Math.max(1, Math.min(day || 1, 31));
  const lastDay = new Date(year, month, 0).getDate();

  return new Date(year, month - 1, Math.min(dueDay, lastDay));
}

function getFallbackColor(card: RemoteCard) {
  const brand = card.bandeira?.toLowerCase() || '';
  return card.cor_cartao || card.conta?.instituicao_financeira?.cor_primaria || BRAND_COLORS[brand] || '#1f4f82';
}

function fallbackMessage(error: unknown) {
  if (!error) {
    return null;
  }

  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'A API de cartoes respondeu sem sessao autenticada. Mantive o preview para voce continuar evoluindo o app.';
    }

    if (error.status === 403) {
      return 'A API de cartoes exigiu protecao adicional. Mantive o preview para nao travar a tela.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API ainda nao foi configurada. O preview segue ativo.';
    }

    return error.message;
  }

  return 'Nao foi possivel usar a API de cartoes agora. O preview continua ativo.';
}

function mapAlert(alert: RemoteAlert, index: number): CartaoAlert {
  if (alert.tipo === 'vencimento_proximo') {
    return {
      id: `alert-${index}-${alert.cartao_id || 'due'}`,
      type: 'due_soon',
      severity: alert.gravidade === 'critico' ? 'critical' : 'attention',
      title: 'Fatura vence logo',
      description: `${alert.nome_cartao || 'Cartao'} vence em ${alert.dias_faltando || 0} dia(s).`,
      amount: Number(alert.valor_fatura || 0),
    };
  }

  return {
    id: `alert-${index}-${alert.cartao_id || 'limit'}`,
    type: 'low_limit',
    severity: alert.gravidade === 'critico' ? 'critical' : 'attention',
    title: 'Limite livre esta apertado',
    description: `${alert.nome_cartao || 'Cartao'} esta com ${Number(alert.percentual_disponivel || 0).toFixed(0)}% do limite disponivel.`,
    amount: Number(alert.limite_disponivel || 0),
  };
}

async function loadCardMeta(card: RemoteCard) {
  const pendingPayload = await httpClient
    .get<RemotePendingMonths>(`api/cartoes/${card.id}/faturas-pendentes`)
    .catch(() => ({ meses: [] }));
  const pendingMonths = Array.isArray(pendingPayload.meses) ? pendingPayload.meses.slice() : [];
  const referenceMonth = pendingMonths.sort((left, right) => {
    const leftValue = left.ano * 100 + left.mes;
    const rightValue = right.ano * 100 + right.mes;
    return leftValue - rightValue;
  })[0];
  const fallbackDueDate = getNextDueDate(card.dia_vencimento);
  const month = referenceMonth?.mes || fallbackDueDate.getMonth() + 1;
  const year = referenceMonth?.ano || fallbackDueDate.getFullYear();
  const dueDate = getDueDateForMonth(month, year, card.dia_vencimento);

  const [invoicePayload, statusPayload] = await Promise.all([
    httpClient
      .get<RemoteInvoice>(`api/cartoes/${card.id}/fatura`, {
        mes: month,
        ano: year,
      })
      .catch(() => ({ total: 0 })),
    httpClient
      .get<RemoteInvoiceStatus>(`api/cartoes/${card.id}/fatura/status`, {
        mes: month,
        ano: year,
      })
      .catch((): RemoteInvoiceStatus => ({ pago: false, valor: 0 })),
  ]);

  const pendingInvoices = pendingMonths.length;
  const invoiceAmount = statusPayload.pago
    ? Number(statusPayload.valor || 0)
    : Number(invoicePayload.total || 0);

  return {
    pendingInvoices,
    invoiceAmount,
    nextDueDate: dueDate.toISOString().slice(0, 10),
    statusLabel: statusPayload.pago
      ? 'Fatura ja paga'
      : pendingInvoices > 0
        ? 'Fatura pendente'
        : 'Sem fatura pendente agora',
  };
}

function mapCard(card: RemoteCard, meta: Awaited<ReturnType<typeof loadCardMeta>>): CartaoOverview {
  const totalLimit = Number(card.limite_total || 0);
  const availableLimit = Number(card.limite_disponivel_real ?? card.limite_disponivel ?? 0);
  const usedLimit = Number(card.limite_utilizado ?? Math.max(0, totalLimit - availableLimit));
  const usagePercent =
    Number(card.percentual_uso ?? (totalLimit > 0 ? (usedLimit / totalLimit) * 100 : 0));

  return {
    id: String(card.id),
    name: card.nome_cartao || 'Cartao',
    brandLabel: card.bandeira || 'Credito',
    lastDigits: card.ultimos_digitos || '0000',
    accentColor: getFallbackColor(card),
    linkedAccount: card.conta?.nome || 'Conta nao definida',
    linkedInstitution: card.conta?.instituicao_financeira?.nome || 'Instituicao nao definida',
    totalLimit,
    availableLimit,
    usedLimit,
    usagePercent,
    invoiceAmount: meta.invoiceAmount,
    pendingInvoices: meta.pendingInvoices,
    nextDueDate: meta.nextDueDate,
    statusLabel: meta.statusLabel,
    needsAttention: meta.pendingInvoices > 0 || usagePercent >= 70,
  };
}

function buildFocus(cards: CartaoOverview[], alerts: CartaoAlert[]) {
  const criticalAlert = alerts.find((alert) => alert.severity === 'critical');
  if (criticalAlert) {
    return {
      title:
        criticalAlert.type === 'due_soon'
          ? 'A proxima fatura precisa da sua atencao'
          : 'Um cartao esta quase sem limite livre',
      description:
        criticalAlert.type === 'due_soon'
          ? 'O app coloca a fatura mais urgente no topo para o usuario agir antes do vencimento.'
          : 'O limite livre esta apertado, entao faz sentido revisar esse cartao antes da proxima compra.',
      amount: criticalAlert.amount,
      supportText: criticalAlert.description,
      tone: 'negative' as const,
    };
  }

  const attentionCard = cards
    .slice()
    .sort((left, right) => right.usagePercent - left.usagePercent)[0];

  if (attentionCard) {
    return {
      title: `${attentionCard.name} virou a principal referencia`,
      description:
        'Sem alertas criticos, o app destaca o cartao com maior uso para o usuario entender rapidamente onde esta o peso da fatura.',
      amount: attentionCard.invoiceAmount || attentionCard.usedLimit,
      supportText: `${attentionCard.brandLabel} final ${attentionCard.lastDigits} com ${attentionCard.usagePercent.toFixed(0)}% do limite usado.`,
      tone: attentionCard.usagePercent >= 70 ? ('warning' as const) : ('positive' as const),
    };
  }

  return {
    title: 'Nenhum cartao cadastrado ainda',
    description:
      'Quando o primeiro cartao entrar, a tela vai mostrar limite, conta vinculada e a fatura mais importante.',
    amount: 0,
    supportText: 'O app vai manter a parte de cartoes e faturas visivel sem misturar com contas bancarias.',
    tone: 'warning' as const,
  };
}

function buildSnapshot(
  resumo: RemoteResumo,
  cards: CartaoOverview[],
  alerts: CartaoAlert[]
): CartoesSnapshot {
  return {
    monthLabel: getCurrentMonthLabel(),
    helperTitle: 'Cartoes e faturas sem misterio',
    helperDescription:
      'O usuario ve primeiro o que vence logo, quanto limite ainda resta e qual cartao esta pedindo mais atencao.',
    totalCards: resumo.total_cartoes,
    totalLimit: Number(resumo.limite_total || 0),
    availableLimit: Number(resumo.limite_disponivel || 0),
    usedLimit: Number(resumo.limite_utilizado || 0),
    usagePercent: Number(resumo.percentual_uso || 0),
    pendingInvoiceCount: cards.filter((card) => card.pendingInvoices > 0).length,
    focus: buildFocus(cards, alerts),
    alerts,
    guidedSteps: [
      {
        id: '1',
        title: 'Abra primeiro a fatura que vence antes',
        description: 'Esse e o caminho mais simples para nao esquecer vencimentos e nao pagar juros a toa.',
        done: !alerts.some((alert) => alert.type === 'due_soon'),
      },
      {
        id: '2',
        title: 'Compare limite livre antes de parcelar',
        description: 'O app deixa o disponivel ao lado do total para a decisao ficar obvia.',
        done: !alerts.some((alert) => alert.type === 'low_limit'),
      },
      {
        id: '3',
        title: 'Mantenha a conta vinculada bem visivel',
        description: 'Quando chegar a hora de pagar, o usuario ja sabe de onde o dinheiro vai sair.',
        done: cards.every((card) => card.linkedAccount !== 'Conta nao definida'),
      },
    ],
    cards: cards
      .slice()
      .sort((left, right) => Number(right.needsAttention) - Number(left.needsAttention)),
  };
}

class CartoesRepository {
  async getSnapshot(): Promise<RepositoryResult<CartoesSnapshot>> {
    try {
      const [resumo, cardsPayload, alertsPayload] = await Promise.all([
        httpClient.get<RemoteResumo>('api/cartoes/resumo'),
        httpClient.get<RemoteCard[]>('api/cartoes'),
        httpClient
          .get<RemoteAlertsPayload>('api/cartoes/alertas')
          .catch(() => ({ total: 0, alertas: [] })),
      ]);

      const cardMetas = await Promise.all(cardsPayload.map((card) => loadCardMeta(card)));
      const cards = cardsPayload.map((card, index) => mapCard(card, cardMetas[index]));
      const alerts = (alertsPayload.alertas || []).map(mapAlert);

      return {
        source: 'remote',
        data: buildSnapshot(resumo, cards, alerts),
      };
    } catch (error) {
      return {
        source: 'preview',
        data: cartoesPreview,
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
      };
    }
  }
}

export const cartoesRepository = new CartoesRepository();
export { formatDueDate };
