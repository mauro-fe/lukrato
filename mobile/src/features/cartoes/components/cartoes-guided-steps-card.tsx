import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { CartaoGuideStep } from '@/src/features/cartoes/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type CartoesGuidedStepsCardProps = {
  steps: CartaoGuideStep[];
};

export function CartoesGuidedStepsCard({ steps }: CartoesGuidedStepsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Se o usuario travar, a ordem e esta</Text>
      <View style={styles.list}>
        {steps.map((step, index) => (
          <View key={step.id} style={styles.item}>
            <View style={[styles.badge, step.done ? styles.badgeDone : styles.badgePending]}>
              {step.done ? (
                <Ionicons name="checkmark" size={16} color={tokens.colors.success} />
              ) : (
                <Text style={styles.badgeText}>{index + 1}</Text>
              )}
            </View>

            <View style={styles.copy}>
              <Text style={styles.itemTitle}>{step.title}</Text>
              <Text style={styles.itemDescription}>{step.description}</Text>
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
    marginBottom: tokens.spacing.md,
    ...tokens.typography.title,
  },
  list: {
    gap: tokens.spacing.md,
  },
  item: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  badge: {
    width: 28,
    height: 28,
    borderRadius: tokens.radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  badgeDone: {
    backgroundColor: '#eef8f2',
    borderColor: '#cae7d7',
  },
  badgePending: {
    backgroundColor: '#fff3e8',
    borderColor: '#f0d2b1',
  },
  badgeText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.small,
  },
  copy: {
    flex: 1,
    gap: 4,
  },
  itemTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  itemDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
});
