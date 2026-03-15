import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { ContaQuickAction } from '@/src/features/contas/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type ContasQuickActionsCardProps = {
  actions: ContaQuickAction[];
  onPressAction: (actionId: ContaQuickAction['id']) => void;
};

export function ContasQuickActionsCard({
  actions,
  onPressAction,
}: ContasQuickActionsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Acoes rapidas sem menu escondido</Text>
      <View style={styles.list}>
        {actions.map((action) => (
          <Pressable
            key={action.id}
            style={({ pressed }) => [styles.actionCard, pressed && styles.actionCardPressed]}
            onPress={() => onPressAction(action.id)}>
            <View style={styles.iconWrap}>
              <Ionicons name={action.icon as never} size={22} color={tokens.colors.primaryStrong} />
            </View>

            <View style={styles.copy}>
              <Text style={styles.label}>{action.label}</Text>
              <Text style={styles.description}>{action.description}</Text>
            </View>

            <Ionicons name="chevron-forward" size={20} color={tokens.colors.textMuted} />
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
  list: {
    gap: tokens.spacing.sm,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
  },
  actionCardPressed: {
    opacity: 0.86,
  },
  iconWrap: {
    width: 42,
    height: 42,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  copy: {
    flex: 1,
    gap: 2,
  },
  label: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
});
