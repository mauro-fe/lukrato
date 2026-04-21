import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { DashboardQuickAction } from '@/src/features/dashboard/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type QuickActionsGridProps = {
  actions: DashboardQuickAction[];
};

export function QuickActionsGrid({ actions }: QuickActionsGridProps) {
  const router = useRouter();

  return (
    <AppCard>
      <Text style={styles.title}>Atalhos que o usuario entende rápido</Text>
      <View style={styles.grid}>
        {actions.map((action) => (
          <Pressable
            key={action.id}
            style={({ pressed }) => [styles.actionCard, pressed && styles.actionCardPressed]}
            onPress={() => router.push(action.route)}>
            <View style={styles.iconWrap}>
              <Ionicons name={action.icon as never} size={22} color={tokens.colors.primaryStrong} />
            </View>
            <Text style={styles.label}>{action.label}</Text>
          </Pressable>
        ))}
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  title: {
    color: tokens.colors.text,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.title,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.sm,
  },
  actionCard: {
    width: '48%',
    minHeight: 112,
    padding: tokens.spacing.md,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    justifyContent: 'space-between',
  },
  actionCardPressed: {
    opacity: 0.85,
  },
  iconWrap: {
    width: 42,
    height: 42,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
});
