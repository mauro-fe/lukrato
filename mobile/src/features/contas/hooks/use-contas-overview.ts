import { useCallback, useState } from 'react';
import { useFocusEffect } from 'expo-router';

import { contasPreview } from '@/src/features/contas/data/contas-preview';
import { contasRepository } from '@/src/features/contas/repositories/contas-repository';

export function useContasOverview() {
  const [snapshot, setSnapshot] = useState(contasPreview);
  const [source, setSource] = useState<'preview' | 'remote'>('preview');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const loadSnapshot = useCallback(async (refreshing = false) => {
    if (refreshing) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }

    try {
      const result = await contasRepository.getSnapshot();
      setSnapshot(result.data);
      setSource(result.source);
      setSourceMessage(result.message ?? null);
    } finally {
      if (refreshing) {
        setIsRefreshing(false);
      } else {
        setIsLoading(false);
      }
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void loadSnapshot(false);
    }, [loadSnapshot])
  );

  return {
    snapshot,
    source,
    sourceMessage,
    isLoading,
    isRefreshing,
    refresh: () => loadSnapshot(true),
  };
}
