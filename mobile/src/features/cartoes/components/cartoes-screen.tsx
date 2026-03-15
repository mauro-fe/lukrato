import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { CartoesAlertsCard } from '@/src/features/cartoes/components/cartoes-alerts-card';
import { CartoesFocusCard } from '@/src/features/cartoes/components/cartoes-focus-card';
import { CartoesGuidedStepsCard } from '@/src/features/cartoes/components/cartoes-guided-steps-card';
import { CartoesHeroCard } from '@/src/features/cartoes/components/cartoes-hero-card';
import { CartoesListCard } from '@/src/features/cartoes/components/cartoes-list-card';
import { useCartoesOverview } from '@/src/features/cartoes/hooks/use-cartoes-overview';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { tokens } from '@/src/theme/tokens';

export function CartoesScreen() {
  const router = useRouter();
  const { snapshot, source, sourceMessage, isLoading, isRefreshing, refresh } = useCartoesOverview();

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.backgroundTop} />
      <View style={styles.backgroundBottom} />

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={tokens.colors.primary} />}>
        <View style={styles.header}>
          <Pressable style={styles.backButton} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={20} color={tokens.colors.text} />
          </Pressable>

          <View style={styles.headerText}>
            <View style={styles.pill}>
              <Text style={styles.pillText}>{snapshot.monthLabel}</Text>
            </View>
            <Text style={styles.eyebrow}>Cartoes e faturas</Text>
            <Text style={styles.title}>{snapshot.helperTitle}</Text>
            <Text style={styles.description}>{snapshot.helperDescription}</Text>
          </View>
        </View>

        <DataSourceBanner
          source={source}
          fallbackMessage={sourceMessage ?? (isLoading ? 'Tentando conectar com a API de cartoes do Lukrato...' : null)}
        />

        <CartoesHeroCard
          totalCards={snapshot.totalCards}
          totalLimit={snapshot.totalLimit}
          availableLimit={snapshot.availableLimit}
          usedLimit={snapshot.usedLimit}
          usagePercent={snapshot.usagePercent}
          pendingInvoiceCount={snapshot.pendingInvoiceCount}
        />

        <CartoesFocusCard
          title={snapshot.focus.title}
          description={snapshot.focus.description}
          amount={snapshot.focus.amount}
          supportText={snapshot.focus.supportText}
          tone={snapshot.focus.tone}
        />

        <CartoesAlertsCard alerts={snapshot.alerts} />

        <CartoesGuidedStepsCard steps={snapshot.guidedSteps} />

        <CartoesListCard cards={snapshot.cards} />
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
    paddingBottom: 96,
  },
  header: {
    flexDirection: 'row',
    gap: tokens.spacing.md,
    alignItems: 'flex-start',
  },
  backButton: {
    width: 42,
    height: 42,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.surface,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerText: {
    flex: 1,
    gap: 4,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.whiteOverlay,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    marginBottom: tokens.spacing.xs,
  },
  pillText: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  eyebrow: {
    color: tokens.colors.primaryStrong,
    textTransform: 'uppercase',
    ...tokens.typography.caption,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.heading,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
  backgroundTop: {
    position: 'absolute',
    top: -80,
    right: -60,
    width: 210,
    height: 210,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#e7eef8',
  },
  backgroundBottom: {
    position: 'absolute',
    bottom: 80,
    left: -70,
    width: 210,
    height: 210,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde7d1',
  },
});
