import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import { ContaInstitutionOption } from '@/src/features/contas/data/conta-form-options';
import { ContaItem, ContasSnapshot } from '@/src/features/contas/types';

type RemoteInstitution = {
  nome?: string;
  tipo?: string;
  cor_primaria?: string;
};

type RemoteConta = {
  id: number;
  nome: string;
  tipo_conta?: string | null;
  saldoAtual?: number | null;
  saldo_atual?: number | null;
  saldoInicial?: number | null;
  saldo_inicial?: number | null;
  entradasTotal?: number | null;
  entradas_total?: number | null;
  saidasTotal?: number | null;
  saidas_total?: number | null;
  ativo?: boolean;
  cor?: string | null;
  instituicao?: string | null;
  instituicao_financeira?: RemoteInstitution | null;
  instituicaoFinanceira?: RemoteInstitution | null;
};

type RemoteInstitutionOption = {
  id: number;
  nome: string;
  tipo?: string;
  cor_primaria?: string;
};

type RepositoryResult<T> = {
  source: 'remote';
  data: T;
  message?: string | null;
};

type ContaRecord = {
  id: string;
  name: string;
  institutionName: string;
  accountType: string;
  balance: number;
  inflow: number;
  outflow: number;
  accentColor: string;
};

export type CreateContaInput = {
  name: string;
  accountType:
  | 'conta_corrente'
  | 'conta_poupanca'
  | 'carteira_digital'
  | 'dinheiro';
  institutionId?: string;
  institutionName?: string;
  initialBalance: number;
};

const RESERVE_TYPES = new Set(['conta_poupanca', 'conta_investimento']);

const TYPE_LABELS: Record<string, string> = {
  conta_corrente: 'Corrente',
  conta_poupanca: 'Poupanca',
  conta_investimento: 'Reserva',
  carteira_digital: 'Carteira',
  dinheiro: 'Dinheiro',
};

const TYPE_ICONS: Record<string, string> = {
  conta_corrente: 'wallet-outline',
  conta_poupanca: 'shield-checkmark-outline',
  conta_investimento: 'shield-checkmark-outline',
  carteira_digital: 'phone-portrait-outline',
  dinheiro: 'cash-outline',
};

const TYPE_COLORS: Record<string, string> = {
  conta_corrente: '#1f4f82',
  conta_poupanca: '#1f9d63',
  conta_investimento: '#1f9d63',
  carteira_digital: '#c86518',
  dinheiro: '#94771d',
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

function sumValues(items: ContaRecord[], key: 'balance' | 'inflow' | 'outflow') {
  return items.reduce((total, item) => total + item[key], 0);
}

function pickInstitution(account: RemoteConta) {
  return account.instituicao_financeira ?? account.instituicaoFinanceira ?? null;
}

function getTypeLabel(accountType: string) {
  return TYPE_LABELS[accountType] ?? 'Conta';
}

function getTypeIcon(accountType: string) {
  return TYPE_ICONS[accountType] ?? 'wallet-outline';
}

function getTypeColor(accountType: string, accentColor?: string | null) {
  return accentColor || TYPE_COLORS[accountType] || TYPE_COLORS.conta_corrente;
}

function mapRemoteConta(account: RemoteConta): ContaRecord {
  const institution = pickInstitution(account);
  const accountType = account.tipo_conta || 'conta_corrente';

  return {
    id: String(account.id),
    name: account.nome,
    institutionName: institution?.nome || account.instituicao || 'Instituicao nao definida',
    accountType,
    balance: Number(
      account.saldoAtual ?? account.saldo_atual ?? account.saldoInicial ?? account.saldo_inicial ?? 0
    ),
    inflow: Number(account.entradasTotal ?? account.entradas_total ?? 0),
    outflow: Number(account.saidasTotal ?? account.saidas_total ?? 0),
    accentColor: getTypeColor(accountType, institution?.cor_primaria ?? account.cor),
  };
}

function buildContaNote(account: ContaRecord) {
  if (account.balance < 0) {
    return 'Saldo negativo. Vale revisar as saídas desta conta antes de continuar usando ela no dia a dia.';
  }

  if (RESERVE_TYPES.has(account.accountType)) {
    return 'Separada do giro do mês para o patrimônio não se misturar com o dinheiro disponível.';
  }

  if (account.outflow > account.inflow && account.outflow > 0) {
    return 'Saiu mais do que entrou neste mês. O card deixa isso visível sem precisar abrir outra tela.';
  }

  return 'Boa para entradas, pagamentos e transferências frequentes.';
}

function decorateAccounts(accounts: ContaRecord[], primaryAccountId: string | null) {
  return accounts.map<ContaItem>((account) => ({
    id: account.id,
    name: account.name,
    institutionName: account.institutionName,
    typeLabel: getTypeLabel(account.accountType),
    icon: getTypeIcon(account.accountType),
    accentColor: account.accentColor,
    balance: account.balance,
    inflow: account.inflow,
    outflow: account.outflow,
    note: buildContaNote(account),
    isPrimary: account.id === primaryAccountId,
  }));
}

function buildQuickActions() {
  return [
    {
      id: 'income' as const,
      label: 'Registrar entrada',
      description: 'Marque o dinheiro que acabou de cair.',
      icon: 'add-circle-outline',
    },
    {
      id: 'expense' as const,
      label: 'Registrar gasto',
      description: 'Lance uma saída sem procurar menu escondido.',
      icon: 'remove-circle-outline',
    },
    {
      id: 'transfer' as const,
      label: 'Transferir valor',
      description: 'Mova dinheiro entre contas com poucos toques.',
      icon: 'swap-horizontal-outline',
    },
  ];
}

function buildEmptySnapshot(monthKey: string, archivedCount = 0): ContasSnapshot {
  return {
    monthLabel: getMonthLabel(monthKey),
    helperTitle: 'Seu dinheiro vai ficar organizado por lugar, não por tentativa e erro',
    helperDescription:
      'Quando a primeira conta entrar, o usuário enxerga logo o que pode usar agora e o que fica separado como reserva.',
    totalBalance: 0,
    everydayBalance: 0,
    reserveBalance: 0,
    activeCount: 0,
    archivedCount,
    negativeCount: 0,
    focus: {
      title: 'Sua primeira conta vai aparecer aqui',
      description:
        'Comece por uma conta usada no dia a dia. Depois o app separa a reserva sem confundir o saldo.',
      amount: 0,
      supportText:
        'Enquanto nao houver conta cadastrada, a interface continua explicita sobre o proximo passo.',
      tone: 'warning',
    },
    guidedSteps: [
      {
        id: '1',
        title: 'Cadastre a primeira conta',
        description: 'Ela vira o ponto de partida para saldo, transferencias e organização financeira.',
      },
      {
        id: '2',
        title: 'Registre a primeira entrada',
        description: 'Assim o usuario ja entende onde o dinheiro entrou e quanto esta disponível.',
      },
      {
        id: '3',
        title: 'Separe reserva quando fizer sentido',
        description: 'Quando vier poupança ou outra reserva, ela vai para outro bloco.',
      },
    ],
    quickActions: buildQuickActions(),
    groups: [
      {
        id: 'everyday',
        title: 'Dia a dia',
        description: 'Contas usadas para pagar, receber e movimentar o mês.',
        totalBalance: 0,
        emptyTitle: 'Nenhuma conta para o dia a dia ainda',
        emptyDescription:
          'Quando a primeira conta entrar, ela aparece aqui com saldo, entradas e saídas do período.',
        accounts: [],
      },
      {
        id: 'reserve',
        title: 'Reserva e objetivos',
        description: 'Poupança e outras reservas ficam separadas do dinheiro que gira no mês.',
        totalBalance: 0,
        emptyTitle: 'Ainda não existe reserva separada',
        emptyDescription:
          'Quando o usuário criar uma reserva, ela aparece aqui sem misturar com o caixa do mês.',
        accounts: [],
      },
    ],
  };
}

export function createEmptyContasSnapshot(monthKey = getCurrentMonthKey()) {
  return buildEmptySnapshot(monthKey);
}

function buildSnapshot(
  activeAccounts: ContaRecord[],
  archivedCount: number,
  monthKey: string
): ContasSnapshot {
  if (activeAccounts.length === 0) {
    return buildEmptySnapshot(monthKey, archivedCount);
  }

  const everydayAccounts = activeAccounts
    .filter((account) => !RESERVE_TYPES.has(account.accountType))
    .sort((left, right) => right.balance - left.balance);
  const reserveAccounts = activeAccounts
    .filter((account) => RESERVE_TYPES.has(account.accountType))
    .sort((left, right) => right.balance - left.balance);

  const primarySource =
    everydayAccounts[0] ??
    activeAccounts.slice().sort((left, right) => right.balance - left.balance)[0];
  const negativeAccounts = activeAccounts
    .filter((account) => account.balance < 0)
    .sort((left, right) => left.balance - right.balance);
  const focusAccount = negativeAccounts[0] ?? primarySource;
  const primaryAccountId = primarySource?.id ?? null;

  const focus =
    negativeAccounts.length > 0
      ? {
        title: 'Revise primeiro a conta que saiu do trilho',
        description:
          'Quando alguma conta fica negativa, ela sobe para o topo porque é a informação que mais ajuda o usuário a agir rápido.',
        amount: focusAccount.balance,
        supportText: `${focusAccount.name} está pedindo ajuste antes de novas saídas.`,
        tone: 'negative' as const,
      }
      : {
        title: 'Sua conta principal está fácil de encontrar',
        description:
          'O app destaca a conta mais forte do dia a dia para o usuário bater o olho e entender onde o dinheiro está entrando.',
        amount: focusAccount.balance,
        supportText: `${focusAccount.name} lidera o saldo disponível neste momento.`,
        tone: 'positive' as const,
      };

  return {
    monthLabel: getMonthLabel(monthKey),
    helperTitle: 'Onde o dinheiro está guardado fica claro logo de cara',
    helperDescription:
      'Primeiro aparecem as contas usadas no dia a dia. Reservas ficam separadas para não confundir o saldo disponível com o patrimônio.',
    totalBalance: sumValues(activeAccounts, 'balance'),
    everydayBalance: sumValues(everydayAccounts, 'balance'),
    reserveBalance: sumValues(reserveAccounts, 'balance'),
    activeCount: activeAccounts.length,
    archivedCount,
    negativeCount: negativeAccounts.length,
    focus,
    guidedSteps: [
      {
        id: '1',
        title: 'Veja a conta usada no dia a dia',
        description:
          'Ela precisa aparecer primeiro porque é onde entram e saem os valores mais frequentes.',
        done: everydayAccounts.length > 0,
      },
      {
        id: '2',
        title: 'Revise saldo negativo antes de lançar mais saídas',
        description:
          'Quando alguma conta fica no vermelho, ela sobe na prioridade para o usuário não se perder.',
        done: negativeAccounts.length === 0,
      },
      {
        id: '3',
        title: 'Mantenha reserva separada do giro do mês',
        description:
          'Poupança e outras reservas ficam em outro bloco para o dinheiro disponível não parecer maior do que realmente está.',
        done: reserveAccounts.length > 0,
      },
    ],
    quickActions: buildQuickActions(),
    groups: [
      {
        id: 'everyday',
        title: 'Dia a dia',
        description: 'Contas usadas para receber, pagar e movimentar o dinheiro do mês.',
        totalBalance: sumValues(everydayAccounts, 'balance'),
        emptyTitle: 'Nenhuma conta para o dia a dia ainda',
        emptyDescription:
          'Quando a primeira conta entrar, ela aparece aqui com saldo, entradas e saídas do período.',
        accounts: decorateAccounts(everydayAccounts, primaryAccountId),
      },
      {
        id: 'reserve',
        title: 'Reserva e objetivos',
        description: 'Contas separadas para guardar dinheiro e acompanhar patrimônio com calma.',
        totalBalance: sumValues(reserveAccounts, 'balance'),
        emptyTitle: 'Ainda não existe reserva separada',
        emptyDescription:
          'Quando o usuário criar uma reserva, ela aparece aqui sem misturar com o caixa do mês.',
        accounts: decorateAccounts(reserveAccounts, primaryAccountId),
      },
    ],
  };
}

function getErrorMessage(error: unknown) {
  if (!error) {
    return null;
  }

  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'Sua sessão não foi aceita pelo backend. Entre novamente para carregar as contas.';
    }

    if (error.status === 403) {
      return 'O backend bloqueou a operação de contas. Revise a permissão ou os limites da sua conta.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API não foi configurada para este aparelho.';
    }

    return error.message;
  }

  return 'ão foi possível carregar as contas agora.';
}

function mapRemoteInstitution(option: RemoteInstitutionOption): ContaInstitutionOption {
  return {
    id: String(option.id),
    name: option.nome,
    type: option.tipo || 'instituicao',
    accentColor: option.cor_primaria || '#1f4f82',
  };
}

class ContasRepository {
  async getSnapshot(monthKey = getCurrentMonthKey()): Promise<RepositoryResult<ContasSnapshot>> {
    try {
      const [activeAccounts, archivedAccounts] = await Promise.all([
        httpClient.get<RemoteConta[]>('api/v1/contas', {
          with_balances: 1,
          only_active: 1,
          month: monthKey,
        }),
        httpClient.get<RemoteConta[]>('api/v1/contas', {
          archived: 1,
          with_balances: 1,
          month: monthKey,
        }),
      ]);

      return {
        source: 'remote',
        data: buildSnapshot(activeAccounts.map(mapRemoteConta), archivedAccounts.length, monthKey),
      };
    } catch (error) {
      return {
        source: 'remote',
        data: buildEmptySnapshot(monthKey),
        message: getErrorMessage(error),
      };
    }
  }

  async getFormOptions(): Promise<RepositoryResult<{ institutions: ContaInstitutionOption[] }>> {
    try {
      const remoteInstitutions = await httpClient.get<RemoteInstitutionOption[]>(
        'api/v1/contas/instituicoes'
      );

      return {
        source: 'remote',
        data: {
          institutions: remoteInstitutions.map(mapRemoteInstitution),
        },
      };
    } catch (error) {
      return {
        source: 'remote',
        data: {
          institutions: [],
        },
        message: getErrorMessage(error),
      };
    }
  }

  async createConta(input: CreateContaInput): Promise<RepositoryResult<{ message: string }>> {
    const payload = {
      nome: input.name.trim(),
      tipo_conta: input.accountType,
      instituicao_financeira_id: input.institutionId ? Number(input.institutionId) : null,
      instituicao: input.institutionName?.trim() || null,
      saldo_inicial: input.initialBalance,
      moeda: 'BRL',
      ativo: true,
    };

    try {
      await httpClient.post('api/v1/contas', payload, undefined, { csrf: true });

      return {
        source: 'remote',
        data: {
          message: 'Conta salva no backend.',
        },
      };
    } catch (error) {
      if (error instanceof HttpClientError) {
        throw error;
      }

      throw new HttpClientError('Não foi possível salvar a conta agora.');
    }
  }
}

export const contasRepository = new ContasRepository();
