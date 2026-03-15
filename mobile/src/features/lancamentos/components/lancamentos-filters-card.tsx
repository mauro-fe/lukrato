import { Ionicons } from '@expo/vector-icons';
import { Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

import { LancamentoFilter } from '@/src/features/lancamentos/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

const FILTER_OPTIONS: { id: LancamentoFilter; label: string }[] = [
  { id: 'all', label: 'Tudo' },
  { id: 'pending', label: 'Pendentes' },
  { id: 'paid', label: 'Pagos' },
  { id: 'income', label: 'Receitas' },
  { id: 'expense', label: 'Despesas' },
];

type LancamentosFiltersCardProps = {
  activeFilter: LancamentoFilter;
  searchQuery: string;
  isPending: boolean;
  onChangeFilter: (filter: LancamentoFilter) => void;
  onChangeSearch: (value: string) => void;
};

export function LancamentosFiltersCard({
  activeFilter,
  searchQuery,
  isPending,
  onChangeFilter,
  onChangeSearch,
}: LancamentosFiltersCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Ache o que precisa sem se perder</Text>

      <View style={styles.searchWrap}>
        <Ionicons name="search-outline" size={18} color={tokens.colors.textMuted} />
        <TextInput
          value={searchQuery}
          onChangeText={onChangeSearch}
          placeholder="Buscar por descricao, conta ou categoria"
          placeholderTextColor={tokens.colors.textMuted}
          style={styles.input}
          autoCapitalize="none"
          autoCorrect={false}
        />
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {FILTER_OPTIONS.map((filter) => {
          const isActive = filter.id === activeFilter;
          return (
            <Pressable
              key={filter.id}
              style={[styles.chip, isActive && styles.chipActive]}
              onPress={() => onChangeFilter(filter.id)}>
              <Text style={[styles.chipText, isActive && styles.chipTextActive]}>{filter.label}</Text>
            </Pressable>
          );
        })}
      </ScrollView>

      {isPending ? <Text style={styles.helper}>Atualizando a lista...</Text> : null}
    </AppCard>
  );
}

const styles = StyleSheet.create({
  title: {
    color: tokens.colors.text,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.title,
  },
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    borderRadius: tokens.radius.md,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 12,
  },
  input: {
    flex: 1,
    color: tokens.colors.text,
    padding: 0,
    ...tokens.typography.body,
  },
  chips: {
    gap: tokens.spacing.sm,
    paddingTop: tokens.spacing.md,
  },
  chip: {
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 10,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  chipActive: {
    backgroundColor: '#fff3e8',
    borderColor: '#f3c28a',
  },
  chipText: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  chipTextActive: {
    color: tokens.colors.primaryStrong,
  },
  helper: {
    marginTop: tokens.spacing.sm,
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
});
