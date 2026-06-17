import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from './Badge';
import { Icon } from './Icon';
import { Toggle } from './Toggle';
import type { IntegrationCardAction } from './IntegrationIconCard';

type IconVariant = 'woo' | 'tutor' | 'wp' | 'elementor' | 'default';

interface IntegrationToggleCardProps {
  icon?: LucideIcon;
  customIcon?: ReactNode;
  iconVariant?: IconVariant;
  title: string;
  description: string;
  active: boolean;
  onChange: (checked: boolean) => void;
  disabled?: boolean;
  hint?: string;
  syncAction?: IntegrationCardAction;
}

export function IntegrationToggleCard({
  icon,
  customIcon,
  iconVariant = 'default',
  title,
  description,
  active,
  onChange,
  disabled = false,
  hint,
  syncAction,
}: IntegrationToggleCardProps) {
  return (
    <article className={`slr-integration-toggle-card${disabled ? ' slr-integration-toggle-card--disabled' : ''}`}>
      <div className={`slr-icon-card__icon slr-icon-card__icon--${iconVariant}`}>
        {customIcon ?? (icon ? <Icon icon={icon} size={22} /> : null)}
      </div>
      <div className="slr-integration-toggle-card__body">
        <div className="slr-icon-card__title">
          <span>{title}</span>
          {active ? <Badge variant="success">Active</Badge> : <Badge variant="default">Inactive</Badge>}
        </div>
        <p className="slr-icon-card__desc">{disabled && hint ? hint : description}</p>
      </div>
      <div className="slr-integration-toggle-card__action">
        {syncAction && (
          <button
            type="button"
            className="slr-icon-card__action"
            onClick={syncAction.onClick}
            disabled={syncAction.disabled || syncAction.loading}
            aria-label={syncAction.label}
            title={syncAction.label}
          >
            <Icon icon={syncAction.icon} size={16} className={syncAction.loading ? 'slr-spin' : ''} />
          </button>
        )}
        <Toggle checked={active} onChange={(e) => onChange(e.target.checked)} disabled={disabled} ariaLabel={`Toggle ${title}`} />
      </div>
    </article>
  );
}
