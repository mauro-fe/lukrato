import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { usePasswordDraft } from '@/src/features/perfil/hooks/use-password-draft';
import { AppCard } from '@/src/shared/ui/app-card';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { tokens } from '@/src/theme/tokens';

export function ChangePasswordScreen() {
  const router = useRouter();
  const {
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
  } = usePasswordDraft();

  async function handleSubmit() {
    const success = await submit();

    if (success) {
      setTimeout(() => {
        router.back();
      }, 450);
    }
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <KeyboardAvoidingView
        style={styles.keyboard}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <View style={styles.backgroundTop} />
        <View style={styles.backgroundBottom} />

        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={styles.header}>
            <Pressable style={styles.backButton} onPress={() => router.back()}>
              <Ionicons name="arrow-back" size={20} color={tokens.colors.text} />
            </Pressable>

            <View style={styles.headerText}>
              <Text style={styles.eyebrow}>Seguranca</Text>
              <Text style={styles.title}>Troque a senha sem linguagem confusa</Text>
              <Text style={styles.description}>
                O app mostra o que falta para a senha ficar forte e nao deixa o usuario adivinhar a regra.
              </Text>
            </View>
          </View>

          {feedback ? (
            <View
              style={[
                styles.feedbackBanner,
                feedback.tone === 'success' ? styles.feedbackSuccess : styles.feedbackError,
              ]}>
              <Ionicons
                name={feedback.tone === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'}
                size={18}
                color={feedback.tone === 'success' ? tokens.colors.success : tokens.colors.danger}
              />
              <Text
                style={[
                  styles.feedbackText,
                  feedback.tone === 'success' ? styles.feedbackSuccessText : styles.feedbackErrorText,
                ]}>
                {feedback.message}
              </Text>
            </View>
          ) : null}

          <DataSourceBanner source={dataSource} fallbackMessage={sourceMessage} />

          <AppCard>
            <Text style={styles.cardTitle}>1. Confirme sua senha atual</Text>
            <TextInput
              value={currentPassword}
              onChangeText={setCurrentPassword}
              placeholder="Senha atual"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              secureTextEntry
              autoCapitalize="none"
            />
            {errors.currentPassword ? <Text style={styles.errorText}>{errors.currentPassword}</Text> : null}
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>2. Escolha uma nova senha forte</Text>
            <TextInput
              value={newPassword}
              onChangeText={setNewPassword}
              placeholder="Nova senha"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              secureTextEntry
              autoCapitalize="none"
            />
            {errors.newPassword ? <Text style={styles.errorText}>{errors.newPassword}</Text> : null}

            <View style={styles.requirements}>
              <RequirementRow label="Pelo menos 8 caracteres" passed={checks.length} />
              <RequirementRow label="Uma letra minuscula" passed={checks.lower} />
              <RequirementRow label="Uma letra maiuscula" passed={checks.upper} />
              <RequirementRow label="Um numero" passed={checks.number} />
              <RequirementRow label="Um caractere especial" passed={checks.special} />
            </View>
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>3. Confirme a nova senha</Text>
            <TextInput
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              placeholder="Repita a nova senha"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              secureTextEntry
              autoCapitalize="none"
            />
            {errors.confirmPassword ? <Text style={styles.errorText}>{errors.confirmPassword}</Text> : null}
          </AppCard>
        </ScrollView>

        <View style={styles.saveBar}>
          <View style={styles.saveSummary}>
            <Text style={styles.saveLabel}>Seguranca</Text>
            <Text style={styles.saveValue}>
              {Object.values(checks).filter(Boolean).length}/5 requisitos atendidos
            </Text>
            <Text style={styles.saveSupport}>A confirmacao so libera quando a senha estiver coerente.</Text>
          </View>
          <Pressable
            style={[styles.saveButton, isSubmitting && styles.saveButtonDisabled]}
            onPress={handleSubmit}>
            <Text style={styles.saveButtonText}>
              {isSubmitting ? 'Salvando...' : 'Salvar senha'}
            </Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function RequirementRow({ label, passed }: { label: string; passed: boolean }) {
  return (
    <View style={styles.requirementRow}>
      <Ionicons
        name={passed ? 'checkmark-circle' : 'ellipse-outline'}
        size={16}
        color={passed ? tokens.colors.success : tokens.colors.textMuted}
      />
      <Text style={[styles.requirementText, passed && styles.requirementTextPassed]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: tokens.colors.background,
  },
  keyboard: {
    flex: 1,
  },
  content: {
    padding: tokens.spacing.lg,
    gap: tokens.spacing.lg,
    paddingBottom: 140,
  },
  header: {
    flexDirection: 'row',
    gap: tokens.spacing.md,
    alignItems: 'flex-start',
  },
  backButton: {
    width: 42,
    height: 42,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surface,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerText: {
    flex: 1,
    gap: 4,
  },
  eyebrow: {
    color: tokens.colors.primaryStrong,
    textTransform: 'uppercase',
    ...tokens.typography.caption,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.heading,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
  feedbackBanner: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'center',
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    padding: tokens.spacing.md,
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
    flex: 1,
    ...tokens.typography.small,
  },
  feedbackSuccessText: {
    color: tokens.colors.success,
  },
  feedbackErrorText: {
    color: tokens.colors.danger,
  },
  cardTitle: {
    color: tokens.colors.text,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.title,
  },
  fieldInput: {
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    borderRadius: tokens.radius.md,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 14,
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  errorText: {
    color: tokens.colors.danger,
    marginTop: tokens.spacing.sm,
    ...tokens.typography.caption,
  },
  requirements: {
    marginTop: tokens.spacing.md,
    gap: tokens.spacing.sm,
  },
  requirementRow: {
    flexDirection: 'row',
    gap: tokens.spacing.xs,
    alignItems: 'center',
  },
  requirementText: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  requirementTextPassed: {
    color: tokens.colors.success,
  },
  saveBar: {
    position: 'absolute',
    left: tokens.spacing.lg,
    right: tokens.spacing.lg,
    bottom: tokens.spacing.lg,
    borderRadius: tokens.radius.lg,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surface,
    padding: tokens.spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.md,
    ...tokens.shadow.soft,
  },
  saveSummary: {
    flex: 1,
    gap: 2,
  },
  saveLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  saveValue: {
    color: tokens.colors.text,
    ...tokens.typography.small,
  },
  saveSupport: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  saveButton: {
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.primary,
    paddingHorizontal: tokens.spacing.lg,
    paddingVertical: 14,
  },
  saveButtonDisabled: {
    opacity: 0.7,
  },
  saveButtonText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.small,
  },
  backgroundTop: {
    position: 'absolute',
    top: -70,
    right: -70,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde7d1',
  },
  backgroundBottom: {
    position: 'absolute',
    bottom: 110,
    left: -70,
    width: 200,
    height: 200,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#e3edf8',
  },
});
