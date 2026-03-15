import { StyleSheet, Text, View } from 'react-native';

import { tokens } from '@/src/theme/tokens';

type DataSourceBannerProps = {
  source: 'preview' | 'remote';
  fallbackMessage?: string | null;
};

export function DataSourceBanner({
  source,
  fallbackMessage,
}: DataSourceBannerProps) {
  const isPreview = source === 'preview';
  const hasRemoteWarning = !isPreview && Boolean(fallbackMessage);

  return (
    <View
      style={[
        styles.banner,
        isPreview
          ? styles.previewBanner
          : hasRemoteWarning
            ? styles.warningBanner
            : styles.remoteBanner,
      ]}>
      <Text
        style={[
          styles.title,
          isPreview
            ? styles.previewText
            : hasRemoteWarning
              ? styles.warningText
              : styles.remoteText,
        ]}>
        {isPreview
          ? 'Modo local'
          : hasRemoteWarning
            ? 'Conexao com backend'
            : 'Conectado ao backend'}
      </Text>
      <Text
        style={[
          styles.description,
          isPreview
            ? styles.previewText
            : hasRemoteWarning
              ? styles.warningText
              : styles.remoteText,
        ]}>
        {isPreview
          ? fallbackMessage ?? 'Usando dados locais enquanto a API real nao responde.'
          : fallbackMessage ?? 'Os dados desta tela vieram da API do Lukrato.'}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    borderRadius: tokens.radius.md,
    borderWidth: 1,
    padding: tokens.spacing.md,
    gap: 4,
  },
  previewBanner: {
    backgroundColor: '#fffaf1',
    borderColor: '#f2d6a3',
  },
  remoteBanner: {
    backgroundColor: '#edf7ff',
    borderColor: '#c7def6',
  },
  warningBanner: {
    backgroundColor: '#fff8e7',
    borderColor: '#f0d8a9',
  },
  title: {
    ...tokens.typography.small,
  },
  description: {
    ...tokens.typography.caption,
  },
  previewText: {
    color: tokens.colors.secondary,
  },
  remoteText: {
    color: tokens.colors.info,
  },
  warningText: {
    color: tokens.colors.secondary,
  },
});
