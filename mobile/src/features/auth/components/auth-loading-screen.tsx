import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type AuthLoadingScreenProps = {
  title: string;
  description: string;
};

export function AuthLoadingScreen({ title, description }: AuthLoadingScreenProps) {
  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <View style={styles.backgroundTop} />
      <View style={styles.backgroundBottom} />

      <View style={styles.content}>
        <View style={styles.pill}>
          <Text style={styles.pillText}>Lukrato mobile</Text>
        </View>

        <AppCard style={styles.card}>
          <ActivityIndicator size="small" color={tokens.colors.primary} />
          <Text style={styles.title}>{title}</Text>
          <Text style={styles.description}>{description}</Text>
        </AppCard>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: tokens.colors.background,
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    padding: tokens.spacing.lg,
    gap: tokens.spacing.lg,
  },
  pill: {
    alignSelf: 'center',
    paddingHorizontal: tokens.spacing.md,
    paddingVertical: 8,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.whiteOverlay,
    borderWidth: 1,
    borderColor: '#f7d2ab',
  },
  pillText: {
    color: tokens.colors.primaryStrong,
    ...tokens.typography.caption,
  },
  card: {
    alignItems: 'center',
    gap: tokens.spacing.sm,
    paddingVertical: tokens.spacing.xl,
  },
  title: {
    color: tokens.colors.text,
    textAlign: 'center',
    ...tokens.typography.heading,
  },
  description: {
    color: tokens.colors.textMuted,
    textAlign: 'center',
    ...tokens.typography.body,
  },
  backgroundTop: {
    position: 'absolute',
    top: -90,
    right: -50,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fde7d1',
  },
  backgroundBottom: {
    position: 'absolute',
    bottom: 100,
    left: -70,
    width: 220,
    height: 220,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#dfeaf7',
  },
});
