import { Platform } from 'react-native';

const fontFamily = Platform.select({
  ios: 'System',
  android: 'sans-serif',
  default: 'sans-serif',
});

const monoFamily = Platform.select({
  ios: 'Menlo',
  android: 'monospace',
  default: 'monospace',
});

export const tokens = {
  colors: {
    background: '#f6f7fb',
    backgroundAccent: '#eaf1f8',
    surface: '#ffffff',
    surfaceAlt: '#f1f5f9',
    surfaceStrong: '#12263f',
    primary: '#e67e22',
    primaryStrong: '#c86518',
    secondary: '#2c3e50',
    secondarySoft: '#dde7f2',
    text: '#102132',
    textMuted: '#64748b',
    textInverse: '#ffffff',
    border: '#dce5ef',
    success: '#1f9d63',
    danger: '#d85a4f',
    warning: '#d98c10',
    info: '#3a79d9',
    whiteOverlay: 'rgba(255,255,255,0.7)',
  },
  spacing: {
    xxs: 4,
    xs: 8,
    sm: 12,
    md: 16,
    lg: 20,
    xl: 24,
    xxl: 32,
  },
  radius: {
    sm: 10,
    md: 16,
    lg: 22,
    xl: 28,
    pill: 999,
  },
  typography: {
    fontFamily,
    monoFamily,
    display: {
      fontFamily,
      fontSize: 32,
      lineHeight: 38,
      fontWeight: '800' as const,
      letterSpacing: -0.8,
    },
    heading: {
      fontFamily,
      fontSize: 22,
      lineHeight: 28,
      fontWeight: '800' as const,
      letterSpacing: -0.5,
    },
    title: {
      fontFamily,
      fontSize: 18,
      lineHeight: 24,
      fontWeight: '700' as const,
    },
    body: {
      fontFamily,
      fontSize: 15,
      lineHeight: 22,
      fontWeight: '500' as const,
    },
    small: {
      fontFamily,
      fontSize: 13,
      lineHeight: 18,
      fontWeight: '600' as const,
    },
    caption: {
      fontFamily,
      fontSize: 12,
      lineHeight: 16,
      fontWeight: '600' as const,
      letterSpacing: 0.3,
    },
    mono: {
      fontFamily: monoFamily,
      fontSize: 13,
      lineHeight: 18,
      fontWeight: '700' as const,
    },
  },
  shadow: {
    soft: {
      shadowColor: '#0f172a',
      shadowOffset: { width: 0, height: 10 },
      shadowOpacity: 0.08,
      shadowRadius: 22,
      elevation: 5,
    },
    subtle: {
      shadowColor: '#0f172a',
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.05,
      shadowRadius: 12,
      elevation: 2,
    },
  },
} as const;
