import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from './Badge';
import { Icon } from './Icon';

type IconVariant = 'woo' | 'tutor' | 'passkey' | 'default';

export interface IntegrationCardAction {
  label: string;
  icon: LucideIcon;
  onClick: () => void;
  loading?: boolean;
  disabled?: boolean;
}

interface IntegrationIconCardProps {
  icon?: LucideIcon;
  customIcon?: ReactNode;
  iconVariant?: IconVariant;
  title: string;
  description: string;
  code?: string;
  badge?: { variant: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'; label: string };
  action?: IntegrationCardAction;
}

export function IntegrationIconCard({
  icon,
  customIcon,
  iconVariant = 'default',
  title,
  description,
  code,
  badge,
  action,
}: IntegrationIconCardProps) {
  return (
    <article className="logixfast-auth-icon-card">
      <div className="logixfast-auth-icon-card__header">
        <div className={`logixfast-auth-icon-card__icon logixfast-auth-icon-card__icon--${iconVariant}`}>
          {customIcon ?? (icon ? <Icon icon={icon} size={22} /> : null)}
        </div>
        {action && (
          <button
            type="button"
            className="logixfast-auth-icon-card__action"
            onClick={action.onClick}
            disabled={action.disabled || action.loading}
            aria-label={action.label}
            title={action.label}
          >
            <Icon icon={action.icon} size={16} className={action.loading ? 'logixfast-auth-spin' : ''} />
          </button>
        )}
      </div>
      <div className="logixfast-auth-icon-card__body">
        <div className="logixfast-auth-icon-card__title">
          <span>{title}</span>
          {badge && <Badge variant={badge.variant}>{badge.label}</Badge>}
        </div>
        <p className="logixfast-auth-icon-card__desc">{description}</p>
        {code && <code className="logixfast-auth-icon-card__code">{code}</code>}
      </div>
    </article>
  );
}
