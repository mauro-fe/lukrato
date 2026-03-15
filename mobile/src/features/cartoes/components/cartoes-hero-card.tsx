import { StyleSheet, Text, View } from 'react-native';

import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type CartoesHeroCardProps = {
  totalCards: number;
  totalLimit: number;
  availableLimit: number;
  usedLimit: number;
  usagePercent: number;
  pendingInvoiceCount: number;
};

function StatItem({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.statItem}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
    </View>
  );
}

export function CartoesHeroCard({
  totalCards,
  totalLimit,
  availableLimit,
  usedLimit,
  usagePercent,
  pendingInvoiceCount,
}: CartoesHeroCardProps) {
  return (
    <AppCard style={styles.card}>
      <Text style={styles.eyebrow}>Limite livre total</Text>
      <Text style={styles.balance}>{formatCurrency(availableLimit)}</Text>
      <Text style={styles.description}>
        O usuario ve de cara quanto ainda pode usar antes de abrir cartao por cartao.
      </Text>

      <View style={styles.statsRow}>
        <StatItem label="Limite total" value={formatCurrency(totalLimit)} />
        <StatItem label="Ja usado" value={formatCurrency(usedLimit)} />
      </View>

      <View style={styles.statsRow}>
        <StatItem label="Cartoes ativos" value={String(totalCards)} />
        <StatItem label="Faturas pendentes" value={String(pendingInvoiceCount)} />
        <StatItem label="Uso medio" value={`${usagePercent.toFixed(0)}%`} />
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: tokens.colors.surfaceStrong,
    borderColor: '#203852',
    gap: tokens.spacing.md,
  },
  eyebrow: {
    color: '#c7d7e9',
    ...tokens.typography.caption,
  },
  balance: {
    color: tokens.colors.textInverse,
    ...tokens.typography.display,
  },
  description: {
    color: '#d9e5f1',
    ...tokens.typography.body,
  },
  statsRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  statItem: {
    flex: 1,
    borderRadius: tokens.radius.md,
    backgroundColor: 'rgba(255,255,255,0.08)',
    padding: tokens.spacing.md,
    gap: 6,
  },
  statLabel: {
    color: '#c7d7e9',
    ...tokens.typography.caption,
  },
  statValue: {
    color: tokens.colors.textInverse,
    ...tokens.typography.title,
  },
});
