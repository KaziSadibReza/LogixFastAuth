import type { ReactNode } from 'react';
import { AlertTriangle, Info, X } from 'lucide-react';
import { Icon } from './Icon';

type NoticeVariant = 'info' | 'warning';

interface NoticeBannerProps {
  variant?: NoticeVariant;
  title: string;
  children: ReactNode;
  onDismiss?: () => void;
}

const icons = {
  info: Info,
  warning: AlertTriangle,
};

export function NoticeBanner({ variant = 'info', title, children, onDismiss }: NoticeBannerProps) {
  return (
    <div className={`slr-notice-banner slr-notice-banner--${variant}`} role="status">
      <span className="slr-notice-banner__icon" aria-hidden="true">
        <Icon icon={icons[variant]} size={18} />
      </span>
      <div className="slr-notice-banner__body">
        <strong>{title}</strong>
        <div className="slr-notice-banner__content">{children}</div>
      </div>
      {onDismiss && (
        <button type="button" className="slr-notice-banner__close" onClick={onDismiss} aria-label="Dismiss">
          <X size={16} strokeWidth={2} />
        </button>
      )}
    </div>
  );
}
