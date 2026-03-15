import { useDeferredValue, useEffect, useMemo, useState } from 'react';

import {
  contaInstitutionOptions,
  ContaInstitutionOption,
} from '@/src/features/contas/data/conta-form-options';
import { contasRepository } from '@/src/features/contas/repositories/contas-repository';

type ContaDraftErrors = Partial<Record<'name', string>>;

type AccountType =
  | 'conta_corrente'
  | 'conta_poupanca'
  | 'conta_investimento'
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

  if (accountType === 'conta_investimento') {
    return baseName ? `Carteira ${baseName}` : 'Carteira de investimentos';
  }

  if (accountType === 'carteira_digital') {
    return baseName ? `${baseName} digital` : 'Carteira digital';
  }

  if (accountType === 'dinheiro') {
    return 'Dinheiro em maos';
  }

  return baseName ? `${baseName} principal` : 'Conta principal';
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
  const [submitMessage, setSubmitMessage] = useState('');
  const [dataSource, setDataSource] = useState<'preview' | 'remote'>('preview');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [allInstitutions, setAllInstitutions] =
    useState<ContaInstitutionOption[]>(contaInstitutionOptions);

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
    setSubmitMessage('');

    if (!validate()) {
      return false;
    }

    setIsSubmitting(true);
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
    setIsSubmitting(false);
    setDataSource(result.source);
    setSourceMessage(result.message ?? null);
    setSubmitMessage(result.data.message);

    return result.source;
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
    submitMessage,
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
