import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { formatCurrency } from '@/src/lib/formatters/currency';
import { ContaGroup } from '@/src/features/contas/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type ContasGroupCardProps = {
  group: ContaGroup;
};

function AccountRow({
  account,
}: {
  account: ContaGroup['accounts'][number];
}) {
  return (
    <View style={styles.accountRow}>
      <View style={[styles.accountAccent, { backgroundColor: account.accentColor }]} />

      <View style={styles.accountMain}>
        <View style={styles.accountTopRow}>
          <View style={styles.accountIdentity}>
            <View style={styles.iconWrap}>
              <Ionicons name={account.icon as never} size={18} color={account.accentColor} />
            </View>

            <View style={styles.accountCopy}>
              <View style={styles.accountNameRow}>
                <Text style={styles.accountName}>{account.name}</Text>
                {account.isPrimary ? <Text style={styles.primaryBadge}>Principal</Text> : null}
              </View>
              <Text style={styles.accountMeta}>
                {account.institutionName} • {account.typeLabel}
              </Text>
            </View>
          </View>

          <Text style={[styles.balance, account.balance < 0 && styles.negativeBalance]}>
            {formatCurrency(account.balance)}
          </Text>
        </View>

        <View style={styles.metricsRow}>
          <Text style={styles.metricText}>Entrou {formatCurrency(account.inflow)}</Text>
          <Text style={styles.metricText}>Saiu {formatCurrency(account.outflow)}</Text>
        </View>

        <Text style={styles.note}>{account.note}</Text>
      </View>
    </View>
  );
}

export function ContasGroupCard({ group }: ContasGroupCardProps) {
  return (
    <AppCard>
      <View style={styles.header}>
        <View style={styles.headerCopy}>
          <Text style={styles.title}>{group.title}</Text>
          <Text style={styles.description}>{group.description}</Text>
        </View>
        <Text style={styles.total}>{formatCurrency(group.totalBalance)}</Text>
      </View>

      {group.accounts.length > 0 ? (
        <View style={styles.list}>
          {group.accounts.map((account) => (
            <AccountRow key={account.id} account={account} />
          ))}
        </View>
      ) : (
        <View style={styles.emptyState}>
          <Text style={styles.emptyTitle}>{group.emptyTitle}</Text>
          <Text style={styles.emptyDescription}>{group.emptyDescription}</Text>
        </View>
      )}
    </AppCard>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    marginBottom: tokens.spacing.md,
  },
  headerCopy: {
    flex: 1,
    gap: 4,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  total: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  list: {
    gap: tokens.spacing.md,
  },
  accountRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  accountAccent: {
    width: 4,
    borderRadius: tokens.radius.pill,
  },
  accountMain: {
    flex: 1,
    gap: tokens.spacing.xs,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    padding: tokens.spacing.md,
  },
  accountTopRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  accountIdentity: {
    flex: 1,
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  iconWrap: {
    width: 34,
    height: 34,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  accountCopy: {
    flex: 1,
    gap: 2,
  },
  accountNameRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.xs,
    alignItems: 'center',
  },
  accountName: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  primaryBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    color: tokens.colors.primaryStrong,
    ...tokens.typography.caption,
  },
  accountMeta: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  balance: {
    color: tokens.colors.secondary,
    textAlign: 'right',
    ...tokens.typography.small,
  },
  negativeBalance: {
    color: tokens.colors.danger,
  },
  metricsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.sm,
  },
  metricText: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  note: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  emptyState: {
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.lg,
    gap: 6,
  },
  emptyTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  emptyDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
});
