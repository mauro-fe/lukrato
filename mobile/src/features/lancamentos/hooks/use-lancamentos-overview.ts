import { useDeferredValue, useEffect, useMemo, useState, useTransition } from 'react';

import {
  createEmptyLancamentosSnapshot,
  lancamentosRepository,
} from '@/src/features/lancamentos/repositories/lancamentos-repository';
import { LancamentoFilter, LancamentoItem, LancamentoSection } from '@/src/features/lancamentos/types';

function matchesFilter(item: LancamentoItem, activeFilter: LancamentoFilter) {
  if (activeFilter === 'all') return true;
  if (activeFilter === 'pending') return item.status === 'pending';
  if (activeFilter === 'paid') return item.status === 'paid';
  if (activeFilter === 'income') return item.type === 'income';
  if (activeFilter === 'expense') return item.type === 'expense';
  return true;
}

function matchesSearch(item: LancamentoItem, query: string) {
  if (!query) return true;

  const haystack = `${item.title} ${item.category} ${item.account} ${item.note ?? ''}`.toLowerCase();
  return haystack.includes(query);
}

function buildSections(items: LancamentoItem[]): LancamentoSection[] {
  const groups = new Map<string, LancamentoItem[]>();

  items.forEach((item) => {
    const label = new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit',
      month: 'long',
    }).format(new Date(item.date));

    if (!groups.has(label)) {
      groups.set(label, []);
    }

    groups.get(label)?.push(item);
  });

  return Array.from(groups.entries()).map(([label, groupedItems], index) => ({
    id: `${label}-${index}`,
    label,
    items: groupedItems,
  }));
}

export function useLancamentosOverview() {
  const [snapshot, setSnapshot] = useState(() => createEmptyLancamentosSnapshot());
  const [activeFilter, setActiveFilter] = useState<LancamentoFilter>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isSheetOpen, setIsSheetOpen] = useState(false);
  const [source, setSource] = useState<'remote'>('remote');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isPending, startTransition] = useTransition();

  const deferredSearch = useDeferredValue(searchQuery.trim().toLowerCase());

  useEffect(() => {
    let isMounted = true;

    async function loadSnapshot() {
      const result = await lancamentosRepository.getSnapshot();

      if (!isMounted) {
        return;
      }

      setSnapshot(result.data);
      setSource(result.source);
      setSourceMessage(result.message ?? null);
      setIsLoading(false);
    }

    void loadSnapshot();

    return () => {
      isMounted = false;
    };
  }, []);

  const filteredItems = useMemo(
    () =>
      snapshot.items.filter(
        (item) => matchesFilter(item, activeFilter) && matchesSearch(item, deferredSearch)
      ),
    [activeFilter, deferredSearch, snapshot.items]
  );

  const sections = useMemo(() => buildSections(filteredItems), [filteredItems]);
  const attentionItems = useMemo(
    () => snapshot.items.filter((item) => item.status === 'pending').slice(0, 3),
    [snapshot.items]
  );

  async function refresh() {
    setIsRefreshing(true);
    const result = await lancamentosRepository.getSnapshot();
    setSnapshot(result.data);
    setSource(result.source);
    setSourceMessage(result.message ?? null);
    setIsRefreshing(false);
  }

  function changeFilter(nextFilter: LancamentoFilter) {
    startTransition(() => {
      setActiveFilter(nextFilter);
    });
  }

  function updateSearch(value: string) {
    setSearchQuery(value);
  }

  return {
    snapshot,
    source,
    sourceMessage,
    isLoading,
    activeFilter,
    searchQuery,
    filteredItems,
    sections,
    attentionItems,
    isRefreshing,
    isSheetOpen,
    isPending,
    refresh,
    changeFilter,
    updateSearch,
    openSheet: () => setIsSheetOpen(true),
    closeSheet: () => setIsSheetOpen(false),
  };
}
