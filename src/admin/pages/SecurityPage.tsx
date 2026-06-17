import { useEffect, useState } from 'react';
import { useAdminData } from '../context/AdminDataContext';
import {
  Ban,
  Clock,
  Fingerprint,
  Gauge,
  Globe,
  KeyRound,
  LogIn,
  RefreshCw,
  ScanFace,
  Shield,
  ShieldBan,
  ShieldCheck,
  Timer,
  Unlock,
  UserRoundPlus,
} from 'lucide-react';
import { useSettings } from '../context/SettingsContext';
import { unblockRateLimit } from '../api/security';
import {
  Button,
  Card,
  EmptyState,
  Icon,
  Input,
  IntegrationIconCard,
  PasskeyIcon,
  SettingsGroup,
  SettingsRow,
  SecurityPageSkeleton,
  SkeletonBlockList,
} from '../ui';

function formatWindow(seconds: number): string {
  if (seconds >= 3600 && seconds % 3600 === 0) return `${seconds / 3600}h`;
  if (seconds >= 60 && seconds % 60 === 0) return `${seconds / 60}m`;
  return `${seconds}s`;
}

export function SecurityPage() {
  const { settings, updateSection, loading } = useSettings();
  const { rateBlocks, blocksLoading, refreshRateBlocks } = useAdminData();
  const [unblocking, setUnblocking] = useState<string | null>(null);

  useEffect(() => {
    if (loading || !settings) return;
    void refreshRateBlocks();
  }, [loading, settings, refreshRateBlocks]);

  const handleUnblock = async (id: string) => {
    setUnblocking(id);
    try {
      await unblockRateLimit(id);
      await refreshRateBlocks();
    } finally {
      setUnblocking(null);
    }
  };

  const blocks = rateBlocks ?? [];

  if (loading || !settings) {
    return <SecurityPageSkeleton />;
  }

  const s = settings.security;
  const rateBadge = `${s.rate_limit_attempts} / ${formatWindow(s.rate_limit_window)}`;

  return (
    <>
      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={ShieldCheck} size={18} />
            </span>
            Protected flows
          </span>
        }
        description="Where SLR applies rate limits, OTP rules and passkey security."
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-security-panel">
          <div className="slr-icon-card-grid slr-icon-card-grid--security">
            <IntegrationIconCard
              icon={LogIn}
              iconVariant="passkey"
              title="Sign in"
              description="Password and OTP login attempts per IP"
              code="auth/login"
              badge={{ variant: 'info', label: rateBadge }}
            />
            <IntegrationIconCard
              icon={UserRoundPlus}
              iconVariant="default"
              title="Registration"
              description="Create account requests per IP"
              code="auth/register"
              badge={{ variant: 'info', label: rateBadge }}
            />
            <IntegrationIconCard
              icon={KeyRound}
              iconVariant="default"
              title="Forgot password"
              description="Password reset requests per IP"
              code="auth/reset"
              badge={{ variant: 'info', label: rateBadge }}
            />
            <IntegrationIconCard
              icon={Timer}
              iconVariant="passkey"
              title="One-time codes"
              description="OTP lifetime, resend cooldown and verify attempts"
              code="auth/otp"
              badge={{ variant: 'success', label: `${s.otp_ttl}s TTL` }}
            />
          </div>
        </div>
      </Card>

      <div className="slr-page-columns slr-page-columns--split">
        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon">
                <Icon icon={Gauge} size={18} />
              </span>
              Rate limits
            </span>
          }
          description="Applied per IP on sign-in, registration and forgot password — not on OTP code entry."
          bodyClassName="slr-card-body--flush"
        >
          <SettingsGroup>
            <SettingsRow
              title={
                <span className="slr-settings-row-title-inline">
                  <Icon icon={Gauge} size={16} className="slr-setting-row-icon" />
                  Max attempts
                </span>
              }
              description="Block further sign-up or sign-in once exceeded in the window."
            >
              <Input
                type="number"
                min={1}
                value={s.rate_limit_attempts}
                onChange={(e) => updateSection('security', { rate_limit_attempts: Number(e.target.value) })}
              />
            </SettingsRow>
            <SettingsRow
              title={
                <span className="slr-settings-row-title-inline">
                  <Icon icon={Clock} size={16} className="slr-setting-row-icon" />
                  Window (seconds)
                </span>
              }
              description="Counter resets after this duration."
            >
              <Input
                type="number"
                min={30}
                value={s.rate_limit_window}
                onChange={(e) => updateSection('security', { rate_limit_window: Number(e.target.value) })}
              />
            </SettingsRow>
          </SettingsGroup>
        </Card>

        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--success">
                <Icon icon={ShieldBan} size={18} />
              </span>
              Blocked IPs
            </span>
          }
          description="Users who hit the rate limit. Unblock to allow sign-in or registration again."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-security-panel slr-security-panel--blocks">
            {blocksLoading || rateBlocks === null ? (
              <SkeletonBlockList count={2} />
            ) : blocks.length === 0 ? (
              <EmptyState
                icon={ShieldCheck}
                title="No active blocks"
                description="IPs that exceed the rate limit will appear here for quick review and unblock."
              />
            ) : (
              <div className="slr-block-list">
                {blocks.map((block) => (
                  <div key={block.id} className="slr-block-item">
                    <div className="slr-block-item__main">
                      <span className="slr-block-item__icon" aria-hidden="true">
                        <Icon icon={Ban} size={16} />
                      </span>
                      <div>
                        <strong>{block.ip || block.key}</strong>
                        <span className="slr-block-meta">
                          {block.action} · until {new Date(block.expires_at * 1000).toLocaleString()}
                        </span>
                      </div>
                    </div>
                    <Button
                      variant="secondary"
                      size="sm"
                      icon={Unlock}
                      loading={unblocking === block.id}
                      onClick={() => handleUnblock(block.id)}
                    >
                      Unblock
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </div>
        </Card>
      </div>

      <div className="slr-page-columns slr-page-columns--split">
        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--primary">
                <Icon icon={Timer} size={18} />
              </span>
              One-time codes
            </span>
          }
          description="OTP lifetime, resend spacing and wrong-guess limits."
          bodyClassName="slr-card-body--flush"
        >
          <SettingsGroup>
            <SettingsRow
              title={
                <span className="slr-settings-row-title-inline">
                  <Icon icon={Timer} size={16} className="slr-setting-row-icon" />
                  Code lifetime (sec)
                </span>
              }
              description="Default 600 = 10 minutes."
            >
              <Input
                type="number"
                min={60}
                value={s.otp_ttl}
                onChange={(e) => updateSection('security', { otp_ttl: Number(e.target.value) })}
              />
            </SettingsRow>
            <SettingsRow
              title={
                <span className="slr-settings-row-title-inline">
                  <Icon icon={RefreshCw} size={16} className="slr-setting-row-icon" />
                  Resend cooldown (sec)
                </span>
              }
              description="Minimum gap before a new code is sent."
            >
              <Input
                type="number"
                min={10}
                value={s.otp_resend_cooldown}
                onChange={(e) => updateSection('security', { otp_resend_cooldown: Number(e.target.value) })}
              />
            </SettingsRow>
            <SettingsRow
              title={
                <span className="slr-settings-row-title-inline">
                  <Icon icon={Shield} size={16} className="slr-setting-row-icon" />
                  Max verify attempts
                </span>
              }
              description="Wrong guesses allowed before the code is invalidated."
            >
              <Input
                type="number"
                min={1}
                value={s.otp_max_attempts}
                onChange={(e) => updateSection('security', { otp_max_attempts: Number(e.target.value) })}
              />
            </SettingsRow>
          </SettingsGroup>
        </Card>

        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--passkey">
                <PasskeyIcon size={18} />
              </span>
              WebAuthn / Passkeys
            </span>
          }
          description="Domain binding for passkey registration and authentication."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-security-panel">
            <div className="slr-icon-card-grid">
              <IntegrationIconCard
                icon={ScanFace}
                iconVariant="passkey"
                title="Biometrics"
                description="Face ID, Touch ID and Windows Hello"
                badge={{ variant: 'success', label: 'Supported' }}
              />
              <IntegrationIconCard
                customIcon={<PasskeyIcon size={22} />}
                iconVariant="passkey"
                title="Security keys"
                description="FIDO2 hardware keys and platform passkeys"
                badge={{ variant: 'success', label: 'FIDO2' }}
              />
              <IntegrationIconCard
                icon={Fingerprint}
                iconVariant="default"
                title="Phishing-resistant"
                description="Credentials bound to your site origin"
                badge={{ variant: 'info', label: 'WebAuthn' }}
              />
              <IntegrationIconCard
                icon={Globe}
                iconVariant="default"
                title="Relying Party ID"
                description="Usually your domain — leave empty to auto-detect"
                code={s.webauthn_rp_id || 'auto-detect'}
              />
            </div>

            <div className="slr-security-rp-field">
              <SettingsGroup>
                <SettingsRow
                  title={
                    <span className="slr-settings-row-title-inline">
                      <Icon icon={Globe} size={16} className="slr-setting-row-icon" />
                      Relying Party ID
                    </span>
                  }
                  description="Set explicitly only if auto-detect fails on your host."
                >
                  <Input
                    value={s.webauthn_rp_id}
                    onChange={(e) => updateSection('security', { webauthn_rp_id: e.target.value })}
                    placeholder="example.com"
                    icon={Globe}
                  />
                </SettingsRow>
              </SettingsGroup>
            </div>
          </div>
        </Card>
      </div>
    </>
  );
}
