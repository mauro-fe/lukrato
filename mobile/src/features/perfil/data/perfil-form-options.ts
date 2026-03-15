import { PerfilSexOption } from '@/src/features/perfil/types';

export const perfilSexOptions: {
  id: PerfilSexOption;
  label: string;
  helper: string;
}[] = [
  {
    id: '',
    label: 'Nao informar',
    helper: 'Mantem esse campo vazio sem travar o cadastro.',
  },
  {
    id: 'F',
    label: 'Feminino',
    helper: 'Opcao direta para quem quiser preencher.',
  },
  {
    id: 'M',
    label: 'Masculino',
    helper: 'Opcao direta para quem quiser preencher.',
  },
  {
    id: 'NB',
    label: 'Nao binario',
    helper: 'Mantem o cadastro mais fiel a pessoa.',
  },
  {
    id: 'O',
    label: 'Outro',
    helper: 'Alternativa simples para nao limitar escolhas.',
  },
  {
    id: 'N',
    label: 'Prefiro nao informar',
    helper: 'Quando a pessoa nao quiser expor esse dado.',
  },
];
