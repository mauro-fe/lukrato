import { Redirect } from 'expo-router';

import { AuthLoadingScreen } from '@/src/features/auth/components/auth-loading-screen';
import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';

export default function IndexRoute() {
  const { session, isBootstrapping } = useAuthSession();

  if (isBootstrapping) {
    return (
      <AuthLoadingScreen
        title="Abrindo seu app"
        description="Organizando a entrada para levar voce direto ao ponto certo."
      />
    );
  }

  if (session.status === 'signed_out') {
    return <Redirect href="/(auth)/login" />;
  }

  return <Redirect href="/(app)/(tabs)" />;
}
