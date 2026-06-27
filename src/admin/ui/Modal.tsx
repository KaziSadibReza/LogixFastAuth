import { useEffect, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import type { LucideIcon } from 'lucide-react';
import { AlertTriangle, Info } from 'lucide-react';
import { Button, type ButtonVariant } from './Button';
import { Icon } from './Icon';

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title: ReactNode;
  description?: ReactNode;
  icon?: LucideIcon;
  iconTone?: 'default' | 'danger' | 'warning';
  children?: ReactNode;
  footer?: ReactNode;
  maxWidth?: number;
}

export function Modal({ open, onClose, title, description, icon, iconTone = 'default', children, footer, maxWidth = 460 }: ModalProps) {
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  if (!open) return null;

  return createPortal(
    <div
      className="logixfast-auth-modal-backdrop"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
      role="dialog"
      aria-modal="true"
    >
      <div className="logixfast-auth-modal" style={{ maxWidth }}>
        <div className="logixfast-auth-modal-header">
          {icon && (
            <div className={`logixfast-auth-modal-icon ${iconTone === 'default' ? '' : iconTone}`} aria-hidden="true">
              <Icon icon={icon} size={20} />
            </div>
          )}
          <div className="logixfast-auth-modal-title">
            <h3>{title}</h3>
            {description && <p>{description}</p>}
          </div>
        </div>
        {children && <div className="logixfast-auth-modal-body">{children}</div>}
        {footer && <div className="logixfast-auth-modal-footer">{footer}</div>}
      </div>
    </div>,
    document.body
  );
}

interface ConfirmDialogProps {
  open: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: ReactNode;
  description?: ReactNode;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'primary' | 'danger';
  loading?: boolean;
}

export function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  description,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  variant = 'primary',
  loading,
}: ConfirmDialogProps) {
  const buttonVariant: ButtonVariant = variant === 'danger' ? 'danger' : 'primary';
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      description={description}
      icon={variant === 'danger' ? AlertTriangle : Info}
      iconTone={variant === 'danger' ? 'danger' : 'default'}
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={loading}>{cancelLabel}</Button>
          <Button variant={buttonVariant} onClick={onConfirm} loading={loading}>{confirmLabel}</Button>
        </>
      }
    />
  );
}
