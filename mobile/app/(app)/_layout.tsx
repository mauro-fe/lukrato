import { Stack } from 'expo-router';

export default function AppLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="contas/nova" options={{ presentation: 'modal' }} />
      <Stack.Screen name="lancamentos/novo" options={{ presentation: 'modal' }} />
    </Stack>
  );
}
