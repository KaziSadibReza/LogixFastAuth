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
import type { SlrLoggedInRedirectType, SlrRedirectType } from '@shared/types';
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
      label: p.is_slr ? `${p.title} (SLR Sign In Page)` : p.title,
      description: p.url,
    })),
  ];

  return (
    <div className="slr-page slr-page--general">
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
            label="SLR logins today"
            value={stats.slr_logins_today.toLocaleString()}
            hint={`${stats.slr_logins_total.toLocaleString()} all time`}
            icon={KeyRound}
            tone="success"
          />
          <StatCard
            label="Registrations today"
            value={stats.slr_registrations_today.toLocaleString()}
            hint={`${stats.slr_registrations_total.toLocaleString()} all time`}
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
        <div className="slr-stats-grid">
          <Skeleton variant="box" height={88} count={4} />
        </div>
      )}

      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
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
        bodyClassName="slr-card-body--flush"
      >
        {pagesLoading || creatingPage ? (
          <div className="slr-card-inset">
            <Skeleton variant="box" height={56} />
          </div>
        ) : g.dedicated_page_id > 0 && activePage ? (
          <div className="slr-page-banner slr-page-banner--active">
            <div className="slr-page-banner-main">
              <span className="slr-page-banner-icon">
                <Icon icon={FileText} size={18} />
              </span>
              <div className="slr-page-banner-text">
                <span className="slr-page-banner-title">
                  {activePage.title}
                  <Badge variant="primary">Sign In + Register</Badge>
                </span>
                <span className="slr-page-banner-url">{activePage.url}</span>
              </div>
            </div>
            <div className="slr-page-banner-actions">
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
                  const base = window.SLR_ADMIN?.homeUrl ?? '/wp-admin/';
                  window.open(`${base}edit.php?post_type=page`, '_self');
                }}
              >
                All pages
              </Button>
            </div>
          </div>
        ) : (
          <div className="slr-page-banner slr-page-banner--empty">
            <div className="slr-page-banner-main">
              <span className="slr-page-banner-icon">
                <Icon icon={MonitorSmartphone} size={18} />
              </span>
              <div className="slr-page-banner-text">
                <span className="slr-page-banner-title">No auth page yet</span>
                <span className="slr-page-banner-url">Create a page with Sign In and Register tabs, like the popup.</span>
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

      <div className="slr-page-columns slr-page-columns--split">
        <Card
          className="slr-card--flush-body slr-card--redirects"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon">
                <Icon icon={ArrowRightLeft} size={18} />
              </span>
              Redirects
            </span>
          }
          description="Choose where users go after sign-in or sign-up — or keep them on the same page."
          bodyClassName="slr-card-body--flush"
        >
          <SettingsGroup>
            <SettingsRow
              title="After login"
              description="Popup example: stay on About after signing in from that page."
            >
              <RedirectControl
                type={(g.login_redirect_type || 'stay') as SlrRedirectType}
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
                type={(g.register_redirect_type || 'stay') as SlrRedirectType}
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
                    login_page_logged_in_redirect_type: type as SlrLoggedInRedirectType,
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
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--success">
                <Icon icon={ShieldCheck} size={18} />
              </span>
              Spam protection
            </span>
          }
          description="Lightweight bot protection without captchas."
          bodyClassName="slr-card-body--flush"
        >
          <SettingsGroup>
            <SettingsRow
              title={
                <span className="slr-settings-row-title-inline">
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
