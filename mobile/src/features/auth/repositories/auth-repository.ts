import { appConfig } from '@/src/lib/config/app-config';
import {
  clearCsrfTokenCache,
  httpClient,
  HttpClientError,
} from '@/src/lib/api/http-client';
import {
  AuthLoginInput,
  AuthSessionSnapshot,
  AuthSessionSource,
} from '@/src/features/auth/types';

type RemoteSessionStatus = {
  authenticated?: boolean;
  expired?: boolean;
  remainingTime?: number;
  showWarning?: boolean;
  canRenew?: boolean;
  warningThreshold?: number;
  sessionLifetime?: number;
  userName?: string;
  isRemembered?: boolean;
};

function createBaseSnapshot(
  overrides: Partial<AuthSessionSnapshot>
): AuthSessionSnapshot {
  return {
    status: 'signed_out',
    source: 'remote',
    userName: null,
    helperMessage: 'Entre para acessar seu dinheiro com o mesmo fluxo simples do app.',
    warningMessage: null,
    isRemembered: false,
    remainingTime: 0,
    allowPreview: appConfig.usePreviewFallback,
    ...overrides,
  };
}

function createSignedInSnapshot(
  status: RemoteSessionStatus,
  helperMessage: string | null = null
): AuthSessionSnapshot {
  const remainingTime = Number(status.remainingTime ?? 0);
  const minutesLeft = Math.max(1, Math.ceil(remainingTime / 60));

  return createBaseSnapshot({
    status: 'signed_in',
    source: 'remote',
    userName: status.userName?.trim() || 'Usuario',
    helperMessage,
    warningMessage:
      status.showWarning && remainingTime > 0
        ? `Sua sessao vence em cerca de ${minutesLeft} minuto${minutesLeft === 1 ? '' : 's'}.`
        : null,
    isRemembered: Boolean(status.isRemembered),
    remainingTime,
  });
}

export function createSignedOutSnapshot(
  helperMessage: string | null,
  source: AuthSessionSource = 'remote'
): AuthSessionSnapshot {
  return createBaseSnapshot({
    status: 'signed_out',
    source,
    helperMessage,
  });
}

export function createPreviewSnapshot(): AuthSessionSnapshot {
  return createBaseSnapshot({
    status: 'preview',
    source: 'preview',
    userName: 'Visitante',
    helperMessage:
      'você entrou na demonstracao local. Os dados reais voltam assim que fizer login.',
  });
}

function getSessionFailureMessage(error: unknown) {
  if (error instanceof HttpClientError) {
    if (error.status === 401) {
      return 'Entre para acessar seus dados financeiros.';
    }

    if (error.code === 'NO_BASE_URL') {
      return 'A URL da API ainda nao foi configurada. você pode testar a demo enquanto isso.';
    }

    return error.message;
  }

  return 'Nao foi possivel falar com a API agora. A demo continua disponivel para nao travar o app.';
}

function getSessionFailureSource(error: unknown): AuthSessionSource {
  if (error instanceof HttpClientError && error.status === 401) {
    return 'remote';
  }

  return appConfig.usePreviewFallback ? 'preview' : 'offline';
}

class AuthRepository {
  async getSessionSnapshot(): Promise<AuthSessionSnapshot> {
    try {
      const session = await httpClient.get<RemoteSessionStatus>('api/session/status');

      if (session.authenticated) {
        return createSignedInSnapshot(session);
      }

      if (session.canRenew) {
        await httpClient.post('api/session/renew');

        const renewedSession = await httpClient.get<RemoteSessionStatus>('api/session/status');
        if (renewedSession.authenticated) {
          return createSignedInSnapshot(
            renewedSession,
            'Sua sessao foi retomada automaticamente neste aparelho.'
          );
        }
      }

      clearCsrfTokenCache();

      return createSignedOutSnapshot(
        session.expired
          ? 'Sua sessao expirou. Entre de novo para continuar de onde parou.'
          : 'Entre para abrir seu painel financeiro.'
      );
    } catch (error) {
      clearCsrfTokenCache();

      return createSignedOutSnapshot(
        getSessionFailureMessage(error),
        getSessionFailureSource(error)
      );
    }
  }

  async login(input: AuthLoginInput): Promise<AuthSessionSnapshot> {
    clearCsrfTokenCache();

    await httpClient.post(
      'login/entrar',
      {
        email: input.email.trim(),
        password: input.password,
        remember: input.remember ? '1' : '0',
      },
      undefined,
      {
        csrf: true,
        tokenId: 'login_form',
      }
    );

    clearCsrfTokenCache();

    const snapshot = await this.getSessionSnapshot();
    if (snapshot.status !== 'signed_in') {
      throw new HttpClientError(
        'O login terminou, mas a sessao nao ficou ativa no app.',
        0,
        'SESSION_NOT_ACTIVE'
      );
    }

    return snapshot;
  }

  async logout() {
    try {
      await httpClient.get('logout');
    } finally {
      clearCsrfTokenCache();
    }
  }
}

export const authRepository = new AuthRepository();
