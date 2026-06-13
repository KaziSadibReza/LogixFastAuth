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
  return <div className={`slr-settings-group ${className}`.trim()}>{children}</div>;
}

export function SettingsRow({ title, description, children, className = '' }: SettingsRowProps) {
  return (
    <div className={`slr-settings-row ${className}`.trim()}>
      <div className="slr-settings-row-meta">
        <div className="slr-settings-row-title">{title}</div>
        {description && <p className="slr-settings-row-desc">{description}</p>}
      </div>
      <div className="slr-settings-row-control">{children}</div>
    </div>
  );
}
