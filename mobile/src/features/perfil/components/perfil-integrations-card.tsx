import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PerfilFeedback, PerfilSnapshot, TelegramLinkDraft } from '@/src/features/perfil/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type PerfilIntegrationsCardProps = {
  telegram: PerfilSnapshot['telegram'];
  telegramLinkDraft: TelegramLinkDraft | null;
  telegramFeedback: PerfilFeedback | null;
  isUpdatingTelegram: boolean;
  onRequestLink: () => void;
  onUnlink: () => void;
  onOpenBot: () => void;
};

export function PerfilIntegrationsCard({
  telegram,
  telegramLinkDraft,
  telegramFeedback,
  isUpdatingTelegram,
  onRequestLink,
  onUnlink,
  onOpenBot,
}: PerfilIntegrationsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Integracoes sem cara de labirinto</Text>
      <Text style={styles.description}>
        O status do Telegram aparece claro, com o proximo passo visivel na mesma area.
      </Text>

      <View style={styles.statusRow}>
        <View style={[styles.statusIcon, telegram.linked ? styles.statusIconLinked : styles.statusIconIdle]}>
          <Ionicons
            name={telegram.linked ? 'checkmark-circle-outline' : 'paper-plane-outline'}
            size={18}
            color={telegram.linked ? tokens.colors.success : tokens.colors.warning}
          />
        </View>
        <View style={styles.statusCopy}>
          <Text style={styles.statusTitle}>
            {telegram.linked ? 'Telegram vinculado' : 'Telegram ainda nao vinculado'}
          </Text>
          <Text style={styles.statusDescription}>{telegram.helperText}</Text>
          {telegram.username ? <Text style={styles.statusMeta}>Identificador: {telegram.username}</Text> : null}
        </View>
      </View>

      {telegramFeedback ? (
        <View
          style={[
            styles.feedbackBanner,
            telegramFeedback.tone === 'success' ? styles.feedbackSuccess : styles.feedbackError,
          ]}>
          <Text
            style={[
              styles.feedbackText,
              telegramFeedback.tone === 'success' ? styles.feedbackSuccessText : styles.feedbackErrorText,
            ]}>
            {telegramFeedback.message}
          </Text>
        </View>
      ) : null}

      {telegramLinkDraft ? (
        <View style={styles.codeBox}>
          <Text style={styles.codeLabel}>Codigo gerado agora</Text>
          <Text style={styles.codeValue} selectable>
            {telegramLinkDraft.code}
          </Text>
          <Text style={styles.codeHint}>
            Ele expira em cerca de {Math.max(1, Math.round(telegramLinkDraft.expiresIn / 60))} minuto(s).
          </Text>
        </View>
      ) : null}

      <View style={styles.actions}>
        {telegram.linked ? (
          <Pressable
            style={[styles.secondaryButton, isUpdatingTelegram && styles.buttonDisabled]}
            onPress={onUnlink}>
            <Text style={styles.secondaryButtonText}>
              {isUpdatingTelegram ? 'Desvinculando...' : 'Desvincular'}
            </Text>
          </Pressable>
        ) : (
          <Pressable
            style={[styles.primaryButton, isUpdatingTelegram && styles.buttonDisabled]}
            onPress={onRequestLink}>
            <Text style={styles.primaryButtonText}>
              {isUpdatingTelegram ? 'Gerando...' : 'Gerar codigo'}
            </Text>
          </Pressable>
        )}

        {telegramLinkDraft ? (
          <Pressable style={styles.secondaryButton} onPress={onOpenBot}>
            <Text style={styles.secondaryButtonText}>Abrir bot</Text>
          </Pressable>
        ) : null}
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    marginTop: 4,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.body,
  },
  statusRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  statusIcon: {
    width: 38,
    height: 38,
    borderRadius: tokens.radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statusIconLinked: {
    backgroundColor: '#eef8f2',
  },
  statusIconIdle: {
    backgroundColor: '#fff3e8',
  },
  statusCopy: {
    flex: 1,
    gap: 2,
  },
  statusTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  statusDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  statusMeta: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  feedbackBanner: {
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    padding: tokens.spacing.md,
    marginTop: tokens.spacing.md,
  },
  feedbackSuccess: {
    backgroundColor: '#ecfdf3',
    borderColor: '#bde7cf',
  },
  feedbackError: {
    backgroundColor: '#fff1ef',
    borderColor: '#f3c7c1',
  },
  feedbackText: {
    ...tokens.typography.small,
  },
  feedbackSuccessText: {
    color: tokens.colors.success,
  },
  feedbackErrorText: {
    color: tokens.colors.danger,
  },
  codeBox: {
    marginTop: tokens.spacing.md,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    padding: tokens.spacing.md,
    gap: 4,
  },
  codeLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  codeValue: {
    color: tokens.colors.secondary,
    ...tokens.typography.mono,
  },
  codeHint: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  actions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.sm,
    marginTop: tokens.spacing.md,
  },
  primaryButton: {
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.primary,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 12,
  },
  primaryButtonText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.small,
  },
  secondaryButton: {
    borderRadius: tokens.radius.pill,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 12,
  },
  secondaryButtonText: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  buttonDisabled: {
    opacity: 0.7,
  },
});
