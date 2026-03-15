import { useCallback, useState } from 'react';
import { useFocusEffect } from 'expo-router';

import { perfilPreview } from '@/src/features/perfil/data/perfil-preview';
import { perfilRepository } from '@/src/features/perfil/repositories/perfil-repository';
import { PerfilContactChannel, PerfilFeedback, TelegramLinkDraft } from '@/src/features/perfil/types';

function getActionErrorMessage(error: unknown, fallback: string) {
  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}

export function usePerfilOverview() {
  const [snapshot, setSnapshot] = useState(perfilPreview);
  const [source, setSource] = useState<'preview' | 'remote'>('preview');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isSendingSupport, setIsSendingSupport] = useState(false);
  const [supportFeedback, setSupportFeedback] = useState<PerfilFeedback | null>(null);
  const [isUpdatingTelegram, setIsUpdatingTelegram] = useState(false);
  const [telegramFeedback, setTelegramFeedback] = useState<PerfilFeedback | null>(null);
  const [telegramLinkDraft, setTelegramLinkDraft] = useState<TelegramLinkDraft | null>(null);

  const loadSnapshot = useCallback(async (refreshing = false) => {
    if (refreshing) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }

    try {
      const result = await perfilRepository.getSnapshot();
      setSnapshot(result.data);
      setSource(result.source);
      setSourceMessage(result.message ?? null);
    } finally {
      if (refreshing) {
        setIsRefreshing(false);
      } else {
        setIsLoading(false);
      }
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void loadSnapshot(false);
    }, [loadSnapshot])
  );

  async function sendSupportMessage(message: string, replyVia: PerfilContactChannel) {
    setIsSendingSupport(true);
    setSupportFeedback(null);

    try {
      const result = await perfilRepository.sendSupportMessage(message, replyVia);
      setSupportFeedback({
        tone: 'success',
        message: result.message,
      });
      return true;
    } catch (error) {
      setSupportFeedback({
        tone: 'error',
        message: getActionErrorMessage(
          error,
          'Nao foi possivel enviar sua mensagem agora. Tente novamente em alguns instantes.'
        ),
      });
      return false;
    } finally {
      setIsSendingSupport(false);
    }
  }

  async function requestTelegramLink() {
    setIsUpdatingTelegram(true);
    setTelegramFeedback(null);

    try {
      const draft = await perfilRepository.requestTelegramLink();
      setTelegramLinkDraft(draft);
      setTelegramFeedback({
        tone: 'success',
        message: 'Codigo gerado. Agora basta abrir o bot e enviar esse codigo.',
      });
      return draft;
    } catch (error) {
      setTelegramFeedback({
        tone: 'error',
        message: getActionErrorMessage(
          error,
          'Nao foi possivel gerar o codigo do Telegram agora.'
        ),
      });
      return null;
    } finally {
      setIsUpdatingTelegram(false);
    }
  }

  async function unlinkTelegram() {
    setIsUpdatingTelegram(true);
    setTelegramFeedback(null);

    try {
      await perfilRepository.unlinkTelegram();
      setTelegramLinkDraft(null);
      setSnapshot((current) => ({
        ...current,
        telegram: {
          linked: false,
          username: null,
          helperText:
            'Conecte o Telegram apenas se fizer sentido para registrar coisas sem abrir o app inteiro.',
        },
      }));
      setTelegramFeedback({
        tone: 'success',
        message: 'Telegram desvinculado da conta.',
      });
      return true;
    } catch (error) {
      setTelegramFeedback({
        tone: 'error',
        message: getActionErrorMessage(
          error,
          'Nao foi possivel desvincular o Telegram agora.'
        ),
      });
      return false;
    } finally {
      setIsUpdatingTelegram(false);
    }
  }

  return {
    snapshot,
    source,
    sourceMessage,
    isLoading,
    isRefreshing,
    refresh: () => loadSnapshot(true),
    isSendingSupport,
    supportFeedback,
    clearSupportFeedback: () => setSupportFeedback(null),
    sendSupportMessage,
    isUpdatingTelegram,
    telegramFeedback,
    clearTelegramFeedback: () => setTelegramFeedback(null),
    telegramLinkDraft,
    requestTelegramLink,
    unlinkTelegram,
  };
}
