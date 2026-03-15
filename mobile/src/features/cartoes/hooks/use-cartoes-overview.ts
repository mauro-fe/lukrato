import { useCallback, useState } from 'react';
import { useFocusEffect } from 'expo-router';

import { cartoesPreview } from '@/src/features/cartoes/data/cartoes-preview';
import { cartoesRepository } from '@/src/features/cartoes/repositories/cartoes-repository';

export function useCartoesOverview() {
  const [snapshot, setSnapshot] = useState(cartoesPreview);
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
      const result = await cartoesRepository.getSnapshot();
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
