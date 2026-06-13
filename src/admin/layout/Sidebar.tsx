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
          <span className="slr-brand-mark" aria-hidden="true">
            <Icon icon={Lock} size={16} />
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
              className={({ isActive }) => (isActive ? 'active' : '')}
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
