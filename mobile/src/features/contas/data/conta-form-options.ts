export type ContaTypeOption = {
  id:
  | 'conta_corrente'
  | 'conta_poupanca'
  | 'carteira_digital'
  | 'dinheiro';
  label: string;
  description: string;
  icon: string;
};

export type ContaInstitutionOption = {
  id: string;
  name: string;
  type: string;
  accentColor: string;
};

export const contaTypeOptions: ContaTypeOption[] = [
  {
    id: 'conta_corrente',
    label: 'Corrente',
    description: 'Para receber, pagar e movimentar o dinheiro do mês.',
    icon: 'wallet-outline',
  },
  {
    id: 'conta_poupanca',
    label: 'Poupança',
    description: 'Para separar reserva sem misturar com o caixa do dia a dia.',
    icon: 'shield-checkmark-outline',
  },
  {
    id: 'carteira_digital',
    label: 'Carteira',
    description: 'Para apps, saldo digital e uso rápido.',
    icon: 'phone-portrait-outline',
  },
  {
    id: 'dinheiro',
    label: 'Dinheiro',
    description: 'Para dinheiro em mãos, caixa ou pequenos valores físicos.',
    icon: 'cash-outline',
  },
];

export const contaInstitutionOptions: ContaInstitutionOption[] = [
  { id: 'nubank', name: 'Nubank', type: 'fintech', accentColor: '#7a4fd0' },
  { id: 'inter', name: 'Inter', type: 'banco', accentColor: '#f07c13' },
  { id: 'itau', name: 'Itau', type: 'banco', accentColor: '#f58220' },
  { id: 'santander', name: 'Santander', type: 'banco', accentColor: '#d71920' },
  { id: 'caixa', name: 'Caixa', type: 'banco', accentColor: '#2276c9' },
  { id: 'bb', name: 'Banco do Brasil', type: 'banco', accentColor: '#f0c419' },
];
