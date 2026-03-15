import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type HeroBalanceCardProps = {
  balance: number;
  income: number;
  expenses: number;
  reserved: number;
  monthlyResult: number;
  isLoading: boolean;
};

export function HeroBalanceCard({
  balance,
  income,
  expenses,
  reserved,
  monthlyResult,
  isLoading,
}: HeroBalanceCardProps) {
  return (
    <AppCard style={styles.card}>
      <View style={styles.header}>
        <View>
          <Text style={styles.label}>Saldo para movimentar agora</Text>
          <Text style={styles.helper}>Resumo feito para voce entender rapido</Text>
        </View>
        <View style={styles.badge}>
          <Text style={styles.badgeText}>Mes atual</Text>
        </View>
      </View>

      {isLoading ? (
        <View style={styles.loadingWrap}>
          <ActivityIndicator size="small" color={tokens.colors.primary} />
          <Text style={styles.loadingText}>Montando sua visao do mes...</Text>
        </View>
      ) : (
        <>
          <Text style={styles.balance}>{formatCurrency(balance)}</Text>
          <View style={styles.metricsGrid}>
            <Metric label="Entrou" value={income} tone="success" />
            <Metric label="Saiu" value={expenses} tone="danger" />
            <Metric label="Reservado" value={reserved} tone="secondary" />
          </View>
          <View style={styles.footer}>
            <Text style={styles.footerLabel}>Resultado do mes</Text>
            <Text style={[styles.footerValue, monthlyResult >= 0 ? styles.positive : styles.negative]}>
              {formatCurrency(monthlyResult)}
            </Text>
          </View>
        </>
      )}
    </AppCard>
  );
}

type MetricProps = {
  label: string;
  value: number;
  tone: 'success' | 'danger' | 'secondary';
};

function Metric({ label, value, tone }: MetricProps) {
  return (
    <View style={styles.metricCard}>
      <Text style={styles.metricLabel}>{label}</Text>
      <Text
        style={[
          styles.metricValue,
          tone === 'success' && styles.positive,
          tone === 'danger' && styles.negative,
          tone === 'secondary' && styles.secondary,
        ]}>
        {formatCurrency(value)}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: tokens.colors.surfaceStrong,
    borderColor: '#203754',
    gap: tokens.spacing.lg,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: tokens.spacing.md,
  },
  label: {
    color: tokens.colors.textInverse,
    ...tokens.typography.title,
  },
  helper: {
    color: 'rgba(255,255,255,0.72)',
    ...tokens.typography.small,
  },
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.sm,
    paddingVertical: 6,
    borderRadius: tokens.radius.pill,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  badgeText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.caption,
  },
  loadingWrap: {
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  loadingText: {
    color: 'rgba(255,255,255,0.7)',
    ...tokens.typography.body,
  },
  balance: {
    color: tokens.colors.textInverse,
    ...tokens.typography.display,
  },
  metricsGrid: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  metricCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: tokens.radius.md,
    padding: tokens.spacing.md,
    gap: 4,
  },
  metricLabel: {
    color: 'rgba(255,255,255,0.68)',
    ...tokens.typography.caption,
  },
  metricValue: {
    ...tokens.typography.small,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: tokens.spacing.sm,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.1)',
  },
  footerLabel: {
    color: 'rgba(255,255,255,0.7)',
    ...tokens.typography.small,
  },
  footerValue: {
    ...tokens.typography.mono,
  },
  positive: {
    color: '#7be6a5',
  },
  negative: {
    color: '#ff9a8f',
  },
  secondary: {
    color: '#ffd39e',
  },
});
