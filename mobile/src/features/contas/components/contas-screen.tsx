import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ContasFocusCard } from '@/src/features/contas/components/contas-focus-card';
import { ContasGroupCard } from '@/src/features/contas/components/contas-group-card';
import { ContasGuidedStepsCard } from '@/src/features/contas/components/contas-guided-steps-card';
import { ContasHeroCard } from '@/src/features/contas/components/contas-hero-card';
import { ContasQuickActionsCard } from '@/src/features/contas/components/contas-quick-actions-card';
import { useContasOverview } from '@/src/features/contas/hooks/use-contas-overview';
import { ContaQuickAction } from '@/src/features/contas/types';
import { DataSourceBanner } from '@/src/shared/ui/data-source-banner';
import { SectionHeading } from '@/src/shared/ui/section-heading';
import { tokens } from '@/src/theme/tokens';

export function ContasScreen() {
  const router = useRouter();
  const { snapshot, source, sourceMessage, isLoading, isRefreshing, refresh } = useContasOverview();

  function handleActionPress(actionId: ContaQuickAction['id']) {
    const mode =
      actionId === 'income' ? 'income' : actionId === 'transfer' ? 'transfer' : 'expense';

    router.push({
      pathname: '/(app)/lancamentos/novo',
      params: { mode },
    });
  }

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
            eyebrow="Contas"
            title={snapshot.helperTitle}
            description={snapshot.helperDescription}
          />
        </View>

        <DataSourceBanner
          source={source}
          fallbackMessage={sourceMessage ?? (isLoading ? 'Tentando conectar com a API do Lukrato...' : null)}
        />

        <View style={styles.shortcuts}>
          <ShortcutCard
            icon="add"
            title="Nova conta"
            description="Cadastre um novo lugar para guardar dinheiro sem sair da logica da tela."
            onPress={() => router.push('/(app)/contas/nova')}
          />
          <ShortcutCard
            icon="card-outline"
            title="Cartoes e faturas"
            description="Abra a area que mostra o que vence logo e quanto limite ainda resta."
            onPress={() => router.push('/(app)/contas/cartoes')}
            tone="light"
          />
        </View>

        <ContasHeroCard
          totalBalance={snapshot.totalBalance}
          everydayBalance={snapshot.everydayBalance}
          reserveBalance={snapshot.reserveBalance}
          activeCount={snapshot.activeCount}
          archivedCount={snapshot.archivedCount}
          negativeCount={snapshot.negativeCount}
        />

        <ContasFocusCard
          title={snapshot.focus.title}
          description={snapshot.focus.description}
          amount={snapshot.focus.amount}
          supportText={snapshot.focus.supportText}
          tone={snapshot.focus.tone}
        />

        <ContasGuidedStepsCard steps={snapshot.guidedSteps} />

        <ContasQuickActionsCard actions={snapshot.quickActions} onPressAction={handleActionPress} />

        {snapshot.groups.map((group) => (
          <ContasGroupCard key={group.id} group={group} />
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

function ShortcutCard({
  icon,
  title,
  description,
  onPress,
  tone = 'dark',
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  description: string;
  onPress: () => void;
  tone?: 'dark' | 'light';
}) {
  const isLight = tone === 'light';

  return (
    <Pressable
      style={[styles.shortcutButton, isLight ? styles.shortcutButtonLight : styles.shortcutButtonDark]}
      onPress={onPress}>
      <View style={[styles.shortcutIcon, isLight && styles.shortcutIconLight]}>
        <Ionicons
          name={icon}
          size={18}
          color={isLight ? tokens.colors.primaryStrong : tokens.colors.textInverse}
        />
      </View>
      <View style={styles.shortcutCopy}>
        <Text style={[styles.shortcutLabel, isLight && styles.shortcutLabelLight]}>{title}</Text>
        <Text
          style={[styles.shortcutDescription, isLight && styles.shortcutDescriptionLight]}>
          {description}
        </Text>
      </View>
    </Pressable>
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
    top: -90,
    left: -50,
    width: 210,
    height: 210,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#e8f0f7',
  },
  backgroundBottom: {
    position: 'absolute',
    bottom: 70,
    right: -70,
    width: 190,
    height: 190,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde7d0',
  },
  shortcuts: {
    gap: tokens.spacing.sm,
  },
  shortcutButton: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'center',
    borderRadius: tokens.radius.lg,
    padding: tokens.spacing.md,
    ...tokens.shadow.subtle,
  },
  shortcutButtonDark: {
    backgroundColor: tokens.colors.secondary,
  },
  shortcutButtonLight: {
    backgroundColor: tokens.colors.surface,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  shortcutIcon: {
    width: 40,
    height: 40,
    borderRadius: tokens.radius.pill,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  shortcutIconLight: {
    backgroundColor: '#fff3e8',
  },
  shortcutCopy: {
    flex: 1,
    gap: 2,
  },
  shortcutLabel: {
    color: tokens.colors.textInverse,
    ...tokens.typography.body,
  },
  shortcutLabelLight: {
    color: tokens.colors.text,
  },
  shortcutDescription: {
    color: 'rgba(255,255,255,0.74)',
    ...tokens.typography.caption,
  },
  shortcutDescriptionLight: {
    color: tokens.colors.textMuted,
  },
});
