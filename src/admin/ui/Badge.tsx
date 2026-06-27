import type { ReactNode } from 'react';

type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info';

interface BadgeProps {
  variant?: BadgeVariant;
  dot?: boolean;
  children: ReactNode;
}

export function Badge({ variant = 'default', dot, children }: BadgeProps) {
  const cls = variant === 'default' ? 'logixfast-auth-badge' : `logixfast-auth-badge logixfast-auth-badge--${variant}`;
  return (
    <span className={cls}>
      {dot && <span className="logixfast-auth-badge-dot" aria-hidden="true" />}
      {children}
    </span>
  );
}
