import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Shield,
  Mail,
  Smartphone,
  Plug,
  Palette,
  Lock,
  ChevronLeft,
  ChevronRight,
  type LucideIcon,
} from 'lucide-react';
import { Icon } from '../ui/Icon';

function BrandMark() {
  return (
    <svg viewBox="0 0 64 64" width="28" height="28" aria-hidden="true">
      <defs>
        <linearGradient id="slr-bm" x1="4" y1="4" x2="60" y2="60" gradientUnits="userSpaceOnUse">
          <stop offset="0" stopColor="var(--slr-primary)" />
          <stop offset="1" stopColor="var(--slr-primary)" />
        </linearGradient>
      </defs>
      <rect x="4" y="4" width="56" height="56" rx="14" fill="url(#slr-bm)" />
      <circle cx="32" cy="22" r="7.5" fill="none" stroke="#fff" strokeWidth="5.5" />
      <path d="M32 30V49" stroke="#fff" strokeWidth="5.5" strokeLinecap="round" />
      <path d="M32 42H40" stroke="#fff" strokeWidth="5.5" strokeLinecap="round" />
    </svg>
  );
}

interface SidebarProps {
  collapsed: boolean;
  mobileOpen: boolean;
  onClose: () => void;
  onToggleCollapse: () => void;
  i18n: Record<string, string>;
}

const navItems: { to: string; labelKey: string; icon: LucideIcon }[] = [
  { to: '/', labelKey: 'general', icon: LayoutDashboard },
  { to: '/auth', labelKey: 'auth', icon: Shield },
  { to: '/mail', labelKey: 'mail', icon: Mail },
  { to: '/sms', labelKey: 'sms', icon: Smartphone },
  { to: '/integrations', labelKey: 'integrations', icon: Plug },
  { to: '/appearance', labelKey: 'appearance', icon: Palette },
  { to: '/security', labelKey: 'security', icon: Lock },
];

export function Sidebar({ collapsed, mobileOpen, onClose, onToggleCollapse, i18n }: SidebarProps) {
  return (
    <>
      <div
        className="slr-mobile-overlay"
        hidden={!mobileOpen}
        onClick={onClose}
        aria-hidden={!mobileOpen}
      />
      <aside className={`slr-sidebar ${collapsed ? 'collapsed' : ''} ${mobileOpen ? 'mobile-open' : ''}`}>
        <div className="slr-brand">
          <span className="slr-brand-mark">
            <BrandMark />
          </span>
          <span className="slr-brand-title">
            <strong>SLR</strong>
            <span>Login &amp; Registration</span>
          </span>
        </div>

        <nav className="slr-nav" aria-label="Settings navigation">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              title={collapsed ? i18n[item.labelKey] : undefined}
              className={({ isActive }) => (isActive ? 'slr-nav-link--active' : '')}
              onClick={onClose}
            >
              <span className="slr-nav-icon">
                <Icon icon={item.icon} size={18} />
              </span>
              <span className="slr-nav-label">{i18n[item.labelKey]}</span>
            </NavLink>
          ))}
        </nav>

        <div className="slr-sidebar-footer">
          <button
            type="button"
            className="slr-sidebar-toggle"
            onClick={onToggleCollapse}
            title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
            aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            <Icon icon={collapsed ? ChevronRight : ChevronLeft} size={18} />
            <span className="slr-sidebar-footer-text">{collapsed ? 'Expand' : 'Collapse'}</span>
          </button>
        </div>
      </aside>
    </>
  );
}
