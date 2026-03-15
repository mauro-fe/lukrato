import { Redirect, Stack } from 'expo-router';

import { AuthLoadingScreen } from '@/src/features/auth/components/auth-loading-screen';
import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';

export default function AuthLayout() {
  const { session, isBootstrapping } = useAuthSession();

  if (isBootstrapping) {
    return (
      <AuthLoadingScreen
        title="Preparando sua entrada"
        description="Conferindo se ja existe uma sessao ativa neste aparelho."
      />
    );
  }

  if (session.status !== 'signed_out') {
    return <Redirect href="/(app)/(tabs)" />;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="login" />
    </Stack>
  );
}
