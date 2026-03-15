import Constants from 'expo-constants';
import { Platform } from 'react-native';

type ExpoExtra = {
  apiBaseUrl?: string;
  usePreviewFallback?: boolean;
};

function readExtra(): ExpoExtra {
  const extra =
    Constants.expoConfig?.extra ??
    Constants.manifest2?.extra?.expoClient?.extra ??
    {};

  return extra as ExpoExtra;
}

function resolveApiBaseUrl() {
  const extra = readExtra();
  const explicitBaseUrl = extra.apiBaseUrl?.trim();

  if (explicitBaseUrl) {
    return explicitBaseUrl.replace(/\/?$/, '/');
  }

  if (Platform.OS === 'android') {
    return 'http://10.0.2.2/lukrato/public/';
  }

  if (Platform.OS === 'ios' || Platform.OS === 'web') {
    return 'http://localhost/lukrato/public/';
  }

  return '';
}

export const appConfig = {
  apiBaseUrl: resolveApiBaseUrl(),
  usePreviewFallback: readExtra().usePreviewFallback === true,
} as const;
