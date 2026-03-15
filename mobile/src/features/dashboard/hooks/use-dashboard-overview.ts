import { useCallback, useState } from 'react';
import { useFocusEffect } from 'expo-router';

import { useAuthSession } from '@/src/features/auth/hooks/use-auth-session';
import {
  createEmptyDashboardSnapshot,
  dashboardRepository,
} from '@/src/features/dashboard/repositories/dashboard-repository';

export function useDashboardOverview() {
  const { session } = useAuthSession();
  const [snapshot, setSnapshot] = useState(() =>
    createEmptyDashboardSnapshot(session.userName || 'Usuario')
  );
  const [source, setSource] = useState<'remote'>('remote');
  const [sourceMessage, setSourceMessage] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const loadSnapshot = useCallback(
    async (refreshing = false) => {
      if (refreshing) {
        setIsRefreshing(true);
      } else {
        setIsLoading(true);
      }

      try {
        const result = await dashboardRepository.getSnapshot(session.userName || 'Usuario');
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
    },
    [session.userName]
  );

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
