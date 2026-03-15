import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Platform, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  contaTypeOptions,
  ContaTypeOption,
} from '@/src/features/contas/data/conta-form-options';
import { useContaDraft } from '@/src/features/contas/hooks/use-conta-draft';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { tokens } from '@/src/theme/tokens';

export function NewContaScreen() {
  const router = useRouter();
  const {
    accountType,
    name,
    institutionId,
    institutionQuery,
    initialBalance,
    initialBalanceInput,
    manualInstitutionName,
    errors,
    isSubmitting,
    submitMessage,
    dataSource,
    sourceMessage,
    selectedInstitution,
    filteredInstitutions,
    suggestedName,
    setAccountType,
    setName,
    setInstitutionQuery,
    setInitialBalanceInput,
    setManualInstitutionName,
    selectInstitution,
    submit,
  } = useContaDraft();

  const activeType =
    contaTypeOptions.find((option) => option.id === accountType) ?? contaTypeOptions[0];

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
      <View style={styles.backgroundTop} />
      <View style={styles.backgroundBottom} />

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <View style={styles.header}>
          <Pressable style={styles.backButton} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={20} color={tokens.colors.text} />
          </Pressable>

          <View style={styles.headerText}>
            <Text style={styles.eyebrow}>Nova conta</Text>
            <Text style={styles.title}>Crie a conta sem deixar o usuario na duvida</Text>
            <Text style={styles.description}>
              Primeiro o app entende para que a conta vai servir. Depois fica facil dar nome, escolher a instituicao e guardar o saldo inicial.
            </Text>
          </View>
        </View>

        {submitMessage ? (
          <View style={styles.successBanner}>
            <Ionicons name="checkmark-circle-outline" size={18} color={tokens.colors.success} />
            <Text style={styles.successText}>{submitMessage}</Text>
          </View>
        ) : null}

        <DataSourceBanner source={dataSource} fallbackMessage={sourceMessage} />

        <AppCard>
          <Text style={styles.cardTitle}>1. Para que essa conta vai servir?</Text>
          <View style={styles.typeGrid}>
            {contaTypeOptions.map((option) => (
              <TypeButton
                key={option.id}
                option={option}
                selected={option.id === accountType}
                onPress={() => setAccountType(option.id)}
              />
            ))}
          </View>
        </AppCard>

        <AppCard>
          <Text style={styles.cardTitle}>2. Onde esse dinheiro fica guardado?</Text>

          {accountType === 'dinheiro' ? (
            <View style={styles.inlineHint}>
              <Ionicons name="cash-outline" size={18} color={tokens.colors.warning} />
              <Text style={styles.inlineHintText}>
                Dinheiro em maos nao precisa de banco. Se quiser, use o campo abaixo para escrever algo como caixa da casa ou carteira fisica.
              </Text>
            </View>
          ) : (
            <>
              <Text style={styles.fieldLabel}>Buscar instituicao</Text>
              <TextInput
                value={institutionQuery}
                onChangeText={setInstitutionQuery}
                placeholder="Ex: Nubank, Itau, Inter"
                placeholderTextColor={tokens.colors.textMuted}
                style={styles.fieldInput}
                autoCapitalize="words"
              />

              <View style={styles.choiceList}>
                {filteredInstitutions.map((institution) => (
                  <InstitutionButton
                    key={institution.id}
                    selected={institution.id === institutionId}
                    label={institution.name}
                    helper={institution.type}
                    accentColor={institution.accentColor}
                    onPress={() => selectInstitution(institution.id)}
                  />
                ))}
              </View>

              {!filteredInstitutions.length ? (
                <Text style={styles.emptyHint}>
                  Nenhuma instituicao apareceu nessa busca. O usuario pode seguir escrevendo o nome manualmente logo abaixo.
                </Text>
              ) : null}
            </>
          )}

          <Text style={styles.fieldLabel}>Se nao estiver na lista, escreva o nome</Text>
          <TextInput
            value={manualInstitutionName}
            onChangeText={setManualInstitutionName}
            placeholder={
              accountType === 'dinheiro'
                ? 'Ex: Carteira fisica, caixa da casa'
                : 'Ex: Cooperativa local, conta da empresa'
            }
            placeholderTextColor={tokens.colors.textMuted}
            style={styles.fieldInput}
            autoCapitalize="words"
          />

          {selectedInstitution ? (
            <Text style={styles.selectionText}>Selecionada: {selectedInstitution.name}</Text>
          ) : null}
        </AppCard>

        <AppCard>
          <Text style={styles.cardTitle}>3. Como o usuario vai reconhecer essa conta?</Text>
          <Text style={styles.fieldLabel}>Nome da conta</Text>
          <TextInput
            value={name}
            onChangeText={setName}
            placeholder={suggestedName}
            placeholderTextColor={tokens.colors.textMuted}
            style={styles.fieldInput}
            autoCapitalize="words"
          />
          {errors.name ? <Text style={styles.errorText}>{errors.name}</Text> : null}

          <Pressable style={styles.suggestionButton} onPress={() => setName(suggestedName)}>
            <Ionicons name="sparkles-outline" size={16} color={tokens.colors.primaryStrong} />
            <Text style={styles.suggestionText}>Usar sugestao: {suggestedName}</Text>
          </Pressable>
        </AppCard>

        <AppCard>
          <Text style={styles.cardTitle}>4. Qual saldo inicial entra nessa conta?</Text>
          <TextInput
            value={initialBalanceInput}
            onChangeText={setInitialBalanceInput}
            placeholder="0,00"
            placeholderTextColor={tokens.colors.textMuted}
            keyboardType={Platform.OS === 'ios' ? 'numbers-and-punctuation' : 'numeric'}
            style={styles.amountInput}
          />
          <Text style={styles.amountPreview}>
            {initialBalanceInput.trim()
              ? formatCurrency(initialBalance)
              : 'Comece em zero se preferir cadastrar o resto depois'}
          </Text>
          <Text style={styles.balanceHint}>
            O saldo pode ser positivo, zero ou negativo. O importante e o usuario comecar do ponto real.
          </Text>
        </AppCard>
      </ScrollView>

      <View style={styles.saveBar}>
        <View style={styles.saveSummary}>
          <Text style={styles.saveLabel}>Resumo</Text>
          <Text style={styles.saveValue}>{name.trim() || suggestedName}</Text>
          <Text style={styles.saveSupport}>
            {activeType.label} • {formatCurrency(initialBalance)}
          </Text>
        </View>

        <Pressable
          style={[styles.saveButton, isSubmitting && styles.saveButtonDisabled]}
          onPress={handleSubmit}>
          <Text style={styles.saveButtonText}>
            {isSubmitting ? 'Salvando...' : 'Salvar conta'}
          </Text>
        </Pressable>
      </View>
    </SafeAreaView>
  );
}

function TypeButton({
  option,
  selected,
  onPress,
}: {
  option: ContaTypeOption;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable style={[styles.typeButton, selected && styles.typeButtonActive]} onPress={onPress}>
      <View style={[styles.typeIcon, selected && styles.typeIconActive]}>
        <Ionicons
          name={option.icon as never}
          size={20}
          color={selected ? tokens.colors.textInverse : tokens.colors.primaryStrong}
        />
      </View>
      <Text style={[styles.typeLabel, selected && styles.typeLabelActive]}>{option.label}</Text>
      <Text style={[styles.typeDescription, selected && styles.typeDescriptionActive]}>
        {option.description}
      </Text>
    </Pressable>
  );
}

function InstitutionButton({
  label,
  helper,
  accentColor,
  selected,
  onPress,
}: {
  label: string;
  helper: string;
  accentColor: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable style={[styles.institutionButton, selected && styles.institutionButtonActive]} onPress={onPress}>
      <View style={[styles.institutionDot, { backgroundColor: accentColor }]} />
      <View style={styles.institutionCopy}>
        <Text style={[styles.institutionLabel, selected && styles.institutionLabelActive]}>
          {label}
        </Text>
        <Text style={[styles.institutionHelper, selected && styles.institutionHelperActive]}>
          {helper}
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
    paddingBottom: 148,
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
  successBanner: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'center',
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: '#bde7cf',
    backgroundColor: '#ecfdf3',
    padding: tokens.spacing.md,
  },
  successText: {
    flex: 1,
    color: tokens.colors.success,
    ...tokens.typography.small,
  },
  cardTitle: {
    color: tokens.colors.text,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.title,
  },
  typeGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.sm,
  },
  typeButton: {
    width: '48%',
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
    gap: 8,
  },
  typeButtonActive: {
    backgroundColor: tokens.colors.secondary,
    borderColor: tokens.colors.secondary,
  },
  typeIcon: {
    width: 40,
    height: 40,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  typeIconActive: {
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  typeLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  typeLabelActive: {
    color: tokens.colors.textInverse,
  },
  typeDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  typeDescriptionActive: {
    color: 'rgba(255,255,255,0.75)',
  },
  inlineHint: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: '#f0d8a9',
    backgroundColor: '#fff8e7',
    padding: tokens.spacing.md,
    marginBottom: tokens.spacing.md,
  },
  inlineHintText: {
    flex: 1,
    color: tokens.colors.secondary,
    ...tokens.typography.small,
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
  choiceList: {
    gap: tokens.spacing.sm,
    marginBottom: tokens.spacing.sm,
  },
  institutionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
  },
  institutionButtonActive: {
    backgroundColor: '#fff3e8',
    borderColor: '#f3c28a',
  },
  institutionDot: {
    width: 12,
    height: 12,
    borderRadius: tokens.radius.pill,
  },
  institutionCopy: {
    flex: 1,
    gap: 2,
  },
  institutionLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  institutionLabelActive: {
    color: tokens.colors.primaryStrong,
  },
  institutionHelper: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  institutionHelperActive: {
    color: tokens.colors.primaryStrong,
  },
  emptyHint: {
    color: tokens.colors.textMuted,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.caption,
  },
  selectionText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.caption,
  },
  errorText: {
    marginTop: -8,
    marginBottom: tokens.spacing.md,
    color: tokens.colors.danger,
    ...tokens.typography.caption,
  },
  suggestionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.xs,
    alignSelf: 'flex-start',
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 10,
  },
  suggestionText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.small,
  },
  amountInput: {
    color: tokens.colors.text,
    fontSize: 34,
    lineHeight: 40,
    fontWeight: '800',
    letterSpacing: -0.8,
    paddingVertical: 0,
  },
  amountPreview: {
    marginTop: 8,
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  balanceHint: {
    marginTop: tokens.spacing.sm,
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
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
