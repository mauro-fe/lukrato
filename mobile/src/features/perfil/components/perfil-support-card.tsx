import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { PerfilContactChannel, PerfilFeedback } from '@/src/features/perfil/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type PerfilSupportCardProps = {
  hint: string;
  replyVia: PerfilContactChannel;
  message: string;
  feedback: PerfilFeedback | null;
  errorMessage: string | null;
  isSending: boolean;
  onChangeReplyVia: (value: PerfilContactChannel) => void;
  onChangeMessage: (value: string) => void;
  onSend: () => void;
};

export function PerfilSupportCard({
  hint,
  replyVia,
  message,
  feedback,
  errorMessage,
  isSending,
  onChangeReplyVia,
  onChangeMessage,
  onSend,
}: PerfilSupportCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Ajuda sem tirar o usuario do fluxo</Text>
      <Text style={styles.description}>{hint}</Text>

      <View style={styles.channelRow}>
        <ChannelButton
          label="Responder por email"
          helper="Segue o canal mais universal"
          selected={replyVia === 'email'}
          onPress={() => onChangeReplyVia('email')}
        />
        <ChannelButton
          label="Responder por WhatsApp"
          helper="Melhor quando a conta ja tem telefone"
          selected={replyVia === 'whatsapp'}
          onPress={() => onChangeReplyVia('whatsapp')}
        />
      </View>

      <TextInput
        value={message}
        onChangeText={onChangeMessage}
        placeholder="Explique sua duvida do jeito que voce contaria para uma pessoa."
        placeholderTextColor={tokens.colors.textMuted}
        multiline
        textAlignVertical="top"
        style={styles.messageInput}
      />

      {errorMessage ? <Text style={styles.errorText}>{errorMessage}</Text> : null}

      {feedback ? (
        <View
          style={[
            styles.feedbackBanner,
            feedback.tone === 'success' ? styles.feedbackSuccess : styles.feedbackError,
          ]}>
          <Text
            style={[
              styles.feedbackText,
              feedback.tone === 'success' ? styles.feedbackSuccessText : styles.feedbackErrorText,
            ]}>
            {feedback.message}
          </Text>
        </View>
      ) : null}

      <Pressable style={[styles.sendButton, isSending && styles.sendButtonDisabled]} onPress={onSend}>
        <Text style={styles.sendButtonText}>{isSending ? 'Enviando...' : 'Enviar mensagem'}</Text>
      </Pressable>
    </AppCard>
  );
}

function ChannelButton({
  label,
  helper,
  selected,
  onPress,
}: {
  label: string;
  helper: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable style={[styles.channelButton, selected && styles.channelButtonActive]} onPress={onPress}>
      <Text style={[styles.channelLabel, selected && styles.channelLabelActive]}>{label}</Text>
      <Text style={[styles.channelHelper, selected && styles.channelHelperActive]}>{helper}</Text>
    </Pressable>
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
  channelRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  channelButton: {
    flex: 1,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
    gap: 6,
  },
  channelButtonActive: {
    backgroundColor: '#fff3e8',
    borderColor: '#f3c28a',
  },
  channelLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  channelLabelActive: {
    color: tokens.colors.primaryStrong,
  },
  channelHelper: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  channelHelperActive: {
    color: tokens.colors.primaryStrong,
  },
  messageInput: {
    minHeight: 140,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    borderRadius: tokens.radius.md,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 14,
    color: tokens.colors.text,
    marginTop: tokens.spacing.md,
    ...tokens.typography.body,
  },
  errorText: {
    color: tokens.colors.danger,
    marginTop: tokens.spacing.sm,
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
  sendButton: {
    marginTop: tokens.spacing.md,
    alignSelf: 'flex-start',
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.primary,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 12,
  },
  sendButtonDisabled: {
    opacity: 0.7,
  },
  sendButtonText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.small,
  },
});
