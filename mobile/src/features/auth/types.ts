export type AuthSessionState = 'booting' | 'signed_out' | 'signed_in' | 'preview';

export type AuthSessionSource = 'remote' | 'preview' | 'offline';

export type AuthSessionSnapshot = {
  status: AuthSessionState;
  source: AuthSessionSource;
  userName: string | null;
  helperMessage: string | null;
  warningMessage: string | null;
  isRemembered: boolean;
  remainingTime: number;
  allowPreview: boolean;
};

export type AuthLoginInput = {
  email: string;
  password: string;
  remember: boolean;
};

export type AuthFormFeedback = {
  tone: 'success' | 'error' | 'info';
  message: string;
};
