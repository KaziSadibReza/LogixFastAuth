import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import {
  GraduationCap,
  LogIn,
  Mail,
  Phone,
  ScanFace,
  ShieldCheck,
  ShoppingCart,
  Smartphone,
  UserRound,
  UserRoundPlus,
} from 'lucide-react';
import { useSettings } from '../context/SettingsContext';
import {
  Badge,
  Button,
  Card,
  Icon,
  IntegrationIconCard,
  PasskeyIcon,
  SettingsGroup,
  SettingsRow,
  AuthPageSkeleton,
  Toggle,
} from '../ui';

import type { SlrSettings } from '@shared/types';

type OtpToggle = {
  key: 'email_otp_enabled' | 'phone_otp_enabled' | 'otp_login_enabled';
  label: string;
  icon: typeof Mail;
  badge: 'core' | null;
  desc: string;
  disabledWhen?: (auth: SlrSettings['auth']) => boolean;
  disabledHint?: string;
};

const otpToggles: OtpToggle[] = [
  {
    key: 'email_otp_enabled',
    label: 'Email OTP',
    icon: Mail,
    badge: 'core',
    desc: 'Send a 6-digit code to verify email ownership.',
  },
  {
    key: 'phone_otp_enabled',
    label: 'Phone OTP',
    icon: Smartphone,
    badge: null,
    desc: 'Requires an SMS provider under SMS Providers.',
    disabledWhen: (auth) => !auth.has_sms_provider,
    disabledHint: 'Connect an SMS provider first (SMS Providers page).',
  },
  {
    key: 'otp_login_enabled',
    label: 'Passwordless OTP login',
    icon: LogIn,
    badge: null,
    desc: 'Sign in with email or phone only — no password.',
  },
];

const passkeyFeatures: { label: string; icon?: LucideIcon; customIcon?: ReactNode }[] = [
  { icon: ScanFace, label: 'Face ID & Touch ID' },
  { icon: ShieldCheck, label: 'Phishing-resistant' },
  { customIcon: <PasskeyIcon size={16} />, label: 'FIDO2 security keys' },
];

export function AuthPage() {
  const { settings, updateSection, loading } = useSettings();

  if (loading || !settings) {
    return <AuthPageSkeleton />;
  }

  const a = settings.auth;
  const plugins = settings.integration_plugins;
  const usesSlrPhone = !plugins?.woocommerce && !plugins?.tutor;
  const passkeyUrls = settings.passkey_manage_urls;
  const profilePasskeysUrl =
    passkeyUrls?.profile || window.SLR_ADMIN?.profilePasskeysUrl || 'profile.php#slr-passkey-manager';

  return (
    <>
      <div className="slr-page-columns slr-page-columns--split">
        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--primary">
                <Icon icon={ShieldCheck} size={18} />
              </span>
              OTP verification
            </span>
          }
          description="One-time codes delivered via email or SMS."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-setting-list slr-setting-list--padded">
            {otpToggles.map((item) => {
              const isDisabled = item.disabledWhen?.(a) ?? false;
              return (
                <div key={item.key} className={`slr-setting-row${isDisabled ? ' slr-setting-row--disabled' : ''}`}>
                  <div className="slr-setting-row-text">
                    <div className="slr-setting-row-title">
                      <Icon icon={item.icon} size={16} className="slr-setting-row-icon" />
                      {item.label}
                      {item.badge === 'core' && <Badge variant="primary">core</Badge>}
                    </div>
                    <p className="slr-setting-row-desc">
                      {isDisabled && item.disabledHint ? item.disabledHint : item.desc}
                    </p>
                  </div>
                  <Toggle
                    checked={!isDisabled && a[item.key]}
                    onChange={(e) => updateSection('auth', { [item.key]: e.target.checked })}
                    disabled={isDisabled}
                    ariaLabel={`Toggle ${item.label}`}
                  />
                </div>
              );
            })}
          </div>
        </Card>

        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--passkey">
                <PasskeyIcon size={18} />
              </span>
              Passkeys
            </span>
          }
          description="Phishing-resistant sign-in with Face ID, Touch ID or security keys."
          bodyClassName="slr-card-body--flush"
        >
          <div className={`slr-auth-passkey-columns${a.webauthn_enabled ? ' slr-auth-passkey-columns--expanded' : ''}`}>
            <div className="slr-auth-passkey-main">
              <div className="slr-setting-list slr-setting-list--padded slr-setting-list--compact">
                <div className="slr-setting-row">
                  <div className="slr-setting-row-text">
                    <div className="slr-setting-row-title">
                      <span className="slr-setting-row-icon slr-setting-row-icon--passkey">
                        <PasskeyIcon size={16} />
                      </span>
                      WebAuthn / Passkeys
                      <Badge variant="info">FIDO2</Badge>
                    </div>
                    <p className="slr-setting-row-desc">Users can register a passkey and sign in instantly.</p>
                  </div>
                  <Toggle
                    checked={a.webauthn_enabled}
                    onChange={(e) => updateSection('auth', { webauthn_enabled: e.target.checked })}
                    ariaLabel="Toggle WebAuthn"
                  />
                </div>
              </div>

              <div className="slr-passkey-features">
                {passkeyFeatures.map((feature) => (
                  <div key={feature.label} className="slr-passkey-feature">
                    <span className="slr-passkey-feature__icon">
                      {feature.customIcon ?? (feature.icon ? <Icon icon={feature.icon} size={15} /> : null)}
                    </span>
                    <span className="slr-passkey-feature__label">{feature.label}</span>
                  </div>
                ))}
              </div>
            </div>

            {a.webauthn_enabled && (
              <div className="slr-auth-passkey-integrations">
                <div className="slr-integration-panel-head">
                  <h4 className="slr-integration-panel-title">Where users manage passkeys</h4>
                  <p className="slr-integration-panel-desc">
                    Passkeys are managed on the user&apos;s own account — not in this settings panel.
                    Site administrators can manage their passkeys on the WordPress profile screen.
                  </p>
                </div>

                <div className="slr-auth-passkey-admin-link">
                  <Button
                    variant="secondary"
                    size="sm"
                    icon={UserRound}
                    onClick={() => window.open(profilePasskeysUrl, '_blank', 'noopener,noreferrer')}
                  >
                    Manage your passkeys
                  </Button>
                  <span className="slr-auth-passkey-admin-hint">Opens your WordPress user profile</span>
                </div>

                <div className="slr-icon-card-grid">
                  <IntegrationIconCard
                    icon={UserRound}
                    iconVariant="default"
                    title="WordPress profile"
                    description="Users → Profile → Passkeys"
                    code="profile.php"
                    badge={{ variant: 'success', label: 'Always available' }}
                  />
                  {plugins?.woocommerce && passkeyUrls?.woocommerce && (
                    <IntegrationIconCard
                      icon={ShoppingCart}
                      iconVariant="woo"
                      title="WooCommerce"
                      description="My Account → Passkeys"
                      code="my-account/passkeys"
                      badge={{ variant: 'success', label: 'Integrated' }}
                    />
                  )}
                  {a.has_tutor_lms && passkeyUrls?.tutor && (
                    <IntegrationIconCard
                      icon={GraduationCap}
                      iconVariant="tutor"
                      title="Tutor LMS"
                      description="Dashboard → Settings → Passkeys"
                      code="settings/passkeys"
                      badge={{ variant: 'success', label: 'Integrated' }}
                    />
                  )}
                </div>
              </div>
            )}
          </div>
        </Card>
      </div>

      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={UserRoundPlus} size={18} />
            </span>
            Registration fields
          </span>
        }
        description="Fields on the SLR register form. Phone syncs to profile data in WooCommerce and Tutor — not their registration forms."
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-reg-fields-columns">
          <div className="slr-reg-fields-col">
            <SettingsGroup>
              <SettingsRow
                title={
                  <span className="slr-settings-row-title-inline">
                    <Icon icon={Phone} size={16} className="slr-setting-row-icon" />
                    Require phone number
                  </span>
                }
                description="Collect phone on the SLR registration form. Tutor and WooCommerce keep their own signup flows unchanged."
              >
                <Toggle
                  checked={a.require_phone}
                  onChange={(e) => updateSection('auth', { require_phone: e.target.checked })}
                  ariaLabel="Require phone on registration"
                />
              </SettingsRow>
            </SettingsGroup>
          </div>

          <div className="slr-reg-fields-col slr-reg-fields-col--sync">
            <div className="slr-reg-sync-panel">
              <div className="slr-integration-panel-head">
                <h4 className="slr-integration-panel-title">Profile field sync</h4>
                <p className="slr-integration-panel-desc">
                  {usesSlrPhone
                    ? 'When WooCommerce and Tutor LMS are not active, phone is stored in the SLR profile field below. Admins and users can edit it on the WordPress profile screen.'
                    : 'When a user registers via SLR, their phone is saved to existing profile fields:'}
                </p>
              </div>
              <div className="slr-icon-card-grid">
                {usesSlrPhone && (
                  <IntegrationIconCard
                    icon={Phone}
                    iconVariant="default"
                    title="SLR profile"
                    description="WordPress user profile field"
                    code="slr_phone"
                    badge={{ variant: 'success', label: 'Primary' }}
                  />
                )}
                <IntegrationIconCard
                  icon={ShoppingCart}
                  iconVariant="woo"
                  title="WooCommerce"
                  description="Customer billing phone"
                  code="billing_phone"
                  badge={{ variant: 'default', label: plugins?.woocommerce ? 'Auto-sync' : 'Not installed' }}
                />
                <IntegrationIconCard
                  icon={GraduationCap}
                  iconVariant="tutor"
                  title="Tutor LMS"
                  description="My Profile dashboard field"
                  code="phone_number"
                  badge={{ variant: 'default', label: plugins?.tutor ? 'Auto-sync' : 'Not installed' }}
                />
              </div>
            </div>
          </div>
        </div>
      </Card>
    </>
  );
}
