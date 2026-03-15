import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { LancamentoQuickAction } from '@/src/features/lancamentos/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type LancamentosQuickActionsCardProps = {
  actions: LancamentoQuickAction[];
  onOpenSheet: () => void;
};

export function LancamentosQuickActionsCard({
  actions,
  onOpenSheet,
}: LancamentosQuickActionsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Registrar sem pensar muito</Text>
      <Text style={styles.description}>
        O foco aqui e diminuir a duvida. O usuario escolhe a intencao antes de ver campos.
      </Text>

      <View style={styles.grid}>
        {actions.map((action) => (
          <Pressable key={action.id} style={styles.button} onPress={onOpenSheet}>
            <View
              style={[
                styles.iconWrap,
                action.tone === 'danger' && styles.iconDanger,
                action.tone === 'success' && styles.iconSuccess,
                action.tone === 'secondary' && styles.iconSecondary,
              ]}>
              <Ionicons name={action.icon as never} size={22} color={tokens.colors.textInverse} />
            </View>
            <Text style={styles.buttonLabel}>{action.label}</Text>
            <Text style={styles.buttonCaption}>{action.caption}</Text>
          </Pressable>
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
  grid: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  button: {
    flex: 1,
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.md,
    gap: 10,
  },
  iconWrap: {
    width: 42,
    height: 42,
    borderRadius: tokens.radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconDanger: {
    backgroundColor: tokens.colors.danger,
  },
  iconSuccess: {
    backgroundColor: tokens.colors.success,
  },
  iconSecondary: {
    backgroundColor: tokens.colors.secondary,
  },
  buttonLabel: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  buttonCaption: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
});
