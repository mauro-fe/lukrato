import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { FocusCard } from '@/src/features/dashboard/components/focus-card';
import { GuidedStepsCard } from '@/src/features/dashboard/components/guided-steps-card';
import { HeroBalanceCard } from '@/src/features/dashboard/components/hero-balance-card';
import { QuickActionsGrid } from '@/src/features/dashboard/components/quick-actions-grid';
import { RecentActivityCard } from '@/src/features/dashboard/components/recent-activity-card';
import { SmartInsightsCard } from '@/src/features/dashboard/components/smart-insights-card';
import { useDashboardPreview } from '@/src/features/dashboard/hooks/use-dashboard-preview';
import { SectionHeading } from '@/src/shared/ui/section-heading';
import { tokens } from '@/src/theme/tokens';

export function DashboardScreen() {
  const { snapshot, isLoading, isRefreshing, refresh } = useDashboardPreview();

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.backgroundBubbleTop} />
      <View style={styles.backgroundBubbleBottom} />

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={tokens.colors.primary} />}>
        <View style={styles.header}>
          <View style={styles.brandPill}>
            <Text style={styles.brandPillText}>Lukrato facilitado</Text>
          </View>

          <SectionHeading
            eyebrow={snapshot.monthLabel}
            title={`Oi, ${snapshot.userName}. Seu dinheiro, sem confusao.`}
            description="O dashboard mostra primeiro o que importa agora, depois os proximos passos e so entao os detalhes."
          />
        </View>

        <HeroBalanceCard
          balance={snapshot.balance}
          income={snapshot.income}
          expenses={snapshot.expenses}
          reserved={snapshot.reserved}
          monthlyResult={snapshot.monthlyResult}
          isLoading={isLoading}
        />

        <FocusCard
          title={snapshot.mainFocus.title}
          description={snapshot.mainFocus.description}
          amount={snapshot.mainFocus.amount}
          supportText={snapshot.mainFocus.supportText}
        />

        <GuidedStepsCard steps={snapshot.guidedSteps} />
        <QuickActionsGrid actions={snapshot.quickActions} />
        <SmartInsightsCard insights={snapshot.insights} />
        <RecentActivityCard transactions={snapshot.transactions} />
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: tokens.colors.background,
  },
  content: {
    padding: tokens.spacing.lg,
    gap: tokens.spacing.lg,
    paddingBottom: tokens.spacing.xxl,
  },
  header: {
    gap: tokens.spacing.md,
  },
  brandPill: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.whiteOverlay,
    borderWidth: 1,
    borderColor: '#f7d2ab',
  },
  brandPillText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.caption,
  },
  backgroundBubbleTop: {
    position: 'absolute',
    top: -90,
    right: -50,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde7d1',
  },
  backgroundBubbleBottom: {
    position: 'absolute',
    bottom: 100,
    left: -70,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#dfeaf7',
  },
});
