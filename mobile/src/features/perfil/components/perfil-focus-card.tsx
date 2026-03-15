import { StyleSheet, Text, View } from 'react-native';

import { PerfilTone } from '@/src/features/perfil/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type PerfilFocusCardProps = {
  title: string;
  description: string;
  valueLabel: string;
  supportText: string;
  tone: PerfilTone;
};

export function PerfilFocusCard({
  title,
  description,
  valueLabel,
  supportText,
  tone,
}: PerfilFocusCardProps) {
  const toneStyle =
    tone === 'negative' ? styles.negative : tone === 'warning' ? styles.warning : styles.positive;
  const valueStyle =
    tone === 'negative'
      ? styles.negativeValue
      : tone === 'warning'
        ? styles.warningValue
        : styles.positiveValue;

  return (
    <AppCard style={[styles.card, toneStyle]}>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.description}>{description}</Text>

      <View style={styles.valueRow}>
        <Text style={[styles.valueLabel, valueStyle]}>{valueLabel}</Text>
        <Text style={styles.supportText}>{supportText}</Text>
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  card: {
    gap: tokens.spacing.sm,
  },
  positive: {
    backgroundColor: '#eef8f2',
    borderColor: '#cae7d7',
  },
  warning: {
    backgroundColor: '#fff8e8',
    borderColor: '#f1ddb0',
  },
  negative: {
    backgroundColor: '#feefee',
    borderColor: '#f3c7c1',
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
  valueRow: {
    gap: 4,
  },
  valueLabel: {
    ...tokens.typography.heading,
  },
  positiveValue: {
    color: tokens.colors.success,
  },
  warningValue: {
    color: tokens.colors.warning,
  },
  negativeValue: {
    color: tokens.colors.danger,
  },
  supportText: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
});
