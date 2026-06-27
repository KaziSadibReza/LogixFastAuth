import type { ReactNode } from 'react';

interface SettingsGroupProps {
  children: ReactNode;
  className?: string;
}

interface SettingsRowProps {
  title: ReactNode;
  description?: ReactNode;
  children: ReactNode;
  className?: string;
}

export function SettingsGroup({ children, className = '' }: SettingsGroupProps) {
  return <div className={`logixfast-auth-settings-group ${className}`.trim()}>{children}</div>;
}

export function SettingsRow({ title, description, children, className = '' }: SettingsRowProps) {
  return (
    <div className={`logixfast-auth-settings-row ${className}`.trim()}>
      <div className="logixfast-auth-settings-row-meta">
        <div className="logixfast-auth-settings-row-title">{title}</div>
        {description && <p className="logixfast-auth-settings-row-desc">{description}</p>}
      </div>
      <div className="logixfast-auth-settings-row-control">{children}</div>
    </div>
  );
}
