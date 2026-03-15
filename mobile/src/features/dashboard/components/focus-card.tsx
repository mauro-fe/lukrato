import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type FocusCardProps = {
  title: string;
  description: string;
  amount: number;
  supportText: string;
};

export function FocusCard({ title, description, amount, supportText }: FocusCardProps) {
  return (
    <AppCard>
      <View style={styles.header}>
        <View style={styles.iconWrap}>
          <Ionicons name="flag-outline" size={18} color={tokens.colors.primaryStrong} />
        </View>
        <Text style={styles.title}>{title}</Text>
      </View>

      <Text style={styles.description}>{description}</Text>

      <View style={styles.footer}>
        <View>
          <Text style={styles.supportText}>{supportText}</Text>
          <Text style={styles.amount}>{formatCurrency(amount)}</Text>
        </View>
        <View style={styles.tip}>
          <Ionicons name="arrow-forward" size={16} color={tokens.colors.secondary} />
        </View>
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    marginBottom: tokens.spacing.sm,
  },
  iconWrap: {
    width: 32,
    height: 32,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  description: {
    color: tokens.colors.textMuted,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.body,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  supportText: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  amount: {
    color: tokens.colors.secondary,
    marginTop: 2,
    ...tokens.typography.mono,
  },
  tip: {
    width: 36,
    height: 36,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surfaceAlt,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
