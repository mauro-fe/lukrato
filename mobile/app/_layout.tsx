import { DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import 'react-native-reanimated';

import { AuthProvider } from '@/src/features/auth/providers/auth-provider';
import { tokens } from '@/src/theme/tokens';

const appTheme = {
  ...DefaultTheme,
  colors: {
    ...DefaultTheme.colors,
    background: tokens.colors.background,
    card: tokens.colors.surface,
    primary: tokens.colors.primary,
    text: tokens.colors.text,
    border: tokens.colors.border,
    notification: tokens.colors.primary,
  },
};

export default function RootLayout() {
  return (
    <ThemeProvider value={appTheme}>
      <AuthProvider>
        <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: tokens.colors.background } }}>
          <Stack.Screen name="index" />
          <Stack.Screen name="(auth)" />
          <Stack.Screen name="(app)" />
        </Stack>
      </AuthProvider>
      <StatusBar style="dark" />
    </ThemeProvider>
  );
}
