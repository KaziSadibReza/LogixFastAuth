import type { ReactNode } from 'react';

type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info';

interface BadgeProps {
  variant?: BadgeVariant;
  dot?: boolean;
  children: ReactNode;
}

export function Badge({ variant = 'default', dot, children }: BadgeProps) {
  const cls = variant === 'default' ? 'slr-badge' : `slr-badge slr-badge--${variant}`;
  return (
    <span className={cls}>
      {dot && <span className="slr-badge-dot" aria-hidden="true" />}
      {children}
    </span>
  );
}
