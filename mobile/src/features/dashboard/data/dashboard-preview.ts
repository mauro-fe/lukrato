import { DashboardSnapshot } from '@/src/features/dashboard/types';

export const dashboardPreview: DashboardSnapshot = {
  monthLabel: 'Marco de 2026',
  userName: 'Mauro',
  balance: 8420.65,
  income: 6350,
  expenses: 4125.8,
  reserved: 950,
  monthlyResult: 2224.2,
  mainFocus: {
    title: 'Sua prioridade de hoje',
    description:
      'você tem uma fatura para revisar antes do vencimento. O app vai sempre tentar mostrar primeiro o que pede atencao imediata.',
    amount: 684.3,
    supportText: 'Fatura Nubank vence em 18 mar',
  },
  guidedSteps: [
    {
      id: '1',
      title: 'Registre primeiro o que entra',
      description: 'Comece pela renda principal para o saldo do mes fazer sentido desde o inicio.',
      cta: 'Adicionar receita',
      done: true,
    },
    {
      id: '2',
      title: 'Depois lance os gastos fixos',
      description: 'Aluguel, internet, energia e contas recorrentes vem antes dos gastos variaveis.',
      cta: 'Adicionar gastos fixos',
    },
    {
      id: '3',
      title: 'Por ultimo revise o que vence logo',
      description: 'Assim você evita esquecer boleto, cartao e pagamento pendente.',
      cta: 'Ver vencimentos',
    },
  ],
  quickActions: [
    {
      id: 'expense',
      label: 'Registrar gasto',
      icon: 'remove-circle-outline',
      route: '/(app)/(tabs)/lancamentos',
    },
    {
      id: 'income',
      label: 'Recebi',
      icon: 'add-circle-outline',
      route: '/(app)/(tabs)/lancamentos',
    },
    {
      id: 'accounts',
      label: 'Ver contas',
      icon: 'wallet-outline',
      route: '/(app)/(tabs)/contas',
    },
    {
      id: 'profile',
      label: 'Ajuda e perfil',
      icon: 'person-outline',
      route: '/(app)/(tabs)/perfil',
    },
  ],
  insights: [
    {
      id: '1',
      title: 'você esta positivo no mes.',
      description: 'Entrou mais dinheiro do que saiu, entao o saldo mensal esta respirando bem.',
    },
    {
      id: '2',
      title: 'Alimentacao puxou seus gastos.',
      description: 'Ela representa a maior parte das saidas desta semana e merece revisao.',
    },
    {
      id: '3',
      title: 'você ja reservou dinheiro.',
      description: 'A quantia guardada evita que a fatura aperte o caixa no fim do mes.',
    },
  ],
  transactions: [
    {
      id: '1',
      title: 'Mercado Assai',
      category: 'Alimentacao',
      account: 'Itau',
      date: '2026-03-14',
      amount: -284.35,
      kind: 'expense',
    },
    {
      id: '2',
      title: 'Freela design',
      category: 'Receita extra',
      account: 'Nubank',
      date: '2026-03-13',
      amount: 850,
      kind: 'income',
    },
    {
      id: '3',
      title: 'Internet fibra',
      category: 'Casa',
      account: 'Santander',
      date: '2026-03-12',
      amount: -119.9,
      kind: 'expense',
    },
    {
      id: '4',
      title: 'Academia',
      category: 'Saude',
      account: 'Nubank',
      date: '2026-03-11',
      amount: -89.9,
      kind: 'expense',
    },
  ],
};
