export type DashboardQuickAction = {
  id: string;
  label: string;
  icon: string;
  route: '/(app)/(tabs)/lancamentos' | '/(app)/(tabs)/contas' | '/(app)/(tabs)/perfil';
};

export type DashboardGuidedStep = {
  id: string;
  title: string;
  description: string;
  cta: string;
  done?: boolean;
};

export type DashboardInsight = {
  id: string;
  title: string;
  description: string;
};

export type DashboardTransaction = {
  id: string;
  title: string;
  category: string;
  account: string;
  date: string;
  amount: number;
  kind: 'income' | 'expense';
};

export type DashboardSnapshot = {
  monthLabel: string;
  userName: string;
  balance: number;
  income: number;
  expenses: number;
  reserved: number;
  monthlyResult: number;
  mainFocus: {
    title: string;
    description: string;
    amount: number;
    supportText: string;
  };
  guidedSteps: DashboardGuidedStep[];
  quickActions: DashboardQuickAction[];
  insights: DashboardInsight[];
  transactions: DashboardTransaction[];
};
