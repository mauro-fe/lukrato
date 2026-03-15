import { StyleSheet, Text, View } from 'react-native';

import { PerfilDetailItem } from '@/src/features/perfil/types';
import { AppCard } from '@/src/shared/ui/app-card';
import { tokens } from '@/src/theme/tokens';

type PerfilDetailsCardProps = {
  details: PerfilDetailItem[];
};

export function PerfilDetailsCard({ details }: PerfilDetailsCardProps) {
  return (
    <AppCard>
      <Text style={styles.title}>Dados que o usuario espera achar aqui</Text>
      <Text style={styles.description}>
        Sem abas escondidas: os dados principais ficam agrupados para a revisao ser rapida.
      </Text>

      <View style={styles.list}>
        {details.map((detail) => (
          <View key={detail.id} style={styles.item}>
            <View style={styles.itemHeader}>
              <Text style={styles.label}>{detail.label}</Text>
              <Text style={styles.value}>{detail.value}</Text>
            </View>
            {detail.helper ? <Text style={styles.helper}>{detail.helper}</Text> : null}
          </View>
        ))}
      </View>
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
  list: {
    gap: tokens.spacing.md,
  },
  item: {
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
    padding: tokens.spacing.md,
    gap: 6,
  },
  itemHeader: {
    flexDirection: 'row',
    gap: tokens.spacing.sm,
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  label: {
    flex: 1,
    color: tokens.colors.textMuted,
    ...tokens.typography.caption,
  },
  value: {
    flex: 1,
    color: tokens.colors.text,
    textAlign: 'right',
    ...tokens.typography.small,
  },
  helper: {
    color: tokens.colors.secondary,
    ...tokens.typography.caption,
  },
});
