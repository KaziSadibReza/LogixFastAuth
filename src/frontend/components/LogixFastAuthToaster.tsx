import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-react';

export type LogixFastAuthToastVariant = 'success' | 'error' | 'warning' | 'info';

interface LogixFastAuthToast {
  id: number;
  message: string;
  title?: string;
  variant: LogixFastAuthToastVariant;
  duration: number;
}

interface LogixFastAuthToastContextValue {
  error: (message: string, title?: string) => void;
  success: (message: string, title?: string) => void;
  info: (message: string, title?: string) => void;
}

const LogixFastAuthToastContext = createContext<LogixFastAuthToastContextValue | null>(null);

const MAX_TOASTS = 3;
let nextId = 1;

export function LogixFastAuthToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<LogixFastAuthToast[]>([]);
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
    (message: string, variant: LogixFastAuthToastVariant, title?: string, duration = variant === 'error' ? 6000 : 4500) => {
      const id = nextId++;
      const toast: LogixFastAuthToast = { id, message, title, variant, duration };
      setToasts((prev) => {
        const next = [...prev, toast];
        return next.length > MAX_TOASTS ? next.slice(-MAX_TOASTS) : next;
      });
      if (duration > 0) setTimeout(() => dismiss(id), duration);
    },
    [dismiss]
  );

  const value = useMemo<LogixFastAuthToastContextValue>(
    () => ({
      error: (message, title) => push(message, 'error', title),
      success: (message, title) => push(message, 'success', title),
      info: (message, title) => push(message, 'info', title),
    }),
    [push]
  );

  return (
    <LogixFastAuthToastContext.Provider value={value}>
      {children}
      <LogixFastAuthToaster toasts={toasts} exiting={exiting} onDismiss={dismiss} />
    </LogixFastAuthToastContext.Provider>
  );
}

function LogixFastAuthToaster({
  toasts,
  exiting,
  onDismiss,
}: {
  toasts: LogixFastAuthToast[];
  exiting: Set<number>;
  onDismiss: (id: number) => void;
}) {
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) return null;

  return createPortal(
    <div className="logixfast-auth-toaster" role="region" aria-live="polite" aria-label="Notifications">
      {toasts.map((t) => (
        <div
          key={t.id}
          className={`logixfast-auth-toast logixfast-auth-toast--${t.variant} ${exiting.has(t.id) ? 'exiting' : ''}`}
          role="alert"
        >
          <span className="logixfast-auth-toast-icon" aria-hidden="true">
            {t.variant === 'error' && <XCircle size={18} />}
            {t.variant === 'success' && <CheckCircle2 size={18} />}
            {t.variant === 'warning' && <AlertTriangle size={18} />}
            {t.variant === 'info' && <Info size={18} />}
          </span>
          <div className="logixfast-auth-toast-body">
            {t.title && <div className="logixfast-auth-toast-title">{t.title}</div>}
            <div className="logixfast-auth-toast-message">{t.message}</div>
          </div>
          <button type="button" className="logixfast-auth-toast-close" onClick={() => onDismiss(t.id)} aria-label="Dismiss">
            <X size={16} />
          </button>
        </div>
      ))}
    </div>,
    document.body
  );
}

export function useLogixFastAuthToast() {
  const ctx = useContext(LogixFastAuthToastContext);
  if (!ctx) throw new Error('useLogixFastAuthToast must be used within LogixFastAuthToastProvider');
  return ctx;
}
