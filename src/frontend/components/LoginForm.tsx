import { FormEvent, useMemo, useState } from 'react';
import { ArrowRight, Eye, EyeOff, KeyRound, Lock, Mail, ShieldCheck } from 'lucide-react';
import { login, webauthnLogin } from '../api/auth';
import { SlrCountryPhone } from './SlrCountryPhone';
import { SlrField } from './SlrField';
import { SlrInput } from './SlrInput';
import { SlrOtpChannelToggle, type OtpChannel } from './SlrOtpChannelToggle';
import { useSlrToast } from './SlrToaster';
import {
  mapApiErrorToLoginFields,
  validateLoginFields,
  type FieldErrors,
  type LoginField,
} from '../utils/formErrors';
import type { SlrConfig } from '@shared/types';
import { loadFormSession, saveFormSession } from '../utils/formStorage';
import type { AuthRedirectResult } from '../utils/redirect';

interface LoginFormProps {
  config: SlrConfig;
  onOtpRequired: (identifier: string, channel: string) => void;
  onForgotPassword: () => void;
  onSuccess: (result: AuthRedirectResult) => void;
}

type LoginMode = 'password' | 'otp';

function canUseOtpLogin(config: SlrConfig): boolean {
  return config.auth.otpLogin;
}

function canUsePhoneOtpLogin(config: SlrConfig): boolean {
  return config.auth.otpLogin && config.auth.phoneOtp;
}

export function LoginForm({ config, onOtpRequired, onForgotPassword, onSuccess }: LoginFormProps) {
  const toast = useSlrToast();
  const otpLoginAvailable = canUseOtpLogin(config);
  const phoneOtpAvailable = canUsePhoneOtpLogin(config);
  const emailOtpAvailable = otpLoginAvailable;

  const [loginMode, setLoginMode] = useState<LoginMode>('password');
  const [otpChannel, setOtpChannel] = useState<OtpChannel>(emailOtpAvailable ? 'email' : 'phone');
  const [email, setEmail] = useState(() => loadFormSession().loginEmail);
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [remember, setRemember] = useState(false);
  const [loading, setLoading] = useState(false);
  const [passkeyLoading, setPasskeyLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors<LoginField>>({});

  const showChannelPicker = useMemo(
    () => loginMode === 'otp' && emailOtpAvailable && phoneOtpAvailable,
    [loginMode, emailOtpAvailable, phoneOtpAvailable]
  );

  const clearField = (field: LoginField) => {
    setFieldErrors((prev) => {
      if (!prev[field]) return prev;
      const next = { ...prev };
      delete next[field];
      return next;
    });
  };

  const showError = (message: string, fields: FieldErrors<LoginField> = {}) => {
    setFieldErrors(fields);
    toast.error(message);
  };

  const handlePasswordSubmit = async (e: FormEvent) => {
    e.preventDefault();

    const validation = validateLoginFields(email, password, {
	  required: config.i18n.errorLoginRequired || 'Email or phone and password are required.',
      email: config.i18n.errorInvalidEmail || 'Please enter a valid email address.',
	  phone: config.i18n.errorPhone || 'Please enter a valid phone number.',
    });

    if (validation) {
      showError(validation.message, validation.fields);
      return;
    }

    setFieldErrors({});
    setLoading(true);

    try {
	  const identifier = email.trim();
	  const result = await login({
		...(identifier.includes('@') ? { email: identifier } : { phone: identifier }),
		password,
		remember,
		slr_hp: '',
	  });
      if (result.requiresOtp) {
        onOtpRequired(email, result.otpChannel || 'email');
        return;
      }
      onSuccess(result);
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      showError(message, mapApiErrorToLoginFields(message));
    } finally {
      setLoading(false);
    }
  };

  const handleOtpLoginSubmit = async (e: FormEvent) => {
    e.preventDefault();

    const activeChannel = showChannelPicker ? otpChannel : emailOtpAvailable ? 'email' : 'phone';

    if (activeChannel === 'email' && !email.trim()) {
      showError(config.i18n.errorInvalidEmail || 'Please enter a valid email address.', { email: true });
      return;
    }

    if (activeChannel === 'phone' && !phone.trim()) {
      showError(config.i18n.errorPhone || 'Please enter a valid phone number.', { email: true });
      return;
    }

    setFieldErrors({});
    setLoading(true);

    try {
      const result = await login({
        email: activeChannel === 'email' ? email : undefined,
        phone: activeChannel === 'phone' ? phone : undefined,
        channel: activeChannel,
        remember,
        slr_hp: '',
      });

      if (result.requiresOtp) {
        const identifier = result.otpChannel === 'phone' ? phone : email;
        if (activeChannel === 'email') {
          saveFormSession({ loginEmail: email });
        }
        onOtpRequired(identifier, result.otpChannel || activeChannel);
        return;
      }

      onSuccess(result);
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      showError(message, mapApiErrorToLoginFields(message));
    } finally {
      setLoading(false);
    }
  };

  const handlePasskey = async () => {
    if (!config.auth.webauthn) return;
    setPasskeyLoading(true);
    setFieldErrors({});
    try {
	  const identifier = email.trim();
	  const result = await webauthnLogin(identifier.includes('@') ? identifier : undefined);
      onSuccess(result);
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      showError(message, mapApiErrorToLoginFields(message));
    } finally {
      setPasskeyLoading(false);
    }
  };

  if (loginMode === 'otp' && otpLoginAvailable) {
    return (
      <form onSubmit={handleOtpLoginSubmit} noValidate>
        <div className="slr-otp-login-header">
          <span className="slr-otp-login-icon" aria-hidden="true">
            <ShieldCheck size={22} strokeWidth={2} />
          </span>
          <div>
            <h3 className="slr-otp-login-title">{config.i18n.otpLoginTitle || 'Sign in with code'}</h3>
            <p className="slr-otp-login-subtitle">
              {config.i18n.otpLoginSubtitle || 'We will send a 6-digit code to verify it is you.'}
            </p>
          </div>
        </div>

        {showChannelPicker && (
          <SlrOtpChannelToggle
            channel={otpChannel}
            emailLabel={config.i18n.otpChannelEmail || 'Email'}
            phoneLabel={config.i18n.otpChannelPhone || 'Phone'}
            onChange={setOtpChannel}
          />
        )}

        {(showChannelPicker ? otpChannel === 'email' : emailOtpAvailable) ? (
          <SlrField label={config.i18n.email} htmlFor="slr-login-otp-email" required hasError={fieldErrors.email}>
            <SlrInput
              id="slr-login-otp-email"
              icon={Mail}
              type="email"
              value={email}
              onChange={(e) => {
                const value = e.target.value;
                setEmail(value);
                saveFormSession({ loginEmail: value });
                clearField('email');
              }}
              autoComplete="email"
              placeholder="you@example.com"
              hasError={fieldErrors.email}
            />
          </SlrField>
        ) : (
          <SlrCountryPhone
            label={config.i18n.phone}
            value={phone}
            onChange={setPhone}
            onLocalChange={() => clearField('email')}
            required
            hasError={fieldErrors.email}
          />
        )}
        <div className="slr-otp-actions flex-column">

        <button type="submit" className="slr-btn slr-btn--primary" disabled={loading}>
          {loading ? (
            <>
              <span className="slr-loading-spinner" aria-hidden="true" />
              {config.i18n.loading}
            </>
          ) : (
            <>
              {config.i18n.sendLoginCode || 'Send verification code'}
              <ArrowRight size={18} className="slr-btn-arrow" aria-hidden="true" />
            </>
          )}
        </button>

        <button
          type="button"
          className="slr-btn slr-btn--ghost"
          onClick={() => {
            setLoginMode('password');
            setFieldErrors({});
          }}
        >
          ← {config.i18n.usePasswordInstead || 'Use password instead'}
        </button>

        </div>
      </form>
    );
  }

  return (
    <form onSubmit={handlePasswordSubmit} noValidate>
	  <SlrField label={config.i18n.emailOrPhone || 'Email or phone number'} htmlFor="slr-login-email" required hasError={fieldErrors.email}>
        <SlrInput
          id="slr-login-email"
          icon={Mail}
		  type="text"
          value={email}
          onChange={(e) => {
            const value = e.target.value;
            setEmail(value);
            saveFormSession({ loginEmail: value });
            clearField('email');
          }}
		  autoComplete="username"
		  placeholder="you@example.com or +8801XXXXXXXXX"
          hasError={fieldErrors.email}
        />
      </SlrField>

      <SlrField label={config.i18n.password} htmlFor="slr-login-password" required hasError={fieldErrors.password}>
        <SlrInput
          id="slr-login-password"
          icon={Lock}
          type={showPassword ? 'text' : 'password'}
          value={password}
          onChange={(e) => {
            setPassword(e.target.value);
            clearField('password');
          }}
          autoComplete="current-password"
          placeholder="Enter your password"
          hasError={fieldErrors.password}
          suffix={
            <button
              type="button"
              className="slr-input-action"
              onClick={() => setShowPassword((v) => !v)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
            >
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </SlrField>

      <div className="slr-login-meta">
        <label className="slr-checkbox-row">
          <input type="checkbox" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
          {config.i18n.remember || 'Remember me'}
        </label>
        <button type="button" className="slr-link-btn" onClick={onForgotPassword}>
          {config.i18n.forgotPassword || 'Forgot password?'}
        </button>
      </div>

      <input type="text" name="slr_hp" className="slr-hp" tabIndex={-1} autoComplete="off" aria-hidden="true" />

      <button type="submit" className="slr-btn slr-btn--primary" disabled={loading}>
        {loading ? (
          <>
            <span className="slr-loading-spinner" aria-hidden="true" />
            {config.i18n.loading}
          </>
        ) : (
          <>
            {config.i18n.submitLogin}
            <ArrowRight size={18} className="slr-btn-arrow" aria-hidden="true" />
          </>
        )}
      </button>

      {otpLoginAvailable && (
        <>
          <div className="slr-divider">{config.i18n.or}</div>
          <button
            type="button"
            className="slr-btn slr-btn--ghost slr-btn--otp-login"
            onClick={() => {
              setLoginMode('otp');
              setFieldErrors({});
              if (!phoneOtpAvailable) setOtpChannel('email');
              else if (!emailOtpAvailable) setOtpChannel('phone');
            }}
            disabled={loading || passkeyLoading}
          >
            <ShieldCheck size={18} aria-hidden="true" />
            {config.i18n.signInWithCode || 'Sign in with code'}
          </button>
        </>
      )}

      {config.auth.webauthn && (
        <>
          <div className="slr-divider">{config.i18n.or}</div>
          <button type="button" className="slr-btn slr-btn--ghost" onClick={handlePasskey} disabled={passkeyLoading || loading}>
            {passkeyLoading ? (
              <>
                <span className="slr-loading-spinner" aria-hidden="true" />
                {config.i18n.loading}
              </>
            ) : (
              <>
                <KeyRound size={18} aria-hidden="true" />
                {config.i18n.usePasskey}
              </>
            )}
          </button>
        </>
      )}
    </form>
  );
}
