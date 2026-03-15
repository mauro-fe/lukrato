import { appConfig } from '@/src/lib/config/app-config';
import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import {
  formAccountOptions,
  formCategoryOptions,
  FormAccountOption,
  FormCategoryOption,
} from '@/src/features/lancamentos/data/lancamento-form-options';
import { lancamentosPreview } from '@/src/features/lancamentos/data/lancamentos-preview';
import {
  LancamentoEntryMode,
  LancamentoItem,
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
  source: 'preview' | 'remote';
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
  investimentos: 'trending-up-outline',
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

function mapRemoteItem(item: RemoteLancamento): LancamentoItem {
  return {
    id: String(item.id),
    title: item.descricao || 'Lancamento',
    category: item.categoria_nome || item.categoria || 'Sem categoria',
    account: item.conta_nome || item.conta || 'Conta',
    date: item.data,
    amount: Number(item.valor) * (item.tipo === 'despesa' ? -1 : 1),
    type: item.tipo === 'receita' ? 'income' : 'expense',
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
    quickActions: lancamentosPreview.quickActions,
    focusTip,
    items,
  };
}

function fallbackMessage(error: unknown) {
  if (!error) {
    return null;
  }

  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'A API respondeu sem sessao autenticada. Mantive o preview para voce continuar evoluindo o app.';
    }

    if (error.status === 403) {
      return 'A API exigiu protecao adicional. Mantive o preview para nao travar o fluxo.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'Defina a URL da API no app quando quiser sair do preview.';
    }

    return error.message;
  }

  return 'Nao foi possivel usar a API agora. O preview segue ativo.';
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

      const snapshot = buildSnapshot(remoteItems.map(mapRemoteItem), monthKey);
      return {
        source: 'remote',
        data: snapshot,
      };
    } catch (error) {
      return {
        source: 'preview',
        data: lancamentosPreview,
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
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
        source: 'preview',
        data: {
          accounts: formAccountOptions,
          categories: formCategoryOptions,
        },
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
      };
    }
  }

  async createLancamento(input: CreateLancamentoInput): Promise<
    RepositoryResult<{ message: string }>
  > {
    if (input.mode === 'transfer') {
      return {
        source: 'preview',
        data: {
          message: 'Transferencia pronta na interface. A integracao real entra junto com autenticacao mobile.',
        },
        message: 'A criacao real de transferencia ainda depende da camada de autenticacao mobile.',
      };
    }

    try {
      await httpClient.post('api/lancamentos', {
        tipo: input.mode === 'income' ? 'receita' : 'despesa',
        data: input.date,
        valor: input.amount,
        descricao: input.description,
        observacao: input.note ?? '',
        conta_id: Number(input.accountId),
        categoria_id: input.categoryId ? Number(input.categoryId) : null,
        pago: input.isPaid,
      }, undefined, { csrf: true });

      return {
        source: 'remote',
        data: {
          message: 'Lancamento salvo no backend.',
        },
      };
    } catch (error) {
      return {
        source: 'preview',
        data: {
          message: 'Lancamento pronto na interface. A API real ainda depende da sessao mobile.',
        },
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
      };
    }
  }
}

export const lancamentosRepository = new LancamentosRepository();
