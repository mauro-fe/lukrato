import { StyleSheet, Text, View } from 'react-native';

import { LancamentoSection } from '@/src/features/lancamentos/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type LancamentosListCardProps = {
  sections: LancamentoSection[];
  hasResults: boolean;
};

export function LancamentosListCard({
  sections,
  hasResults,
}: LancamentosListCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Sua lista organizada</Text>
      <Text style={styles.description}>
        Agrupamos por data e deixamos o status visivel para o usuario nao abrir item por item.
      </Text>

      {!hasResults ? (
        <View style={styles.emptyState}>
          <Text style={styles.emptyTitle}>Nenhum lancamento com esse filtro</Text>
          <Text style={styles.emptyDescription}>Tente limpar a busca ou voltar para Tudo.</Text>
        </View>
      ) : (
        <View style={styles.sections}>
          {sections.map((section) => (
            <View key={section.id} style={styles.section}>
              <Text style={styles.sectionLabel}>{section.label}</Text>
              <View style={styles.items}>
                {section.items.map((item) => (
                  <View key={item.id} style={styles.row}>
                    <View style={styles.rowContent}>
                      <View style={styles.rowTop}>
                        <Text style={styles.itemTitle}>{item.title}</Text>
                        <View style={[styles.status, item.status === 'pending' ? styles.statusPending : styles.statusPaid]}>
                          <Text
                            style={[
                              styles.statusText,
                              item.status === 'pending' ? styles.statusPendingText : styles.statusPaidText,
                            ]}>
                            {item.status === 'pending' ? 'Pendente' : 'Pago'}
                          </Text>
                        </View>
                      </View>
                      <Text style={styles.meta}>
                        {item.category} • {item.account}
                      </Text>
                      {item.note ? <Text style={styles.note}>{item.note}</Text> : null}
                    </View>
                    <Text style={[styles.amount, item.type === 'expense' ? styles.expense : styles.income]}>
                      {formatCurrency(item.amount)}
                    </Text>
                  </View>
                ))}
              </View>
            </View>
          ))}
        </View>
      )}
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
  sections: {
    gap: tokens.spacing.lg,
  },
  section: {
    gap: tokens.spacing.sm,
  },
  sectionLabel: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  items: {
    gap: tokens.spacing.sm,
  },
  row: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
    padding: tokens.spacing.md,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  rowContent: {
    flex: 1,
    gap: 4,
  },
  rowTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: tokens.spacing.sm,
  },
  itemTitle: {
    flex: 1,
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  meta: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
  note: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  amount: {
    ...tokens.typography.mono,
  },
  expense: {
    color: tokens.colors.danger,
  },
  income: {
    color: tokens.colors.success,
  },
  status: {
    paddingHorizontal: tokens.spacing.sm,
    paddingVertical: 6,
    borderRadius: tokens.radius.pill,
  },
  statusPending: {
    backgroundColor: '#fff1ef',
  },
  statusPaid: {
    backgroundColor: '#ecfdf3',
  },
  statusText: {
    ...tokens.typography.caption,
  },
  statusPendingText: {
    color: tokens.colors.danger,
  },
  statusPaidText: {
    color: tokens.colors.success,
  },
  emptyState: {
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    backgroundColor: tokens.colors.surfaceAlt,
    padding: tokens.spacing.lg,
    gap: tokens.spacing.xs,
  },
  emptyTitle: {
    color: tokens.colors.text,
    ...tokens.typography.title,
  },
  emptyDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
});
