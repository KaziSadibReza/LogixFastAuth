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
    <div className={`slr-card slr-skeleton-frame-card ${className}`.trim()}>
      <div className={`slr-card-header${flush ? '' : ' with-border'}`}>
        <div className="slr-skeleton-frame-card__title-row">
          <span className="slr-skeleton slr-skeleton--icon" />
          <div className="slr-skeleton-frame-card__title-text">
            <span className="slr-skeleton slr-skeleton--title" style={{ width: 160 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '92%', marginTop: 8 }} />
          </div>
        </div>
      </div>
      <div className={flush ? 'slr-card-body slr-card-body--flush' : 'slr-card-body'}>{children}</div>
    </div>
  );
}

function SkeletonColumns({ children }: { children: ReactNode }) {
  return <div className="slr-page-columns slr-page-columns--split">{children}</div>;
}

function SkeletonStatsGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="slr-stats-grid">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="slr-stat-card slr-skeleton-frame-stat">
          <span className="slr-skeleton slr-skeleton--icon" />
          <div className="slr-skeleton-frame-stat__body">
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '55%', height: 11 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '40%', height: 22, marginTop: 6 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '70%', height: 11, marginTop: 6 }} />
          </div>
        </div>
      ))}
    </div>
  );
}

function SkeletonIconCard() {
  return (
    <div className="slr-icon-card slr-skeleton-frame-icon-card" aria-hidden="true">
      <span className="slr-skeleton slr-skeleton--icon-lg" />
      <div className="slr-skeleton-frame-icon-card__body">
        <span className="slr-skeleton slr-skeleton--text" style={{ width: '58%', height: 14 }} />
        <span className="slr-skeleton slr-skeleton--text" style={{ width: '92%' }} />
        <span className="slr-skeleton slr-skeleton--pill" style={{ width: 76, marginTop: 4 }} />
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
    <div className={`slr-icon-card-grid ${className}`.trim()}>
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonIconCard key={i} />
      ))}
    </div>
  );
}

function SkeletonToggleCard() {
  return (
    <div className="slr-integration-toggle-card slr-skeleton-frame-toggle-card" aria-hidden="true">
      <span className="slr-skeleton slr-skeleton--icon-lg" />
      <div className="slr-skeleton-frame-icon-card__body">
        <span className="slr-skeleton slr-skeleton--text" style={{ width: '50%', height: 14 }} />
        <span className="slr-skeleton slr-skeleton--text" style={{ width: '100%' }} />
        <span className="slr-skeleton slr-skeleton--text" style={{ width: '88%' }} />
      </div>
      <div className="slr-integration-toggle-card__action">
        <span className="slr-skeleton slr-skeleton--toggle" />
      </div>
    </div>
  );
}

function SkeletonToggleCardGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="slr-integration-toggle-grid">
      {Array.from({ length: count }).map((_, i) => (
        <SkeletonToggleCard key={i} />
      ))}
    </div>
  );
}

function SkeletonSettingsGroup({ rows = 2 }: { rows?: number }) {
  return (
    <div className="slr-settings-group">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="slr-settings-row slr-skeleton-frame-settings-row">
          <div className="slr-settings-row-meta">
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '42%', height: 14 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '78%' }} />
          </div>
          <div className="slr-settings-row-control">
            <span className="slr-skeleton slr-skeleton--control" />
          </div>
        </div>
      ))}
    </div>
  );
}

function SkeletonSettingList({ rows = 3 }: { rows?: number }) {
  return (
    <div className="slr-setting-list slr-setting-list--padded">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="slr-setting-row slr-skeleton-frame-setting-row">
          <div className="slr-setting-row-text">
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '46%', height: 14 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '88%' }} />
          </div>
          <span className="slr-skeleton slr-skeleton--toggle" />
        </div>
      ))}
    </div>
  );
}

function SkeletonBanner() {
  return (
    <div className="slr-page-banner slr-skeleton-frame-banner">
      <div className="slr-page-banner-main">
        <span className="slr-skeleton slr-skeleton--icon" />
        <div className="slr-skeleton-frame-banner__text">
          <span className="slr-skeleton slr-skeleton--text" style={{ width: 180, height: 14 }} />
          <span className="slr-skeleton slr-skeleton--text" style={{ width: 260, height: 12, marginTop: 6 }} />
        </div>
      </div>
      <span className="slr-skeleton slr-skeleton--btn" />
    </div>
  );
}

function SkeletonTransportCards() {
  return (
    <div className="slr-mail-transport-panel">
      <div className="slr-transport-cards">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="slr-transport-card slr-skeleton-frame-transport-card">
            <span className="slr-skeleton slr-skeleton--icon-lg" />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '62%', height: 14 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '90%' }} />
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonPresetGrid({ count = 5 }: { count?: number }) {
  return (
    <div className="slr-appearance-presets-panel">
      <div className="slr-preset-icon-grid">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="slr-preset-icon-card slr-skeleton-frame-preset-card">
            <div className="slr-skeleton-frame-preset-card__swatches">
              <span className="slr-skeleton slr-skeleton--swatch" />
              <span className="slr-skeleton slr-skeleton--swatch" />
              <span className="slr-skeleton slr-skeleton--swatch" />
            </div>
            <span className="slr-skeleton slr-skeleton--text" style={{ width: 56, height: 12 }} />
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonAppearanceFields({ count = 3 }: { count?: number }) {
  return (
    <div className="slr-appearance-settings-panel">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="slr-skeleton-frame-field">
          <span className="slr-skeleton slr-skeleton--text" style={{ width: 88, height: 12 }} />
          <span className="slr-skeleton slr-skeleton--control" />
        </div>
      ))}
    </div>
  );
}

function SkeletonPreviewFrame() {
  return (
    <div className="slr-appearance-preview-wrap slr-skeleton-frame-preview">
      <div className="slr-appearance-preview-toolbar slr-skeleton-frame-preview__toolbar">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="slr-skeleton-frame-preview__toolbar-group">
            <span className="slr-skeleton slr-skeleton--text" style={{ width: 36, height: 10 }} />
            <span className="slr-skeleton slr-skeleton--pill" style={{ width: 120, height: 28 }} />
          </div>
        ))}
        <span className="slr-skeleton slr-skeleton--btn" style={{ width: 100 }} />
      </div>
      <div className="slr-appearance-preview__stage">
        <span className="slr-skeleton slr-skeleton--preview" />
      </div>
    </div>
  );
}

function SkeletonFieldMappings({ count = 3 }: { count?: number }) {
  return (
    <div className="slr-integrations-panel">
      <div className="slr-field-mapping-list">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="slr-field-mapping-row slr-skeleton-frame-mapping-row">
            <span className="slr-skeleton slr-skeleton--text" style={{ width: 72, height: 14 }} />
            <div className="slr-field-mapping-targets">
              <span className="slr-skeleton slr-skeleton--pill" style={{ width: 108, height: 24 }} />
              <span className="slr-skeleton slr-skeleton--pill" style={{ width: 92, height: 24 }} />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonPasskeyPanel() {
  return (
    <div className="slr-auth-passkey-columns">
      <div className="slr-auth-passkey-main">
        <SkeletonSettingList rows={1} />
        <div className="slr-passkey-features slr-skeleton-frame-passkey-features">
          {Array.from({ length: 3 }).map((_, i) => (
            <span key={i} className="slr-skeleton slr-skeleton--pill" style={{ width: 128, height: 30 }} />
          ))}
        </div>
      </div>
    </div>
  );
}

function SkeletonRegFieldsColumns() {
  return (
    <div className="slr-reg-fields-columns">
      <div className="slr-reg-fields-col">
        <SkeletonSettingsGroup rows={1} />
      </div>
      <div className="slr-reg-fields-col slr-reg-fields-col--sync">
        <div className="slr-reg-sync-panel">
          <div className="slr-integration-panel-head">
            <span className="slr-skeleton slr-skeleton--text" style={{ width: 140, height: 14 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: '95%' }} />
          </div>
          <SkeletonIconCardGrid count={2} />
        </div>
      </div>
    </div>
  );
}

function SkeletonPanel({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`slr-integrations-panel ${className}`.trim()}>{children}</div>;
}

function SkeletonBlockList({ count = 2 }: { count?: number }) {
  return (
    <div className="slr-security-panel slr-security-panel--blocks">
      <div className="slr-block-list">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="slr-block-item slr-skeleton-frame-block-item">
            <div className="slr-block-item__main">
              <span className="slr-skeleton slr-skeleton--icon" />
              <div>
                <span className="slr-skeleton slr-skeleton--text" style={{ width: 120, height: 14 }} />
                <span className="slr-skeleton slr-skeleton--text" style={{ width: 200, height: 11, marginTop: 6 }} />
              </div>
            </div>
            <span className="slr-skeleton slr-skeleton--btn" style={{ width: 88, height: 32 }} />
          </div>
        ))}
      </div>
    </div>
  );
}

function SkeletonProviderList({ count = 2 }: { count?: number }) {
  return (
    <div className="slr-provider-list">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="slr-provider-item slr-skeleton-frame-provider-item">
          <div className="slr-provider-item-info">
            <span className="slr-skeleton slr-skeleton--icon" style={{ width: 18, height: 18, borderRadius: 4 }} />
            <span className="slr-skeleton slr-skeleton--text" style={{ width: 120, height: 14 }} />
          </div>
          <span className="slr-skeleton slr-skeleton--pill" style={{ width: 64, height: 22 }} />
        </div>
      ))}
    </div>
  );
}

function SkeletonCodeBlock() {
  return <span className="slr-skeleton slr-skeleton--code" />;
}

function SkeletonMailTestPanel() {
  return (
    <div className="slr-mail-test-panel">
      <span className="slr-skeleton slr-skeleton--text" style={{ width: '95%' }} />
      <span className="slr-skeleton slr-skeleton--text" style={{ width: '72%' }} />
      <span className="slr-skeleton slr-skeleton--btn" style={{ width: 148, marginTop: 8 }} />
    </div>
  );
}

function SkeletonFormGrid({ fields = 2 }: { fields?: number }) {
  return (
    <div className="slr-form-grid">
      {Array.from({ length: fields }).map((_, i) => (
        <div key={i} className="slr-skeleton-frame-field">
          <span className="slr-skeleton slr-skeleton--text" style={{ width: 80, height: 12 }} />
          <span className="slr-skeleton slr-skeleton--control" />
        </div>
      ))}
    </div>
  );
}

export function GeneralPageSkeleton() {
  return (
    <div className="slr-page slr-page--general">
      <SkeletonStatsGrid />
      <SkeletonCardFrame flush>
        <SkeletonBanner />
        <SkeletonSettingsGroup rows={2} />
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame flush className="slr-card--redirects">
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
          <SkeletonIconCardGrid count={4} className="slr-icon-card-grid--mail" />
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
      <SkeletonCardFrame flush className="slr-card--preview">
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
        <SkeletonPanel className="slr-security-panel">
          <SkeletonIconCardGrid count={4} className="slr-icon-card-grid--security" />
        </SkeletonPanel>
      </SkeletonCardFrame>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonSettingsGroup rows={2} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <div className="slr-security-panel slr-security-panel--blocks">
            <div className="slr-empty slr-skeleton-frame-empty">
              <span className="slr-skeleton slr-skeleton--icon-lg slr-skeleton-frame-empty__icon" />
              <span className="slr-skeleton slr-skeleton--text slr-skeleton-frame-empty__title" />
              <span className="slr-skeleton slr-skeleton--text slr-skeleton-frame-empty__desc" />
            </div>
          </div>
        </SkeletonCardFrame>
      </SkeletonColumns>
      <SkeletonColumns>
        <SkeletonCardFrame flush>
          <SkeletonSettingsGroup rows={3} />
        </SkeletonCardFrame>
        <SkeletonCardFrame flush>
          <SkeletonPanel className="slr-security-panel">
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
