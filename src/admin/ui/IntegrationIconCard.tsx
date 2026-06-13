import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from './Badge';
import { Icon } from './Icon';

type IconVariant = 'woo' | 'tutor' | 'passkey' | 'default';

interface IntegrationIconCardProps {
  icon?: LucideIcon;
  customIcon?: ReactNode;
  iconVariant?: IconVariant;
  title: string;
  description: string;
  code?: string;
  badge?: { variant: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'; label: string };
}

export function IntegrationIconCard({
  icon,
  customIcon,
  iconVariant = 'default',
  title,
  description,
  code,
  badge,
}: IntegrationIconCardProps) {
  return (
    <article className="slr-icon-card">
      <div className={`slr-icon-card__icon slr-icon-card__icon--${iconVariant}`}>
        {customIcon ?? (icon ? <Icon icon={icon} size={22} /> : null)}
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
