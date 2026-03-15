export type PerfilTone = 'positive' | 'warning' | 'negative';
export type PerfilContactChannel = 'email' | 'whatsapp';

export type PerfilGuidedStep = {
  id: string;
  title: string;
  description: string;
  done?: boolean;
};

export type PerfilSexOption = '' | 'M' | 'F' | 'O' | 'NB' | 'N';

export type PerfilDetailItem = {
  id: string;
  label: string;
  value: string;
  helper?: string;
};

export type PerfilSnapshot = {
  helperTitle: string;
  helperDescription: string;
  identity: {
    name: string;
    email: string;
    avatarUrl: string;
    initials: string;
    supportCode: string;
    completionScore: number;
    completionLabel: string;
  };
  focus: {
    title: string;
    description: string;
    valueLabel: string;
    supportText: string;
    tone: PerfilTone;
  };
  guidedSteps: PerfilGuidedStep[];
  details: PerfilDetailItem[];
  referral: {
    code: string;
    link: string;
    rewardDays: number;
    totalInvites: number;
    completedInvites: number;
    pendingInvites: number;
    monthlyUsed: number;
    monthlyLimit: number;
    monthlyRemaining: number;
  };
  telegram: {
    linked: boolean;
    username: string | null;
    helperText: string;
  };
  support: {
    recommendedChannel: PerfilContactChannel;
    hint: string;
  };
};

export type TelegramLinkDraft = {
  code: string;
  botUrl: string;
  expiresIn: number;
};

export type PerfilFormData = {
  name: string;
  email: string;
  cpf: string;
  phone: string;
  sex: PerfilSexOption;
  birthDate: string;
  addressCep: string;
  addressStreet: string;
  addressNumber: string;
  addressComplement: string;
  addressNeighborhood: string;
  addressCity: string;
  addressState: string;
};

export type PerfilFormErrors = Partial<
  Record<
    | 'name'
    | 'email'
    | 'cpf'
    | 'phone'
    | 'birthDate'
    | 'addressCep'
    | 'addressStreet'
    | 'addressNumber'
    | 'addressNeighborhood'
    | 'addressCity'
    | 'addressState',
    string
  >
>;

export type PasswordFormErrors = Partial<
  Record<'currentPassword' | 'newPassword' | 'confirmPassword', string>
>;

export type PerfilFeedback = {
  tone: 'success' | 'error';
  message: string;
};
