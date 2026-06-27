import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Info } from 'lucide-react';
import { Icon } from './Icon';

interface EmptyStateProps {
  icon?: LucideIcon;
  title: ReactNode;
  description?: ReactNode;
  action?: ReactNode;
}

export function EmptyState({ icon = Info, title, description, action }: EmptyStateProps) {
  return (
    <div className="logixfast-auth-empty">
      <div className="logixfast-auth-empty-icon" aria-hidden="true">
        <Icon icon={icon} size={28} strokeWidth={1.5} />
      </div>
      <h4>{title}</h4>
      {description && <p>{description}</p>}
      {action}
    </div>
  );
}
