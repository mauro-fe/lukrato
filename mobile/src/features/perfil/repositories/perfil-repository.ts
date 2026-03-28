import { httpClient, HttpClientError } from '@/src/lib/api/http-client';
import {
  PasswordFormErrors,
  PerfilContactChannel,
  PerfilFormData,
  PerfilSnapshot,
  PerfilSexOption,
  TelegramLinkDraft,
} from '@/src/features/perfil/types';

type RemoteProfilePayload = {
  user: RemoteUser;
};

type RemoteUser = {
  nome?: string;
  email?: string;
  avatar?: string;
  support_code?: string;
  data_nascimento?: string;
  sexo?: string;
  cpf?: string;
  telefone?: string;
  endereco?: {
    cep?: string;
    rua?: string;
    numero?: string;
    complemento?: string;
    bairro?: string;
    cidade?: string;
    estado?: string;
  };
};

type RemoteReferralStats = {
  referral_code?: string;
  referral_link?: string;
  total_indicacoes?: number;
  indicacoes_completadas?: number;
  indicacoes_pendentes?: number;
  dias_ganhos?: number;
  indicacoes_mes?: number;
  limite_mensal?: number;
  indicacoes_restantes?: number;
};

type RemoteTelegramStatus = {
  linked?: boolean;
  username?: string | null;
};

type RemoteTelegramLink = {
  code: string;
  bot_url: string;
  expires_in?: number;
};

type SupportResponse = {
  message?: string;
};

type UpdateProfileResponse = {
  message?: string;
  user?: RemoteUser;
};

type ChangePasswordResponse = {
  message?: string;
};

type RepositoryResult<T> = {
  source: 'remote';
  data: T;
  message?: string | null;
};

export const emptyPerfilFormData: PerfilFormData = {
  name: '',
  email: '',
  cpf: '',
  phone: '',
  sex: '',
  birthDate: '',
  addressCep: '',
  addressStreet: '',
  addressNumber: '',
  addressComplement: '',
  addressNeighborhood: '',
  addressCity: '',
  addressState: '',
};

function buildEmptyReferral(): PerfilSnapshot['referral'] {
  return {
    code: 'Sem codigo ainda',
    link: '',
    rewardDays: 0,
    totalInvites: 0,
    completedInvites: 0,
    pendingInvites: 0,
    monthlyUsed: 0,
    monthlyLimit: 0,
    monthlyRemaining: 0,
  };
}

function buildEmptyTelegram(): PerfilSnapshot['telegram'] {
  return {
    linked: false,
    username: null,
    helperText:
      'Conecte o Telegram apenas se fizer sentido para registrar coisas sem abrir o app inteiro.',
  };
}

function normalizeValue(value: string | null | undefined, fallback = 'Nao informado') {
  const trimmed = String(value ?? '').trim();
  return trimmed || fallback;
}

function normalizeSex(value?: string): PerfilSexOption {
  const normalized = String(value ?? '').trim().toUpperCase();

  if (
    normalized === 'M' ||
    normalized === 'F' ||
    normalized === 'O' ||
    normalized === 'NB' ||
    normalized === 'N'
  ) {
    return normalized;
  }

  return '';
}

function formatBirthDate(value?: string) {
  if (!value) {
    return 'Nao informado';
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return normalizeValue(value);
  }

  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(parsed);
}

function buildAddressLabel(address?: RemoteUser['endereco']) {
  if (!address) {
    return 'Nao informado';
  }

  const parts = [
    [address.rua, address.numero].filter(Boolean).join(', '),
    address.complemento,
    address.bairro,
    [address.cidade, address.estado].filter(Boolean).join(' - '),
    address.cep ? `CEP ${address.cep}` : '',
  ]
    .map((part) => String(part ?? '').trim())
    .filter(Boolean);

  return parts.length ? parts.join(', ') : 'Nao informado';
}

function getInitials(name?: string) {
  const normalized = String(name ?? '').trim();

  if (!normalized) {
    return 'LU';
  }

  const parts = normalized.split(/\s+/).slice(0, 2);
  return parts.map((part) => part.charAt(0).toUpperCase()).join('');
}

function getCompletion(user: RemoteUser) {
  const checks = [
    Boolean(String(user.nome ?? '').trim()),
    Boolean(String(user.email ?? '').trim()),
    Boolean(String(user.avatar ?? '').trim()),
    Boolean(String(user.telefone ?? '').trim()),
    Boolean(String(user.data_nascimento ?? '').trim()),
    Boolean(String(user.cpf ?? '').trim()),
    buildAddressLabel(user.endereco) !== 'Nao informado',
  ];

  const completed = checks.filter(Boolean).length;
  const score = Math.round((completed / checks.length) * 100);

  if (score >= 100) {
    return { score, label: 'Perfil redondo' };
  }

  if (score >= 75) {
    return { score, label: 'Perfil quase completo' };
  }

  if (score >= 50) {
    return { score, label: 'Perfil no caminho certo' };
  }

  return { score, label: 'Perfil ainda incompleto' };
}

function buildFocus(
  completion: ReturnType<typeof getCompletion>,
  telegram: PerfilSnapshot['telegram'],
  referral: PerfilSnapshot['referral'],
  supportCode: string
): PerfilSnapshot['focus'] {
  if (completion.score < 75) {
    return {
      title: 'Seu perfil ainda precisa de alguns detalhes',
      description:
        'Completar os dados reduz duvida quando você precisar de ajuda ou quiser ligar algum recurso mais avancado.',
      valueLabel: `${completion.score}% completo`,
      supportText: 'Telefone, avatar e endereco costumam ser os pontos que faltam primeiro.',
      tone: 'warning',
    };
  }

  if (!telegram.linked) {
    return {
      title: 'Telegram segue opcional, mas facil de achar',
      description:
        'A integracao fica aqui para o usuario decidir com calma se quer usar esse atalho fora do app.',
      valueLabel: 'Telegram desligado',
      supportText: 'Nada fica escondido: se quiser conectar, o bot e o codigo aparecem na mesma tela.',
      tone: 'warning',
    };
  }

  if (referral.pendingInvites > 0) {
    return {
      title: 'você tem indicacoes esperando fechar o ciclo',
      description:
        'O perfil mostra o progresso do programa de indicacao sem misturar isso com configuracoes sensiveis.',
      valueLabel: `${referral.pendingInvites} pendente(s)`,
      supportText: 'Assim fica facil entender se a recompensa ja virou dias de PRO ou se ainda esta em andamento.',
      tone: 'positive',
    };
  }

  return {
    title: 'Seu codigo de suporte esta pronto para uso',
    description:
      'Esse e um dado simples, mas importante. Por isso ele aparece cedo e sem precisar abrir outra aba.',
    valueLabel: supportCode,
    supportText: 'Quando precisar falar com a equipe, ter esse codigo por perto acelera bastante.',
    tone: 'positive',
  };
}

function mapRemoteUserToFormData(user: RemoteUser): PerfilFormData {
  return {
    name: String(user.nome ?? '').trim(),
    email: String(user.email ?? '').trim(),
    cpf: String(user.cpf ?? '').trim(),
    phone: String(user.telefone ?? '').trim(),
    sex: normalizeSex(user.sexo),
    birthDate: String(user.data_nascimento ?? '').trim(),
    addressCep: String(user.endereco?.cep ?? '').trim(),
    addressStreet: String(user.endereco?.rua ?? '').trim(),
    addressNumber: String(user.endereco?.numero ?? '').trim(),
    addressComplement: String(user.endereco?.complemento ?? '').trim(),
    addressNeighborhood: String(user.endereco?.bairro ?? '').trim(),
    addressCity: String(user.endereco?.cidade ?? '').trim(),
    addressState: String(user.endereco?.estado ?? '').trim(),
  };
}

export function createEmptyPerfilSnapshot(): PerfilSnapshot {
  return buildSnapshot({}, buildEmptyReferral(), buildEmptyTelegram());
}

function buildSnapshot(
  user: RemoteUser,
  referral: PerfilSnapshot['referral'],
  telegram: PerfilSnapshot['telegram'],
  identityExtras?: { avatarUrl?: string; supportCode?: string }
): PerfilSnapshot {
  const completion = getCompletion(user);
  const supportCode = normalizeValue(identityExtras?.supportCode ?? user.support_code, 'Sem codigo');
  const avatarUrl = String(identityExtras?.avatarUrl ?? user.avatar ?? '').trim();

  return {
    helperTitle: 'Perfil claro, ajuda perto e nada escondido',
    helperDescription:
      'O usuario encontra seus dados, suporte, indicacoes e integracoes sem precisar adivinhar em qual menu tocar.',
    identity: {
      name: normalizeValue(user.nome, 'Usuario Lukrato'),
      email: normalizeValue(user.email, 'Sem email'),
      avatarUrl,
      initials: getInitials(user.nome),
      supportCode,
      completionScore: completion.score,
      completionLabel: completion.label,
    },
    focus: buildFocus(completion, telegram, referral, supportCode),
    guidedSteps: [
      {
        id: '1',
        title: 'Confira os dados que a conta usa de verdade',
        description: 'Manter telefone, endereco e email corretos evita idas e voltas quando algo precisar ser validado.',
        done: completion.score >= 75,
      },
      {
        id: '2',
        title: 'Guarde o codigo de suporte onde você lembra',
        description: 'Ele acelera ajuda humana sem obrigar o usuario a explicar tudo do zero.',
        done: supportCode !== 'Sem codigo',
      },
      {
        id: '3',
        title: 'Ative integracao so se ela encurtar sua rotina',
        description: 'O app mostra o caminho, mas deixa claro que Telegram e opcional.',
        done: telegram.linked,
      },
    ],
    details: [
      {
        id: 'phone',
        label: 'Telefone',
        value: normalizeValue(user.telefone),
        helper: 'Canal util para suporte e confirmacoes.',
      },
      {
        id: 'birth',
        label: 'Nascimento',
        value: formatBirthDate(user.data_nascimento),
        helper: 'Ajuda a manter o cadastro coerente.',
      },
      {
        id: 'cpf',
        label: 'CPF',
        value: normalizeValue(user.cpf),
        helper: 'Fica visivel so para o usuario conferir o essencial.',
      },
      {
        id: 'sex',
        label: 'Sexo',
        value: normalizeValue(user.sexo),
      },
      {
        id: 'address',
        label: 'Endereco',
        value: buildAddressLabel(user.endereco),
        helper: 'Importante para pagamento, suporte e dados da conta.',
      },
    ],
    referral,
    telegram,
    support: {
      recommendedChannel: String(user.telefone ?? '').trim() ? 'whatsapp' : 'email',
      hint: 'Se surgir duvida, escreva com suas palavras. O app manda a mensagem sem o usuario cacar email ou formulario externo.',
    },
  };
}

function getErrorMessage(error: unknown) {
  if (!error) {
    return null;
  }

  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'Sua sessao nao foi aceita pelo backend. Entre novamente para carregar o perfil.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API nao foi configurada para este aparelho.';
    }

    return error.message;
  }

  return 'Nao foi possivel carregar o perfil agora.';
}

function buildProfileFormData(formData: PerfilFormData) {
  const body = new FormData();

  body.append('nome', formData.name);
  body.append('email', formData.email);
  body.append('cpf', formData.cpf);
  body.append('telefone', formData.phone);
  body.append('sexo', formData.sex);
  body.append('data_nascimento', formData.birthDate);
  body.append('endereco[cep]', formData.addressCep);
  body.append('endereco[rua]', formData.addressStreet);
  body.append('endereco[numero]', formData.addressNumber);
  body.append('endereco[complemento]', formData.addressComplement);
  body.append('endereco[bairro]', formData.addressNeighborhood);
  body.append('endereco[cidade]', formData.addressCity);
  body.append('endereco[estado]', formData.addressState);

  return body;
}

function buildPasswordFormData(currentPassword: string, newPassword: string, confirmPassword: string) {
  const body = new FormData();
  body.append('senha_atual', currentPassword);
  body.append('nova_senha', newPassword);
  body.append('conf_senha', confirmPassword);
  return body;
}

function isValidationDetails(details: unknown): details is Record<string, unknown> {
  return Boolean(details && typeof details === 'object' && !Array.isArray(details));
}

function mapPasswordErrors(error: HttpClientError): PasswordFormErrors {
  if (!isValidationDetails(error.details)) {
    return {};
  }

  const details = error.details;
  const nextErrors: PasswordFormErrors = {};

  if (typeof details.senha_atual === 'string') {
    nextErrors.currentPassword = details.senha_atual;
  }

  if (typeof details.nova_senha === 'string' || typeof details.senha === 'string') {
    nextErrors.newPassword = String(details.nova_senha ?? details.senha);
  }

  if (typeof details.conf_senha === 'string') {
    nextErrors.confirmPassword = details.conf_senha;
  }

  return nextErrors;
}

class PerfilRepository {
  async getSnapshot(): Promise<RepositoryResult<PerfilSnapshot>> {
    try {
      const profilePayload = await httpClient.get<RemoteProfilePayload>('api/perfil');
      const optionalWarnings: string[] = [];

      const [referralPayload, telegramPayload] = await Promise.all([
        httpClient.get<RemoteReferralStats>('api/referral/stats').catch(() => {
          optionalWarnings.push('Indicacoes indisponiveis no momento.');
          return null;
        }),
        httpClient.get<RemoteTelegramStatus>('api/telegram/status').catch(() => {
          optionalWarnings.push('Status do Telegram indisponivel no momento.');
          return null;
        }),
      ]);

      const referral = referralPayload
        ? {
          code: normalizeValue(referralPayload.referral_code, 'Sem codigo ainda'),
          link: String(referralPayload.referral_link ?? '').trim(),
          rewardDays: Number(referralPayload.dias_ganhos ?? 0),
          totalInvites: Number(referralPayload.total_indicacoes ?? 0),
          completedInvites: Number(referralPayload.indicacoes_completadas ?? 0),
          pendingInvites: Number(referralPayload.indicacoes_pendentes ?? 0),
          monthlyUsed: Number(referralPayload.indicacoes_mes ?? 0),
          monthlyLimit: Number(referralPayload.limite_mensal ?? 0),
          monthlyRemaining: Number(referralPayload.indicacoes_restantes ?? 0),
        }
        : buildEmptyReferral();

      const telegram = telegramPayload
        ? {
          linked: Boolean(telegramPayload.linked),
          username: telegramPayload.username ?? null,
          helperText: telegramPayload.linked
            ? 'Telegram vinculado. O usuario consegue ver isso sem medo de mexer em configuracao sensivel.'
            : 'Conecte o Telegram apenas se fizer sentido para registrar coisas sem abrir o app inteiro.',
        }
        : buildEmptyTelegram();

      return {
        source: 'remote',
        data: buildSnapshot(profilePayload.user, referral, telegram),
        message: optionalWarnings.length ? optionalWarnings.join(' ') : null,
      };
    } catch (error) {
      return {
        source: 'remote',
        data: createEmptyPerfilSnapshot(),
        message: getErrorMessage(error),
      };
    }
  }

  async getFormData(): Promise<RepositoryResult<{ profile: PerfilFormData }>> {
    try {
      const profilePayload = await httpClient.get<RemoteProfilePayload>('api/perfil');

      return {
        source: 'remote',
        data: {
          profile: mapRemoteUserToFormData(profilePayload.user),
        },
      };
    } catch (error) {
      return {
        source: 'remote',
        data: {
          profile: { ...emptyPerfilFormData },
        },
        message: getErrorMessage(error),
      };
    }
  }

  async updateProfile(formData: PerfilFormData) {
    const payload = await httpClient.postForm<UpdateProfileResponse>(
      'api/perfil',
      buildProfileFormData(formData),
      undefined,
      { csrf: true }
    );

    return {
      source: 'remote' as const,
      data: {
        message: payload.message || 'Perfil atualizado com sucesso.',
      },
    };
  }

  async changePassword(currentPassword: string, newPassword: string, confirmPassword: string) {
    try {
      const payload = await httpClient.postForm<ChangePasswordResponse>(
        'api/perfil/senha',
        buildPasswordFormData(currentPassword, newPassword, confirmPassword),
        undefined,
        { csrf: true }
      );

      return {
        message: payload.message || 'Senha alterada com sucesso.',
      };
    } catch (error) {
      if (error instanceof HttpClientError) {
        throw error;
      }

      throw new HttpClientError(
        'Nao foi possivel alterar a senha agora. Tente novamente em alguns instantes.'
      );
    }
  }

  async sendSupportMessage(message: string, replyVia: PerfilContactChannel) {
    const payload = await httpClient.post<SupportResponse>('api/suporte/enviar', {
      message,
      retorno: replyVia,
    });

    return {
      message: payload.message || 'Mensagem enviada com sucesso.',
    };
  }

  async requestTelegramLink(): Promise<TelegramLinkDraft> {
    const payload = await httpClient.post<RemoteTelegramLink>(
      'api/telegram/link',
      undefined,
      undefined,
      { csrf: true }
    );

    return {
      code: payload.code,
      botUrl: payload.bot_url,
      expiresIn: payload.expires_in ?? 600,
    };
  }

  async unlinkTelegram() {
    await httpClient.post('api/telegram/unlink', undefined, undefined, { csrf: true });
  }
}

export const perfilRepository = new PerfilRepository();
export { mapPasswordErrors };
