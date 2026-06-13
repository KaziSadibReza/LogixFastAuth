import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { CheckCircle2, XCircle, AlertTriangle, Info, X, type LucideIcon } from 'lucide-react';
import { Icon } from './Icon';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: number;
  title?: string;
  message: string;
  variant: ToastVariant;
  duration: number;
}

interface ToastContextValue {
  toast: (input: Omit<Toast, 'id' | 'duration' | 'variant'> & { variant?: ToastVariant; duration?: number }) => number;
  success: (message: string, title?: string) => number;
  error: (message: string, title?: string) => number;
  warning: (message: string, title?: string) => number;
  info: (message: string, title?: string) => number;
  dismiss: (id: number) => void;
}

const ToastContext = createContext<ToastContextValue | null>(null);

const variantIcon: Record<ToastVariant, LucideIcon> = {
  success: CheckCircle2,
  error: XCircle,
  warning: AlertTriangle,
  info: Info,
};

let nextId = 1;

const MAX_TOASTS = 3;

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);
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
    }, 200);
  }, []);

  const push = useCallback(
    (input: Omit<Toast, 'id' | 'duration' | 'variant'> & { variant?: ToastVariant; duration?: number }) => {
      const id = nextId++;
      const toast: Toast = {
        id,
        title: input.title,
        message: input.message,
        variant: input.variant || 'info',
        duration: input.duration ?? 4500,
      };
      setToasts((prev) => {
        const next = [...prev, toast];
        return next.length > MAX_TOASTS ? next.slice(-MAX_TOASTS) : next;
      });
      if (toast.duration > 0) {
        setTimeout(() => dismiss(id), toast.duration);
      }
      return id;
    },
    [dismiss]
  );

  const value = useMemo<ToastContextValue>(
    () => ({
      toast: push,
      success: (message, title) => push({ message, title, variant: 'success' }),
      error: (message, title) => push({ message, title, variant: 'error', duration: 6000 }),
      warning: (message, title) => push({ message, title, variant: 'warning' }),
      info: (message, title) => push({ message, title, variant: 'info' }),
      dismiss,
    }),
    [push, dismiss]
  );

  return (
    <ToastContext.Provider value={value}>
      {children}
      <Toaster toasts={toasts} exiting={exiting} onDismiss={dismiss} />
    </ToastContext.Provider>
  );
}

function Toaster({
  toasts,
  exiting,
  onDismiss,
}: {
  toasts: Toast[];
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
        <div key={t.id} className={`slr-toast slr-toast--${t.variant} ${exiting.has(t.id) ? 'exiting' : ''}`}>
          <span className="slr-toast-icon" aria-hidden="true">
            <Icon icon={variantIcon[t.variant]} size={16} />
          </span>
          <div className="slr-toast-body">
            {t.title && <div className="slr-toast-title">{t.title}</div>}
            <div className="slr-toast-message">{t.message}</div>
          </div>
          <button
            type="button"
            className="slr-toast-close"
            onClick={() => onDismiss(t.id)}
            aria-label="Dismiss notification"
          >
            <X size={16} strokeWidth={2} />
          </button>
        </div>
      ))}
    </div>,
    document.body
  );
}

export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within ToastProvider');
  return ctx;
}
