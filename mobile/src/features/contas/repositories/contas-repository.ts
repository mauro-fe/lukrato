import { appConfig } from '@/src/lib/config/app-config';
import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import {
  contaInstitutionOptions,
  ContaInstitutionOption,
} from '@/src/features/contas/data/conta-form-options';
import { contasPreview } from '@/src/features/contas/data/contas-preview';
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
  source: 'preview' | 'remote';
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
    | 'conta_investimento'
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
  conta_investimento: 'Investimento',
  carteira_digital: 'Carteira',
  dinheiro: 'Dinheiro',
};

const TYPE_ICONS: Record<string, string> = {
  conta_corrente: 'wallet-outline',
  conta_poupanca: 'shield-checkmark-outline',
  conta_investimento: 'trending-up-outline',
  carteira_digital: 'phone-portrait-outline',
  dinheiro: 'cash-outline',
};

const TYPE_COLORS: Record<string, string> = {
  conta_corrente: '#1f4f82',
  conta_poupanca: '#1f9d63',
  conta_investimento: '#3a79d9',
  carteira_digital: '#c86518',
  dinheiro: '#94771d',
};

let previewSnapshotState = clonePreviewSnapshot();

function clonePreviewSnapshot() {
  return JSON.parse(JSON.stringify(contasPreview)) as ContasSnapshot;
}

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
    return 'Saldo negativo. Vale revisar as saidas desta conta antes de continuar usando ela no dia a dia.';
  }

  if (RESERVE_TYPES.has(account.accountType)) {
    return 'Separada do giro do mes para o patrimonio nao se misturar com o dinheiro disponivel.';
  }

  if (account.outflow > account.inflow && account.outflow > 0) {
    return 'Saiu mais do que entrou neste mes. O card deixa isso visivel sem precisar abrir outra tela.';
  }

  return 'Boa para entradas, pagamentos e transferencias frequentes.';
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
      description: 'Lance uma saida sem procurar menu escondido.',
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
    helperTitle: 'Seu dinheiro vai ficar organizado por lugar, nao por tentativa e erro',
    helperDescription:
      'Quando a primeira conta entrar, o usuario enxerga logo o que pode usar agora e o que fica separado como reserva.',
    totalBalance: 0,
    everydayBalance: 0,
    reserveBalance: 0,
    activeCount: 0,
    archivedCount,
    negativeCount: 0,
    focus: {
      title: 'Sua primeira conta vai aparecer aqui',
      description:
        'Comece por uma conta usada no dia a dia. Depois o app separa reserva e investimento sem confundir o saldo.',
      amount: 0,
      supportText:
        'Enquanto nao houver conta cadastrada, a interface continua explicita sobre o proximo passo.',
      tone: 'warning',
    },
    guidedSteps: [
      {
        id: '1',
        title: 'Cadastre a primeira conta',
        description: 'Ela vira o ponto de partida para saldo, transferencias e organizacao financeira.',
      },
      {
        id: '2',
        title: 'Registre a primeira entrada',
        description: 'Assim o usuario ja entende onde o dinheiro entrou e quanto esta disponivel.',
      },
      {
        id: '3',
        title: 'Separe reserva quando fizer sentido',
        description: 'Quando vier poupanca ou investimento, eles vao para outro bloco.',
      },
    ],
    quickActions: buildQuickActions(),
    groups: [
      {
        id: 'everyday',
        title: 'Dia a dia',
        description: 'Contas usadas para pagar, receber e movimentar o mes.',
        totalBalance: 0,
        emptyTitle: 'Nenhuma conta para o dia a dia ainda',
        emptyDescription:
          'Quando a primeira conta entrar, ela aparece aqui com saldo, entradas e saidas do periodo.',
        accounts: [],
      },
      {
        id: 'reserve',
        title: 'Reserva e objetivos',
        description: 'Poupanca e investimento ficam separados do dinheiro que gira no mes.',
        totalBalance: 0,
        emptyTitle: 'Ainda nao existe reserva separada',
        emptyDescription:
          'Quando o usuario criar poupanca ou investimento, eles aparecem aqui sem misturar com o caixa do mes.',
        accounts: [],
      },
    ],
  };
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
            'Quando alguma conta fica negativa, ela sobe para o topo porque e a informacao que mais ajuda o usuario a agir rapido.',
          amount: focusAccount.balance,
          supportText: `${focusAccount.name} esta pedindo ajuste antes de novas saidas.`,
          tone: 'negative' as const,
        }
      : {
          title: 'Sua conta principal esta facil de encontrar',
          description:
            'O app destaca a conta mais forte do dia a dia para o usuario bater o olho e entender onde o dinheiro esta entrando.',
          amount: focusAccount.balance,
          supportText: `${focusAccount.name} lidera o saldo disponivel neste momento.`,
          tone: 'positive' as const,
        };

  return {
    monthLabel: getMonthLabel(monthKey),
    helperTitle: 'Onde o dinheiro esta guardado fica claro logo de cara',
    helperDescription:
      'Primeiro aparecem as contas usadas no dia a dia. Reserva e investimento ficam separados para nao confundir o saldo disponivel com o patrimonio.',
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
          'Ela precisa aparecer primeiro porque e onde entram e saem os valores mais frequentes.',
        done: everydayAccounts.length > 0,
      },
      {
        id: '2',
        title: 'Revise saldo negativo antes de lancar mais saidas',
        description:
          'Quando alguma conta fica no vermelho, ela sobe na prioridade para o usuario nao se perder.',
        done: negativeAccounts.length === 0,
      },
      {
        id: '3',
        title: 'Mantenha reserva separada do giro do mes',
        description:
          'Poupanca e investimento ficam em outro bloco para o dinheiro disponivel nao parecer maior do que realmente esta.',
        done: reserveAccounts.length > 0,
      },
    ],
    quickActions: buildQuickActions(),
    groups: [
      {
        id: 'everyday',
        title: 'Dia a dia',
        description: 'Contas usadas para receber, pagar e movimentar o dinheiro do mes.',
        totalBalance: sumValues(everydayAccounts, 'balance'),
        emptyTitle: 'Nenhuma conta para o dia a dia ainda',
        emptyDescription:
          'Quando a primeira conta entrar, ela aparece aqui com saldo, entradas e saidas do periodo.',
        accounts: decorateAccounts(everydayAccounts, primaryAccountId),
      },
      {
        id: 'reserve',
        title: 'Reserva e objetivos',
        description: 'Contas separadas para guardar dinheiro e acompanhar patrimonio com calma.',
        totalBalance: sumValues(reserveAccounts, 'balance'),
        emptyTitle: 'Ainda nao existe reserva separada',
        emptyDescription:
          'Quando o usuario criar poupanca ou investimento, eles aparecem aqui sem misturar com o caixa do mes.',
        accounts: decorateAccounts(reserveAccounts, primaryAccountId),
      },
    ],
  };
}

function fallbackMessage(error: unknown) {
  if (!error) {
    return null;
  }

  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'A API de contas respondeu sem sessao autenticada. Mantive o preview para voce continuar evoluindo o app.';
    }

    if (error.status === 403) {
      return 'A API de contas exigiu protecao adicional. Mantive o preview para nao travar a interface.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API ainda nao foi configurada. O preview segue ativo.';
    }

    return error.message;
  }

  return 'Nao foi possivel usar a API de contas agora. O preview continua ativo.';
}

function mapRemoteInstitution(option: RemoteInstitutionOption): ContaInstitutionOption {
  return {
    id: String(option.id),
    name: option.nome,
    type: option.tipo || 'instituicao',
    accentColor: option.cor_primaria || '#1f4f82',
  };
}

function getPreviewInstitutionName(input: CreateContaInput) {
  if (input.institutionName?.trim()) {
    return input.institutionName.trim();
  }

  if (input.accountType === 'dinheiro') {
    return 'Fora do banco';
  }

  return 'Instituicao nao definida';
}

function appendPreviewAccount(
  snapshot: ContasSnapshot,
  input: CreateContaInput
): ContasSnapshot {
  const nextSnapshot = clonePreviewSnapshot();
  Object.assign(nextSnapshot, snapshot);
  nextSnapshot.groups = snapshot.groups.map((group) => ({
    ...group,
    accounts: [...group.accounts],
  }));

  const targetGroupId = RESERVE_TYPES.has(input.accountType) ? 'reserve' : 'everyday';
  const targetGroup = nextSnapshot.groups.find((group) => group.id === targetGroupId);

  if (!targetGroup) {
    return snapshot;
  }

  const institutionName = getPreviewInstitutionName(input);
  const newAccount: ContaItem = {
    id: `preview-${Date.now()}`,
    name: input.name.trim(),
    institutionName,
    typeLabel: getTypeLabel(input.accountType),
    icon: getTypeIcon(input.accountType),
    accentColor: getTypeColor(input.accountType),
    balance: input.initialBalance,
    inflow: input.initialBalance > 0 ? input.initialBalance : 0,
    outflow: input.initialBalance < 0 ? Math.abs(input.initialBalance) : 0,
    note:
      input.initialBalance < 0
        ? 'Conta criada com saldo negativo. O app vai destacar isso para o usuario agir rapido.'
        : RESERVE_TYPES.has(input.accountType)
          ? 'Conta criada para separar patrimonio e objetivos do dinheiro que gira no mes.'
          : 'Conta criada para organizar o dinheiro do dia a dia sem esconder nada.',
  };

  targetGroup.accounts = [newAccount, ...targetGroup.accounts];
  targetGroup.totalBalance += input.initialBalance;
  nextSnapshot.totalBalance += input.initialBalance;
  nextSnapshot.activeCount += 1;

  if (targetGroupId === 'reserve') {
    nextSnapshot.reserveBalance += input.initialBalance;
  } else {
    nextSnapshot.everydayBalance += input.initialBalance;
  }

  if (input.initialBalance < 0) {
    nextSnapshot.negativeCount += 1;
    nextSnapshot.focus = {
      title: 'A nova conta nasceu pedindo atencao',
      description:
        'Como o saldo inicial entrou negativo, o app destacou isso de imediato para o usuario nao descobrir tarde demais.',
      amount: input.initialBalance,
      supportText: `${input.name.trim()} comecou abaixo de zero.`,
      tone: 'negative',
    };
  } else if (nextSnapshot.activeCount === 1) {
    nextSnapshot.focus = {
      title: 'Sua primeira conta ja ficou visivel no topo',
      description:
        'Agora o saldo desta conta passa a ser a referencia principal para o usuario entender o que esta disponivel.',
      amount: input.initialBalance,
      supportText: `${input.name.trim()} ja esta pronta para receber movimentacoes.`,
      tone: 'positive',
    };
  }

  return nextSnapshot;
}

class ContasRepository {
  async getSnapshot(monthKey = getCurrentMonthKey()): Promise<RepositoryResult<ContasSnapshot>> {
    try {
      const [activeAccounts, archivedAccounts] = await Promise.all([
        httpClient.get<RemoteConta[]>('api/contas', {
          with_balances: 1,
          only_active: 1,
          month: monthKey,
        }),
        httpClient.get<RemoteConta[]>('api/contas', {
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
        source: 'preview',
        data: previewSnapshotState,
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
      };
    }
  }

  async getFormOptions(): Promise<RepositoryResult<{ institutions: ContaInstitutionOption[] }>> {
    try {
      const remoteInstitutions = await httpClient.get<RemoteInstitutionOption[]>(
        'api/contas/instituicoes'
      );

      return {
        source: 'remote',
        data: {
          institutions: remoteInstitutions.map(mapRemoteInstitution),
        },
      };
    } catch (error) {
      return {
        source: 'preview',
        data: {
          institutions: contaInstitutionOptions,
        },
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
      };
    }
  }

  async createConta(
    input: CreateContaInput
  ): Promise<RepositoryResult<{ message: string }>> {
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
      await httpClient.post('api/contas', payload, undefined, { csrf: true });

      return {
        source: 'remote',
        data: {
          message: 'Conta salva no backend.',
        },
      };
    } catch (error) {
      previewSnapshotState = appendPreviewAccount(previewSnapshotState, input);

      return {
        source: 'preview',
        data: {
          message:
            'Conta criada no preview. A interface ja mostra esse novo lugar na lista de contas.',
        },
        message: appConfig.usePreviewFallback ? fallbackMessage(error) : null,
      };
    }
  }
}

export const contasRepository = new ContasRepository();
