import { useEffect, useState } from 'react';
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
import { disconnectGoogle, testSmtp } from '../api/settings';
import { useSettings } from '../context/SettingsContext';
import {
  Badge,
  Button,
  Card,
  Field,
  GoogleIcon,
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
    description: 'Send through Gmail with an email address and app password.',
    variant: 'google' as const,
    customIcon: <GoogleIcon size={22} />,
  },
];

export function MailPage() {
  const { settings, updateSection, loading, save, reload, dirty } = useSettings();
  const [testing, setTesting] = useState(false);
  const [disconnecting, setDisconnecting] = useState(false);
  const [conflictDismissed, setConflictDismissed] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [testEmailReady, setTestEmailReady] = useState(false);
  const toast = useToast();

  useEffect(() => {
    if (!settings || testEmailReady) return;
    const mail = settings.mail;
    const initial =
      mail.transport === 'google' && mail.google_smtp_user?.trim()
        ? mail.google_smtp_user.trim()
        : mail.from_email;
    setTestEmail(initial);
    setTestEmailReady(true);
  }, [settings, testEmailReady]);

  if (loading || !settings) {
    return <MailPageSkeleton />;
  }

  const m = settings.mail;
  const auth = settings.auth;
  const smtpConflicts = settings.mail_smtp_conflicts ?? [];
  const showConflictNotice = !conflictDismissed && smtpConflicts.length > 0;
  const conflictNames = smtpConflicts.map((plugin) => plugin.name).join(', ');
  const usingLogixFastAuthSmtp = m.transport === 'smtp' || m.transport === 'google';

  const gmailReady = Boolean(m.google_smtp_user?.trim() && m.google_smtp_pass);
  const gmailAddress = m.google_smtp_user?.trim() ?? '';
  const usingGmailAccount = m.transport === 'google' && Boolean(gmailAddress);
  const transportBadge =
    m.transport === 'wp_mail'
      ? { variant: 'default' as const, label: 'WP default' }
      : m.transport === 'google' && (gmailReady || m.google_connected)
        ? { variant: 'success' as const, label: gmailReady ? 'Gmail ready' : 'Google connected' }
        : { variant: 'info' as const, label: 'Custom' };

  const handleTest = async () => {
    const recipient = testEmail.trim();
    if (!recipient) {
      toast.warning('Enter the address that should receive the test.');
      return;
    }
    setTesting(true);
    try {
      if (dirty) {
        await save();
      }
      await testSmtp(recipient);
      toast.success(`Test email sent to ${recipient}.`, 'Delivered');
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Failed to send.', 'SMTP test failed');
    } finally {
      setTesting(false);
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
          variant={usingLogixFastAuthSmtp ? 'warning' : 'info'}
          title={usingLogixFastAuthSmtp ? 'Another SMTP plugin is active' : 'SMTP handled by another plugin'}
          onDismiss={() => setConflictDismissed(true)}
        >
          {usingLogixFastAuthSmtp ? (
            <>
              <p>
                <strong>{conflictNames}</strong> may also configure <code>wp_mail()</code>. Running two SMTP
                handlers can cause duplicate or failed delivery.
              </p>
              <p>Disable the other plugin or switch LogixFastAuth to <strong>WordPress mail</strong> to avoid conflicts.</p>
            </>
          ) : (
            <p>
              <strong>{conflictNames}</strong> is active, so site email is likely routed through that plugin. LogixFastAuth is
              currently set to <strong>WordPress mail</strong>.
            </p>
          )}
        </NoticeBanner>
      )}

      <Card
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={Zap} size={18} />
            </span>
            Delivery method
          </span>
        }
        description="Choose how this site sends email — LogixFastAuth OTPs, WooCommerce, WordPress, and other plugins."
        actions={<Badge variant={transportBadge.variant} dot>{transportBadge.label}</Badge>}
        bodyClassName="logixfast-auth-card-body--flush"
      >
        <div className="logixfast-auth-mail-transport-panel">
          <div className="logixfast-auth-transport-cards">
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
            <div className="logixfast-auth-mail-transport-config">
              <div className="logixfast-auth-form-grid logixfast-auth-form-grid--tight">
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
            <div className="logixfast-auth-mail-transport-config">
              <div className="logixfast-auth-form-grid logixfast-auth-form-grid--tight">
                <Field label="Gmail address" required>
                  <Input
                    type="email"
                    value={m.google_smtp_user ?? ''}
                    onChange={(e) =>
                      updateSection('mail', {
                        google_smtp_user: e.target.value,
                        from_email: e.target.value.trim(),
                      })
                    }
                    placeholder="you@gmail.com"
                    icon={Mail}
                  />
                </Field>
                <Field
                  label="App password"
                  help="Not your normal Gmail password. Leave unchanged to keep the saved password."
                >
                  <Input
                    type="password"
                    value={m.google_smtp_pass ?? ''}
                    onChange={(e) => updateSection('mail', { google_smtp_pass: e.target.value })}
                    placeholder="16-character app password"
                    icon={Lock}
                  />
                </Field>
              </div>
              <p className="logixfast-auth-field-help">
                Turn on 2-Step Verification, then create an app password at{' '}
                <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">
                  myaccount.google.com/apppasswords
                </a>
                . No Google Cloud Console project is required. Mail is sent through smtp.gmail.com.
              </p>
              {m.google_connected && !gmailReady && (
                <div className="logixfast-auth-google-connected-card">
                  <div className="logixfast-auth-google-connected-card__body">
                    <strong>Older Google connection is still active</strong>
                    <div className="logixfast-auth-google-connected-card__meta">
                      <span>{m.google_account_email || 'Connected account'}</span>
                    </div>
                  </div>
                  <Button variant="secondary" onClick={handleGoogleDisconnect} loading={disconnecting}>
                    Disconnect
                  </Button>
                </div>
              )}
            </div>
          )}
        </div>
      </Card>

      <div className="logixfast-auth-page-columns logixfast-auth-page-columns--split">
        <Card
          title={
            <span className="logixfast-auth-card-title-row">
              <span className="logixfast-auth-card-title-icon">
                <Icon icon={UserCircle} size={18} />
              </span>
              Sender identity
            </span>
          }
          description="How your messages appear in the inbox."
        >
          <div className="logixfast-auth-form-grid">
            <Field
              label="From email"
              required
              help={usingGmailAccount ? 'Updates only when you edit the Gmail address above.' : undefined}
            >
              <Input
                type="email"
                value={usingGmailAccount ? gmailAddress : m.from_email}
                readOnly={usingGmailAccount}
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
            <span className="logixfast-auth-card-title-row">
              <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--success">
                <Icon icon={Send} size={18} />
              </span>
              Test delivery
            </span>
          }
          description="Choose who receives the test. This does not change the SMTP account."
        >
          <div className="logixfast-auth-mail-test-panel">
            <Field label="Send test to">
              <Input
                type="email"
                value={testEmail}
                onChange={(e) => setTestEmail(e.target.value)}
                placeholder="you@example.com"
                icon={Mail}
              />
            </Field>
            <p className="logixfast-auth-mail-test-hint">
              Verifies your current transport settings before users receive OTP emails.
            </p>
            <Button variant="secondary" icon={Send} onClick={handleTest} loading={testing}>
              Send test email
            </Button>
          </div>
        </Card>
      </div>

      <Card
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={ShieldCheck} size={18} />
            </span>
            Email integrations
          </span>
        }
        description="LogixFastAuth features that send email through this delivery method."
        bodyClassName="logixfast-auth-card-body--flush"
      >
        <div className="logixfast-auth-mail-integrations-panel">
          <div className="logixfast-auth-icon-card-grid logixfast-auth-icon-card-grid--mail">
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
