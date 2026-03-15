import { Ionicons } from '@expo/vector-icons';
import { useEffect, useState } from 'react';
import { useRouter } from 'expo-router';
import { Linking, Pressable, RefreshControl, ScrollView, Share, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';
import { PerfilDetailsCard } from '@/src/features/perfil/components/perfil-details-card';
import { PerfilFocusCard } from '@/src/features/perfil/components/perfil-focus-card';
import { PerfilGuidedStepsCard } from '@/src/features/perfil/components/perfil-guided-steps-card';
import { PerfilHeroCard } from '@/src/features/perfil/components/perfil-hero-card';
import { PerfilIntegrationsCard } from '@/src/features/perfil/components/perfil-integrations-card';
import { PerfilReferralCard } from '@/src/features/perfil/components/perfil-referral-card';
import { PerfilSupportCard } from '@/src/features/perfil/components/perfil-support-card';
import { usePerfilOverview } from '@/src/features/perfil/hooks/use-perfil-overview';
import { PerfilContactChannel } from '@/src/features/perfil/types';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { SectionHeading } from '@/src/shared/ui/section-heading';
import { tokens } from '@/src/theme/tokens';

export function PerfilScreen() {
  const router = useRouter();
  const { session, signOut, isSigningOut } = useAuthSession();
  const {
    snapshot,
    source,
    sourceMessage,
    isLoading,
    isRefreshing,
    refresh,
    isSendingSupport,
    supportFeedback,
    sendSupportMessage,
    isUpdatingTelegram,
    telegramFeedback,
    telegramLinkDraft,
    requestTelegramLink,
    unlinkTelegram,
  } = usePerfilOverview();
  const [replyVia, setReplyVia] = useState<PerfilContactChannel>(snapshot.support.recommendedChannel);
  const [supportMessage, setSupportMessage] = useState('');
  const [supportError, setSupportError] = useState<string | null>(null);

  useEffect(() => {
    setReplyVia(snapshot.support.recommendedChannel);
  }, [snapshot.support.recommendedChannel]);

  async function handleShareReferral() {
    await Share.share({
      message: `Use meu link do Lukrato e ganhe dias gratis de PRO: ${snapshot.referral.link}`,
    });
  }

  async function handleOpenTelegramBot() {
    if (!telegramLinkDraft?.botUrl) {
      return;
    }

    await Linking.openURL(telegramLinkDraft.botUrl);
  }

  async function handleSendSupport() {
    const trimmed = supportMessage.trim();

    if (trimmed.length < 10) {
      setSupportError('Explique um pouco mais. A mensagem precisa ter pelo menos 10 caracteres.');
      return;
    }

    setSupportError(null);
    const success = await sendSupportMessage(trimmed, replyVia);

    if (success) {
      setSupportMessage('');
    }
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.backgroundTop} />
      <View style={styles.backgroundBottom} />

      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={tokens.colors.primary} />}>
        <View style={styles.header}>
          <View style={styles.pill}>
            <Text style={styles.pillText}>Perfil</Text>
          </View>
          <SectionHeading
            eyebrow={snapshot.identity.name}
            title={snapshot.helperTitle}
            description={snapshot.helperDescription}
          />
        </View>

        <DataSourceBanner
          source={source}
          fallbackMessage={sourceMessage ?? (isLoading ? 'Tentando conectar com a API do Lukrato...' : null)}
        />

        {session.warningMessage ? (
          <View style={styles.sessionWarningBanner}>
            <Ionicons name="time-outline" size={18} color={tokens.colors.warning} />
            <Text style={styles.sessionWarningText}>{session.warningMessage}</Text>
          </View>
        ) : null}

        <PerfilHeroCard
          name={snapshot.identity.name}
          email={snapshot.identity.email}
          avatarUrl={snapshot.identity.avatarUrl}
          initials={snapshot.identity.initials}
          supportCode={snapshot.identity.supportCode}
          completionScore={snapshot.identity.completionScore}
          completionLabel={snapshot.identity.completionLabel}
        />

        <PerfilFocusCard
          title={snapshot.focus.title}
          description={snapshot.focus.description}
          valueLabel={snapshot.focus.valueLabel}
          supportText={snapshot.focus.supportText}
          tone={snapshot.focus.tone}
        />

        <View style={styles.shortcuts}>
          <ShortcutCard
            icon="create-outline"
            title="Editar dados"
            description="Ajuste nome, email, telefone e endereco em uma ordem simples."
            onPress={() => router.push('/(app)/perfil/editar')}
          />
          <ShortcutCard
            icon="lock-closed-outline"
            title="Senha e seguranca"
            description="Troque a senha com os requisitos visiveis antes de salvar."
            onPress={() => router.push('/(app)/perfil/seguranca')}
            tone="light"
          />
        </View>

        <PerfilGuidedStepsCard steps={snapshot.guidedSteps} />
        <PerfilDetailsCard details={snapshot.details} />
        <PerfilReferralCard referral={snapshot.referral} onShare={handleShareReferral} />
        <PerfilIntegrationsCard
          telegram={snapshot.telegram}
          telegramLinkDraft={telegramLinkDraft}
          telegramFeedback={telegramFeedback}
          isUpdatingTelegram={isUpdatingTelegram}
          onRequestLink={() => {
            void requestTelegramLink();
          }}
          onUnlink={() => {
            void unlinkTelegram();
          }}
          onOpenBot={() => {
            void handleOpenTelegramBot();
          }}
        />
        <PerfilSupportCard
          hint={snapshot.support.hint}
          replyVia={replyVia}
          message={supportMessage}
          feedback={supportFeedback}
          errorMessage={supportError}
          isSending={isSendingSupport}
          onChangeReplyVia={setReplyVia}
          onChangeMessage={(value) => {
            setSupportMessage(value);
            if (supportError) {
              setSupportError(null);
            }
          }}
          onSend={() => {
            void handleSendSupport();
          }}
        />

        <Pressable
          style={[styles.logoutButton, isSigningOut && styles.logoutButtonDisabled]}
          onPress={() => {
            void signOut();
          }}>
          <View style={styles.logoutIcon}>
            <Ionicons name="log-out-outline" size={18} color={tokens.colors.danger} />
          </View>
          <View style={styles.logoutCopy}>
            <Text style={styles.logoutTitle}>
              {session.status === 'preview' ? 'Sair da demonstracao' : 'Sair do app'}
            </Text>
            <Text style={styles.logoutDescription}>
              {isSigningOut
                ? 'Encerrando a sessao deste aparelho...'
                : 'Volta para a tela de entrada sem esconder onde fica a saida.'}
            </Text>
          </View>
        </Pressable>
      </ScrollView>
    </SafeAreaView>
  );
}

function ShortcutCard({
  icon,
  title,
  description,
  onPress,
  tone = 'dark',
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  description: string;
  onPress: () => void;
  tone?: 'dark' | 'light';
}) {
  const isLight = tone === 'light';

  return (
    <Pressable
      style={[styles.shortcutButton, isLight ? styles.shortcutButtonLight : styles.shortcutButtonDark]}
      onPress={onPress}>
      <View style={[styles.shortcutIcon, isLight && styles.shortcutIconLight]}>
        <Ionicons
          name={icon}
          size={18}
          color={isLight ? tokens.colors.primaryStrong : tokens.colors.textInverse}
        />
      </View>
      <View style={styles.shortcutCopy}>
        <Text style={[styles.shortcutLabel, isLight && styles.shortcutLabelLight]}>{title}</Text>
        <Text
          style={[styles.shortcutDescription, isLight && styles.shortcutDescriptionLight]}>
          {description}
        </Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: tokens.colors.background,
  },
  content: {
    padding: tokens.spacing.lg,
    gap: tokens.spacing.lg,
    paddingBottom: 96,
  },
  header: {
    gap: tokens.spacing.md,
  },
  shortcuts: {
    gap: tokens.spacing.sm,
  },
  shortcutButton: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'center',
    borderRadius: tokens.radius.lg,
    padding: tokens.spacing.md,
    ...tokens.shadow.subtle,
  },
  shortcutButtonDark: {
    backgroundColor: tokens.colors.secondary,
  },
  shortcutButtonLight: {
    backgroundColor: tokens.colors.surface,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  shortcutIcon: {
    width: 40,
    height: 40,
    borderRadius: tokens.radius.pill,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  shortcutIconLight: {
    backgroundColor: '#fff3e8',
  },
  shortcutCopy: {
    flex: 1,
    gap: 2,
  },
  shortcutLabel: {
    color: tokens.colors.textInverse,
    ...tokens.typography.body,
  },
  shortcutLabelLight: {
    color: tokens.colors.text,
  },
  shortcutDescription: {
    color: 'rgba(255,255,255,0.74)',
    ...tokens.typography.caption,
  },
  shortcutDescriptionLight: {
    color: tokens.colors.textMuted,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.whiteOverlay,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  pillText: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  sessionWarningBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: '#f0d8a9',
    backgroundColor: '#fff8e7',
    padding: tokens.spacing.md,
  },
  sessionWarningText: {
    flex: 1,
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    borderRadius: tokens.radius.lg,
    borderWidth: 1,
    borderColor: '#f0d3d0',
    backgroundColor: '#fff3f1',
    padding: tokens.spacing.md,
  },
  logoutButtonDisabled: {
    opacity: 0.7,
  },
  logoutIcon: {
    width: 40,
    height: 40,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde4e1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoutCopy: {
    flex: 1,
    gap: 2,
  },
  logoutTitle: {
    color: tokens.colors.danger,
    ...tokens.typography.body,
  },
  logoutDescription: {
    color: '#995d56',
    ...tokens.typography.caption,
  },
  backgroundTop: {
    position: 'absolute',
    top: -90,
    right: -50,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde7d1',
  },
  backgroundBottom: {
    position: 'absolute',
    bottom: 90,
    left: -70,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#dfeaf7',
  },
});
