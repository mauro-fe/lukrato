import { Image, StyleSheet, Text, View } from 'react-native';

import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type PerfilHeroCardProps = {
  name: string;
  email: string;
  avatarUrl: string;
  initials: string;
  supportCode: string;
  completionScore: number;
  completionLabel: string;
};

export function PerfilHeroCard({
  name,
  email,
  avatarUrl,
  initials,
  supportCode,
  completionScore,
  completionLabel,
}: PerfilHeroCardProps) {
  return (
    <AppCard style={styles.card}>
      <View style={styles.header}>
        {avatarUrl ? (
          <Image source={{ uri: avatarUrl }} style={styles.avatarImage} />
        ) : (
          <View style={styles.avatarFallback}>
            <Text style={styles.avatarText}>{initials}</Text>
          </View>
        )}

        <View style={styles.identity}>
          <Text style={styles.name}>{name}</Text>
          <Text style={styles.email}>{email}</Text>
        </View>
      </View>

      <Text style={styles.description}>
        O perfil deixa os dados essenciais no topo para o usuario bater o olho e saber se esta tudo certo.
      </Text>

      <View style={styles.chips}>
        <View style={styles.chip}>
          <Text style={styles.chipLabel}>Suporte</Text>
          <Text style={styles.chipValue} selectable>
            {supportCode}
          </Text>
        </View>
        <View style={styles.chip}>
          <Text style={styles.chipLabel}>Cadastro</Text>
          <Text style={styles.chipValue}>
            {completionScore}% • {completionLabel}
          </Text>
        </View>
      </View>
    </AppCard>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: tokens.colors.surfaceStrong,
    borderColor: '#203852',
    gap: tokens.spacing.md,
  },
  header: {
    flexDirection: 'row',
    gap: tokens.spacing.md,
    alignItems: 'center',
  },
  avatarImage: {
    width: 64,
    height: 64,
    borderRadius: tokens.radius.pill,
    backgroundColor: 'rgba(255,255,255,0.12)',
  },
  avatarFallback: {
    width: 64,
    height: 64,
    borderRadius: tokens.radius.pill,
    backgroundColor: 'rgba(255,255,255,0.14)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: tokens.colors.textInverse,
    ...tokens.typography.title,
  },
  identity: {
    flex: 1,
    gap: 4,
  },
  name: {
    color: tokens.colors.textInverse,
    ...tokens.typography.heading,
  },
  email: {
    color: '#d8e6f3',
    ...tokens.typography.body,
  },
  description: {
    color: '#d8e6f3',
    ...tokens.typography.body,
  },
  chips: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
  },
  chip: {
    flex: 1,
    borderRadius: tokens.radius.md,
    backgroundColor: 'rgba(255,255,255,0.08)',
    padding: tokens.spacing.md,
    gap: 4,
  },
  chipLabel: {
    color: '#c7d7e9',
    ...tokens.typography.caption,
  },
  chipValue: {
    color: tokens.colors.textInverse,
    ...tokens.typography.small,
  },
});
