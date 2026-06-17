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
    <article className="slr-icon-card">
      <div className="slr-icon-card__header">
        <div className={`slr-icon-card__icon slr-icon-card__icon--${iconVariant}`}>
          {customIcon ?? (icon ? <Icon icon={icon} size={22} /> : null)}
        </div>
        {action && (
          <button
            type="button"
            className="slr-icon-card__action"
            onClick={action.onClick}
            disabled={action.disabled || action.loading}
            aria-label={action.label}
            title={action.label}
          >
            <Icon icon={action.icon} size={16} className={action.loading ? 'slr-spin' : ''} />
          </button>
        )}
      </div>
      <div className="slr-icon-card__body">
        <div className="slr-icon-card__title">
          <span>{title}</span>
          {badge && <Badge variant={badge.variant}>{badge.label}</Badge>}
        </div>
        <p className="slr-icon-card__desc">{description}</p>
        {code && <code className="slr-icon-card__code">{code}</code>}
      </div>
    </article>
  );
}
