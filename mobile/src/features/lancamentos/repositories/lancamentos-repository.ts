import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import {
  FormAccountOption,
  FormCategoryOption,
} from '@/src/features/lancamentos/data/lancamento-form-options';
import {
  LancamentoEntryMode,
  LancamentoItem,
  LancamentoQuickAction,
  LancamentoSnapshot,
} from '@/src/features/lancamentos/types';

type RemoteLancamento = {
  id: number;
  data: string;
  tipo: string;
  valor: number;
  descricao: string;
  observacao?: string;
  categoria?: string;
  categoria_nome?: string;
  conta?: string;
  conta_nome?: string;
  conta_destino?: string;
  conta_destino_nome?: string;
  eh_transferencia?: boolean | number;
  pago?: boolean;
};

type RemoteOptionsResponse = {
  categorias: {
    receitas: { id: number; nome: string }[];
    despesas: { id: number; nome: string }[];
  };
  contas: { id: number; nome: string }[];
};

type RepositoryResult<T> = {
  source: 'remote';
  data: T;
  message?: string | null;
};

export type CreateLancamentoInput = {
  mode: LancamentoEntryMode;
  amount: number;
  description: string;
  date: string;
  note?: string;
  accountId: string;
  destinationAccountId?: string;
  categoryId?: string;
  isPaid: boolean;
};

const CATEGORY_ICON_MAP: Record<string, string> = {
  salario: 'cash-outline',
  freela: 'briefcase-outline',
  alimentacao: 'restaurant-outline',
  transporte: 'car-outline',
  casa: 'home-outline',
  saude: 'fitness-outline',
  lazer: 'game-controller-outline',
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

function normalizeTitle(value: string) {
  return value
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '');
}

function pickCategoryIcon(label: string) {
  const normalized = normalizeTitle(label);
  return CATEGORY_ICON_MAP[normalized] ?? 'grid-outline';
}

function buildQuickActions(): LancamentoQuickAction[] {
  return [
    {
      id: 'expense',
      label: 'Registrar gasto',
      caption: 'Despesa do dia',
      icon: 'remove-circle-outline',
      tone: 'danger',
    },
    {
      id: 'income',
      label: 'Recebi',
      caption: 'Entrada rapida',
      icon: 'add-circle-outline',
      tone: 'success',
    },
    {
      id: 'transfer',
      label: 'Transferir',
      caption: 'Entre contas',
      icon: 'swap-horizontal-outline',
      tone: 'secondary',
    },
  ];
}

export function createEmptyLancamentosSnapshot(
  monthKey = getCurrentMonthKey()
): LancamentoSnapshot {
  return {
    monthLabel: getMonthLabel(monthKey),
    helperTitle: 'Tudo que entrou e saiu sem a bagunca de uma tabela fria',
    helperDescription:
      'O usuario enxerga primeiro o que esta pendente, depois encontra qualquer lancamento com poucos toques.',
    totalItems: 0,
    pendingCount: 0,
    pendingAmount: 0,
    paidCount: 0,
    quickActions: buildQuickActions(),
    focusTip: {
      title: 'Comece registrando o que ja aconteceu neste mes',
      description:
        'Quando os primeiros lancamentos entram, a tela passa a mostrar pendencias e historico de um jeito bem mais util.',
    },
    items: [],
  };
}

function mapRemoteItem(item: RemoteLancamento): LancamentoItem {
  const isTransfer =
    item.eh_transferencia === true ||
    item.eh_transferencia === 1 ||
    item.tipo === 'transferencia';
  const type = isTransfer ? 'transfer' : item.tipo === 'receita' ? 'income' : 'expense';
  const sourceAccount = item.conta_nome || item.conta || 'Conta';
  const destinationAccount = item.conta_destino_nome || item.conta_destino || '';

  return {
    id: String(item.id),
    title: item.descricao || 'Lancamento',
    category: item.categoria_nome || item.categoria || (isTransfer ? 'Transferencia' : 'Sem categoria'),
    account: isTransfer && destinationAccount ? `${sourceAccount} -> ${destinationAccount}` : sourceAccount,
    date: item.data,
    amount:
      type === 'expense'
        ? -Math.abs(Number(item.valor || 0))
        : Math.abs(Number(item.valor || 0)),
    type,
    status: item.pago === false ? 'pending' : 'paid',
    note: item.observacao || undefined,
  };
}

function buildSnapshot(items: LancamentoItem[], monthKey: string): LancamentoSnapshot {
  const pendingItems = items.filter((item) => item.status === 'pending');
  const paidCount = items.filter((item) => item.status === 'paid').length;
  const pendingAmount = pendingItems.reduce((total, item) => total + Math.abs(item.amount), 0);

  const focusTip =
    pendingItems.length > 0
      ? {
          title: 'Existem pendencias pedindo sua atencao',
          description: 'O app priorizou o que ainda nao foi pago ou recebido para facilitar sua decisao.',
        }
      : {
          title: 'Tudo em dia por enquanto',
          description: 'Como nao ha pendencias, a lista fica livre para consulta e ajustes.',
        };

  return {
    monthLabel: getMonthLabel(monthKey),
    helperTitle: 'Tudo que entrou e saiu sem a bagunca de uma tabela fria',
    helperDescription:
      'O usuario enxerga primeiro o que esta pendente, depois encontra qualquer lancamento com poucos toques.',
    totalItems: items.length,
    pendingCount: pendingItems.length,
    pendingAmount,
    paidCount,
    quickActions: buildQuickActions(),
    focusTip,
    items,
  };
}

function getErrorMessage(error: unknown) {
  if (!error) {
    return null;
  }

  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'Sua sessao nao foi aceita pelo backend. Entre novamente para carregar os lancamentos.';
    }

    if (error.status === 403) {
      return 'O backend bloqueou a operacao. Revise a permissao ou os limites da sua conta.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API nao foi configurada para este aparelho.';
    }

    return error.message;
  }

  return 'Nao foi possivel carregar os lancamentos agora.';
}

function mapRemoteOptions(data: RemoteOptionsResponse): {
  accounts: FormAccountOption[];
  categories: FormCategoryOption[];
} {
  return {
    accounts: data.contas.map((account) => ({
      id: String(account.id),
      name: account.nome,
      subtitle: 'Conta conectada',
    })),
    categories: [
      ...data.categorias.receitas.map((category) => ({
        id: String(category.id),
        label: category.nome,
        icon: pickCategoryIcon(category.nome),
        type: 'income' as const,
      })),
      ...data.categorias.despesas.map((category) => ({
        id: String(category.id),
        label: category.nome,
        icon: pickCategoryIcon(category.nome),
        type: 'expense' as const,
      })),
    ],
  };
}

class LancamentosRepository {
  async getSnapshot(monthKey = getCurrentMonthKey()): Promise<RepositoryResult<LancamentoSnapshot>> {
    try {
      const remoteItems = await httpClient.get<RemoteLancamento[]>('api/lancamentos', {
        month: monthKey,
      });

      return {
        source: 'remote',
        data: buildSnapshot(remoteItems.map(mapRemoteItem), monthKey),
      };
    } catch (error) {
      return {
        source: 'remote',
        data: createEmptyLancamentosSnapshot(monthKey),
        message: getErrorMessage(error),
      };
    }
  }

  async getFormOptions(): Promise<
    RepositoryResult<{ accounts: FormAccountOption[]; categories: FormCategoryOption[] }>
  > {
    try {
      const remoteOptions = await httpClient.get<RemoteOptionsResponse>('api/options');
      return {
        source: 'remote',
        data: mapRemoteOptions(remoteOptions),
      };
    } catch (error) {
      return {
        source: 'remote',
        data: {
          accounts: [],
          categories: [],
        },
        message: getErrorMessage(error),
      };
    }
  }

  async createLancamento(
    input: CreateLancamentoInput
  ): Promise<RepositoryResult<{ message: string }>> {
    if (input.mode === 'transfer') {
      await httpClient.post(
        'api/transfers',
        {
          conta_id: Number(input.accountId),
          conta_id_destino: Number(input.destinationAccountId),
          valor: input.amount,
          data: input.date,
          descricao: input.description,
          observacao: input.note ?? '',
        },
        undefined,
        { csrf: true }
      );

      return {
        source: 'remote',
        data: {
          message: 'Transferencia salva no backend.',
        },
      };
    }

    try {
      await httpClient.post(
        'api/lancamentos',
        {
          tipo: input.mode === 'income' ? 'receita' : 'despesa',
          data: input.date,
          valor: input.amount,
          descricao: input.description,
          observacao: input.note ?? '',
          conta_id: Number(input.accountId),
          categoria_id: input.categoryId ? Number(input.categoryId) : null,
          pago: input.isPaid,
        },
        undefined,
        { csrf: true }
      );

      return {
        source: 'remote',
        data: {
          message: 'Lancamento salvo no backend.',
        },
      };
    } catch (error) {
      if (error instanceof HttpClientError) {
        throw error;
      }

      throw new HttpClientError('Nao foi possivel salvar o lancamento agora.');
    }
  }
}

export const lancamentosRepository = new LancamentosRepository();
