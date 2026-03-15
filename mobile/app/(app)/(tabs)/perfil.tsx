import { FeaturePlaceholderScreen } from '@/src/shared/ui/feature-placeholder-screen';

export default function PerfilRoute() {
  return (
    <FeaturePlaceholderScreen
      eyebrow="Configuracoes"
      title="Perfil, ajuda e seguranca"
      description="O objetivo desta tela e deixar as configuracoes importantes faceis de achar, sem esconder nada critico."
      highlights={[
        'Dados pessoais, tema e seguranca em secoes separadas.',
        'Acesso rapido a ajuda e suporte.',
        'Acoes sensiveis sempre com linguagem clara e confirmacao.',
      ]}
    />
  );
}
