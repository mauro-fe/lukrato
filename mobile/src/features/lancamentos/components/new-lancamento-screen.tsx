import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect } from 'react';
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

import { useLancamentoDraft } from '@/src/features/lancamentos/hooks/use-lancamento-draft';
import { LancamentoEntryMode } from '@/src/features/lancamentos/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { tokens } from '@/src/theme/tokens';

const MODE_OPTIONS: { id: LancamentoEntryMode; label: string; helper: string; icon: string }[] = [
  { id: 'expense', label: 'Gasto', helper: 'O que saiu do bolso', icon: 'remove-circle-outline' },
  { id: 'income', label: 'Receita', helper: 'Dinheiro que entrou', icon: 'add-circle-outline' },
  { id: 'transfer', label: 'Transferencia', helper: 'Entre suas contas', icon: 'swap-horizontal-outline' },
];

function isEntryMode(value: unknown): value is LancamentoEntryMode {
  return value === 'expense' || value === 'income' || value === 'transfer';
}

function getModeMeta(mode: LancamentoEntryMode) {
  if (mode === 'income') {
    return {
      title: 'Nova receita',
      description: 'Primeiro o usuário diz que entrou dinheiro. Só depois ele preenche os detalhes.',
      submitLabel: 'Salvar receita',
    };
  }

  if (mode === 'transfer') {
    return {
      title: 'Nova transferência',
      description: 'Movimentar entre contas precisa ser simples e sem risco de escolher a conta errada.',
      submitLabel: 'Salvar transferência',
    };
  }

  return {
    title: 'Novo gasto',
    description: 'Registrar um gasto deve ser rápido. O essencial vem primeiro, o resto fica opcional.',
    submitLabel: 'Salvar gasto',
  };
}

export function NewLancamentoScreen() {
  const params = useLocalSearchParams<{ mode?: string }>();
  const router = useRouter();
  const initialMode = isEntryMode(params.mode) ? params.mode : 'expense';
  const {
    mode,
    amount,
    amountInput,
    description,
    date,
    accountId,
    destinationAccountId,
    categoryId,
    note,
    isPaid,
    errors,
    isSubmitting,
    submitFeedback,
    dataSource,
    sourceMessage,
    availableAccounts,
    availableCategories,
    setMode,
    setAmountInput,
    setDescription,
    setDate,
    setAccountId,
    setDestinationAccountId,
    setCategoryId,
    setNote,
    setIsPaid,
    submit,
  } = useLancamentoDraft(initialMode);

  useEffect(() => {
    if (isEntryMode(params.mode)) {
      setMode(params.mode);
    }
  }, [params.mode, setMode]);

  useEffect(() => {
    if (submitFeedback?.tone !== 'success') {
      return;
    }

    const timeout = setTimeout(() => {
      router.back();
    }, 450);

    return () => clearTimeout(timeout);
  }, [submitFeedback, router]);

  const modeMeta = getModeMeta(mode);

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
              <Text style={styles.eyebrow}>Novo lançamento</Text>
              <Text style={styles.title}>{modeMeta.title}</Text>
              <Text style={styles.description}>{modeMeta.description}</Text>
            </View>
          </View>

          {submitFeedback ? (
            <View
              style={[
                styles.feedbackBanner,
                submitFeedback.tone === 'success' ? styles.feedbackSuccess : styles.feedbackError,
              ]}>
              <Ionicons
                name={submitFeedback.tone === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'}
                size={18}
                color={submitFeedback.tone === 'success' ? tokens.colors.success : tokens.colors.danger}
              />
              <Text
                style={[
                  styles.feedbackText,
                  submitFeedback.tone === 'success'
                    ? styles.feedbackSuccessText
                    : styles.feedbackErrorText,
                ]}>
                {submitFeedback.message}
              </Text>
            </View>
          ) : null}

          <DataSourceBanner source={dataSource} fallbackMessage={sourceMessage} />

          <AppCard>
            <Text style={styles.cardTitle}>1. O que você quer registrar?</Text>
            <View style={styles.modeGrid}>
              {MODE_OPTIONS.map((option) => {
                const isActive = option.id === mode;
                return (
                  <Pressable
                    key={option.id}
                    style={[styles.modeButton, isActive && styles.modeButtonActive]}
                    onPress={() => setMode(option.id)}>
                    <View style={[styles.modeIcon, isActive && styles.modeIconActive]}>
                      <Ionicons
                        name={option.icon as never}
                        size={20}
                        color={isActive ? tokens.colors.textInverse : tokens.colors.primaryStrong}
                      />
                    </View>
                    <Text style={[styles.modeLabel, isActive && styles.modeLabelActive]}>{option.label}</Text>
                    <Text style={[styles.modeHelper, isActive && styles.modeHelperActive]}>{option.helper}</Text>
                  </Pressable>
                );
              })}
            </View>
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>2. Quanto foi?</Text>
            <TextInput
              value={amountInput}
              onChangeText={setAmountInput}
              placeholder="0,00"
              placeholderTextColor={tokens.colors.textMuted}
              keyboardType={Platform.OS === 'ios' ? 'decimal-pad' : 'numeric'}
              style={styles.amountInput}
            />
            <Text style={styles.amountPreview}>
              {amount > 0 ? formatCurrency(amount) : 'Digite um valor para ver o resumo'}
            </Text>
            {errors.amount ? <Text style={styles.errorText}>{errors.amount}</Text> : null}
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>3. Os detalhes essenciais</Text>
            {mode !== 'transfer' ? (
              <>
                <Text style={styles.fieldLabel}>Descrição</Text>
                <TextInput
                  value={description}
                  onChangeText={setDescription}
                  placeholder={mode === 'income' ? 'Ex: Salário, freela, comissão' : 'Ex: Mercado, Uber, farmácia'}
                  placeholderTextColor={tokens.colors.textMuted}
                  style={styles.fieldInput}
                />
                {errors.description ? <Text style={styles.errorText}>{errors.description}</Text> : null}
              </>
            ) : (
              <>
                <Text style={styles.fieldLabel}>Descrição opcional</Text>
                <TextInput
                  value={description}
                  onChangeText={setDescription}
                  placeholder="Ex: Reserva para despesas da casa"
                  placeholderTextColor={tokens.colors.textMuted}
                  style={styles.fieldInput}
                />
              </>
            )}

            <Text style={styles.fieldLabel}>Data</Text>
            <TextInput
              value={date}
              onChangeText={setDate}
              placeholder="YYYY-MM-DD"
              placeholderTextColor={tokens.colors.textMuted}
              style={styles.fieldInput}
            />
            {errors.date ? <Text style={styles.errorText}>{errors.date}</Text> : null}

            <Text style={styles.fieldLabel}>Observação opcional</Text>
            <TextInput
              value={note}
              onChangeText={setNote}
              placeholder="Algum detalhe que ajude a lembrar depois"
              placeholderTextColor={tokens.colors.textMuted}
              style={[styles.fieldInput, styles.noteInput]}
              multiline
            />
          </AppCard>

          <AppCard>
            <Text style={styles.cardTitle}>
              {mode === 'transfer' ? '4. De onde para onde?' : '4. Em qual conta entrou?'}
            </Text>
            <Text style={styles.fieldLabel}>{mode === 'income' ? 'Conta de entrada' : 'Conta principal'}</Text>
            <View style={styles.choiceList}>
              {availableAccounts.map((account) => (
                <ChoiceButton
                  key={account.id}
                  selected={account.id === accountId}
                  label={account.name}
                  helper={account.subtitle}
                  onPress={() => setAccountId(account.id)}
                />
              ))}
            </View>
            {errors.account ? <Text style={styles.errorText}>{errors.account}</Text> : null}

            {mode === 'transfer' ? (
              <>
                <Text style={styles.fieldLabel}>Conta de destino</Text>
                <View style={styles.choiceList}>
                  {availableAccounts.map((account) => (
                    <ChoiceButton
                      key={`${account.id}-dest`}
                      selected={account.id === destinationAccountId}
                      label={account.name}
                      helper={account.subtitle}
                      onPress={() => setDestinationAccountId(account.id)}
                    />
                  ))}
                </View>
                {errors.destination ? <Text style={styles.errorText}>{errors.destination}</Text> : null}
              </>
            ) : null}
          </AppCard>

          {mode !== 'transfer' ? (
            <AppCard>
              <Text style={styles.cardTitle}>5. Qual categoria combina mais?</Text>
              <View style={styles.categoryGrid}>
                {availableCategories.map((category) => (
                  <Pressable
                    key={category.id}
                    style={[styles.categoryButton, category.id === categoryId && styles.categoryButtonActive]}
                    onPress={() => setCategoryId(category.id)}>
                    <View
                      style={[
                        styles.categoryIcon,
                        category.id === categoryId && styles.categoryIconActive,
                      ]}>
                      <Ionicons
                        name={category.icon as never}
                        size={18}
                        color={category.id === categoryId ? tokens.colors.textInverse : tokens.colors.primaryStrong}
                      />
                    </View>
                    <Text
                      style={[
                        styles.categoryLabel,
                        category.id === categoryId && styles.categoryLabelActive,
                      ]}>
                      {category.label}
                    </Text>
                  </Pressable>
                ))}
              </View>
              {errors.category ? <Text style={styles.errorText}>{errors.category}</Text> : null}
            </AppCard>
          ) : null}

          <AppCard>
            <Text style={styles.cardTitle}>6. Já foi pago ou recebido?</Text>
            <View style={styles.statusRow}>
              <StatusButton
                label={mode === 'income' ? 'Já entrou' : 'Já foi pago'}
                helper="Atualiza seu saldo real"
                selected={isPaid}
                onPress={() => setIsPaid(true)}
              />
              <StatusButton
                label="Ainda não"
                helper="Fica como pendente"
                selected={!isPaid}
                onPress={() => setIsPaid(false)}
              />
            </View>
          </AppCard>
        </ScrollView>

        <View style={styles.saveBar}>
          <View style={styles.saveSummary}>
            <Text style={styles.saveLabel}>Resumo</Text>
            <Text style={styles.saveValue}>{amount > 0 ? formatCurrency(amount) : 'Sem valor ainda'}</Text>
          </View>
          <Pressable
            style={[styles.saveButton, isSubmitting && styles.saveButtonDisabled]}
            onPress={() => {
              void submit();
            }}>
            <Text style={styles.saveButtonText}>{isSubmitting ? 'Salvando...' : modeMeta.submitLabel}</Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

type ChoiceButtonProps = {
  label: string;
  helper: string;
  selected: boolean;
  onPress: () => void;
};

function ChoiceButton({ label, helper, selected, onPress }: ChoiceButtonProps) {
  return (
    <Pressable style={[styles.choiceButton, selected && styles.choiceButtonActive]} onPress={onPress}>
      <Text style={[styles.choiceLabel, selected && styles.choiceLabelActive]}>{label}</Text>
      <Text style={[styles.choiceHelper, selected && styles.choiceHelperActive]}>{helper}</Text>
    </Pressable>
  );
}

type StatusButtonProps = {
  label: string;
  helper: string;
  selected: boolean;
  onPress: () => void;
};

function StatusButton({ label, helper, selected, onPress }: StatusButtonProps) {
  return (
    <Pressable style={[styles.statusButton, selected && styles.statusButtonActive]} onPress={onPress}>
      <Text style={[styles.statusLabel, selected && styles.statusLabelActive]}>{label}</Text>
      <Text style={[styles.statusHelper, selected && styles.statusHelperActive]}>{helper}</Text>
    </Pressable>
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
    borderColor: '#bde7cf',
    backgroundColor: '#ecfdf3',
  },
  feedbackError: {
    borderColor: '#f3c7c1',
    backgroundColor: '#fff1ef',
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
  modeGrid: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  modeButton: {
    flex: 1,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
    gap: 8,
  },
  modeButtonActive: {
    backgroundColor: tokens.colors.secondary,
    borderColor: tokens.colors.secondary,
  },
  modeIcon: {
    width: 40,
    height: 40,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modeIconActive: {
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  modeLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  modeLabelActive: {
    color: tokens.colors.textInverse,
  },
  modeHelper: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  modeHelperActive: {
    color: 'rgba(255,255,255,0.74)',
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
  noteInput: {
    minHeight: 96,
    textAlignVertical: 'top',
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
    marginBottom: tokens.spacing.sm,
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
    ...tokens.typography.small,
  },
  choiceHelperActive: {
    color: tokens.colors.primaryStrong,
  },
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.sm,
  },
  categoryButton: {
    width: '48%',
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
    gap: 10,
  },
  categoryButtonActive: {
    backgroundColor: tokens.colors.secondary,
    borderColor: tokens.colors.secondary,
  },
  categoryIcon: {
    width: 38,
    height: 38,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  categoryIconActive: {
    backgroundColor: 'rgba(255,255,255,0.16)',
  },
  categoryLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  categoryLabelActive: {
    color: tokens.colors.textInverse,
  },
  statusRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  statusButton: {
    flex: 1,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
    gap: 6,
  },
  statusButtonActive: {
    backgroundColor: '#ecfdf3',
    borderColor: '#bde7cf',
  },
  statusLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  statusLabelActive: {
    color: tokens.colors.success,
  },
  statusHelper: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  statusHelperActive: {
    color: tokens.colors.success,
  },
  errorText: {
    marginTop: -8,
    marginBottom: tokens.spacing.md,
    color: tokens.colors.danger,
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
