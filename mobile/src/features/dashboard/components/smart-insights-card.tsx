import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { DashboardInsight } from '@/src/features/dashboard/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type SmartInsightsCardProps = {
  insights: DashboardInsight[];
};

export function SmartInsightsCard({ insights }: SmartInsightsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Seu mes em portugues claro</Text>
      <Text style={styles.description}>
        Nada de numeros jogados na tela sem contexto. O app explica o que mudou e por que isso importa.
      </Text>

      <View style={styles.list}>
        {insights.map((insight) => (
          <View key={insight.id} style={styles.item}>
            <View style={styles.iconWrap}>
              <Ionicons name="sparkles-outline" size={16} color={tokens.colors.info} />
            </View>
            <View style={styles.content}>
              <Text style={styles.itemTitle}>{insight.title}</Text>
              <Text style={styles.itemDescription}>{insight.description}</Text>
            </View>
          </View>
        ))}
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    marginTop: 4,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.body,
  },
  list: {
    gap: tokens.spacing.md,
  },
  item: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  iconWrap: {
    width: 28,
    height: 28,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#edf4ff',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  content: {
    flex: 1,
    gap: 2,
  },
  itemTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  itemDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
});
