import { useEffect, useState } from 'react';

import { perfilPreviewFormData } from '@/src/features/perfil/data/perfil-preview';
import { perfilRepository } from '@/src/features/perfil/repositories/perfil-repository';
import { HttpClientError } from '@/src/lib/api/http-client';
import { PerfilFeedback, PerfilFormData, PerfilFormErrors } from '@/src/features/perfil/types';

function hasAddressData(profile: PerfilFormData) {
  return Boolean(
    profile.addressCep ||
      profile.addressStreet ||
      profile.addressNumber ||
      profile.addressComplement ||
      profile.addressNeighborhood ||
      profile.addressCity ||
      profile.addressState
  );
}

function mapApiErrors(error: HttpClientError): PerfilFormErrors {
  const nextErrors: PerfilFormErrors = {};
  const details = error.details;

  if (!details || typeof details !== 'object' || Array.isArray(details)) {
    return nextErrors;
  }

  const detailMap = details as Record<string, unknown>;

  if (typeof detailMap.nome === 'string') {
    nextErrors.name = detailMap.nome;
  }

  if (typeof detailMap.email === 'string') {
    nextErrors.email = detailMap.email;
  }

  if (typeof detailMap.cpf === 'string') {
    nextErrors.cpf = detailMap.cpf;
  }

  if (typeof detailMap.telefone === 'string') {
    nextErrors.phone = detailMap.telefone;
  }

  if (typeof detailMap.data_nascimento === 'string') {
    nextErrors.birthDate = detailMap.data_nascimento;
  }

  if (typeof detailMap['endereco.cep'] === 'string') {
    nextErrors.addressCep = detailMap['endereco.cep'];
  }

  if (typeof detailMap['endereco.rua'] === 'string') {
    nextErrors.addressStreet = detailMap['endereco.rua'];
  }

  if (typeof detailMap['endereco.numero'] === 'string') {
    nextErrors.addressNumber = detailMap['endereco.numero'];
  }

  if (typeof detailMap['endereco.bairro'] === 'string') {
    nextErrors.addressNeighborhood = detailMap['endereco.bairro'];
  }

  if (typeof detailMap['endereco.cidade'] === 'string') {
    nextErrors.addressCity = detailMap['endereco.cidade'];
  }

  if (typeof detailMap['endereco.estado'] === 'string') {
    nextErrors.addressState = detailMap['endereco.estado'];
  }

  return nextErrors;
}

export function usePerfilFormDraft() {
  const [profile, setProfile] = useState<PerfilFormData>(perfilPreviewFormData);
  const [errors, setErrors] = useState<PerfilFormErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [feedback, setFeedback] = useState<PerfilFeedback | null>(null);
  const [dataSource, setDataSource] = useState<'preview' | 'remote'>('preview');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    async function loadFormData() {
      const result = await perfilRepository.getFormData();

      if (!isMounted) {
        return;
      }

      setProfile(result.data.profile);
      setDataSource(result.source);
      setSourceMessage(result.message ?? null);
    }

    void loadFormData();

    return () => {
      isMounted = false;
    };
  }, []);

  function updateField<Key extends keyof PerfilFormData>(field: Key, value: PerfilFormData[Key]) {
    setProfile((current) => ({
      ...current,
      [field]: value,
    }));

    setErrors((current) => {
      if (!(field in current)) {
        return current;
      }

      const nextErrors = { ...current };
      delete nextErrors[field as keyof PerfilFormErrors];
      return nextErrors;
    });
  }

  function validate() {
    const nextErrors: PerfilFormErrors = {};

    if (!profile.name.trim()) {
      nextErrors.name = 'Use o nome que o usuario realmente reconhece como dono da conta.';
    }

    if (!profile.email.trim() || !profile.email.includes('@')) {
      nextErrors.email = 'Informe um email valido.';
    }

    if (hasAddressData(profile)) {
      if (!profile.addressStreet.trim()) {
        nextErrors.addressStreet = 'Rua e obrigatoria quando o usuario decide preencher endereco.';
      }

      if (!profile.addressNumber.trim()) {
        nextErrors.addressNumber = 'Numero e obrigatorio quando o endereco entra no cadastro.';
      }

      if (!profile.addressNeighborhood.trim()) {
        nextErrors.addressNeighborhood = 'Bairro e obrigatorio quando o endereco entra no cadastro.';
      }

      if (!profile.addressCity.trim()) {
        nextErrors.addressCity = 'Cidade e obrigatoria quando o endereco entra no cadastro.';
      }

      if (!profile.addressState.trim()) {
        nextErrors.addressState = 'Estado e obrigatorio quando o endereco entra no cadastro.';
      } else if (profile.addressState.trim().length !== 2) {
        nextErrors.addressState = 'Use a UF com 2 letras.';
      }
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  }

  async function submit() {
    setFeedback(null);

    if (!validate()) {
      return false;
    }

    setIsSubmitting(true);

    try {
      const result = await perfilRepository.updateProfile(profile);
      setDataSource(result.source);
      setSourceMessage(result.message ?? null);
      setFeedback({
        tone: 'success',
        message: result.data.message,
      });
      return result.source;
    } catch (error) {
      if (error instanceof HttpClientError) {
        setErrors(mapApiErrors(error));
        setFeedback({
          tone: 'error',
          message: error.message,
        });
      } else {
        setFeedback({
          tone: 'error',
          message: 'Nao foi possivel salvar o perfil agora.',
        });
      }

      return false;
    } finally {
      setIsSubmitting(false);
    }
  }

  return {
    profile,
    errors,
    isSubmitting,
    feedback,
    dataSource,
    sourceMessage,
    updateField,
    submit,
  };
}
