import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';
import { useLoginDraft } from '@/src/features/auth/hooks/use-login-draft';
import { appConfig } from '@/src/lib/config/app-config';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

function buildWebUrl(path: string) {
  return new URL(path.replace(/^\//, ''), appConfig.apiBaseUrl).toString();
}

export function LoginScreen() {
  const { session, enterPreview } = useAuthSession();
  const {
    email,
    password,
    remember,
    errors,
    feedback,
    isSubmitting,
    setEmail,
    setPassword,
    setRemember,
    clearFeedback,
    submit,
  } = useLoginDraft();
  const [isPasswordVisible, setIsPasswordVisible] = useState(false);

  async function handleSubmit() {
    await submit();
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.backgroundTop} />
      <View style={styles.backgroundBottom} />

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.keyboardArea}>
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled">
          <View style={styles.header}>
            <View style={styles.pill}>
              <Text style={styles.pillText}>Entrar</Text>
            </View>
            <Text style={styles.title}>Abra o Lukrato sem pensar demais no caminho</Text>
            <Text style={styles.description}>
              Primeiro voce entra. Depois o app mostra o saldo, os proximos passos e os atalhos mais usados sem menu escondido.
            </Text>
          </View>

          {session.helperMessage ? (
            <Banner tone={session.source === 'remote' ? 'info' : 'warning'}>
              {session.helperMessage}
            </Banner>
          ) : null}

          {feedback ? (
            <Banner tone={feedback.tone === 'error' ? 'danger' : feedback.tone}>
              {feedback.message}
            </Banner>
          ) : null}

          <AppCard>
            <Text style={styles.cardTitle}>O que voce vai encontrar logo depois do login</Text>
            <FeaturePoint
              icon="home-outline"
              title="Inicio ja organizado"
              description="Saldo, foco do dia e proximos passos aparecem no topo."
            />
            <FeaturePoint
              icon="receipt-outline"
              title="Lancamentos sem menus confusos"
              description="Registrar gasto, receita ou transferencia fica a poucos toques."
            />
            <FeaturePoint
              icon="wallet-outline"
              title="Contas e cartoes separados do jeito certo"
              description="O dinheiro do mes nao se mistura com reserva ou fatura."
            />
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>Seus dados para entrar</Text>

            <Text style={styles.fieldLabel}>E-mail</Text>
            <TextInput
              value={email}
              onChangeText={(value) => {
                setEmail(value);
                if (feedback) {
                  clearFeedback();
                }
              }}
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="email-address"
              placeholder="voce@exemplo.com"
              placeholderTextColor={tokens.colors.textMuted}
              style={[styles.fieldInput, errors.email && styles.fieldInputError]}
            />
            {errors.email ? <Text style={styles.errorText}>{errors.email}</Text> : null}

            <Text style={styles.fieldLabel}>Senha</Text>
            <View style={[styles.passwordField, errors.password && styles.fieldInputError]}>
              <TextInput
                value={password}
                onChangeText={(value) => {
                  setPassword(value);
                  if (feedback) {
                    clearFeedback();
                  }
                }}
                secureTextEntry={!isPasswordVisible}
                autoCapitalize="none"
                placeholder="Digite sua senha"
                placeholderTextColor={tokens.colors.textMuted}
                style={styles.passwordInput}
              />
              <Pressable
                style={styles.passwordToggle}
                onPress={() => setIsPasswordVisible((current) => !current)}>
                <Ionicons
                  name={isPasswordVisible ? 'eye-off-outline' : 'eye-outline'}
                  size={18}
                  color={tokens.colors.textMuted}
                />
              </Pressable>
            </View>
            {errors.password ? <Text style={styles.errorText}>{errors.password}</Text> : null}

            <Pressable
              style={[styles.rememberRow, remember && styles.rememberRowActive]}
              onPress={() => setRemember(!remember)}>
              <View style={[styles.rememberCheck, remember && styles.rememberCheckActive]}>
                {remember ? (
                  <Ionicons name="checkmark" size={16} color={tokens.colors.textInverse} />
                ) : null}
              </View>
              <View style={styles.rememberCopy}>
                <Text style={styles.rememberTitle}>Manter neste aparelho</Text>
                <Text style={styles.rememberDescription}>
                  Quando fizer sentido, o app tenta manter sua sessao ativa por mais tempo.
                </Text>
              </View>
            </Pressable>
          </AppCard>

          <View style={styles.actions}>
            <Pressable
              style={[styles.primaryButton, isSubmitting && styles.buttonDisabled]}
              onPress={() => {
                void handleSubmit();
              }}>
              <Text style={styles.primaryButtonText}>
                {isSubmitting ? 'Entrando...' : 'Entrar no Lukrato'}
              </Text>
            </Pressable>

            {session.allowPreview ? (
              <Pressable style={styles.secondaryButton} onPress={enterPreview}>
                <Ionicons
                  name="phone-portrait-outline"
                  size={16}
                  color={tokens.colors.primaryStrong}
                />
                <Text style={styles.secondaryButtonText}>Ver a demo primeiro</Text>
              </Pressable>
            ) : null}
          </View>

          <AppCard>
            <Text style={styles.cardTitle}>Precisa de ajuda antes de entrar?</Text>

            <InlineLink
              icon="refresh-outline"
              label="Esqueci minha senha"
              description="Abre a recuperacao no navegador para continuar sem travar."
              onPress={() => {
                void Linking.openURL(buildWebUrl('recuperar-senha'));
              }}
            />
            <InlineLink
              icon="person-add-outline"
              label="Criar conta"
              description="Se ainda nao existe cadastro, o fluxo de registro abre no navegador."
              onPress={() => {
                void Linking.openURL(buildWebUrl('login?tab=register'));
              }}
            />
          </AppCard>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function Banner({
  children,
  tone,
}: {
  children: string;
  tone: 'info' | 'warning' | 'success' | 'danger';
}) {
  const toneStyles = {
    info: {
      wrapper: styles.bannerInfo,
      text: styles.bannerInfoText,
      icon: 'information-circle-outline' as const,
    },
    warning: {
      wrapper: styles.bannerWarning,
      text: styles.bannerWarningText,
      icon: 'sparkles-outline' as const,
    },
    success: {
      wrapper: styles.bannerSuccess,
      text: styles.bannerSuccessText,
      icon: 'checkmark-circle-outline' as const,
    },
    danger: {
      wrapper: styles.bannerDanger,
      text: styles.bannerDangerText,
      icon: 'alert-circle-outline' as const,
    },
  }[tone];

  return (
    <View style={[styles.banner, toneStyles.wrapper]}>
      <Ionicons
        name={toneStyles.icon}
        size={18}
        color={
          tone === 'danger'
            ? tokens.colors.danger
            : tone === 'success'
              ? tokens.colors.success
              : tokens.colors.primaryStrong
        }
      />
      <Text style={[styles.bannerText, toneStyles.text]}>{children}</Text>
    </View>
  );
}

function FeaturePoint({
  icon,
  title,
  description,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  description: string;
}) {
  return (
    <View style={styles.featurePoint}>
      <View style={styles.featureIcon}>
        <Ionicons name={icon} size={18} color={tokens.colors.primaryStrong} />
      </View>
      <View style={styles.featureCopy}>
        <Text style={styles.featureTitle}>{title}</Text>
        <Text style={styles.featureDescription}>{description}</Text>
      </View>
    </View>
  );
}

function InlineLink({
  icon,
  label,
  description,
  onPress,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  description: string;
  onPress: () => void;
}) {
  return (
    <Pressable style={styles.inlineLink} onPress={onPress}>
      <View style={styles.inlineIcon}>
        <Ionicons name={icon} size={18} color={tokens.colors.primaryStrong} />
      </View>
      <View style={styles.inlineCopy}>
        <Text style={styles.inlineTitle}>{label}</Text>
        <Text style={styles.inlineDescription}>{description}</Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: tokens.colors.background,
  },
  keyboardArea: {
    flex: 1,
  },
  content: {
    padding: tokens.spacing.lg,
    gap: tokens.spacing.lg,
    paddingBottom: tokens.spacing.xxl,
  },
  header: {
    gap: tokens.spacing.sm,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.whiteOverlay,
    borderWidth: 1,
    borderColor: '#f7d2ab',
  },
  pillText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.caption,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.display,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
  cardTitle: {
    marginBottom: tokens.spacing.md,
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    borderRadius: tokens.radius.md,
    padding: tokens.spacing.md,
    borderWidth: 1,
  },
  bannerText: {
    flex: 1,
    ...tokens.typography.small,
  },
  bannerInfo: {
    backgroundColor: '#eef5ff',
    borderColor: '#caddf6',
  },
  bannerInfoText: {
    color: tokens.colors.info,
  },
  bannerWarning: {
    backgroundColor: '#fff7ec',
    borderColor: '#f0d8a9',
  },
  bannerWarningText: {
    color: tokens.colors.primaryStrong,
  },
  bannerSuccess: {
    backgroundColor: '#ecfdf3',
    borderColor: '#bde7cf',
  },
  bannerSuccessText: {
    color: tokens.colors.success,
  },
  bannerDanger: {
    backgroundColor: '#fff1ef',
    borderColor: '#f3cdc8',
  },
  bannerDangerText: {
    color: tokens.colors.danger,
  },
  featurePoint: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
    paddingVertical: tokens.spacing.xs,
  },
  featureIcon: {
    width: 36,
    height: 36,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureCopy: {
    flex: 1,
    gap: 2,
  },
  featureTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  featureDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  fieldLabel: {
    marginBottom: 8,
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  fieldInput: {
    borderWidth: 1,
    borderColor: tokens.colors.border,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 14,
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  fieldInputError: {
    borderColor: '#e7b7b1',
    backgroundColor: '#fff7f6',
  },
  passwordField: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: tokens.colors.border,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    paddingLeft: tokens.spacing.md,
  },
  passwordInput: {
    flex: 1,
    color: tokens.colors.text,
    paddingVertical: 14,
    ...tokens.typography.body,
  },
  passwordToggle: {
    width: 46,
    alignItems: 'center',
    justifyContent: 'center',
  },
  errorText: {
    marginTop: 6,
    marginBottom: tokens.spacing.md,
    color: tokens.colors.danger,
    ...tokens.typography.caption,
  },
  rememberRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: tokens.spacing.sm,
    marginTop: tokens.spacing.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    borderRadius: tokens.radius.md,
    padding: tokens.spacing.md,
    backgroundColor: tokens.colors.surfaceAlt,
  },
  rememberRowActive: {
    borderColor: '#f3c28a',
    backgroundColor: '#fff7ec',
  },
  rememberCheck: {
    width: 22,
    height: 22,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rememberCheckActive: {
    borderColor: tokens.colors.primary,
    backgroundColor: tokens.colors.primary,
  },
  rememberCopy: {
    flex: 1,
    gap: 2,
  },
  rememberTitle: {
    color: tokens.colors.text,
    ...tokens.typography.small,
  },
  rememberDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  actions: {
    gap: tokens.spacing.sm,
  },
  primaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.primary,
    paddingVertical: 16,
  },
  primaryButtonText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.body,
  },
  secondaryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: tokens.spacing.xs,
    borderRadius: tokens.radius.pill,
    borderWidth: 1,
    borderColor: '#f3c28a',
    backgroundColor: '#fff7ec',
    paddingVertical: 14,
  },
  secondaryButtonText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.small,
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  inlineLink: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'center',
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
  },
  inlineIcon: {
    width: 36,
    height: 36,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  inlineCopy: {
    flex: 1,
    gap: 2,
  },
  inlineTitle: {
    color: tokens.colors.text,
    ...tokens.typography.small,
  },
  inlineDescription: {
    color: tokens.colors.textMuted,
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
    bottom: 100,
    left: -70,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#dfeaf7',
  },
});
