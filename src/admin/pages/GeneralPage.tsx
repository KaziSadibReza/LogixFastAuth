import { useCallback, useEffect, useState } from 'react';
import { useAdminData } from '../context/AdminDataContext';
import {
  UsersRound,
  KeyRound,
  UserRoundPlus,
  Blocks,
  MonitorSmartphone,
  ExternalLink,
  Sparkles,
  ArrowRightLeft,
  ShieldCheck,
  RefreshCw,
  FileText,
  LayoutList,
} from 'lucide-react';
import type { LogixFastAuthLoggedInRedirectType, LogixFastAuthRedirectType } from '@shared/types';
import { useSettings } from '../context/SettingsContext';
import { ensureLoginPage } from '../api/settings';
import {
  Card,
  Select,
  Toggle,
  GeneralPageSkeleton,
  Skeleton,
  Badge,
  StatCard,
  StatsGrid,
  Button,
  Icon,
  SettingsGroup,
  SettingsRow,
  RedirectControl,
} from '../ui';

export function GeneralPage() {
  const { settings, updateSection, loading } = useSettings();
  const {
    pages,
    pagesLoading,
    stats,
    statsLoading,
    ensurePages,
    ensureStats,
    refreshPages,
    refreshStats,
  } = useAdminData();
  const [creatingPage, setCreatingPage] = useState(false);

  const setupLoginPage = useCallback(async () => {
    setCreatingPage(true);
    try {
      const result = await ensureLoginPage();
      updateSection('general', { dedicated_page_id: result.id });
      await Promise.all([refreshPages(), refreshStats()]);
    } finally {
      setCreatingPage(false);
    }
  }, [updateSection, refreshPages, refreshStats]);

  useEffect(() => {
    if (loading || !settings) return;
    void ensurePages();
    void ensureStats();
  }, [loading, settings, ensurePages, ensureStats]);

  useEffect(() => {
    if (loading || !settings || settings.general.dedicated_page_id > 0) {
      return;
    }
    setupLoginPage();
  }, [loading, settings, setupLoginPage]);

  if (loading || !settings) {
    return <GeneralPageSkeleton />;
  }

  const g = settings.general;
  const pageList = pages ?? [];
  const activePage = pageList.find((p) => p.id === g.dedicated_page_id);

  const pageOptions = [
    { value: 0, label: '— Select a page —' },
    ...pageList.map((p) => ({
      value: p.id,
      label: p.is_logixfastauth ? `${p.title} (LogixFastAuth Sign In Page)` : p.title,
      description: p.url,
    })),
  ];

  return (
    <div className="logixfast-auth-page logixfast-auth-page--general">
      {stats && !statsLoading ? (
        <StatsGrid>
          <StatCard
            label="Total users"
            value={stats.total_users.toLocaleString()}
            hint={`${stats.new_users_today} new today`}
            icon={UsersRound}
            tone="primary"
          />
          <StatCard
            label="LogixFastAuth logins today"
            value={stats.logixfast_auth_logins_today.toLocaleString()}
            hint={`${stats.logixfast_auth_logins_total.toLocaleString()} all time`}
            icon={KeyRound}
            tone="success"
          />
          <StatCard
            label="Registrations today"
            value={stats.logixfast_auth_registrations_today.toLocaleString()}
            hint={`${stats.logixfast_auth_registrations_total.toLocaleString()} all time`}
            icon={UserRoundPlus}
            tone="info"
          />
          <StatCard
            label="Active integrations"
            value={stats.active_integrations}
            hint={stats.dedicated_page_set ? stats.dedicated_page_title : 'No login page set'}
            icon={Blocks}
          />
        </StatsGrid>
      ) : (
        <div className="logixfast-auth-stats-grid">
          <Skeleton variant="box" height={88} count={4} />
        </div>
      )}

      <Card
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={MonitorSmartphone} size={18} />
            </span>
            Sign In & Register
          </span>
        }
        description="One page with Sign In and Register tabs — same UI as the popup. Auto-created and labeled in Pages."
        actions={
          g.dedicated_page_id > 0 ? (
            <Button variant="ghost" size="sm" icon={RefreshCw} loading={creatingPage} onClick={setupLoginPage}>
              Sync
            </Button>
          ) : null
        }
        bodyClassName="logixfast-auth-card-body--flush"
      >
        {pagesLoading || creatingPage ? (
          <div className="logixfast-auth-card-inset">
            <Skeleton variant="box" height={56} />
          </div>
        ) : g.dedicated_page_id > 0 && activePage ? (
          <div className="logixfast-auth-page-banner logixfast-auth-page-banner--active">
            <div className="logixfast-auth-page-banner-main">
              <span className="logixfast-auth-page-banner-icon">
                <Icon icon={FileText} size={18} />
              </span>
              <div className="logixfast-auth-page-banner-text">
                <span className="logixfast-auth-page-banner-title">
                  {activePage.title}
                  <Badge variant="primary">Sign In + Register</Badge>
                </span>
                <span className="logixfast-auth-page-banner-url">{activePage.url}</span>
              </div>
            </div>
            <div className="logixfast-auth-page-banner-actions">
              <Button
                variant="secondary"
                size="sm"
                icon={ExternalLink}
                onClick={() => window.open(activePage.url, '_blank', 'noopener,noreferrer')}
              >
                View
              </Button>
              <Button
                variant="ghost"
                size="sm"
                icon={LayoutList}
                onClick={() => {
                  const base = window.LOGIXFAST_AUTH_ADMIN?.homeUrl ?? '/wp-admin/';
                  window.open(`${base}edit.php?post_type=page`, '_self');
                }}
              >
                All pages
              </Button>
            </div>
          </div>
        ) : (
          <div className="logixfast-auth-page-banner logixfast-auth-page-banner--empty">
            <div className="logixfast-auth-page-banner-main">
              <span className="logixfast-auth-page-banner-icon">
                <Icon icon={MonitorSmartphone} size={18} />
              </span>
              <div className="logixfast-auth-page-banner-text">
                <span className="logixfast-auth-page-banner-title">No auth page yet</span>
                <span className="logixfast-auth-page-banner-url">Create a page with Sign In and Register tabs, like the popup.</span>
              </div>
            </div>
            <Button variant="primary" size="sm" icon={Sparkles} loading={creatingPage} onClick={setupLoginPage}>
              Create page
            </Button>
          </div>
        )}

        <SettingsGroup>
          <SettingsRow
            title="WordPress page"
            description="Full-page version of the popup — users switch between Sign In and Register tabs."
          >
            {pagesLoading ? (
              <Skeleton variant="box" height={40} />
            ) : (
              <Select
                value={g.dedicated_page_id}
                onChange={(v) => updateSection('general', { dedicated_page_id: Number(v) })}
                options={pageOptions}
                searchable
                placeholder="Choose a page"
              />
            )}
          </SettingsRow>
          <SettingsRow title="Default tab" description="Which tab is active first — Sign In or Register — in the popup and dedicated page.">
            <Select
              value={g.default_mode}
              onChange={(v) => updateSection('general', { default_mode: String(v) })}
              options={[
                { value: 'login', label: 'Login' },
                { value: 'register', label: 'Register' },
              ]}
            />
          </SettingsRow>
        </SettingsGroup>
      </Card>

      <div className="logixfast-auth-page-columns logixfast-auth-page-columns--split">
        <Card
          className="logixfast-auth-card--flush-body logixfast-auth-card--redirects"
          title={
            <span className="logixfast-auth-card-title-row">
              <span className="logixfast-auth-card-title-icon">
                <Icon icon={ArrowRightLeft} size={18} />
              </span>
              Redirects
            </span>
          }
          description="Choose where users go after sign-in or sign-up — or keep them on the same page."
          bodyClassName="logixfast-auth-card-body--flush"
        >
          <SettingsGroup>
            <SettingsRow
              title="After login"
              description="Popup example: stay on About after signing in from that page."
            >
              <RedirectControl
                type={(g.login_redirect_type || 'stay') as LogixFastAuthRedirectType}
                pageId={g.login_redirect_page_id || 0}
                url={g.login_redirect_url}
                pages={pageList}
                pagesLoading={pagesLoading}
                onTypeChange={(type) => updateSection('general', { login_redirect_type: type })}
                onPageChange={(pageId) => updateSection('general', { login_redirect_page_id: pageId })}
                onUrlChange={(url) => updateSection('general', { login_redirect_url: url })}
                urlPlaceholder="https://example.com/dashboard"
              />
            </SettingsRow>
            <SettingsRow title="After registration" description="Where new accounts land after completing registration.">
              <RedirectControl
                type={(g.register_redirect_type || 'stay') as LogixFastAuthRedirectType}
                pageId={g.register_redirect_page_id || 0}
                url={g.register_redirect_url}
                pages={pageList}
                pagesLoading={pagesLoading}
                onTypeChange={(type) => updateSection('general', { register_redirect_type: type })}
                onPageChange={(pageId) => updateSection('general', { register_redirect_page_id: pageId })}
                onUrlChange={(url) => updateSection('general', { register_redirect_url: url })}
                urlPlaceholder="https://example.com/welcome"
              />
            </SettingsRow>
            <SettingsRow
              title="Already logged in"
              description="When a signed-in user opens the login page, send them to the dashboard or home instead."
            >
              <RedirectControl
                allowStay={false}
                type={g.login_page_logged_in_redirect_type || 'default'}
                pageId={g.login_page_logged_in_redirect_page_id || 0}
                url={g.login_page_logged_in_redirect_url || ''}
                pages={pageList}
                pagesLoading={pagesLoading}
                onTypeChange={(type) =>
                  updateSection('general', {
                    login_page_logged_in_redirect_type: type as LogixFastAuthLoggedInRedirectType,
                  })
                }
                onPageChange={(pageId) => updateSection('general', { login_page_logged_in_redirect_page_id: pageId })}
                onUrlChange={(url) => updateSection('general', { login_page_logged_in_redirect_url: url })}
                urlPlaceholder="https://example.com/dashboard"
              />
            </SettingsRow>
          </SettingsGroup>
        </Card>

        <Card
          className="logixfast-auth-card--flush-body"
          title={
            <span className="logixfast-auth-card-title-row">
              <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--success">
                <Icon icon={ShieldCheck} size={18} />
              </span>
              Spam protection
            </span>
          }
          description="Lightweight bot protection without captchas."
          bodyClassName="logixfast-auth-card-body--flush"
        >
          <SettingsGroup>
            <SettingsRow
              title={
                <span className="logixfast-auth-settings-row-title-inline">
                  Honeypot field <Badge variant="success">recommended</Badge>
                </span>
              }
              description="Invisible field that bots fill in. Legitimate users never see it."
            >
              <Toggle
                checked={g.honeypot_enabled}
                onChange={(e) => updateSection('general', { honeypot_enabled: e.target.checked })}
                ariaLabel="Enable honeypot"
              />
            </SettingsRow>
          </SettingsGroup>
        </Card>
      </div>
    </div>
  );
}
