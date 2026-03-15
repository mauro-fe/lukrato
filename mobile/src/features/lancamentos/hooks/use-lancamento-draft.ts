import { useEffect, useMemo, useState } from 'react';

import {
  FormAccountOption,
  FormCategoryOption,
  formAccountOptions,
  formCategoryOptions,
} from '@/src/features/lancamentos/data/lancamento-form-options';
import { lancamentosRepository } from '@/src/features/lancamentos/repositories/lancamentos-repository';
import { LancamentoEntryMode } from '@/src/features/lancamentos/types';

type DraftErrors = Partial<Record<'amount' | 'description' | 'date' | 'account' | 'category' | 'destination', string>>;

function todayString() {
  return new Date().toISOString().slice(0, 10);
}

function normalizeAmount(input: string) {
  const cleaned = input.replace(/[^\d,.-]/g, '').replace(',', '.');
  const parsed = Number.parseFloat(cleaned);

  if (!Number.isFinite(parsed)) {
    return 0;
  }

  return Math.abs(parsed);
}

export function useLancamentoDraft(initialMode: LancamentoEntryMode) {
  const [mode, setMode] = useState<LancamentoEntryMode>(initialMode);
  const [amountInput, setAmountInput] = useState('');
  const [description, setDescription] = useState('');
  const [date, setDate] = useState(todayString());
  const [accountId, setAccountId] = useState(formAccountOptions[0]?.id ?? '');
  const [destinationAccountId, setDestinationAccountId] = useState(formAccountOptions[1]?.id ?? '');
  const [categoryId, setCategoryId] = useState('');
  const [note, setNote] = useState('');
  const [isPaid, setIsPaid] = useState(true);
  const [errors, setErrors] = useState<DraftErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitMessage, setSubmitMessage] = useState('');
  const [dataSource, setDataSource] = useState<'preview' | 'remote'>('preview');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [allAccounts, setAllAccounts] = useState<FormAccountOption[]>(formAccountOptions);
  const [allCategories, setAllCategories] = useState<FormCategoryOption[]>(formCategoryOptions);

  useEffect(() => {
    let isMounted = true;

    async function loadOptions() {
      const result = await lancamentosRepository.getFormOptions();

      if (!isMounted) {
        return;
      }

      setAllAccounts(result.data.accounts);
      setAllCategories(result.data.categories);
      setDataSource(result.source);
      setSourceMessage(result.message ?? null);
      setAccountId((current) =>
        result.data.accounts.some((account) => account.id === current)
          ? current
          : result.data.accounts[0]?.id || ''
      );
      setDestinationAccountId((current) =>
        result.data.accounts.some((account) => account.id === current)
          ? current
          : result.data.accounts[1]?.id || result.data.accounts[0]?.id || ''
      );
    }

    void loadOptions();

    return () => {
      isMounted = false;
    };
  }, []);

  useEffect(() => {
    if (mode === 'transfer') {
      setCategoryId('');
      return;
    }

    const firstCategory = allCategories.find((item) => item.type === mode)?.id ?? '';
    setCategoryId((current) => current || firstCategory);
  }, [allCategories, mode]);

  const amount = useMemo(() => normalizeAmount(amountInput), [amountInput]);

  const availableCategories = useMemo(
    () => allCategories.filter((item) => item.type === mode),
    [allCategories, mode]
  );

  function validate() {
    const nextErrors: DraftErrors = {};

    if (!amount || amount <= 0) {
      nextErrors.amount = 'Informe um valor maior que zero.';
    }

    if (!date) {
      nextErrors.date = 'Escolha a data.';
    }

    if (!accountId) {
      nextErrors.account = 'Escolha a conta principal.';
    }

    if (mode !== 'transfer' && !description.trim()) {
      nextErrors.description = 'Descreva rapidamente o lancamento.';
    }

    if (mode !== 'transfer' && !categoryId) {
      nextErrors.category = 'Escolha a categoria.';
    }

    if (mode === 'transfer') {
      if (!destinationAccountId) {
        nextErrors.destination = 'Escolha a conta de destino.';
      } else if (destinationAccountId === accountId) {
        nextErrors.destination = 'Origem e destino precisam ser diferentes.';
      }
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
    const result = await lancamentosRepository.createLancamento({
      mode,
      amount,
      description,
      date,
      note,
      accountId,
      destinationAccountId,
      categoryId,
      isPaid,
    });
    setIsSubmitting(false);
    setDataSource(result.source);
    setSourceMessage(result.message ?? null);
    setSubmitMessage(result.data.message);
    return true;
  }

  return {
    mode,
    amount,
    amountInput,
    description,
    date,
    accountId,
    destinationAccountId,
    categoryId,
    note,
    isPaid,
    errors,
    isSubmitting,
    submitMessage,
    dataSource,
    sourceMessage,
    availableAccounts: allAccounts,
    availableCategories,
    setMode,
    setAmountInput,
    setDescription,
    setDate,
    setAccountId,
    setDestinationAccountId,
    setCategoryId,
    setNote,
    setIsPaid,
    submit,
  };
}
