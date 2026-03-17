import { useDeferredValue, useEffect, useMemo, useState } from 'react';

import { ContaInstitutionOption } from '@/src/features/contas/data/conta-form-options';
import { contasRepository } from '@/src/features/contas/repositories/contas-repository';
import { HttpClientError } from '@/src/lib/api/http-client';

type ContaDraftErrors = Partial<Record<'name' | 'initialBalance', string>>;
type SubmitFeedback = {
  tone: 'success' | 'error';
  message: string;
};

type AccountType =
  | 'conta_corrente'
  | 'conta_poupanca'
  | 'carteira_digital'
  | 'dinheiro';

function normalizeAmount(input: string) {
  const cleaned = input.replace(/[^\d,.-]/g, '').replace(',', '.');

  if (!cleaned || cleaned === '-' || cleaned === '.' || cleaned === '-.') {
    return 0;
  }

  const parsed = Number.parseFloat(cleaned);
  return Number.isFinite(parsed) ? parsed : 0;
}

function buildSuggestedName(
  accountType: AccountType,
  selectedInstitution?: ContaInstitutionOption,
  manualInstitutionName?: string
) {
  const baseName = manualInstitutionName?.trim() || selectedInstitution?.name || '';

  if (accountType === 'conta_poupanca') {
    return baseName ? `Reserva ${baseName}` : 'Reserva principal';
  }

  if (accountType === 'carteira_digital') {
    return baseName ? `${baseName} digital` : 'Carteira digital';
  }

  if (accountType === 'dinheiro') {
    return 'Dinheiro em maos';
  }

  return baseName ? `${baseName} principal` : 'Conta principal';
}

function mapApiErrors(error: HttpClientError): ContaDraftErrors {
  const nextErrors: ContaDraftErrors = {};
  const details = error.details;

  if (!details || typeof details !== 'object' || Array.isArray(details)) {
    return nextErrors;
  }

  const detailMap = details as Record<string, unknown>;

  if (typeof detailMap.nome === 'string') {
    nextErrors.name = detailMap.nome;
  }

  if (typeof detailMap.saldo_inicial === 'string') {
    nextErrors.initialBalance = detailMap.saldo_inicial;
  }

  return nextErrors;
}

export function useContaDraft(
  initialType: AccountType = 'conta_corrente'
) {
  const [accountType, setAccountType] = useState<AccountType>(initialType);
  const [name, setName] = useState('');
  const [institutionId, setInstitutionId] = useState('');
  const [manualInstitutionName, setManualInstitutionName] = useState('');
  const [institutionQuery, setInstitutionQuery] = useState('');
  const [initialBalanceInput, setInitialBalanceInput] = useState('');
  const [errors, setErrors] = useState<ContaDraftErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitFeedback, setSubmitFeedback] = useState<SubmitFeedback | null>(null);
  const [dataSource, setDataSource] = useState<'remote'>('remote');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [allInstitutions, setAllInstitutions] = useState<ContaInstitutionOption[]>([]);

  useEffect(() => {
    let isMounted = true;

    async function loadOptions() {
      const result = await contasRepository.getFormOptions();

      if (!isMounted) {
        return;
      }

      setAllInstitutions(result.data.institutions);
      setDataSource(result.source);
      setSourceMessage(result.message ?? null);
      setInstitutionId((current) =>
        result.data.institutions.some((institution) => institution.id === current)
          ? current
          : ''
      );
    }

    void loadOptions();

    return () => {
      isMounted = false;
    };
  }, []);

  useEffect(() => {
    if (accountType === 'dinheiro') {
      setInstitutionId('');
      setInstitutionQuery('');
    }
  }, [accountType]);

  const deferredQuery = useDeferredValue(institutionQuery.trim().toLowerCase());
  const initialBalance = useMemo(
    () => normalizeAmount(initialBalanceInput),
    [initialBalanceInput]
  );
  const selectedInstitution = useMemo(
    () => allInstitutions.find((institution) => institution.id === institutionId),
    [allInstitutions, institutionId]
  );

  const filteredInstitutions = useMemo(() => {
    if (accountType === 'dinheiro') {
      return [];
    }

    if (!deferredQuery) {
      return allInstitutions.slice(0, 8);
    }

    return allInstitutions
      .filter((institution) =>
        `${institution.name} ${institution.type}`.toLowerCase().includes(deferredQuery)
      )
      .slice(0, 8);
  }, [accountType, allInstitutions, deferredQuery]);

  const suggestedName = useMemo(
    () => buildSuggestedName(accountType, selectedInstitution, manualInstitutionName),
    [accountType, manualInstitutionName, selectedInstitution]
  );

  function selectInstitution(nextInstitutionId: string) {
    setInstitutionId((current) =>
      current === nextInstitutionId ? '' : nextInstitutionId
    );
  }

  function validate() {
    const nextErrors: ContaDraftErrors = {};

    if (!name.trim()) {
      nextErrors.name = 'Escolha um nome simples para o usuario reconhecer a conta.';
    } else if (name.trim().length > 100) {
      nextErrors.name = 'Use um nome mais curto, com no maximo 100 caracteres.';
    }

    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  }

  async function submit() {
    setSubmitFeedback(null);

    if (!validate()) {
      return false;
    }

    setIsSubmitting(true);

    try {
      const result = await contasRepository.createConta({
        name,
        accountType,
        institutionId: institutionId || undefined,
        institutionName:
          institutionId || accountType === 'dinheiro'
            ? accountType === 'dinheiro'
              ? 'Fora do banco'
              : undefined
            : manualInstitutionName.trim() || undefined,
        initialBalance,
      });

      setDataSource(result.source);
      setSourceMessage(result.message ?? null);
      setSubmitFeedback({
        tone: 'success',
        message: result.data.message,
      });
      setErrors({});
      return true;
    } catch (error) {
      if (error instanceof HttpClientError) {
        setErrors(mapApiErrors(error));
        setSubmitFeedback({
          tone: 'error',
          message: error.message,
        });
      } else {
        setSubmitFeedback({
          tone: 'error',
          message: 'Nao foi possivel salvar a conta agora.',
        });
      }

      return false;
    } finally {
      setIsSubmitting(false);
    }
  }

  return {
    accountType,
    name,
    institutionId,
    institutionQuery,
    initialBalance,
    initialBalanceInput,
    manualInstitutionName,
    errors,
    isSubmitting,
    submitFeedback,
    dataSource,
    sourceMessage,
    selectedInstitution,
    filteredInstitutions,
    suggestedName,
    setAccountType,
    setName,
    setInstitutionQuery,
    setInitialBalanceInput,
    setManualInstitutionName,
    selectInstitution,
    submit,
  };
}
