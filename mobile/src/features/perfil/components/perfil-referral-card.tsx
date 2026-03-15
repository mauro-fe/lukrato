import { Ionicons } from '@expo/vector-icons';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PerfilSnapshot } from '@/src/features/perfil/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type PerfilReferralCardProps = {
  referral: PerfilSnapshot['referral'];
  onShare: () => void;
};

export function PerfilReferralCard({ referral, onShare }: PerfilReferralCardProps) {
  const progress = referral.monthlyLimit
    ? Math.max(0, Math.min((referral.monthlyUsed / referral.monthlyLimit) * 100, 100))
    : 0;

  return (
    <AppCard>
      <Text style={styles.title}>Programa de indicacao sem enrolacao</Text>
      <Text style={styles.description}>
        O usuario enxerga o codigo, o link e o progresso do mes no mesmo lugar.
      </Text>

      <View style={styles.codeBox}>
        <Text style={styles.boxLabel}>Seu codigo</Text>
        <Text style={styles.boxValue} selectable>
          {referral.code}
        </Text>
      </View>

      <View style={styles.codeBox}>
        <Text style={styles.boxLabel}>Seu link</Text>
        <Text style={styles.linkValue} selectable>
          {referral.link}
        </Text>
      </View>

      <View style={styles.metricsRow}>
        <MetricItem label="Convites" value={String(referral.totalInvites)} />
        <MetricItem label="Concluidos" value={String(referral.completedInvites)} />
        <MetricItem label="Dias ganhos" value={String(referral.rewardDays)} />
      </View>

      <View style={styles.progressBlock}>
        <View style={styles.progressHeader}>
          <Text style={styles.progressLabel}>Uso do limite mensal</Text>
          <Text style={styles.progressValue}>
            {referral.monthlyUsed}/{referral.monthlyLimit}
          </Text>
        </View>
        <View style={styles.progressTrack}>
          <View style={[styles.progressFill, { width: `${progress}%` }]} />
        </View>
        <Text style={styles.progressHint}>
          Ainda restam {referral.monthlyRemaining} convite(s) este mes.
        </Text>
      </View>

      <Pressable style={styles.shareButton} onPress={onShare}>
        <Ionicons name="share-social-outline" size={18} color={tokens.colors.textInverse} />
        <Text style={styles.shareButtonText}>Compartilhar convite</Text>
      </Pressable>
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
  codeBox: {
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    padding: tokens.spacing.md,
    gap: 4,
    marginBottom: tokens.spacing.sm,
  },
  boxLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  boxValue: {
    color: tokens.colors.secondary,
    ...tokens.typography.mono,
  },
  linkValue: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  metricsRow: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    marginTop: tokens.spacing.xs,
  },
  metricItem: {
    flex: 1,
    borderRadius: tokens.radius.md,
    backgroundColor: '#fff8e8',
    borderWidth: 1,
    borderColor: '#f1ddb0',
    padding: tokens.spacing.md,
    gap: 4,
  },
  metricLabel: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  metricValue: {
    color: tokens.colors.secondary,
    ...tokens.typography.small,
  },
  progressBlock: {
    marginTop: tokens.spacing.md,
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
    backgroundColor: tokens.colors.primary,
  },
  progressHint: {
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  shareButton: {
    marginTop: tokens.spacing.md,
    alignSelf: 'flex-start',
    flexDirection: 'row',
    gap: tokens.spacing.xs,
    alignItems: 'center',
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.primary,
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 12,
  },
  shareButtonText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.small,
  },
});
