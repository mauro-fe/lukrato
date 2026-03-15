import { useCallback, useEffect, useMemo, useState } from 'react';

import { dashboardPreview } from '@/src/features/dashboard/data/dashboard-preview';

export function useDashboardPreview() {
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsLoading(false);
    }, 700);

    return () => clearTimeout(timer);
  }, []);

  const refresh = useCallback(async () => {
    setIsRefreshing(true);
    await new Promise((resolve) => setTimeout(resolve, 900));
    setIsRefreshing(false);
  }, []);

  const snapshot = useMemo(() => dashboardPreview, []);

  return {
    snapshot,
    isLoading,
    isRefreshing,
    refresh,
  };
}
