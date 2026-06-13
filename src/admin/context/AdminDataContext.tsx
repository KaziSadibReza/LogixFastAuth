import { createContext, useCallback, useContext, useRef, useState, type ReactNode } from 'react';
import { fetchRateBlocks, type RateBlock } from '../api/security';
import {
  fetchPages,
  fetchSmsProviders,
  fetchStats,
  type DashboardStats,
  type PageOption,
} from '../api/settings';

interface AdminDataContextValue {
  pages: PageOption[] | null;
  pagesLoading: boolean;
  stats: DashboardStats | null;
  statsLoading: boolean;
  smsProviders: { name: string }[] | null;
  smsLoading: boolean;
  rateBlocks: RateBlock[] | null;
  blocksLoading: boolean;
  ensurePages: () => Promise<PageOption[]>;
  ensureStats: () => Promise<DashboardStats | null>;
  ensureSmsProviders: () => Promise<{ name: string }[]>;
  ensureRateBlocks: () => Promise<RateBlock[]>;
  refreshPages: () => Promise<PageOption[]>;
  refreshStats: () => Promise<DashboardStats | null>;
  refreshRateBlocks: () => Promise<RateBlock[]>;
}

const AdminDataContext = createContext<AdminDataContextValue | null>(null);

type CacheState<T> = {
  data: T | null;
  promise: Promise<T> | null;
};

function createCache<T>() {
  return { data: null, promise: null } as CacheState<T>;
}

async function loadCached<T>(
  cache: CacheState<T>,
  fetcher: () => Promise<T>,
  force = false
): Promise<T> {
  if (!force && cache.data !== null) {
    return cache.data;
  }
  if (!force && cache.promise) {
    return cache.promise;
  }

  cache.promise = fetcher()
    .then((result) => {
      cache.data = result;
      cache.promise = null;
      return result;
    })
    .catch((err) => {
      cache.promise = null;
      throw err;
    });

  return cache.promise;
}

export function AdminDataProvider({ children }: { children: ReactNode }) {
  const [pages, setPages] = useState<PageOption[] | null>(null);
  const [pagesLoading, setPagesLoading] = useState(false);
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [statsLoading, setStatsLoading] = useState(false);
  const [smsProviders, setSmsProviders] = useState<{ name: string }[] | null>(null);
  const [smsLoading, setSmsLoading] = useState(false);
  const [rateBlocks, setRateBlocks] = useState<RateBlock[] | null>(null);
  const [blocksLoading, setBlocksLoading] = useState(false);

  const pagesCache = useRef(createCache<PageOption[]>());
  const statsCache = useRef(createCache<DashboardStats>());
  const smsCache = useRef(createCache<{ name: string }[]>());
  const blocksCache = useRef(createCache<RateBlock[]>());

  const ensurePages = useCallback(async () => {
    if (pagesCache.current.data) {
      if (!pages) setPages(pagesCache.current.data);
      return pagesCache.current.data;
    }
    setPagesLoading(true);
    try {
      const data = await loadCached(pagesCache.current, fetchPages);
      setPages(data);
      return data;
    } catch {
      pagesCache.current.data = [];
      setPages([]);
      return [];
    } finally {
      setPagesLoading(false);
    }
  }, [pages]);

  const refreshPages = useCallback(async () => {
    pagesCache.current = createCache<PageOption[]>();
    setPagesLoading(true);
    try {
      const data = await loadCached(pagesCache.current, fetchPages, true);
      setPages(data);
      return data;
    } catch {
      pagesCache.current.data = [];
      setPages([]);
      return [];
    } finally {
      setPagesLoading(false);
    }
  }, []);

  const ensureStats = useCallback(async () => {
    if (statsCache.current.data) {
      if (!stats) setStats(statsCache.current.data);
      return statsCache.current.data;
    }
    setStatsLoading(true);
    try {
      const data = await loadCached(statsCache.current, fetchStats);
      setStats(data);
      return data;
    } catch {
      setStats(null);
      return null;
    } finally {
      setStatsLoading(false);
    }
  }, [stats]);

  const refreshStats = useCallback(async () => {
    statsCache.current = createCache<DashboardStats>();
    setStatsLoading(true);
    try {
      const data = await loadCached(statsCache.current, fetchStats, true);
      setStats(data);
      return data;
    } catch {
      setStats(null);
      return null;
    } finally {
      setStatsLoading(false);
    }
  }, []);

  const ensureSmsProviders = useCallback(async () => {
    if (smsCache.current.data) {
      if (!smsProviders) setSmsProviders(smsCache.current.data);
      return smsCache.current.data;
    }
    setSmsLoading(true);
    try {
      const data = await loadCached(smsCache.current, fetchSmsProviders);
      setSmsProviders(data);
      return data;
    } catch {
      smsCache.current.data = [];
      setSmsProviders([]);
      return [];
    } finally {
      setSmsLoading(false);
    }
  }, [smsProviders]);

  const ensureRateBlocks = useCallback(async () => {
    if (blocksCache.current.data) {
      if (!rateBlocks) setRateBlocks(blocksCache.current.data);
      return blocksCache.current.data;
    }
    setBlocksLoading(true);
    try {
      const data = await loadCached(blocksCache.current, async () => {
        const res = await fetchRateBlocks();
        return res.blocks;
      });
      setRateBlocks(data);
      return data;
    } catch {
      blocksCache.current.data = [];
      setRateBlocks([]);
      return [];
    } finally {
      setBlocksLoading(false);
    }
  }, [rateBlocks]);

  const refreshRateBlocks = useCallback(async () => {
    blocksCache.current = createCache<RateBlock[]>();
    setBlocksLoading(true);
    try {
      const data = await loadCached(
        blocksCache.current,
        async () => {
          const res = await fetchRateBlocks();
          return res.blocks;
        },
        true
      );
      setRateBlocks(data);
      return data;
    } catch {
      blocksCache.current.data = [];
      setRateBlocks([]);
      return [];
    } finally {
      setBlocksLoading(false);
    }
  }, []);

  return (
    <AdminDataContext.Provider
      value={{
        pages,
        pagesLoading,
        stats,
        statsLoading,
        smsProviders,
        smsLoading,
        rateBlocks,
        blocksLoading,
        ensurePages,
        ensureStats,
        ensureSmsProviders,
        ensureRateBlocks,
        refreshPages,
        refreshStats,
        refreshRateBlocks,
      }}
    >
      {children}
    </AdminDataContext.Provider>
  );
}

export function useAdminData() {
  const ctx = useContext(AdminDataContext);
  if (!ctx) throw new Error('useAdminData must be used within AdminDataProvider');
  return ctx;
}
