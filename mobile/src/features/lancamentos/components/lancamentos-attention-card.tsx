import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { LancamentoItem } from '@/src/features/lancamentos/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { formatShortDate } from '@/src/lib/formatters/date';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type LancamentosAttentionCardProps = {
  title: string;
  description: string;
  items: LancamentoItem[];
};

export function LancamentosAttentionCard({
  title,
  description,
  items,
}: LancamentosAttentionCardProps) {
  return (
    <AppCard style={styles.card}>
      <View style={styles.header}>
        <View style={styles.flag}>
          <Ionicons name="alert-circle-outline" size={18} color={tokens.colors.warning} />
        </View>
        <View style={styles.headerContent}>
          <Text style={styles.title}>{title}</Text>
          <Text style={styles.description}>{description}</Text>
        </View>
      </View>

      <View style={styles.list}>
        {items.map((item) => (
          <View key={item.id} style={styles.row}>
            <View style={styles.rowContent}>
              <Text style={styles.itemTitle}>{item.title}</Text>
              <Text style={styles.itemMeta}>
                {item.account} • {item.note ?? `Vence em ${formatShortDate(item.dueDate ?? item.date)}`}
              </Text>
            </View>
            <Text style={[styles.amount, item.type === 'expense' ? styles.expense : styles.income]}>
              {formatCurrency(item.amount)}
            </Text>
          </View>
        ))}
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fffaf1',
    borderColor: '#f2d6a3',
    gap: tokens.spacing.md,
  },
  header: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  flag: {
    width: 34,
    height: 34,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff1cf',
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerContent: {
    flex: 1,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    marginTop: 2,
    ...tokens.typography.small,
  },
  list: {
    gap: tokens.spacing.md,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: tokens.spacing.sm,
  },
  rowContent: {
    flex: 1,
    gap: 2,
  },
  itemTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  itemMeta: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  amount: {
    ...tokens.typography.mono,
  },
  expense: {
    color: tokens.colors.danger,
  },
  income: {
    color: tokens.colors.success,
  },
});
