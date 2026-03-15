import { useState } from 'react';

import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';
import { AuthFormFeedback } from '@/src/features/auth/types';
import { HttpClientError } from '@/src/lib/api/http-client';

type LoginErrors = {
  email?: string;
  password?: string;
};

function readFirstDetail(details: unknown, key: string) {
  if (!details || typeof details !== 'object') {
    return null;
  }

  const value = (details as Record<string, unknown>)[key];
  if (typeof value === 'string' && value.trim()) {
    return value;
  }

  if (Array.isArray(value)) {
    const first = value.find(
      (item): item is string => typeof item === 'string' && item.trim().length > 0
    );
    return first ?? null;
  }

  return null;
}

function mapLoginError(error: unknown) {
  const errors: LoginErrors = {};

  if (error instanceof HttpClientError) {
    errors.email = readFirstDetail(error.details, 'email') ?? undefined;
    errors.password = readFirstDetail(error.details, 'password') ?? undefined;

    if (readFirstDetail(error.details, 'require_captcha')) {
      return {
        errors,
        message:
          'O backend pediu verificacao extra. Espere um pouco ou conclua o login pelo navegador para liberar novas tentativas.',
      };
    }

    if (readFirstDetail(error.details, 'email_not_verified')) {
      return {
        errors,
        message:
          'Seu e-mail ainda nao foi verificado. Abra o navegador e confirme a conta antes de entrar no app.',
      };
    }

    if (error.status === 429) {
      return {
        errors,
        message: 'Muitas tentativas. Aguarde um minuto e tente de novo.',
      };
    }

    return {
      errors,
      message: error.message || 'Nao foi possivel entrar agora.',
    };
  }

  return {
    errors,
    message: 'Nao foi possivel entrar agora.',
  };
}

export function useLoginDraft() {
  const { signIn } = useAuthSession();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);
  const [errors, setErrors] = useState<LoginErrors>({});
  const [feedback, setFeedback] = useState<AuthFormFeedback | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function submit() {
    const nextErrors: LoginErrors = {};

    if (!email.trim()) {
      nextErrors.email = 'Informe o e-mail usado na conta.';
    } else if (!email.includes('@')) {
      nextErrors.email = 'Digite um e-mail valido.';
    }

    if (!password) {
      nextErrors.password = 'Digite sua senha para continuar.';
    }

    if (nextErrors.email || nextErrors.password) {
      setErrors(nextErrors);
      setFeedback({
        tone: 'error',
        message: 'Revise os campos destacados antes de entrar.',
      });
      return false;
    }

    setIsSubmitting(true);
    setErrors({});
    setFeedback(null);

    try {
      await signIn({
        email,
        password,
        remember,
      });

      setFeedback({
        tone: 'success',
        message: 'Entrada liberada. Carregando seu app.',
      });
      return true;
    } catch (error) {
      const mapped = mapLoginError(error);
      setErrors(mapped.errors);
      setFeedback({
        tone: 'error',
        message: mapped.message,
      });
      return false;
    } finally {
      setIsSubmitting(false);
    }
  }

  return {
    email,
    password,
    remember,
    errors,
    feedback,
    isSubmitting,
    setEmail,
    setPassword,
    setRemember,
    clearFeedback: () => setFeedback(null),
    submit,
  };
}
