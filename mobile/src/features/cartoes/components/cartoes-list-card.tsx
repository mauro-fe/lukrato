import { StyleSheet, Text, View } from 'react-native';

import { CartaoOverview } from '@/src/features/cartoes/types';
import { formatCurrency } from '@/src/lib/formatters/currency';
import { formatShortDate } from '@/src/lib/formatters/date';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type CartoesListCardProps = {
  cards: CartaoOverview[];
};

export function CartoesListCard({ cards }: CartoesListCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Seus cartoes organizados</Text>
      <Text style={styles.description}>
        Cada linha mostra limite, fatura, vencimento e conta vinculada sem obrigar o usuario a abrir outra tela.
      </Text>

      {!cards.length ? (
        <View style={styles.emptyState}>
          <Text style={styles.emptyTitle}>Nenhum cartao cadastrado ainda</Text>
          <Text style={styles.emptyDescription}>
            Quando o primeiro cartao entrar, esta area vai destacar o limite e a proxima fatura.
          </Text>
        </View>
      ) : (
        <View style={styles.list}>
          {cards.map((card) => {
            const clampedUsage = Math.max(0, Math.min(card.usagePercent, 100));

            return (
              <View key={card.id} style={styles.item}>
                <View style={styles.itemHeader}>
                  <View style={styles.identity}>
                    <View style={[styles.accentBar, { backgroundColor: card.accentColor }]} />
                    <View style={styles.copy}>
                      <View style={styles.titleRow}>
                        <Text style={styles.cardName}>{card.name}</Text>
                        {card.needsAttention ? <Text style={styles.attentionBadge}>Atencao</Text> : null}
                      </View>
                      <Text style={styles.meta}>
                        {card.brandLabel} final {card.lastDigits} • {card.linkedInstitution}
                      </Text>
                      <Text style={styles.subMeta}>Paga pela conta {card.linkedAccount}</Text>
                    </View>
                  </View>

                  <View style={styles.rightSummary}>
                    <Text style={styles.rightLabel}>Livre</Text>
                    <Text style={styles.rightValue}>{formatCurrency(card.availableLimit)}</Text>
                  </View>
                </View>

                <View style={styles.metricGrid}>
                  <MetricItem label="Fatura" value={formatCurrency(card.invoiceAmount)} />
                  <MetricItem label="Ja usado" value={formatCurrency(card.usedLimit)} />
                </View>

                <View style={styles.progressSection}>
                  <View style={styles.progressHeader}>
                    <Text style={styles.progressLabel}>Uso do limite</Text>
                    <Text style={styles.progressValue}>{clampedUsage.toFixed(0)}%</Text>
                  </View>
                  <View style={styles.progressTrack}>
                    <View
                      style={[
                        styles.progressFill,
                        {
                          width: `${clampedUsage}%`,
                          backgroundColor: card.accentColor,
                        },
                      ]}
                    />
                  </View>
                  <Text style={styles.progressSupport}>
                    {formatCurrency(card.usedLimit)} de {formatCurrency(card.totalLimit)}
                  </Text>
                </View>

                <View style={styles.footer}>
                  <View style={styles.footerBlock}>
                    <Text style={styles.footerLabel}>Vencimento</Text>
                    <Text style={styles.footerValue}>{formatShortDate(card.nextDueDate)}</Text>
                  </View>
                  <View style={styles.footerBlock}>
                    <Text style={styles.footerLabel}>Status</Text>
                    <Text style={styles.footerValue}>{card.statusLabel}</Text>
                  </View>
                  <View style={styles.footerBlock}>
                    <Text style={styles.footerLabel}>Pendencias</Text>
                    <Text style={styles.footerValue}>{String(card.pendingInvoices)}</Text>
                  </View>
                </View>
              </View>
            );
          })}
        </View>
      )}
    </AppCard>
  );
}

function MetricItem({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.metricItem}>
      <Text style={styles.metricLabel}>{label}</Text>
      <Text style={styles.metricValue}>{value}</Text>
    </View>
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
    gap: tokens.spacing.md,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    padding: tokens.spacing.md,
  },
  itemHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  identity: {
    flex: 1,
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'stretch',
  },
  accentBar: {
    width: 5,
    borderRadius: tokens.radius.pill,
  },
  copy: {
    flex: 1,
    gap: 2,
  },
  titleRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: tokens.spacing.xs,
    alignItems: 'center',
  },
  cardName: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  attentionBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff1ef',
    color: tokens.colors.danger,
    ...tokens.typography.caption,
  },
  meta: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  subMeta: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  rightSummary: {
    alignItems: 'flex-end',
    gap: 2,
  },
  rightLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  rightValue: {
    color: tokens.colors.secondary,
    textAlign: 'right',
    ...tokens.typography.small,
  },
  metricGrid: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  metricItem: {
    flex: 1,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surface,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    padding: tokens.spacing.md,
    gap: 4,
  },
  metricLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  metricValue: {
    color: tokens.colors.text,
    ...tokens.typography.small,
  },
  progressSection: {
    gap: 6,
  },
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: tokens.spacing.sm,
  },
  progressLabel: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  progressValue: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  progressTrack: {
    width: '100%',
    height: 10,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#d9e4ef',
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: tokens.radius.pill,
  },
  progressSupport: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  footer: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  footerBlock: {
    flex: 1,
    gap: 4,
  },
  footerLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  footerValue: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
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
