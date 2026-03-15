import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { LancamentosAttentionCard } from '@/src/features/lancamentos/components/lancamentos-attention-card';
import { LancamentosFiltersCard } from '@/src/features/lancamentos/components/lancamentos-filters-card';
import { LancamentosListCard } from '@/src/features/lancamentos/components/lancamentos-list-card';
import { LancamentosOverviewCard } from '@/src/features/lancamentos/components/lancamentos-overview-card';
import { LancamentosQuickActionsCard } from '@/src/features/lancamentos/components/lancamentos-quick-actions-card';
import { QuickEntrySheet } from '@/src/features/lancamentos/components/quick-entry-sheet';
import { useLancamentosOverview } from '@/src/features/lancamentos/hooks/use-lancamentos-overview';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { SectionHeading } from '@/src/shared/ui/section-heading';
import { tokens } from '@/src/theme/tokens';

export function LancamentosScreen() {
  const router = useRouter();
  const {
    snapshot,
    source,
    sourceMessage,
    isLoading,
    activeFilter,
    searchQuery,
    filteredItems,
    sections,
    attentionItems,
    isRefreshing,
    isSheetOpen,
    isPending,
    refresh,
    changeFilter,
    updateSearch,
    openSheet,
    closeSheet,
  } = useLancamentosOverview();

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.backgroundTop} />
      <View style={styles.backgroundBottom} />

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={tokens.colors.primary} />}>
        <View style={styles.header}>
          <View style={styles.pill}>
            <Text style={styles.pillText}>{snapshot.monthLabel}</Text>
          </View>
          <SectionHeading
            eyebrow="Lancamentos"
            title={snapshot.helperTitle}
            description={snapshot.helperDescription}
          />
        </View>

        <DataSourceBanner
          source={source}
          fallbackMessage={sourceMessage ?? (isLoading ? 'Tentando conectar com a API do Lukrato...' : null)}
        />

        <LancamentosOverviewCard
          totalItems={snapshot.totalItems}
          pendingCount={snapshot.pendingCount}
          pendingAmount={snapshot.pendingAmount}
          paidCount={snapshot.paidCount}
        />

        <LancamentosQuickActionsCard actions={snapshot.quickActions} onOpenSheet={openSheet} />

        <LancamentosAttentionCard
          title={snapshot.focusTip.title}
          description={snapshot.focusTip.description}
          items={attentionItems}
        />

        <LancamentosFiltersCard
          activeFilter={activeFilter}
          searchQuery={searchQuery}
          isPending={isPending}
          onChangeFilter={changeFilter}
          onChangeSearch={updateSearch}
        />

        <LancamentosListCard sections={sections} hasResults={filteredItems.length > 0} />
      </ScrollView>

      <Pressable style={styles.fab} onPress={() => router.push('/(app)/lancamentos/novo?mode=expense')}>
        <Ionicons name="add" size={24} color={tokens.colors.textInverse} />
        <Text style={styles.fabText}>Novo lancamento</Text>
      </Pressable>

      <QuickEntrySheet visible={isSheetOpen} actions={snapshot.quickActions} onClose={closeSheet} />
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
    paddingBottom: 120,
  },
  header: {
    gap: tokens.spacing.md,
  },
  pill: {
    alignSelf: 'flex-start',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.whiteOverlay,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  pillText: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
  backgroundTop: {
    position: 'absolute',
    top: -80,
    right: -80,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#e5edf7',
  },
  backgroundBottom: {
    position: 'absolute',
    bottom: 80,
    left: -60,
    width: 180,
    height: 180,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fdebd8',
  },
  fab: {
    position: 'absolute',
    right: tokens.spacing.lg,
    bottom: 94,
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.xs,
    backgroundColor: tokens.colors.primary,
    borderRadius: tokens.radius.pill,
    paddingHorizontal: tokens.spacing.lg,
    paddingVertical: 14,
    ...tokens.shadow.soft,
  },
  fabText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.small,
  },
});
