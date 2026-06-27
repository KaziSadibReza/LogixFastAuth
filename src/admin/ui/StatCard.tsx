import type { ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Icon } from './Icon';

interface StatCardProps {
  label: string;
  value: ReactNode;
  hint?: string;
  icon?: LucideIcon;
  tone?: 'default' | 'primary' | 'success' | 'info';
}

export function StatCard({ label, value, hint, icon, tone = 'default' }: StatCardProps) {
  return (
    <div className={`logixfast-auth-stat-card logixfast-auth-stat-card--${tone}`}>
      {icon && (
        <div className="logixfast-auth-stat-card-icon" aria-hidden="true">
          <Icon icon={icon} size={22} />
        </div>
      )}
      <div className="logixfast-auth-stat-card-body">
        <span className="logixfast-auth-stat-card-label">{label}</span>
        <span className="logixfast-auth-stat-card-value">{value}</span>
        {hint && <span className="logixfast-auth-stat-card-hint">{hint}</span>}
      </div>
    </div>
  );
}

export function StatsGrid({ children }: { children: ReactNode }) {
  return <div className="logixfast-auth-stats-grid">{children}</div>;
}
