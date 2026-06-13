import { useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Menu, Save } from 'lucide-react';
import { Sidebar } from './Sidebar';
import { Button } from '../ui/Button';
import { Badge } from '../ui/Badge';
import { useSettings } from '../context/SettingsContext';

interface AdminLayoutProps {
  i18n: Record<string, string>;
}

const routeMeta: Record<string, { key: string; subtitle: string }> = {
  '/': { key: 'general', subtitle: 'Sign In & Register page, redirects and spam protection.' },
  '/auth': { key: 'auth', subtitle: 'Password, OTP and passkey sign-in methods.' },
  '/mail': { key: 'mail', subtitle: 'Email delivery for OTPs and notifications.' },
  '/sms': { key: 'sms', subtitle: 'SMS providers for phone OTP.' },
  '/integrations': { key: 'integrations', subtitle: 'Replace native login in WP, WooCommerce, Tutor and Elementor.' },
  '/appearance': { key: 'appearance', subtitle: 'Brand colors, spacing and overlay style.' },
  '/security': { key: 'security', subtitle: 'Rate limits, OTP lifetimes and WebAuthn.' },
};

export function AdminLayout({ i18n }: AdminLayoutProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const location = useLocation();
  const meta = routeMeta[location.pathname] || routeMeta['/'];
  const { dirty, save, reset, saving } = useSettings();

  return (
    <div className="slr-app">
      <Sidebar
        collapsed={sidebarCollapsed}
        mobileOpen={mobileOpen}
        onClose={() => setMobileOpen(false)}
        onToggleCollapse={() => setSidebarCollapsed((v) => !v)}
        i18n={i18n}
      />
      <main className="slr-main">
        <header className="slr-header">
          <div className="slr-header-left">
            <button
              type="button"
              className="slr-hamburger"
              onClick={() => setMobileOpen(true)}
              aria-label="Open menu"
            >
              <Menu size={20} strokeWidth={2} aria-hidden="true" />
            </button>
            <div className="slr-header-title">
              <h1>{i18n[meta.key] || i18n.title}</h1>
              <p>{meta.subtitle}</p>
            </div>
          </div>
          <div className="slr-header-actions">
            {dirty && <Badge variant="warning" dot>Unsaved</Badge>}
            {dirty && (
              <Button variant="ghost" size="sm" onClick={reset} disabled={saving}>
                Discard
              </Button>
            )}
            <Button variant="primary" size="sm" icon={Save} onClick={save} loading={saving}>
              Save changes
            </Button>
          </div>
        </header>
        <div className="slr-content-wrapper">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
