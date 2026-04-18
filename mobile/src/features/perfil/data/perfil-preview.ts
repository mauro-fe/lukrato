import { PerfilFormData, PerfilSnapshot } from '@/src/features/perfil/types';

export const perfilPreview: PerfilSnapshot = {
  helperTitle: 'Perfil claro, ajuda perto e nada escondido',
  helperDescription:
    'O usuário encontra seus dados, suporte, indicações e integrações sem precisar adivinhar em qual menu tocar.',
  identity: {
    name: 'Mauro Silva',
    email: 'mauro@lukrato.com',
    avatarUrl: '',
    initials: 'MS',
    supportCode: 'LK-24A9',
    completionScore: 86,
    completionLabel: 'Perfil quase completo',
  },
  focus: {
    title: 'Seu perfil já orienta o suporte e as integrações',
    description:
      'Com os dados básicos preenchidos, fica mais fácil receber ajuda certa, compartilhar o app e conectar canais externos.',
    valueLabel: '86% completo',
    supportText: 'Falta pouco para o perfil ficar redondo e sem lacunas.',
    tone: 'positive',
  },
  guidedSteps: [
    {
      id: '1',
      title: 'Confirme os dados que você usa de verdade',
      description: 'Nome, telefone e endereço corretos evitam dúvida quando precisar de suporte ou recuperar acesso.',
      done: true,
    },
    {
      id: '2',
      title: 'Guarde o código de suporte em lugar fácil',
      description: 'Se algo travar, esse código ajuda a equipe a achar sua conta mais rápido.',
      done: true,
    },
    {
      id: '3',
      title: 'Ative uma integração só se ela fizer sentido',
      description: 'A ideia é facilitar a rotina, não empurrar recurso que o usuário não quer usar.',
      done: false,
    },
  ],
  details: [
    {
      id: 'phone',
      label: 'Telefone',
      value: '(11) 99999-1234',
      helper: 'Canal útil para suporte e recuperação.',
    },
    {
      id: 'birth',
      label: 'Nascimento',
      value: '14/09/1992',
      helper: 'Usado quando a conta precisa ser confirmada.',
    },
    {
      id: 'cpf',
      label: 'CPF',
      value: '123.456.789-09',
      helper: 'Mantido visível só o necessário para o usuário conferir.',
    },
    {
      id: 'sex',
      label: 'Sexo',
      value: 'Não informado',
    },
    {
      id: 'address',
      label: 'Endereço',
      value: 'Rua das Laranjeiras, 150, Centro, São Paulo - SP',
      helper: 'Ajuda a manter o cadastro coerente com pagamentos e suporte.',
    },
  ],
  referral: {
    code: 'LUKR4TO',
    link: 'https://lukrato.com.br/login?ref=LUKR4TO',
    rewardDays: 45,
    totalInvites: 6,
    completedInvites: 3,
    pendingInvites: 1,
    monthlyUsed: 2,
    monthlyLimit: 10,
    monthlyRemaining: 8,
  },
  telegram: {
    linked: false,
    username: null,
    helperText:
      'Conecte o Telegram apenas se fizer sentido para registrar coisas sem abrir o app inteiro.',
  },
  support: {
    recommendedChannel: 'whatsapp',
    hint: 'Se surgir dúvida, escreva com suas palavras. O app manda a mensagem sem o usuário caçar e-mail ou formulário externo.',
  },
};

export const perfilPreviewFormData: PerfilFormData = {
  name: 'Mauro Silva',
  email: 'mauro@lukrato.com',
  cpf: '123.456.789-09',
  phone: '(11) 99999-1234',
  sex: 'N',
  birthDate: '1992-09-14',
  addressCep: '01001-000',
  addressStreet: 'Rua das Laranjeiras',
  addressNumber: '150',
  addressComplement: '',
  addressNeighborhood: 'Centro',
  addressCity: 'São Paulo',
  addressState: 'SP',
};