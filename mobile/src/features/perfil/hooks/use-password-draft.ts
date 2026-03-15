import { useEffect, useState } from 'react';

import { HttpClientError } from '@/src/lib/api/http-client';
import { perfilRepository, mapPasswordErrors } from '@/src/features/perfil/repositories/perfil-repository';
import { PasswordFormErrors, PerfilFeedback } from '@/src/features/perfil/types';

function passwordChecks(password: string) {
  return {
    length: password.length >= 8,
    lower: /[a-z]/.test(password),
    upper: /[A-Z]/.test(password),
    number: /[0-9]/.test(password),
    special: /[^a-zA-Z0-9]/.test(password),
  };
}

export function usePasswordDraft() {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [errors, setErrors] = useState<PasswordFormErrors>({});
  const [feedback, setFeedback] = useState<PerfilFeedback | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [dataSource, setDataSource] = useState<'preview' | 'remote'>('preview');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    async function loadContext() {
      const result = await perfilRepository.getFormData();

      if (!isMounted) {
        return;
      }

      setDataSource(result.source);
      setSourceMessage(result.message ?? null);
    }

    void loadContext();

    return () => {
      isMounted = false;
    };
  }, []);

  const checks = passwordChecks(newPassword);

  function validate() {
    const nextErrors: PasswordFormErrors = {};

    if (!currentPassword) {
      nextErrors.currentPassword = 'Digite sua senha atual.';
    }

    if (!newPassword) {
      nextErrors.newPassword = 'Digite a nova senha.';
    } else if (!Object.values(checks).every(Boolean)) {
      nextErrors.newPassword = 'A nova senha ainda nao cumpre todos os requisitos.';
    }

    if (!confirmPassword) {
      nextErrors.confirmPassword = 'Confirme a nova senha.';
    } else if (confirmPassword !== newPassword) {
      nextErrors.confirmPassword = 'As senhas nao coincidem.';
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  }

  async function submit() {
    setFeedback(null);

    if (!validate()) {
      return false;
    }

    setIsSubmitting(true);

    try {
      const result = await perfilRepository.changePassword(
        currentPassword,
        newPassword,
        confirmPassword
      );
      setFeedback({
        tone: 'success',
        message: result.message,
      });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setErrors({});
      return true;
    } catch (error) {
      if (error instanceof HttpClientError) {
        setErrors(mapPasswordErrors(error));
        setFeedback({
          tone: 'error',
          message: error.message,
        });
      } else {
        setFeedback({
          tone: 'error',
          message: 'Nao foi possivel alterar a senha agora.',
        });
      }

      return false;
    } finally {
      setIsSubmitting(false);
    }
  }

  return {
    currentPassword,
    newPassword,
    confirmPassword,
    checks,
    errors,
    feedback,
    isSubmitting,
    dataSource,
    sourceMessage,
    setCurrentPassword,
    setNewPassword,
    setConfirmPassword,
    submit,
  };
}
