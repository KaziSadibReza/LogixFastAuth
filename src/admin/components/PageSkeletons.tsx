import type { ReactNode } from 'react';

function SkeletonCardFrame({
  flush = false,
  className = '',
  children,
}: {
  flush?: boolean;
  className?: string;
  children?: ReactNode;
}) {
  return (
    <div className={`logixfast-auth-card logixfast-auth-skeleton-frame-card ${className}`.trim()}>
      <div className={`logixfast-auth-card-header${flush ? '' : ' with-border'}`}>
        <div className="logixfast-auth-skeleton-frame-card__title-row">
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon" />
          <div className="logixfast-auth-skeleton-frame-card__title-text">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--title" style={{ width: 160 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '92%', marginTop: 8 }} />
          </div>
        </div>
      </div>
      <div className={flush ? 'logixfast-auth-card-body logixfast-auth-card-body--flush' : 'logixfast-auth-card-body'}>{children}</div>
    </div>
  );
}

function SkeletonColumns({ children }: { children: ReactNode }) {
  return <div className="logixfast-auth-page-columns logixfast-auth-page-columns--split">{children}</div>;
}

function SkeletonStatsGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="logixfast-auth-stats-grid">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="logixfast-auth-stat-card logixfast-auth-skeleton-frame-stat">
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon" />
          <div className="logixfast-auth-skeleton-frame-stat__body">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '55%', height: 11 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '40%', height: 22, marginTop: 6 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '70%', height: 11, marginTop: 6 }} />
          </div>
        </div>
      ))}
    </div>
  );
}

function SkeletonIconCard() {
  return (
    <div className="logixfast-auth-icon-card logixfast-auth-skeleton-frame-icon-card" aria-hidden="true">
      <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon-lg" />
      <div className="logixfast-auth-skeleton-frame-icon-card__body">
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '58%', height: 14 }} />
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '92%' }} />
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--pill" style={{ width: 76, marginTop: 4 }} />
      </div>
    </div>
  );
}

function SkeletonIconCardGrid({
  count = 4,
  className = '',
}: {
  count?: number;
  className?: string;
}) {
  return (
    <div className={`logixfast-auth-icon-card-grid ${className}`.trim()}>
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonIconCard key={i} />
      ))}
    </div>
  );
}

function SkeletonToggleCard() {
  return (
    <div className="logixfast-auth-integration-toggle-card logixfast-auth-skeleton-frame-toggle-card" aria-hidden="true">
      <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon-lg" />
      <div className="logixfast-auth-skeleton-frame-icon-card__body">
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '50%', height: 14 }} />
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '100%' }} />
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '88%' }} />
      </div>
      <div className="logixfast-auth-integration-toggle-card__action">
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--toggle" />
      </div>
    </div>
  );
}

function SkeletonToggleCardGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="logixfast-auth-integration-toggle-grid">
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonToggleCard key={i} />
      ))}
    </div>
  );
}

function SkeletonSettingsGroup({ rows = 2 }: { rows?: number }) {
  return (
    <div className="logixfast-auth-settings-group">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="logixfast-auth-settings-row logixfast-auth-skeleton-frame-settings-row">
          <div className="logixfast-auth-settings-row-meta">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '42%', height: 14 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '78%' }} />
          </div>
          <div className="logixfast-auth-settings-row-control">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--control" />
          </div>
        </div>
      ))}
    </div>
  );
}

function SkeletonSettingList({ rows = 3 }: { rows?: number }) {
  return (
    <div className="logixfast-auth-setting-list logixfast-auth-setting-list--padded">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="logixfast-auth-setting-row logixfast-auth-skeleton-frame-setting-row">
          <div className="logixfast-auth-setting-row-text">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '46%', height: 14 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '88%' }} />
          </div>
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--toggle" />
        </div>
      ))}
    </div>
  );
}

function SkeletonBanner() {
  return (
    <div className="logixfast-auth-page-banner logixfast-auth-skeleton-frame-banner">
      <div className="logixfast-auth-page-banner-main">
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon" />
        <div className="logixfast-auth-skeleton-frame-banner__text">
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 180, height: 14 }} />
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 260, height: 12, marginTop: 6 }} />
        </div>
      </div>
      <span className="logixfast-auth-skeleton logixfast-auth-skeleton--btn" />
    </div>
  );
}

function SkeletonTransportCards() {
  return (
    <div className="logixfast-auth-mail-transport-panel">
      <div className="logixfast-auth-transport-cards">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="logixfast-auth-transport-card logixfast-auth-skeleton-frame-transport-card">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon-lg" />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '62%', height: 14 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '90%' }} />
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonPresetGrid({ count = 5 }: { count?: number }) {
  return (
    <div className="logixfast-auth-appearance-presets-panel">
      <div className="logixfast-auth-preset-icon-grid">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="logixfast-auth-preset-icon-card logixfast-auth-skeleton-frame-preset-card">
            <div className="logixfast-auth-skeleton-frame-preset-card__swatches">
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--swatch" />
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--swatch" />
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--swatch" />
            </div>
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 56, height: 12 }} />
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonAppearanceFields({ count = 3 }: { count?: number }) {
  return (
    <div className="logixfast-auth-appearance-settings-panel">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="logixfast-auth-skeleton-frame-field">
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 88, height: 12 }} />
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--control" />
        </div>
      ))}
    </div>
  );
}

function SkeletonPreviewFrame() {
  return (
    <div className="logixfast-auth-appearance-preview-wrap logixfast-auth-skeleton-frame-preview">
      <div className="logixfast-auth-appearance-preview-toolbar logixfast-auth-skeleton-frame-preview__toolbar">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="logixfast-auth-skeleton-frame-preview__toolbar-group">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 36, height: 10 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--pill" style={{ width: 120, height: 28 }} />
          </div>
        ))}
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--btn" style={{ width: 100 }} />
      </div>
      <div className="logixfast-auth-appearance-preview__stage">
        <span className="logixfast-auth-skeleton logixfast-auth-skeleton--preview" />
      </div>
    </div>
  );
}

function SkeletonFieldMappings({ count = 3 }: { count?: number }) {
  return (
    <div className="logixfast-auth-integrations-panel">
      <div className="logixfast-auth-field-mapping-list">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="logixfast-auth-field-mapping-row logixfast-auth-skeleton-frame-mapping-row">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 72, height: 14 }} />
            <div className="logixfast-auth-field-mapping-targets">
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--pill" style={{ width: 108, height: 24 }} />
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--pill" style={{ width: 92, height: 24 }} />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonPasskeyPanel() {
  return (
    <div className="logixfast-auth-auth-passkey-columns">
      <div className="logixfast-auth-auth-passkey-main">
        <SkeletonSettingList rows={1} />
        <div className="logixfast-auth-passkey-features logixfast-auth-skeleton-frame-passkey-features">
          {Array.from({ length: 3 }).map((_, i) => (
            <span key={i} className="logixfast-auth-skeleton logixfast-auth-skeleton--pill" style={{ width: 128, height: 30 }} />
          ))}
        </div>
      </div>
    </div>
  );
}

function SkeletonRegFieldsColumns() {
  return (
    <div className="logixfast-auth-reg-fields-columns">
      <div className="logixfast-auth-reg-fields-col">
        <SkeletonSettingsGroup rows={1} />
      </div>
      <div className="logixfast-auth-reg-fields-col logixfast-auth-reg-fields-col--sync">
        <div className="logixfast-auth-reg-sync-panel">
          <div className="logixfast-auth-integration-panel-head">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 140, height: 14 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '95%' }} />
          </div>
          <SkeletonIconCardGrid count={2} />
        </div>
      </div>
    </div>
  );
}

function SkeletonPanel({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`logixfast-auth-integrations-panel ${className}`.trim()}>{children}</div>;
}

function SkeletonBlockList({ count = 2 }: { count?: number }) {
  return (
    <div className="logixfast-auth-security-panel logixfast-auth-security-panel--blocks">
      <div className="logixfast-auth-block-list">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="logixfast-auth-block-item logixfast-auth-skeleton-frame-block-item">
            <div className="logixfast-auth-block-item__main">
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon" />
              <div>
                <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 120, height: 14 }} />
                <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 200, height: 11, marginTop: 6 }} />
              </div>
            </div>
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--btn" style={{ width: 88, height: 32 }} />
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonProviderList({ count = 2 }: { count?: number }) {
  return (
    <div className="logixfast-auth-provider-list">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="logixfast-auth-provider-item logixfast-auth-skeleton-frame-provider-item">
          <div className="logixfast-auth-provider-item-info">
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon" style={{ width: 18, height: 18, borderRadius: 4 }} />
            <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 120, height: 14 }} />
          </div>
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--pill" style={{ width: 64, height: 22 }} />
        </div>
      ))}
    </div>
  );
}

function SkeletonCodeBlock() {
  return <span className="logixfast-auth-skeleton logixfast-auth-skeleton--code" />;
}

function SkeletonMailTestPanel() {
  return (
    <div className="logixfast-auth-mail-test-panel">
      <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '95%' }} />
      <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: '72%' }} />
      <span className="logixfast-auth-skeleton logixfast-auth-skeleton--btn" style={{ width: 148, marginTop: 8 }} />
    </div>
  );
}

function SkeletonFormGrid({ fields = 2 }: { fields?: number }) {
  return (
    <div className="logixfast-auth-form-grid">
      {Array.from({ length: fields }).map((_, i) => (
        <div key={i} className="logixfast-auth-skeleton-frame-field">
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text" style={{ width: 80, height: 12 }} />
          <span className="logixfast-auth-skeleton logixfast-auth-skeleton--control" />
        </div>
      ))}
    </div>
  );
}

export function GeneralPageSkeleton() {
  return (
    <div className="logixfast-auth-page logixfast-auth-page--general">
      <SkeletonStatsGrid />
      <SkeletonCardFrame flush>
        <SkeletonBanner />
        <SkeletonSettingsGroup rows={2} />
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame flush className="logixfast-auth-card--redirects">
          <SkeletonSettingsGroup rows={3} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <SkeletonSettingsGroup rows={1} />
        </SkeletonCardFrame>
      </SkeletonColumns>
    </div>
  );
}

export function AuthPageSkeleton() {
  return (
    <>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonSettingList rows={3} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <SkeletonPasskeyPanel />
        </SkeletonCardFrame>
      </SkeletonColumns>
      <SkeletonCardFrame flush>
        <SkeletonRegFieldsColumns />
      </SkeletonCardFrame>
    </>
  );
}

export function MailPageSkeleton() {
  return (
    <>
      <SkeletonCardFrame flush>
        <SkeletonTransportCards />
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame>
          <SkeletonFormGrid fields={2} />
        </SkeletonCardFrame>
        <SkeletonCardFrame>
          <SkeletonMailTestPanel />
        </SkeletonCardFrame>
      </SkeletonColumns>
      <SkeletonCardFrame flush>
        <SkeletonPanel>
          <SkeletonIconCardGrid count={4} className="logixfast-auth-icon-card-grid--mail" />
        </SkeletonPanel>
      </SkeletonCardFrame>
    </>
  );
}

export function AppearancePageSkeleton() {
  return (
    <>
      <SkeletonCardFrame flush>
        <SkeletonPresetGrid />
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonAppearanceFields count={3} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <SkeletonAppearanceFields count={3} />
        </SkeletonCardFrame>
      </SkeletonColumns>
      <SkeletonCardFrame flush className="logixfast-auth-card--preview">
        <SkeletonPreviewFrame />
      </SkeletonCardFrame>
    </>
  );
}

export function IntegrationsPageSkeleton() {
  return (
    <>
      <SkeletonCardFrame flush>
        <SkeletonPanel>
          <SkeletonToggleCardGrid count={4} />
        </SkeletonPanel>
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonPanel>
            <SkeletonIconCardGrid count={2} />
          </SkeletonPanel>
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <SkeletonFieldMappings count={3} />
        </SkeletonCardFrame>
      </SkeletonColumns>
    </>
  );
}

export function SecurityPageSkeleton() {
  return (
    <>
      <SkeletonCardFrame flush>
        <SkeletonPanel className="logixfast-auth-security-panel">
          <SkeletonIconCardGrid count={4} className="logixfast-auth-icon-card-grid--security" />
        </SkeletonPanel>
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonSettingsGroup rows={2} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <div className="logixfast-auth-security-panel logixfast-auth-security-panel--blocks">
            <div className="logixfast-auth-empty logixfast-auth-skeleton-frame-empty">
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--icon-lg logixfast-auth-skeleton-frame-empty__icon" />
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text logixfast-auth-skeleton-frame-empty__title" />
              <span className="logixfast-auth-skeleton logixfast-auth-skeleton--text logixfast-auth-skeleton-frame-empty__desc" />
            </div>
          </div>
        </SkeletonCardFrame>
      </SkeletonColumns>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonSettingsGroup rows={3} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <SkeletonPanel className="logixfast-auth-security-panel">
            <SkeletonIconCardGrid count={4} />
            <SkeletonSettingsGroup rows={1} />
          </SkeletonPanel>
        </SkeletonCardFrame>
      </SkeletonColumns>
    </>
  );
}

export function SmsPageSkeleton() {
  return (
    <>
      <SkeletonCardFrame>
        <SkeletonProviderList count={2} />
      </SkeletonCardFrame>
      <SkeletonCardFrame>
        <SkeletonCodeBlock />
      </SkeletonCardFrame>
    </>
  );
}

export { SkeletonBlockList };
