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
    <div className={`slr-stat-card slr-stat-card--${tone}`}>
      {icon && (
        <div className="slr-stat-card-icon" aria-hidden="true">
          <Icon icon={icon} size={22} />
        </div>
      )}
      <div className="slr-stat-card-body">
        <span className="slr-stat-card-label">{label}</span>
        <span className="slr-stat-card-value">{value}</span>
        {hint && <span className="slr-stat-card-hint">{hint}</span>}
      </div>
    </div>
  );
}

export function StatsGrid({ children }: { children: ReactNode }) {
  return <div className="slr-stats-grid">{children}</div>;
}
