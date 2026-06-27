import { createContext, useCallback, useContext, useEffect, useRef, useState, type ReactNode } from 'react';
import type { LogixFastAuthSettings } from '@shared/types';
import { fetchSettings, saveSettings as apiSave } from '../api/settings';
import { useToast } from '../ui/Toaster';

interface SettingsContextValue {
  settings: LogixFastAuthSettings | null;
  loading: boolean;
  saving: boolean;
  dirty: boolean;
  error: string;
  updateSection: <K extends keyof LogixFastAuthSettings>(section: K, data: Partial<LogixFastAuthSettings[K]>) => void;
  save: () => Promise<void>;
  reset: () => void;
  reload: () => Promise<void>;
}

const SettingsContext = createContext<SettingsContextValue | null>(null);

export function SettingsProvider({ children }: { children: ReactNode }) {
  const [settings, setSettings] = useState<LogixFastAuthSettings | null>(null);
  const [originalSettings, setOriginalSettings] = useState<LogixFastAuthSettings | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [dirty, setDirty] = useState(false);
  const toast = useToast();
  const initialLoad = useRef(true);
  const settingsRef = useRef<LogixFastAuthSettings | null>(null);

  useEffect(() => {
    settingsRef.current = settings;
  }, [settings]);

  useEffect(() => {
    fetchSettings()
      .then((s) => {
        setSettings(s);
        setOriginalSettings(JSON.parse(JSON.stringify(s)));
      })
      .catch((e) => {
        setError(e.message);
        toast.error(e.message, 'Failed to load settings');
      })
      .finally(() => {
        setLoading(false);
        initialLoad.current = false;
      });
  }, [toast]);

  const updateSection = useCallback(<K extends keyof LogixFastAuthSettings>(section: K, data: Partial<LogixFastAuthSettings[K]>) => {
    setSettings((prev) => {
      if (!prev) return prev;
      const next = { ...prev, [section]: { ...prev[section], ...data } };
      return next;
    });
    setDirty(true);
  }, []);

  const persistSettings = useCallback(async (next: LogixFastAuthSettings, message = 'Settings updated') => {
    setSaving(true);
    setError('');
    try {
      const saved = await apiSave(next);
      setSettings(saved);
      settingsRef.current = saved;
      setOriginalSettings(JSON.parse(JSON.stringify(saved)));
      setDirty(false);
      toast.success('Your changes have been saved.', message);
    } catch (e) {
      const msg = e instanceof Error ? e.message : 'Save failed';
      setError(msg);
      toast.error(msg, 'Could not save');
      throw e;
    } finally {
      setSaving(false);
    }
  }, [toast]);

  const save = useCallback(async () => {
    const current = settingsRef.current;
    if (!current) return;
    if (!dirty) {
      toast.info('No changes to save.');
      return;
    }
    await persistSettings(current);
  }, [dirty, persistSettings, toast]);

  const reset = useCallback(() => {
    if (!originalSettings) return;
    setSettings(JSON.parse(JSON.stringify(originalSettings)));
    setDirty(false);
    toast.info('Unsaved changes discarded.');
  }, [originalSettings, toast]);

  const reload = useCallback(async () => {
    try {
      const s = await fetchSettings();
      setSettings(s);
      setOriginalSettings(JSON.parse(JSON.stringify(s)));
      setDirty(false);
    } catch (e) {
      const msg = e instanceof Error ? e.message : 'Reload failed';
      toast.error(msg, 'Could not reload settings');
    }
  }, [toast]);

  return (
    <SettingsContext.Provider
      value={{ settings, loading, saving, dirty, error, updateSection, save, reset, reload }}
    >
      {children}
    </SettingsContext.Provider>
  );
}

export function useSettings() {
  const ctx = useContext(SettingsContext);
  if (!ctx) throw new Error('useSettings must be used within SettingsProvider');
  return ctx;
}
