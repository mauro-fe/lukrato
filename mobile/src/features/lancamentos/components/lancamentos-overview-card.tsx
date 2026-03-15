import { StyleSheet, Text, View } from 'react-native';

import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type LancamentosOverviewCardProps = {
  totalItems: number;
  pendingCount: number;
  pendingAmount: number;
  paidCount: number;
};

export function LancamentosOverviewCard({
  totalItems,
  pendingCount,
  pendingAmount,
  paidCount,
}: LancamentosOverviewCardProps) {
  return (
    <AppCard style={styles.card}>
      <View style={styles.header}>
        <View>
          <Text style={styles.title}>Resumo claro do mes</Text>
          <Text style={styles.description}>
            O usuario bate o olho e entende volume, pendencias e o que ja foi resolvido.
          </Text>
        </View>
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{totalItems} itens</Text>
        </View>
      </View>

      <View style={styles.metrics}>
        <Metric label="Pendentes" value={`${pendingCount}`} tone="danger" />
        <Metric label="Ja pagos" value={`${paidCount}`} tone="success" />
        <Metric label="Pede atencao" value={formatCurrency(pendingAmount)} tone="secondary" />
      </View>
    </AppCard>
  );
}

type MetricProps = {
  label: string;
  value: string;
  tone: 'danger' | 'success' | 'secondary';
};

function Metric({ label, value, tone }: MetricProps) {
  return (
    <View style={styles.metricCard}>
      <Text style={styles.metricLabel}>{label}</Text>
      <Text
        style={[
          styles.metricValue,
          tone === 'danger' && styles.danger,
          tone === 'success' && styles.success,
          tone === 'secondary' && styles.secondary,
        ]}>
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    gap: tokens.spacing.md,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: tokens.spacing.md,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    marginTop: 4,
    ...tokens.typography.small,
  },
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.sm,
    paddingVertical: 6,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
  },
  badgeText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.caption,
  },
  metrics: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  metricCard: {
    flex: 1,
    backgroundColor: tokens.colors.surfaceAlt,
    borderRadius: tokens.radius.md,
    padding: tokens.spacing.md,
    gap: 4,
  },
  metricLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  metricValue: {
    ...tokens.typography.small,
  },
  danger: {
    color: tokens.colors.danger,
  },
  success: {
    color: tokens.colors.success,
  },
  secondary: {
    color: tokens.colors.secondary,
  },
});
