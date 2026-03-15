import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { CartaoAlert } from '@/src/features/cartoes/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type CartoesAlertsCardProps = {
  alerts: CartaoAlert[];
};

export function CartoesAlertsCard({ alerts }: CartoesAlertsCardProps) {
  if (!alerts.length) {
    return (
      <AppCard style={styles.emptyCard}>
        <View style={styles.emptyHeader}>
          <View style={styles.emptyIcon}>
            <Ionicons name="checkmark-circle-outline" size={18} color={tokens.colors.success} />
          </View>
          <View style={styles.emptyCopy}>
            <Text style={styles.title}>Nada urgente entre os cartoes</Text>
            <Text style={styles.description}>
              Quando surgir limite apertado ou vencimento proximo, o alerta aparece aqui no topo.
            </Text>
          </View>
        </View>
      </AppCard>
    );
  }

  return (
    <AppCard style={styles.card}>
      <Text style={styles.title}>O que merece atencao agora</Text>
      <Text style={styles.description}>
        O app junta vencimento e limite apertado no mesmo bloco para o usuario decidir rapido.
      </Text>

      <View style={styles.list}>
        {alerts.map((alert) => (
          <View
            key={alert.id}
            style={[
              styles.item,
              alert.severity === 'critical' ? styles.itemCritical : styles.itemAttention,
            ]}>
            <View style={styles.itemHeader}>
              <View
                style={[
                  styles.itemIcon,
                  alert.severity === 'critical' ? styles.itemIconCritical : styles.itemIconAttention,
                ]}>
                <Ionicons
                  name={alert.type === 'due_soon' ? 'alarm-outline' : 'alert-circle-outline'}
                  size={18}
                  color={alert.severity === 'critical' ? tokens.colors.danger : tokens.colors.warning}
                />
              </View>
              <View style={styles.itemCopy}>
                <Text style={styles.itemTitle}>{alert.title}</Text>
                <Text style={styles.itemDescription}>{alert.description}</Text>
              </View>
              <Text style={styles.itemAmount}>{formatCurrency(alert.amount)}</Text>
            </View>
          </View>
        ))}
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  card: {
    gap: tokens.spacing.md,
  },
  emptyCard: {
    backgroundColor: '#eef8f2',
    borderColor: '#cae7d7',
  },
  emptyHeader: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  emptyIcon: {
    width: 36,
    height: 36,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#dff5e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyCopy: {
    flex: 1,
    gap: 2,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
  list: {
    gap: tokens.spacing.sm,
  },
  item: {
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    padding: tokens.spacing.md,
  },
  itemCritical: {
    backgroundColor: '#fff1ef',
    borderColor: '#f3c7c1',
  },
  itemAttention: {
    backgroundColor: '#fffaf1',
    borderColor: '#f2d6a3',
  },
  itemHeader: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  itemIcon: {
    width: 36,
    height: 36,
    borderRadius: tokens.radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  itemIconCritical: {
    backgroundColor: '#ffe2de',
  },
  itemIconAttention: {
    backgroundColor: '#fff1cf',
  },
  itemCopy: {
    flex: 1,
    gap: 2,
  },
  itemTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  itemDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  itemAmount: {
    color: tokens.colors.secondary,
    textAlign: 'right',
    ...tokens.typography.mono,
  },
});
