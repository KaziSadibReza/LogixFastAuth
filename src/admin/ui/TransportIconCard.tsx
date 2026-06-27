import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Icon } from './Icon';

interface TransportIconCardProps {
  icon?: LucideIcon;
  customIcon?: ReactNode;
  title: string;
  description: string;
  selected?: boolean;
  onClick?: () => void;
  variant?: 'default' | 'google' | 'smtp' | 'wp';
}

export function TransportIconCard({
  icon,
  customIcon,
  title,
  description,
  selected = false,
  onClick,
  variant = 'default',
}: TransportIconCardProps) {
  return (
    <button
      type="button"
      className={`logixfast-auth-transport-card logixfast-auth-transport-card--${variant}${selected ? ' logixfast-auth-transport-card--selected' : ''}`}
      onClick={onClick}
      aria-pressed={selected}
    >
      <span className={`logixfast-auth-transport-card__icon logixfast-auth-transport-card__icon--${variant}`}>
        {customIcon ?? (icon ? <Icon icon={icon} size={22} /> : null)}
      </span>
      <span className="logixfast-auth-transport-card__title">{title}</span>
      <span className="logixfast-auth-transport-card__desc">{description}</span>
    </button>
  );
}
