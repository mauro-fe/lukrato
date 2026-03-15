export type LancamentoEntryMode = 'expense' | 'income' | 'transfer';

export type LancamentoFilter = 'all' | 'pending' | 'paid' | 'income' | 'expense';

export type LancamentoQuickAction = {
  id: LancamentoEntryMode;
  label: string;
  caption: string;
  icon: string;
  tone: 'danger' | 'success' | 'secondary';
};

export type LancamentoItem = {
  id: string;
  title: string;
  category: string;
  account: string;
  date: string;
  dueDate?: string;
  amount: number;
  type: 'income' | 'expense' | 'transfer';
  status: 'pending' | 'paid';
  note?: string;
};

export type LancamentoSection = {
  id: string;
  label: string;
  items: LancamentoItem[];
};

export type LancamentoSnapshot = {
  monthLabel: string;
  helperTitle: string;
  helperDescription: string;
  totalItems: number;
  pendingCount: number;
  pendingAmount: number;
  paidCount: number;
  quickActions: LancamentoQuickAction[];
  focusTip: {
    title: string;
    description: string;
  };
  items: LancamentoItem[];
};
