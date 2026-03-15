export type CartaoTone = 'positive' | 'warning' | 'negative';

export type CartaoAlert = {
  id: string;
  type: 'due_soon' | 'low_limit';
  severity: 'critical' | 'attention';
  title: string;
  description: string;
  amount: number;
};

export type CartaoGuideStep = {
  id: string;
  title: string;
  description: string;
  done?: boolean;
};

export type CartaoOverview = {
  id: string;
  name: string;
  brandLabel: string;
  lastDigits: string;
  accentColor: string;
  linkedAccount: string;
  linkedInstitution: string;
  totalLimit: number;
  availableLimit: number;
  usedLimit: number;
  usagePercent: number;
  invoiceAmount: number;
  pendingInvoices: number;
  nextDueDate: string;
  statusLabel: string;
  needsAttention: boolean;
};

export type CartoesSnapshot = {
  monthLabel: string;
  helperTitle: string;
  helperDescription: string;
  totalCards: number;
  totalLimit: number;
  availableLimit: number;
  usedLimit: number;
  usagePercent: number;
  pendingInvoiceCount: number;
  focus: {
    title: string;
    description: string;
    amount: number;
    supportText: string;
    tone: CartaoTone;
  };
  alerts: CartaoAlert[];
  guidedSteps: CartaoGuideStep[];
  cards: CartaoOverview[];
};
