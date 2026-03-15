import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useMemo } from 'react';
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

import { perfilSexOptions } from '@/src/features/perfil/data/perfil-form-options';
import { usePerfilFormDraft } from '@/src/features/perfil/hooks/use-perfil-form-draft';
import { AppCard } from '@/src/shared/ui/app-card';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { tokens } from '@/src/theme/tokens';

export function EditPerfilScreen() {
  const router = useRouter();
  const {
    profile,
    errors,
    isSubmitting,
    feedback,
    dataSource,
    sourceMessage,
    updateField,
    submit,
  } = usePerfilFormDraft();

  const summaryLabel = useMemo(
    () => profile.name.trim() || profile.email.trim() || 'Seu perfil',
    [profile.email, profile.name]
  );

  async function handleSubmit() {
    const result = await submit();

    if (result) {
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
              <Text style={styles.eyebrow}>Editar perfil</Text>
              <Text style={styles.title}>Ajuste seus dados sem se perder</Text>
              <Text style={styles.description}>
                Primeiro vem identidade, depois os dados de apoio e por fim o endereco. Tudo em uma ordem que faz sentido.
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
            <Text style={styles.cardTitle}>1. Como essa pessoa aparece no app?</Text>

            <Text style={styles.fieldLabel}>Nome</Text>
            <TextInput
              value={profile.name}
              onChangeText={(value) => updateField('name', value)}
              placeholder="Nome completo"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              autoCapitalize="words"
            />
            {errors.name ? <Text style={styles.errorText}>{errors.name}</Text> : null}

            <Text style={styles.fieldLabel}>Email</Text>
            <TextInput
              value={profile.email}
              onChangeText={(value) => updateField('email', value)}
              placeholder="voce@exemplo.com"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              keyboardType="email-address"
              autoCapitalize="none"
            />
            {errors.email ? <Text style={styles.errorText}>{errors.email}</Text> : null}
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>2. Dados que ajudam a reconhecer a conta</Text>

            <Text style={styles.fieldLabel}>CPF</Text>
            <TextInput
              value={profile.cpf}
              onChangeText={(value) => updateField('cpf', value)}
              placeholder="000.000.000-00"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              keyboardType={Platform.OS === 'ios' ? 'numbers-and-punctuation' : 'numeric'}
            />
            {errors.cpf ? <Text style={styles.errorText}>{errors.cpf}</Text> : null}

            <Text style={styles.fieldLabel}>Telefone</Text>
            <TextInput
              value={profile.phone}
              onChangeText={(value) => updateField('phone', value)}
              placeholder="(11) 99999-9999"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              keyboardType="phone-pad"
            />
            {errors.phone ? <Text style={styles.errorText}>{errors.phone}</Text> : null}

            <Text style={styles.fieldLabel}>Nascimento</Text>
            <TextInput
              value={profile.birthDate}
              onChangeText={(value) => updateField('birthDate', value)}
              placeholder="YYYY-MM-DD"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
            />
            {errors.birthDate ? <Text style={styles.errorText}>{errors.birthDate}</Text> : null}

            <Text style={styles.fieldLabel}>Sexo</Text>
            <View style={styles.choiceList}>
              {perfilSexOptions.map((option) => (
                <Pressable
                  key={option.id || 'empty'}
                  style={[styles.choiceButton, profile.sex === option.id && styles.choiceButtonActive]}
                  onPress={() => updateField('sex', option.id)}>
                  <Text
                    style={[
                      styles.choiceLabel,
                      profile.sex === option.id && styles.choiceLabelActive,
                    ]}>
                    {option.label}
                  </Text>
                  <Text
                    style={[
                      styles.choiceHelper,
                      profile.sex === option.id && styles.choiceHelperActive,
                    ]}>
                    {option.helper}
                  </Text>
                </Pressable>
              ))}
            </View>
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>3. Endereco sem campos escondidos</Text>

            <Text style={styles.fieldLabel}>CEP</Text>
            <TextInput
              value={profile.addressCep}
              onChangeText={(value) => updateField('addressCep', value)}
              placeholder="00000-000"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              keyboardType={Platform.OS === 'ios' ? 'numbers-and-punctuation' : 'numeric'}
            />
            {errors.addressCep ? <Text style={styles.errorText}>{errors.addressCep}</Text> : null}

            <Text style={styles.fieldLabel}>Rua</Text>
            <TextInput
              value={profile.addressStreet}
              onChangeText={(value) => updateField('addressStreet', value)}
              placeholder="Rua, avenida ou travessa"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              autoCapitalize="words"
            />
            {errors.addressStreet ? <Text style={styles.errorText}>{errors.addressStreet}</Text> : null}

            <View style={styles.inlineRow}>
              <View style={styles.inlineField}>
                <Text style={styles.fieldLabel}>Numero</Text>
                <TextInput
                  value={profile.addressNumber}
                  onChangeText={(value) => updateField('addressNumber', value)}
                  placeholder="123"
                  placeholderTextColor={tokens.colors.textMuted}
                  style={styles.fieldInput}
                />
                {errors.addressNumber ? <Text style={styles.errorText}>{errors.addressNumber}</Text> : null}
              </View>

              <View style={styles.inlineField}>
                <Text style={styles.fieldLabel}>Complemento</Text>
                <TextInput
                  value={profile.addressComplement}
                  onChangeText={(value) => updateField('addressComplement', value)}
                  placeholder="Opcional"
                  placeholderTextColor={tokens.colors.textMuted}
                  style={styles.fieldInput}
                />
              </View>
            </View>

            <Text style={styles.fieldLabel}>Bairro</Text>
            <TextInput
              value={profile.addressNeighborhood}
              onChangeText={(value) => updateField('addressNeighborhood', value)}
              placeholder="Bairro"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
              autoCapitalize="words"
            />
            {errors.addressNeighborhood ? <Text style={styles.errorText}>{errors.addressNeighborhood}</Text> : null}

            <View style={styles.inlineRow}>
              <View style={styles.inlineFieldWide}>
                <Text style={styles.fieldLabel}>Cidade</Text>
                <TextInput
                  value={profile.addressCity}
                  onChangeText={(value) => updateField('addressCity', value)}
                  placeholder="Cidade"
                  placeholderTextColor={tokens.colors.textMuted}
                  style={styles.fieldInput}
                  autoCapitalize="words"
                />
                {errors.addressCity ? <Text style={styles.errorText}>{errors.addressCity}</Text> : null}
              </View>

              <View style={styles.inlineFieldCompact}>
                <Text style={styles.fieldLabel}>UF</Text>
                <TextInput
                  value={profile.addressState}
                  onChangeText={(value) => updateField('addressState', value.toUpperCase())}
                  placeholder="SP"
                  placeholderTextColor={tokens.colors.textMuted}
                  style={styles.fieldInput}
                  autoCapitalize="characters"
                  maxLength={2}
                />
                {errors.addressState ? <Text style={styles.errorText}>{errors.addressState}</Text> : null}
              </View>
            </View>
          </AppCard>
        </ScrollView>

        <View style={styles.saveBar}>
          <View style={styles.saveSummary}>
            <Text style={styles.saveLabel}>Resumo</Text>
            <Text style={styles.saveValue}>{summaryLabel}</Text>
            <Text style={styles.saveSupport}>{profile.email.trim() || 'Sem email informado'}</Text>
          </View>
          <Pressable
            style={[styles.saveButton, isSubmitting && styles.saveButtonDisabled]}
            onPress={handleSubmit}>
            <Text style={styles.saveButtonText}>{isSubmitting ? 'Salvando...' : 'Salvar perfil'}</Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
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
  fieldLabel: {
    color: tokens.colors.secondary,
    marginBottom: 8,
    ...tokens.typography.small,
  },
  fieldInput: {
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    borderRadius: tokens.radius.md,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 14,
    color: tokens.colors.text,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.body,
  },
  errorText: {
    marginTop: -8,
    marginBottom: tokens.spacing.md,
    color: tokens.colors.danger,
    ...tokens.typography.caption,
  },
  choiceList: {
    gap: tokens.spacing.sm,
  },
  choiceButton: {
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
  },
  choiceButtonActive: {
    backgroundColor: '#fff3e8',
    borderColor: '#f3c28a',
  },
  choiceLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  choiceLabelActive: {
    color: tokens.colors.primaryStrong,
  },
  choiceHelper: {
    color: tokens.colors.textMuted,
    marginTop: 2,
    ...tokens.typography.caption,
  },
  choiceHelperActive: {
    color: tokens.colors.primaryStrong,
  },
  inlineRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  inlineField: {
    flex: 1,
  },
  inlineFieldWide: {
    flex: 2,
  },
  inlineFieldCompact: {
    flex: 1,
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
