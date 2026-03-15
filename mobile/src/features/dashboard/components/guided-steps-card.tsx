import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { DashboardGuidedStep } from '@/src/features/dashboard/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type GuidedStepsCardProps = {
  steps: DashboardGuidedStep[];
};

export function GuidedStepsCard({ steps }: GuidedStepsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Comece por aqui</Text>
      <Text style={styles.description}>
        Se o usuario estiver perdido, esta sequencia mostra exatamente o que fazer primeiro.
      </Text>

      <View style={styles.list}>
        {steps.map((step, index) => (
          <View key={step.id} style={styles.stepRow}>
            <View style={[styles.stepIndex, step.done && styles.stepIndexDone]}>
              <Text style={[styles.stepIndexText, step.done && styles.stepIndexTextDone]}>{index + 1}</Text>
            </View>
            <View style={styles.stepContent}>
              <Text style={styles.stepTitle}>{step.title}</Text>
              <Text style={styles.stepDescription}>{step.description}</Text>
              <Pressable style={styles.stepButton}>
                <Text style={styles.stepButtonText}>{step.cta}</Text>
                <Ionicons name="chevron-forward" size={14} color={tokens.colors.primaryStrong} />
              </Pressable>
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
  stepRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  stepIndex: {
    width: 32,
    height: 32,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepIndexDone: {
    backgroundColor: '#ecfdf3',
    borderColor: '#bde7cf',
  },
  stepIndexText: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  stepIndexTextDone: {
    color: tokens.colors.success,
  },
  stepContent: {
    flex: 1,
    gap: 4,
  },
  stepTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  stepDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  stepButton: {
    marginTop: 4,
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: tokens.spacing.sm,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
  },
  stepButtonText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.small,
  },
});
