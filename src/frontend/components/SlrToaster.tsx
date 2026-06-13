import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-react';

export type SlrToastVariant = 'success' | 'error' | 'warning' | 'info';

interface SlrToast {
  id: number;
  message: string;
  title?: string;
  variant: SlrToastVariant;
  duration: number;
}

interface SlrToastContextValue {
  error: (message: string, title?: string) => void;
  success: (message: string, title?: string) => void;
  info: (message: string, title?: string) => void;
}

const SlrToastContext = createContext<SlrToastContextValue | null>(null);

const MAX_TOASTS = 3;
let nextId = 1;

export function SlrToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<SlrToast[]>([]);
  const [exiting, setExiting] = useState<Set<number>>(new Set());

  const dismiss = useCallback((id: number) => {
    setExiting((prev) => new Set(prev).add(id));
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
      setExiting((prev) => {
        const next = new Set(prev);
        next.delete(id);
        return next;
      });
    }, 220);
  }, []);

  const push = useCallback(
    (message: string, variant: SlrToastVariant, title?: string, duration = variant === 'error' ? 6000 : 4500) => {
      const id = nextId++;
      const toast: SlrToast = { id, message, title, variant, duration };
      setToasts((prev) => {
        const next = [...prev, toast];
        return next.length > MAX_TOASTS ? next.slice(-MAX_TOASTS) : next;
      });
      if (duration > 0) setTimeout(() => dismiss(id), duration);
    },
    [dismiss]
  );

  const value = useMemo<SlrToastContextValue>(
    () => ({
      error: (message, title) => push(message, 'error', title),
      success: (message, title) => push(message, 'success', title),
      info: (message, title) => push(message, 'info', title),
    }),
    [push]
  );

  return (
    <SlrToastContext.Provider value={value}>
      {children}
      <SlrToaster toasts={toasts} exiting={exiting} onDismiss={dismiss} />
    </SlrToastContext.Provider>
  );
}

function SlrToaster({
  toasts,
  exiting,
  onDismiss,
}: {
  toasts: SlrToast[];
  exiting: Set<number>;
  onDismiss: (id: number) => void;
}) {
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) return null;

  return createPortal(
    <div className="slr-toaster" role="region" aria-live="polite" aria-label="Notifications">
      {toasts.map((t) => (
        <div
          key={t.id}
          className={`slr-toast slr-toast--${t.variant} ${exiting.has(t.id) ? 'exiting' : ''}`}
          role="alert"
        >
          <span className="slr-toast-icon" aria-hidden="true">
            {t.variant === 'error' && <XCircle size={18} />}
            {t.variant === 'success' && <CheckCircle2 size={18} />}
            {t.variant === 'warning' && <AlertTriangle size={18} />}
            {t.variant === 'info' && <Info size={18} />}
          </span>
          <div className="slr-toast-body">
            {t.title && <div className="slr-toast-title">{t.title}</div>}
            <div className="slr-toast-message">{t.message}</div>
          </div>
          <button type="button" className="slr-toast-close" onClick={() => onDismiss(t.id)} aria-label="Dismiss">
            <X size={16} />
          </button>
        </div>
      ))}
    </div>,
    document.body
  );
}

export function useSlrToast() {
  const ctx = useContext(SlrToastContext);
  if (!ctx) throw new Error('useSlrToast must be used within SlrToastProvider');
  return ctx;
}
