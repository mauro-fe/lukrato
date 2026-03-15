import { StyleSheet, Text, View } from 'react-native';

import { CartaoTone } from '@/src/features/cartoes/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type CartoesFocusCardProps = {
  title: string;
  description: string;
  amount: number;
  supportText: string;
  tone: CartaoTone;
};

export function CartoesFocusCard({
  title,
  description,
  amount,
  supportText,
  tone,
}: CartoesFocusCardProps) {
  const toneStyle =
    tone === 'negative' ? styles.negative : tone === 'warning' ? styles.warning : styles.positive;
  const amountStyle =
    tone === 'negative'
      ? styles.negativeAmount
      : tone === 'warning'
        ? styles.warningAmount
        : styles.positiveAmount;

  return (
    <AppCard style={[styles.card, toneStyle]}>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.description}>{description}</Text>

      <View style={styles.amountRow}>
        <Text style={[styles.amount, amountStyle]}>{formatCurrency(amount)}</Text>
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
  amountRow: {
    gap: 4,
  },
  amount: {
    ...tokens.typography.heading,
  },
  positiveAmount: {
    color: tokens.colors.success,
  },
  warningAmount: {
    color: tokens.colors.warning,
  },
  negativeAmount: {
    color: tokens.colors.danger,
  },
  supportText: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
});
