import { Pressable, StyleSheet, Text, View } from 'react-native';

import { DashboardTransaction } from '@/src/features/dashboard/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { formatShortDate } from '@/src/lib/formatters/date';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type RecentActivityCardProps = {
  transactions: DashboardTransaction[];
};

export function RecentActivityCard({ transactions }: RecentActivityCardProps) {
  return (
    <AppCard>
      <View style={styles.header}>
        <Text style={styles.title}>Ultimos lancamentos</Text>
        <Pressable style={styles.link}>
          <Text style={styles.linkText}>Ver tudo</Text>
        </Pressable>
      </View>

      <View style={styles.list}>
        {transactions.map((transaction) => (
          <View key={transaction.id} style={styles.row}>
            <View style={[styles.statusDot, transaction.kind === 'income' ? styles.statusIncome : styles.statusExpense]} />
            <View style={styles.info}>
              <Text style={styles.transactionTitle}>{transaction.title}</Text>
              <Text style={styles.transactionMeta}>
                {transaction.category} • {transaction.account} • {formatShortDate(transaction.date)}
              </Text>
            </View>
            <Text style={[styles.amount, transaction.kind === 'income' ? styles.income : styles.expense]}>
              {formatCurrency(transaction.amount)}
            </Text>
          </View>
        ))}
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: tokens.spacing.md,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  link: {
    paddingHorizontal: tokens.spacing.sm,
    paddingVertical: 6,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surfaceAlt,
  },
  linkText: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  list: {
    gap: tokens.spacing.md,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
  },
  statusDot: {
    width: 10,
    height: 10,
    borderRadius: tokens.radius.pill,
  },
  statusIncome: {
    backgroundColor: tokens.colors.success,
  },
  statusExpense: {
    backgroundColor: tokens.colors.danger,
  },
  info: {
    flex: 1,
    gap: 2,
  },
  transactionTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  transactionMeta: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  amount: {
    ...tokens.typography.mono,
  },
  income: {
    color: tokens.colors.success,
  },
  expense: {
    color: tokens.colors.danger,
  },
});
