import type { ReactNode } from 'react';

interface CardProps {
  title?: ReactNode;
  description?: ReactNode;
  actions?: ReactNode;
  footer?: ReactNode;
  children: ReactNode;
  className?: string;
  bodyClassName?: string;
}

export function Card({ title, description, actions, footer, children, className = '', bodyClassName = '' }: CardProps) {
  const hasHeader = title || description || actions;

  return (
    <div className={`slr-card ${className}`}>
      {hasHeader && (
        <div className={`slr-card-header ${actions ? 'with-border' : ''}`} style={actions ? { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 } : undefined}>
          <div>
            {title && <h3>{title}</h3>}
            {description && <p>{description}</p>}
          </div>
          {actions && <div style={{ display: 'flex', gap: 8 }}>{actions}</div>}
        </div>
      )}
      <div className={`slr-card-body ${bodyClassName}`}>{children}</div>
      {footer && <div className="slr-card-footer">{footer}</div>}
    </div>
  );
}
