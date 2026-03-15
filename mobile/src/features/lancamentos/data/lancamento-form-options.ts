export type FormAccountOption = {
  id: string;
  name: string;
  subtitle: string;
};

export type FormCategoryOption = {
  id: string;
  label: string;
  icon: string;
  type: 'income' | 'expense';
};

export const formAccountOptions: FormAccountOption[] = [
  { id: 'itau', name: 'Itau', subtitle: 'Conta principal' },
  { id: 'nubank', name: 'Nubank', subtitle: 'Uso diario' },
  { id: 'santander', name: 'Santander', subtitle: 'Despesas da casa' },
];

export const formCategoryOptions: FormCategoryOption[] = [
  { id: 'salario', label: 'Salario', icon: 'cash-outline', type: 'income' },
  { id: 'freela', label: 'Freela', icon: 'briefcase-outline', type: 'income' },
  { id: 'investimentos', label: 'Rendimento', icon: 'trending-up-outline', type: 'income' },
  { id: 'alimentacao', label: 'Alimentacao', icon: 'restaurant-outline', type: 'expense' },
  { id: 'transporte', label: 'Transporte', icon: 'car-outline', type: 'expense' },
  { id: 'casa', label: 'Casa', icon: 'home-outline', type: 'expense' },
  { id: 'saude', label: 'Saude', icon: 'fitness-outline', type: 'expense' },
  { id: 'lazer', label: 'Lazer', icon: 'game-controller-outline', type: 'expense' },
];
