import {
  createContext,
  PropsWithChildren,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
} from 'react';
import { AppState } from 'react-native';

import {
  authRepository,
  createPreviewSnapshot,
  createSignedOutSnapshot,
} from '@/src/features/auth/repositories/auth-repository';
import { AuthLoginInput, AuthSessionSnapshot } from '@/src/features/auth/types';

type AuthContextValue = {
  session: AuthSessionSnapshot;
  isBootstrapping: boolean;
  isSigningOut: boolean;
  signIn: (input: AuthLoginInput) => Promise<AuthSessionSnapshot>;
  signOut: () => Promise<void>;
  enterPreview: () => void;
  refreshSession: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

const initialSession: AuthSessionSnapshot = {
  status: 'booting',
  source: 'remote',
  userName: null,
  helperMessage: null,
  warningMessage: null,
  isRemembered: false,
  remainingTime: 0,
  allowPreview: true,
};

export function AuthProvider({ children }: PropsWithChildren) {
  const [session, setSession] = useState<AuthSessionSnapshot>(initialSession);
  const [isBootstrapping, setIsBootstrapping] = useState(true);
  const [isSigningOut, setIsSigningOut] = useState(false);
  const sessionRef = useRef(session);

  useEffect(() => {
    sessionRef.current = session;
  }, [session]);

  const syncSession = useCallback(async (reason: 'bootstrap' | 'foreground' | 'manual') => {
    if (reason !== 'bootstrap' && sessionRef.current.status === 'preview') {
      return;
    }

    if (reason === 'bootstrap') {
      setIsBootstrapping(true);
    }

    const nextSession = await authRepository.getSessionSnapshot();
    setSession(nextSession);

    if (reason === 'bootstrap') {
      setIsBootstrapping(false);
    }
  }, []);

  useEffect(() => {
    void syncSession('bootstrap');
  }, [syncSession]);

  useEffect(() => {
    const subscription = AppState.addEventListener('change', (nextState) => {
      if (nextState === 'active') {
        void syncSession('foreground');
      }
    });

    return () => {
      subscription.remove();
    };
  }, [syncSession]);

  async function signIn(input: AuthLoginInput) {
    const nextSession = await authRepository.login(input);
    setSession(nextSession);
    setIsBootstrapping(false);
    return nextSession;
  }

  async function signOut() {
    setIsSigningOut(true);

    try {
      if (session.status !== 'preview') {
        await authRepository.logout();
      }

      setSession(createSignedOutSnapshot('Voce saiu do app com seguranca.'));
    } catch {
      setSession(
        createSignedOutSnapshot(
          'Voce saiu deste aparelho. Se a conexao falhou, o servidor pode levar um pouco mais para refletir isso.'
        )
      );
    } finally {
      setIsSigningOut(false);
      setIsBootstrapping(false);
    }
  }

  function enterPreview() {
    setSession(createPreviewSnapshot());
    setIsBootstrapping(false);
  }

  async function refreshSession() {
    const nextSession = await authRepository.getSessionSnapshot();
    setSession(nextSession);
    setIsBootstrapping(false);
  }

  return (
    <AuthContext.Provider
      value={{
        session,
        isBootstrapping,
        isSigningOut,
        signIn,
        signOut,
        enterPreview,
        refreshSession,
      }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuthSession() {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuthSession must be used inside AuthProvider.');
  }

  return context;
}
