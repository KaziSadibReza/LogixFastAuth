import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  KeyRound,
  Lock,
  LogIn,
  Mail,
  Server,
  Send,
  ShieldCheck,
  User,
  UserCircle,
  UserRoundPlus,
  Zap,
} from 'lucide-react';
import { disconnectGoogle, getGoogleOAuthUrl, testSmtp } from '../api/settings';
import { GoogleOAuthSetupGuide } from '../components/GoogleOAuthSetupGuide';
import { useSettings } from '../context/SettingsContext';
import {
  Badge,
  Button,
  Card,
  Field,
  GoogleIcon,
  HelpTooltip,
  Icon,
  Input,
  IntegrationIconCard,
  NoticeBanner,
  Select,
  MailPageSkeleton,
  TransportIconCard,
  useToast,
} from '../ui';

const encryptionOptions = [
  { value: 'tls', label: 'TLS' },
  { value: 'ssl', label: 'SSL' },
  { value: '', label: 'None' },
];

const transports = [
  {
    value: 'wp_mail',
    title: 'WordPress mail',
    description: 'Uses your host default mail handler for all site email.',
    variant: 'wp' as const,
    icon: Mail,
  },
  {
    value: 'smtp',
    title: 'Custom SMTP',
    description: 'Routes all WordPress email through your SMTP server.',
    variant: 'smtp' as const,
    icon: Server,
  },
  {
    value: 'google',
    title: 'Google SMTP',
    description: 'Routes all WordPress email through Gmail OAuth.',
    variant: 'google' as const,
    customIcon: <GoogleIcon size={22} />,
  },
];

export function MailPage() {
  const { settings, updateSection, loading, save, reload, dirty } = useSettings();
  const [testing, setTesting] = useState(false);
  const [connecting, setConnecting] = useState(false);
  const [disconnecting, setDisconnecting] = useState(false);
  const [conflictDismissed, setConflictDismissed] = useState(false);
  const [searchParams, setSearchParams] = useSearchParams();
  const toast = useToast();

  useEffect(() => {
    const status = searchParams.get('google');
    if (!status) return;

    const message = searchParams.get('google_message');

    if (status === 'connected') {
      reload().then(() => toast.success('Google account connected. Gmail SMTP is ready.', 'Connected'));
    } else if (status === 'error') {
      toast.error(message ? decodeURIComponent(message) : 'Google connection failed.', 'Connection failed');
    }

    searchParams.delete('google');
    searchParams.delete('google_message');
    setSearchParams(searchParams, { replace: true });
  }, [searchParams, setSearchParams, reload, toast]);

  if (loading || !settings) {
    return <MailPageSkeleton />;
  }

  const m = settings.mail;
  const auth = settings.auth;
  const smtpConflicts = settings.mail_smtp_conflicts ?? [];
  const showConflictNotice = !conflictDismissed && smtpConflicts.length > 0;
  const conflictNames = smtpConflicts.map((plugin) => plugin.name).join(', ');
  const usingSlrSmtp = m.transport === 'smtp' || m.transport === 'google';

  const transportBadge =
    m.transport === 'wp_mail'
      ? { variant: 'default' as const, label: 'WP default' }
      : m.transport === 'google' && m.google_connected
        ? { variant: 'success' as const, label: 'Google connected' }
        : { variant: 'info' as const, label: 'Custom' };

  const handleTest = async () => {
    if (!m.from_email) {
      toast.warning('Set a From email first.');
      return;
    }
    setTesting(true);
    try {
      await testSmtp(m.from_email);
      toast.success(`Test email sent to ${m.from_email}.`, 'Delivered');
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Failed to send.', 'SMTP test failed');
    } finally {
      setTesting(false);
    }
  };

  const handleGoogleConnect = async () => {
    if (!m.google_client_id?.trim()) {
      toast.warning('Enter your Google Client ID and save changes first.');
      return;
    }

    const hasSecret =
      m.google_configured ||
      (m.google_client_secret?.trim() && m.google_client_secret !== '********');
    if (!hasSecret) {
      toast.warning('Enter your Google Client Secret and save changes first.');
      return;
    }

    setConnecting(true);
    try {
      if (dirty) {
        await save();
      }
      const { url } = await getGoogleOAuthUrl();
      window.location.href = url;
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Could not start Google sign-in.', 'Google OAuth');
      setConnecting(false);
    }
  };

  const handleGoogleDisconnect = async () => {
    setDisconnecting(true);
    try {
      await disconnectGoogle();
      await reload();
      toast.success('Google account disconnected.');
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Could not disconnect.', 'Disconnect failed');
    } finally {
      setDisconnecting(false);
    }
  };

  return (
    <>
      {showConflictNotice && (
        <NoticeBanner
          variant={usingSlrSmtp ? 'warning' : 'info'}
          title={usingSlrSmtp ? 'Another SMTP plugin is active' : 'SMTP handled by another plugin'}
          onDismiss={() => setConflictDismissed(true)}
        >
          {usingSlrSmtp ? (
            <>
              <p>
                <strong>{conflictNames}</strong> may also configure <code>wp_mail()</code>. Running two SMTP
                handlers can cause duplicate or failed delivery.
              </p>
              <p>Disable the other plugin or switch SLR to <strong>WordPress mail</strong> to avoid conflicts.</p>
            </>
          ) : (
            <p>
              <strong>{conflictNames}</strong> is active, so site email is likely routed through that plugin. SLR is
              currently set to <strong>WordPress mail</strong>.
            </p>
          )}
        </NoticeBanner>
      )}

      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={Zap} size={18} />
            </span>
            Delivery method
          </span>
        }
        description="Choose how this site sends email — SLR OTPs, WooCommerce, WordPress, and other plugins."
        actions={<Badge variant={transportBadge.variant} dot>{transportBadge.label}</Badge>}
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-mail-transport-panel">
          <div className="slr-transport-cards">
            {transports.map((item) => (
              <TransportIconCard
                key={item.value}
                icon={item.icon}
                customIcon={item.customIcon}
                title={item.title}
                description={item.description}
                variant={item.variant}
                selected={m.transport === item.value}
                onClick={() => updateSection('mail', { transport: item.value })}
              />
            ))}
          </div>

          {m.transport === 'smtp' && (
            <div className="slr-mail-transport-config">
              <div className="slr-form-grid slr-form-grid--tight">
                <Field label="SMTP host" required>
                  <Input
                    value={m.smtp_host}
                    onChange={(e) => updateSection('mail', { smtp_host: e.target.value })}
                    placeholder="smtp.example.com"
                    icon={Server}
                  />
                </Field>
                <Field label="Port" required>
                  <Input
                    type="number"
                    value={m.smtp_port}
                    onChange={(e) => updateSection('mail', { smtp_port: Number(e.target.value) })}
                    placeholder="587"
                  />
                </Field>
                <Field label="Encryption">
                  <Select
                    value={m.smtp_encryption}
                    options={encryptionOptions}
                    onChange={(v) => updateSection('mail', { smtp_encryption: String(v) })}
                  />
                </Field>
                <Field label="Username">
                  <Input
                    value={m.smtp_user}
                    onChange={(e) => updateSection('mail', { smtp_user: e.target.value })}
                    icon={User}
                  />
                </Field>
                <Field label="Password" help="Leave unchanged to keep the saved password.">
                  <Input
                    type="password"
                    value={m.smtp_pass}
                    onChange={(e) => updateSection('mail', { smtp_pass: e.target.value })}
                    placeholder="••••••••"
                    icon={Lock}
                  />
                </Field>
              </div>
            </div>
          )}

          {m.transport === 'google' && (
            <div className="slr-mail-transport-config slr-mail-google-panel">
              <div className="slr-mail-google-columns">
                <div className="slr-mail-google-credentials">
                  <div className="slr-integration-panel-head slr-integration-panel-head--with-help">
                    <div>
                      <h4 className="slr-integration-panel-title">Google OAuth credentials</h4>
                      <p className="slr-integration-panel-desc">
                        Create a Google Cloud OAuth app once, then connect with one click.
                      </p>
                    </div>
                    <HelpTooltip label="How to get Google OAuth credentials">
                      <GoogleOAuthSetupGuide />
                    </HelpTooltip>
                  </div>
                  <div className="slr-form-stack">
                    <Field label="Client ID" required>
                      <Input
                        value={m.google_client_id ?? ''}
                        onChange={(e) => updateSection('mail', { google_client_id: e.target.value })}
                        placeholder="xxxx.apps.googleusercontent.com"
                      />
                    </Field>
                    <Field label="Client Secret" help="Stored encrypted. Leave unchanged to keep saved secret.">
                      <Input
                        type="password"
                        value={m.google_client_secret ?? ''}
                        onChange={(e) => updateSection('mail', { google_client_secret: e.target.value })}
                        placeholder="••••••••"
                        icon={Lock}
                      />
                    </Field>
                    {m.google_redirect_uri && (
                      <Field label="Authorized redirect URI" help="Add this exact URL in Google Cloud Console.">
                        <Input value={m.google_redirect_uri} readOnly />
                      </Field>
                    )}
                  </div>
                </div>

                <div className="slr-mail-google-divider" aria-hidden="true" />

                <div className="slr-mail-google-connect">
                  {m.google_connected ? (
                    <div className="slr-google-connected-card">
                      <div className="slr-google-connected-card__icon">
                        <GoogleIcon size={28} />
                      </div>
                      <div className="slr-google-connected-card__body">
                        <strong>Connected to Google</strong>
                        <div className="slr-google-connected-card__meta">
                          <span>{m.google_account_email || m.from_email}</span>
                          <Badge variant="success" dot>
                            Active
                          </Badge>
                        </div>
                      </div>
                      <Button variant="secondary" onClick={handleGoogleDisconnect} loading={disconnecting}>
                        Disconnect
                      </Button>
                    </div>
                  ) : (
                    <div className="slr-google-connect-card">
                      <div className="slr-google-connect-card__icon">
                        <GoogleIcon size={32} />
                      </div>
                      <h4>Connect Gmail in one click</h4>
                      <p>Sign in with Google to authorize SMTP sending. No manual tokens needed.</p>
                      <Button
                        variant="primary"
                        customIcon={<GoogleIcon size={18} />}
                        onClick={handleGoogleConnect}
                        loading={connecting}
                      >
                        Sign in with Google
                      </Button>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}
        </div>
      </Card>

      <div className="slr-page-columns slr-page-columns--split">
        <Card
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon">
                <Icon icon={UserCircle} size={18} />
              </span>
              Sender identity
            </span>
          }
          description="How your messages appear in the inbox."
        >
          <div className="slr-form-grid">
            <Field label="From email" required>
              <Input
                type="email"
                value={m.from_email}
                onChange={(e) => updateSection('mail', { from_email: e.target.value })}
                placeholder="noreply@yoursite.com"
                icon={Mail}
              />
            </Field>
            <Field label="From name">
              <Input
                value={m.from_name}
                onChange={(e) => updateSection('mail', { from_name: e.target.value })}
                placeholder="Your Site"
                icon={UserCircle}
              />
            </Field>
          </div>
        </Card>

        <Card
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--success">
                <Icon icon={Send} size={18} />
              </span>
              Test delivery
            </span>
          }
          description={`Send a test to ${m.from_email || 'your From email'}.`}
        >
          <div className="slr-mail-test-panel">
            <p className="slr-mail-test-hint">
              Verifies your current transport settings before users receive OTP emails.
            </p>
            <Button variant="secondary" icon={Send} onClick={handleTest} loading={testing}>
              Send test email
            </Button>
          </div>
        </Card>
      </div>

      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={ShieldCheck} size={18} />
            </span>
            Email integrations
          </span>
        }
        description="SLR features that send email through this delivery method."
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-mail-integrations-panel">
          <div className="slr-icon-card-grid slr-icon-card-grid--mail">
            <IntegrationIconCard
              icon={Mail}
              iconVariant="passkey"
              title="Email OTP"
              description="Registration and verification codes"
              code="auth/email_otp"
              badge={
                auth.email_otp_enabled
                  ? { variant: 'success', label: 'Enabled' }
                  : { variant: 'default', label: 'Disabled' }
              }
            />
            <IntegrationIconCard
              icon={LogIn}
              iconVariant="default"
              title="Passwordless login"
              description="OTP codes for sign-in without password"
              code="auth/otp_login"
              badge={
                auth.otp_login_enabled
                  ? { variant: 'success', label: 'Enabled' }
                  : { variant: 'default', label: 'Disabled' }
              }
            />
            <IntegrationIconCard
              icon={KeyRound}
              iconVariant="default"
              title="Password reset"
              description="Recovery codes via email or phone"
              code="auth/reset"
              badge={{ variant: 'info', label: 'Always on' }}
            />
            <IntegrationIconCard
              icon={UserRoundPlus}
              iconVariant="tutor"
              title="Registration flow"
              description="Welcome OTP after account creation"
              code="register/verify"
              badge={
                auth.email_otp_enabled || auth.phone_otp_enabled
                  ? { variant: 'success', label: 'Active' }
                  : { variant: 'default', label: 'Inactive' }
              }
            />
          </div>
        </div>
      </Card>
    </>
  );
}
