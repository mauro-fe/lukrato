export type ContaTone = 'positive' | 'warning' | 'negative';

export type ContaQuickAction = {
  id: 'income' | 'expense' | 'transfer';
  label: string;
  description: string;
  icon: string;
};

export type ContaGuidedStep = {
  id: string;
  title: string;
  description: string;
  done?: boolean;
};

export type ContaItem = {
  id: string;
  name: string;
  institutionName: string;
  typeLabel: string;
  icon: string;
  accentColor: string;
  balance: number;
  inflow: number;
  outflow: number;
  note: string;
  isPrimary?: boolean;
};

export type ContaGroup = {
  id: 'everyday' | 'reserve';
  title: string;
  description: string;
  totalBalance: number;
  emptyTitle: string;
  emptyDescription: string;
  accounts: ContaItem[];
};

export type ContasSnapshot = {
  monthLabel: string;
  helperTitle: string;
  helperDescription: string;
  totalBalance: number;
  everydayBalance: number;
  reserveBalance: number;
  activeCount: number;
  archivedCount: number;
  negativeCount: number;
  focus: {
    title: string;
    description: string;
    amount: number;
    supportText: string;
    tone: ContaTone;
  };
  guidedSteps: ContaGuidedStep[];
  quickActions: ContaQuickAction[];
  groups: ContaGroup[];
};
