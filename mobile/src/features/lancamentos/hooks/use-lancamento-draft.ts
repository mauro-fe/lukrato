import { useEffect, useMemo, useState } from 'react';

import {
  FormAccountOption,
  FormCategoryOption,
} from '@/src/features/lancamentos/data/lancamento-form-options';
import { lancamentosRepository } from '@/src/features/lancamentos/repositories/lancamentos-repository';
import { LancamentoEntryMode } from '@/src/features/lancamentos/types';
import { HttpClientError } from '@/src/lib/api/http-client';

type DraftErrors = Partial<
  Record<'amount' | 'description' | 'date' | 'account' | 'category' | 'destination', string>
>;
type SubmitFeedback = {
  tone: 'success' | 'error';
  message: string;
};

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

function mapApiErrors(error: HttpClientError): DraftErrors {
  const nextErrors: DraftErrors = {};
  const details = error.details;

  if (!details || typeof details !== 'object' || Array.isArray(details)) {
    return nextErrors;
  }

  const detailMap = details as Record<string, unknown>;

  if (typeof detailMap.valor === 'string') {
    nextErrors.amount = detailMap.valor;
  }

  if (typeof detailMap.descricao === 'string') {
    nextErrors.description = detailMap.descricao;
  }

  if (typeof detailMap.data === 'string') {
    nextErrors.date = detailMap.data;
  }

  if (typeof detailMap.conta_id === 'string') {
    nextErrors.account = detailMap.conta_id;
  }

  if (typeof detailMap.categoria_id === 'string') {
    nextErrors.category = detailMap.categoria_id;
  }

  if (typeof detailMap.conta_id_destino === 'string') {
    nextErrors.destination = detailMap.conta_id_destino;
  }

  return nextErrors;
}

export function useLancamentoDraft(initialMode: LancamentoEntryMode) {
  const [mode, setMode] = useState<LancamentoEntryMode>(initialMode);
  const [amountInput, setAmountInput] = useState('');
  const [description, setDescription] = useState('');
  const [date, setDate] = useState(todayString());
  const [accountId, setAccountId] = useState('');
  const [destinationAccountId, setDestinationAccountId] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [note, setNote] = useState('');
  const [isPaid, setIsPaid] = useState(true);
  const [errors, setErrors] = useState<DraftErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitFeedback, setSubmitFeedback] = useState<SubmitFeedback | null>(null);
  const [dataSource, setDataSource] = useState<'remote'>('remote');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [allAccounts, setAllAccounts] = useState<FormAccountOption[]>([]);
  const [allCategories, setAllCategories] = useState<FormCategoryOption[]>([]);

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
    setSubmitFeedback(null);

    if (!validate()) {
      return false;
    }

    setIsSubmitting(true);

    try {
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
          message: 'Nao foi possivel salvar o lancamento agora.',
        });
      }

      return false;
    } finally {
      setIsSubmitting(false);
    }
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
    submitFeedback,
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
