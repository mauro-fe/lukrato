import { Redirect, Stack } from 'expo-router';

import { AuthLoadingScreen } from '@/src/features/auth/components/auth-loading-screen';
import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';

export default function AppLayout() {
  const { session, isBootstrapping } = useAuthSession();

  if (isBootstrapping) {
    return (
      <AuthLoadingScreen
        title="Validando sua sessao"
        description="Assim o app so abre a area interna quando souber exatamente onde você deve cair."
      />
    );
  }

  if (session.status === 'signed_out') {
    return <Redirect href="/(auth)/login" />;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="contas/cartoes" />
      <Stack.Screen name="contas/nova" options={{ presentation: 'modal' }} />
      <Stack.Screen name="lancamentos/novo" options={{ presentation: 'modal' }} />
      <Stack.Screen name="perfil/editar" options={{ presentation: 'modal' }} />
      <Stack.Screen name="perfil/seguranca" options={{ presentation: 'modal' }} />
    </Stack>
  );
}
