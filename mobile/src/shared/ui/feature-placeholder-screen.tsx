import { Ionicons } from '@expo/vector-icons';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppCard } from '@/src/shared/ui/app-card';
import { SectionHeading } from '@/src/shared/ui/section-heading';
import { tokens } from '@/src/theme/tokens';

type FeaturePlaceholderScreenProps = {
  eyebrow: string;
  title: string;
  description: string;
  highlights: string[];
};

export function FeaturePlaceholderScreen({
  eyebrow,
  title,
  description,
  highlights,
}: FeaturePlaceholderScreenProps) {
  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <ScrollView contentContainerStyle={styles.content}>
        <SectionHeading eyebrow={eyebrow} title={title} description={description} />

        <AppCard>
          <Text style={styles.cardTitle}>Como essa area vai ajudar</Text>
          <View style={styles.list}>
            {highlights.map((item) => (
              <View key={item} style={styles.listItem}>
                <View style={styles.iconWrap}>
                  <Ionicons name="checkmark" size={16} color={tokens.colors.primaryStrong} />
                </View>
                <Text style={styles.listText}>{item}</Text>
              </View>
            ))}
          </View>
        </AppCard>
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
  },
  cardTitle: {
    color: tokens.colors.text,
    marginBottom: tokens.spacing.md,
    ...tokens.typography.title,
  },
  list: {
    gap: tokens.spacing.md,
  },
  listItem: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    alignItems: 'flex-start',
  },
  iconWrap: {
    marginTop: 2,
    width: 24,
    height: 24,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  listText: {
    flex: 1,
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
});
