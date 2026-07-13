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
  Type,
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
  Field,
  Input,
} from '../ui';

import type { LogixFastAuthSettings } from '@shared/types';
import {
  buildLoginIdentifierPlaceholderDefault,
  countEnabledLoginMethods,
  getPlaceholderDefaults,
  loginPlaceholderFields,
  registerPlaceholderFields,
  type AuthPlaceholderKey,
} from '../utils/authPlaceholders';

type OtpToggle = {
  key: 'email_otp_enabled' | 'phone_otp_enabled' | 'otp_login_enabled';
  label: string;
  icon: typeof Mail;
  badge: 'core' | null;
  desc: string;
  disabledWhen?: (auth: LogixFastAuthSettings['auth']) => boolean;
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

type LoginMethodToggle = {
  key: 'login_allow_email' | 'login_allow_phone' | 'login_allow_username';
  label: string;
  icon: typeof Mail;
  desc: string;
};

const loginMethodToggles: LoginMethodToggle[] = [
  { key: 'login_allow_email', label: 'Allow email login', icon: Mail, desc: 'Users can sign in with their email address.' },
  { key: 'login_allow_phone', label: 'Allow phone login', icon: Phone, desc: 'Users can sign in with a phone number on their profile.' },
  { key: 'login_allow_username', label: 'Allow username login', icon: UserRound, desc: 'Users can sign in with their WordPress username.' },
];

export function AuthPage() {
  const { settings, updateSection, loading } = useSettings();

  if (loading || !settings) {
    return <AuthPageSkeleton />;
  }

  const a = settings.auth;
  const enabledLoginMethods = countEnabledLoginMethods(a);
  const placeholderDefaults = getPlaceholderDefaults(a);

  const updatePlaceholder = (key: AuthPlaceholderKey, value: string) => {
    updateSection('auth', {
      placeholders: {
        ...a.placeholders,
        [key]: value,
      },
    });
  };

  const renderPlaceholderField = (field: { key: AuthPlaceholderKey; label: string }) => {
    const defaultValue =
      field.key === 'login_identifier'
        ? buildLoginIdentifierPlaceholderDefault(a)
        : placeholderDefaults[field.key];

    return (
      <Field key={field.key} label={field.label} help={`Default: ${defaultValue}`}>
        <Input
          value={a.placeholders?.[field.key] ?? ''}
          onChange={(e) => updatePlaceholder(field.key, e.target.value)}
          placeholder={defaultValue}
        />
      </Field>
    );
  };

  const toggleLoginMethod = (key: LoginMethodToggle['key'], checked: boolean) => {
    if (!checked && enabledLoginMethods <= 1 && a[key]) {
      return;
    }
    updateSection('auth', { [key]: checked });
  };
  const plugins = settings.integration_plugins;
  const usesLogixFastAuthPhone = !plugins?.woocommerce && !plugins?.tutor;
  const passkeyUrls = settings.passkey_manage_urls;
  const profilePasskeysUrl =
    passkeyUrls?.profile || window.LOGIXFAST_AUTH_ADMIN?.profilePasskeysUrl || 'profile.php#logixfast-auth-passkey-manager';

  return (
    <>
      <div className="logixfast-auth-page-columns logixfast-auth-page-columns--split">
        <Card
          className="logixfast-auth-card--flush-body"
          title={
            <span className="logixfast-auth-card-title-row">
              <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
                <Icon icon={ShieldCheck} size={18} />
              </span>
              OTP verification
            </span>
          }
          description="One-time codes delivered via email or SMS."
          bodyClassName="logixfast-auth-card-body--flush"
        >
          <div className="logixfast-auth-setting-list logixfast-auth-setting-list--padded">
            {otpToggles.map((item) => {
              const isDisabled = item.disabledWhen?.(a) ?? false;
              return (
                <div key={item.key} className={`logixfast-auth-setting-row${isDisabled ? ' logixfast-auth-setting-row--disabled' : ''}`}>
                  <div className="logixfast-auth-setting-row-text">
                    <div className="logixfast-auth-setting-row-title">
                      <Icon icon={item.icon} size={16} className="logixfast-auth-setting-row-icon" />
                      {item.label}
                      {item.badge === 'core' && <Badge variant="primary">core</Badge>}
                    </div>
                    <p className="logixfast-auth-setting-row-desc">
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
          className="logixfast-auth-card--flush-body"
          title={
            <span className="logixfast-auth-card-title-row">
              <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--passkey">
                <PasskeyIcon size={18} />
              </span>
              Passkeys
            </span>
          }
          description="Phishing-resistant sign-in with Face ID, Touch ID or security keys."
          bodyClassName="logixfast-auth-card-body--flush"
        >
          <div className={`logixfast-auth-auth-passkey-columns${a.webauthn_enabled ? ' logixfast-auth-auth-passkey-columns--expanded' : ''}`}>
            <div className="logixfast-auth-auth-passkey-main">
              <div className="logixfast-auth-setting-list logixfast-auth-setting-list--padded logixfast-auth-setting-list--compact">
                <div className="logixfast-auth-setting-row">
                  <div className="logixfast-auth-setting-row-text">
                    <div className="logixfast-auth-setting-row-title">
                      <span className="logixfast-auth-setting-row-icon logixfast-auth-setting-row-icon--passkey">
                        <PasskeyIcon size={16} />
                      </span>
                      WebAuthn / Passkeys
                      <Badge variant="info">FIDO2</Badge>
                    </div>
                    <p className="logixfast-auth-setting-row-desc">Users can register a passkey and sign in instantly.</p>
                  </div>
                  <Toggle
                    checked={a.webauthn_enabled}
                    onChange={(e) => updateSection('auth', { webauthn_enabled: e.target.checked })}
                    ariaLabel="Toggle WebAuthn"
                  />
                </div>
              </div>

              <div className="logixfast-auth-passkey-features">
                {passkeyFeatures.map((feature) => (
                  <div key={feature.label} className="logixfast-auth-passkey-feature">
                    <span className="logixfast-auth-passkey-feature__icon">
                      {feature.customIcon ?? (feature.icon ? <Icon icon={feature.icon} size={15} /> : null)}
                    </span>
                    <span className="logixfast-auth-passkey-feature__label">{feature.label}</span>
                  </div>
                ))}
              </div>
            </div>

            {a.webauthn_enabled && (
              <div className="logixfast-auth-auth-passkey-integrations">
                <div className="logixfast-auth-integration-panel-head">
                  <h4 className="logixfast-auth-integration-panel-title">Where users manage passkeys</h4>
                  <p className="logixfast-auth-integration-panel-desc">
                    Passkeys are managed on the user&apos;s own account — not in this settings panel.
                    Site administrators can manage their passkeys on the WordPress profile screen.
                  </p>
                </div>

                <div className="logixfast-auth-auth-passkey-admin-link">
                  <Button
                    variant="secondary"
                    size="sm"
                    icon={UserRound}
                    onClick={() => window.open(profilePasskeysUrl, '_blank', 'noopener,noreferrer')}
                  >
                    Manage your passkeys
                  </Button>
                  <span className="logixfast-auth-auth-passkey-admin-hint">Opens your WordPress user profile</span>
                </div>

                <div className="logixfast-auth-icon-card-grid">
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
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={LogIn} size={18} />
            </span>
            Login identifiers
          </span>
        }
        description="Choose which identifiers users can enter on the password login form."
        bodyClassName="logixfast-auth-card-body--flush"
      >
        <div className="logixfast-auth-setting-list logixfast-auth-setting-list--padded">
          {loginMethodToggles.map((item) => {
            const isLastEnabled = enabledLoginMethods <= 1 && a[item.key];
            return (
              <div
                key={item.key}
                className={`logixfast-auth-setting-row${isLastEnabled ? ' logixfast-auth-setting-row--disabled' : ''}`}
              >
                <div className="logixfast-auth-setting-row-text">
                  <div className="logixfast-auth-setting-row-title">
                    <Icon icon={item.icon} size={16} className="logixfast-auth-setting-row-icon" />
                    {item.label}
                  </div>
                  <p className="logixfast-auth-setting-row-desc">
                    {isLastEnabled ? 'At least one login method must stay enabled.' : item.desc}
                  </p>
                </div>
                <Toggle
                  checked={a[item.key]}
                  onChange={(e) => toggleLoginMethod(item.key, e.target.checked)}
                  disabled={isLastEnabled}
                  ariaLabel={`Toggle ${item.label}`}
                />
              </div>
            );
          })}
        </div>
      </Card>

      <Card
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={UserRoundPlus} size={18} />
            </span>
            Registration fields
          </span>
        }
        description="Fields on the LogixFastAuth register form. Phone syncs to profile data in WooCommerce and Tutor — not their registration forms."
        bodyClassName="logixfast-auth-card-body--flush"
      >
        <div className="logixfast-auth-reg-fields-columns">
          <div className="logixfast-auth-reg-fields-col">
            <SettingsGroup>
              <SettingsRow
                title={
                  <span className="logixfast-auth-settings-row-title-inline">
                    <Icon icon={Phone} size={16} className="logixfast-auth-setting-row-icon" />
                    Require phone number
                  </span>
                }
                description="Collect phone on the LogixFastAuth registration form. Tutor and WooCommerce keep their own signup flows unchanged."
              >
                <Toggle
                  checked={a.require_phone}
                  onChange={(e) => updateSection('auth', { require_phone: e.target.checked })}
                  ariaLabel="Require phone on registration"
                />
              </SettingsRow>
              <SettingsRow
                title={
                  <span className="logixfast-auth-settings-row-title-inline">
                    <Icon icon={UserRound} size={16} className="logixfast-auth-setting-row-icon" />
                    Show username field
                  </span>
                }
                description="When enabled, a username is suggested from the email address and auto-filled if available. Users can edit it before registering."
              >
                <Toggle
                  checked={a.show_username_field}
                  onChange={(e) => updateSection('auth', { show_username_field: e.target.checked })}
                  ariaLabel="Show username field on registration"
                />
              </SettingsRow>
            </SettingsGroup>
          </div>

          <div className="logixfast-auth-reg-fields-col logixfast-auth-reg-fields-col--sync">
            <div className="logixfast-auth-reg-sync-panel">
              <div className="logixfast-auth-integration-panel-head">
                <h4 className="logixfast-auth-integration-panel-title">Profile field sync</h4>
                <p className="logixfast-auth-integration-panel-desc">
                  {usesLogixFastAuthPhone
                    ? 'When WooCommerce and Tutor LMS are not active, phone is stored in the LogixFastAuth profile field below. Admins and users can edit it on the WordPress profile screen.'
                    : 'When a user registers via LogixFastAuth, their phone is saved to existing profile fields:'}
                </p>
              </div>
              <div className="logixfast-auth-icon-card-grid">
                {usesLogixFastAuthPhone && (
                  <IntegrationIconCard
                    icon={Phone}
                    iconVariant="default"
                    title="LogixFastAuth profile"
                    description="WordPress user profile field"
                    code="logixfast_auth_phone"
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

      <Card
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={Type} size={18} />
            </span>
            Form placeholders
          </span>
        }
        description="Smart defaults are used automatically. Enable custom placeholders to override copy on login and register forms."
        bodyClassName="logixfast-auth-card-body--flush"
      >
        <div className="logixfast-auth-placeholder-panel">
          <div className="logixfast-auth-setting-row logixfast-auth-placeholder-panel__toggle">
            <div className="logixfast-auth-setting-row-text">
              <div className="logixfast-auth-setting-row-title">Use custom placeholders</div>
              <p className="logixfast-auth-setting-row-desc">
                Override the built-in placeholder text. Leave a field empty to keep its smart default.
              </p>
            </div>
            <Toggle
              checked={a.use_custom_placeholders}
              onChange={(e) => updateSection('auth', { use_custom_placeholders: e.target.checked })}
              ariaLabel="Use custom placeholders"
            />
          </div>

          {a.use_custom_placeholders ? (
            <div className="logixfast-auth-placeholder-custom">
              <div className="logixfast-auth-placeholder-custom__section">
                <h4 className="logixfast-auth-placeholder-custom__heading">Login form</h4>
                <div className="logixfast-auth-placeholder-custom__fields">
                  {loginPlaceholderFields.map(renderPlaceholderField)}
                </div>
              </div>

              <div className="logixfast-auth-placeholder-custom__section">
                <h4 className="logixfast-auth-placeholder-custom__heading">Register form</h4>
                <div className="logixfast-auth-placeholder-custom__fields logixfast-auth-placeholder-custom__fields--grid">
                  {registerPlaceholderFields.map(renderPlaceholderField)}
                </div>
              </div>
            </div>
          ) : (
            <div className="logixfast-auth-placeholder-defaults">
              <p className="logixfast-auth-placeholder-defaults__intro">
                Placeholders adapt to your login identifier settings. Examples with your current configuration:
              </p>
              <dl className="logixfast-auth-placeholder-defaults__list">
                <div>
                  <dt>Login identifier</dt>
                  <dd>{buildLoginIdentifierPlaceholderDefault(a)}</dd>
                </div>
                <div>
                  <dt>Login password</dt>
                  <dd>{placeholderDefaults.login_password}</dd>
                </div>
                <div>
                  <dt>Register username</dt>
                  <dd>{placeholderDefaults.register_username}</dd>
                </div>
                <div>
                  <dt>Register email</dt>
                  <dd>{placeholderDefaults.register_email}</dd>
                </div>
              </dl>
            </div>
          )}
        </div>
      </Card>
    </>
  );
}
