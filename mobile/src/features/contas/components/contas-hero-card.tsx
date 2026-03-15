import { StyleSheet, Text, View } from 'react-native';

import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type ContasHeroCardProps = {
  totalBalance: number;
  everydayBalance: number;
  reserveBalance: number;
  activeCount: number;
  archivedCount: number;
  negativeCount: number;
};

function StatItem({
  label,
  value,
  tone = 'default',
}: {
  label: string;
  value: string;
  tone?: 'default' | 'danger';
}) {
  return (
    <View style={styles.statItem}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={[styles.statValue, tone === 'danger' && styles.statValueDanger]}>{value}</Text>
    </View>
  );
}

export function ContasHeroCard({
  totalBalance,
  everydayBalance,
  reserveBalance,
  activeCount,
  archivedCount,
  negativeCount,
}: ContasHeroCardProps) {
  return (
    <AppCard style={styles.card}>
      <Text style={styles.eyebrow}>Saldo total nas contas ativas</Text>
      <Text style={styles.balance}>{formatCurrency(totalBalance)}</Text>
      <Text style={styles.description}>
        O usuario enxerga primeiro o que esta disponivel nas contas ativas e depois entende quanto esta separado como reserva.
      </Text>

      <View style={styles.statsRow}>
        <StatItem label="Dia a dia" value={formatCurrency(everydayBalance)} />
        <StatItem label="Reserva" value={formatCurrency(reserveBalance)} />
      </View>

      <View style={styles.statsRow}>
        <StatItem label="Contas ativas" value={String(activeCount)} />
        <StatItem label="Arquivadas" value={String(archivedCount)} />
        <StatItem label="No vermelho" value={String(negativeCount)} tone={negativeCount > 0 ? 'danger' : 'default'} />
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
  statValueDanger: {
    color: '#ffd1cd',
  },
});
