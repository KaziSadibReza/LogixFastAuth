import { FormEvent, useEffect, useRef, useState } from 'react';
import { ArrowRight, Eye, EyeOff, Lock, Mail, User } from 'lucide-react';
import { checkUsername, register, suggestUsername } from '../api/auth';
import { LogixFastAuthCountryPhone } from './LogixFastAuthCountryPhone';
import { LogixFastAuthField } from './LogixFastAuthField';
import { LogixFastAuthInput } from './LogixFastAuthInput';
import { LogixFastAuthOtpChannelToggle, type OtpChannel } from './LogixFastAuthOtpChannelToggle';
import { useLogixFastAuthToast } from './LogixFastAuthToaster';
import {
  mapApiErrorToRegisterFields,
  validateRegisterFields,
  type FieldErrors,
  type RegisterField,
} from '../utils/formErrors';
import type { LogixFastAuthConfig } from '@shared/types';
import { loadFormSession, saveFormSession } from '../utils/formStorage';
import type { AuthRedirectResult } from '../utils/redirect';

interface RegisterFormProps {
  config: LogixFastAuthConfig;
  onOtpRequired: (identifier: string, channel: string, pendingToken?: string, sessionExpiresAt?: number) => void;
  onSuccess: (result: AuthRedirectResult) => void;
}

function hasBothOtpChannels(config: LogixFastAuthConfig): boolean {
  return config.auth.emailOtp && config.auth.phoneOtp && config.auth.requirePhone;
}

export function RegisterForm({ config, onOtpRequired, onSuccess }: RegisterFormProps) {
  const toast = useLogixFastAuthToast();
  const saved = loadFormSession().register;
  const bothChannels = hasBothOtpChannels(config);
  const showUsernameField = config.auth.showUsernameField;

  const [fullName, setFullName] = useState(saved.fullName);
  const [email, setEmail] = useState(saved.email);
  const [phone, setPhone] = useState(saved.phone);
  const [username, setUsername] = useState('');
  const [usernameTouched, setUsernameTouched] = useState(false);
  const usernameTouchedRef = useRef(false);
  const [usernameStatus, setUsernameStatus] = useState<'idle' | 'checking' | 'available' | 'taken' | 'invalid'>('idle');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [loading, setLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors<RegisterField>>({});
  const [otpChannel, setOtpChannel] = useState<OtpChannel>('email');
  const usernameCheckTimer = useRef<number | null>(null);
  const emailSuggestTimer = useRef<number | null>(null);

  const placeholders = config.i18n.placeholders;

  const persistRegister = (patch: Partial<{ fullName: string; email: string; phone: string }>) => {
    const current = loadFormSession().register;
    saveFormSession({ register: { ...current, ...patch } });
  };

  const clearField = (field: RegisterField) => {
    setFieldErrors((prev) => {
      if (!prev[field]) return prev;
      const next = { ...prev };
      delete next[field];
      return next;
    });
  };

  const showError = (message: string, fields: FieldErrors<RegisterField> = {}) => {
    setFieldErrors(fields);
    toast.error(message);
  };

  useEffect(() => {
    return () => {
      if (usernameCheckTimer.current) {
        window.clearTimeout(usernameCheckTimer.current);
      }
      if (emailSuggestTimer.current) {
        window.clearTimeout(emailSuggestTimer.current);
      }
    };
  }, []);

  const scheduleUsernameCheck = (value: string) => {
    if (usernameCheckTimer.current) {
      window.clearTimeout(usernameCheckTimer.current);
    }

    if (!showUsernameField || value.trim().length < 3) {
      setUsernameStatus('idle');
      return;
    }

    setUsernameStatus('checking');
    usernameCheckTimer.current = window.setTimeout(async () => {
      try {
        const result = await checkUsername(value.trim());
        if (!result.valid) {
          setUsernameStatus('invalid');
          return;
        }
        setUsernameStatus(result.available ? 'available' : 'taken');
      } catch {
        setUsernameStatus('idle');
      }
    }, 500);
  };

  const scheduleUsernameSuggestion = (emailValue: string) => {
    if (!showUsernameField || usernameTouchedRef.current || !emailValue.trim()) {
      return;
    }

    if (emailSuggestTimer.current) {
      window.clearTimeout(emailSuggestTimer.current);
    }

    emailSuggestTimer.current = window.setTimeout(async () => {
      if (usernameTouchedRef.current) return;
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue.trim())) {
        return;
      }

      setUsernameStatus('checking');
      try {
        const result = await suggestUsername(emailValue.trim());
        if (usernameTouchedRef.current) return;
        setUsername(result.username);
        setUsernameStatus('available');
      } catch {
        if (!usernameTouchedRef.current) {
          setUsernameStatus('idle');
        }
      }
    }, 500);
  };

  const handleEmailBlur = () => {
    scheduleUsernameSuggestion(email);
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (showUsernameField && usernameStatus === 'taken') {
      showError(config.i18n.usernameTaken || 'Username is already taken.', { username: true });
      return;
    }

    if (showUsernameField && usernameStatus === 'invalid') {
      showError(config.i18n.usernameInvalid || 'Please enter a valid username.', { username: true });
      return;
    }

    const validation = validateRegisterFields(
      {
        fullName,
        email,
        phone,
        password,
        confirmPassword,
        requirePhone: config.auth.requirePhone,
        username,
        showUsername: showUsernameField,
      },
      {
        required: config.i18n.errorRequired || 'Please fill in all required fields.',
        email: config.i18n.errorInvalidEmail || 'Please enter a valid email address.',
        phone: config.i18n.errorPhone || 'Please enter a valid phone number.',
        passwordMin: config.i18n.errorPasswordMin || 'Password must be at least 8 characters.',
        mismatch: config.i18n.passwordsMismatch || 'Passwords do not match.',
        username: config.i18n.usernameInvalid || 'Please enter a valid username.',
      }
    );

    if (validation) {
      const fields = { ...validation.fields };
      if (!config.auth.requirePhone) delete fields.phone;
      if (!showUsernameField) delete fields.username;
      showError(validation.message, fields);
      return;
    }

    setFieldErrors({});
    setLoading(true);

    try {
      const result = await register({
        full_name: fullName,
        email,
        phone,
        password,
        username: showUsernameField ? username.trim() : undefined,
        preferred_channel: bothChannels ? otpChannel : undefined,
        logixfast_auth_hp: '',
      });
      if (result.requiresOtp) {
        const identifier = result.otpChannel === 'phone' ? phone : email;
        onOtpRequired(identifier, result.otpChannel || 'email', result.pending_token, result.session_expires_at);
        return;
      }
      onSuccess(result);
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      showError(message, mapApiErrorToRegisterFields(message));
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <LogixFastAuthField label={config.i18n.fullName} htmlFor="logixfast-auth-reg-name" required hasError={fieldErrors.fullName}>
        <LogixFastAuthInput
          id="logixfast-auth-reg-name"
          icon={User}
          type="text"
          value={fullName}
          onChange={(e) => {
            const value = e.target.value;
            setFullName(value);
            persistRegister({ fullName: value });
            clearField('fullName');
          }}
          autoComplete="name"
          placeholder={placeholders?.registerFullName || 'Jane Doe'}
          hasError={fieldErrors.fullName}
        />
      </LogixFastAuthField>

      <LogixFastAuthField label={config.i18n.email} htmlFor="logixfast-auth-reg-email" required hasError={fieldErrors.email}>
        <LogixFastAuthInput
          id="logixfast-auth-reg-email"
          icon={Mail}
          type="email"
          value={email}
          onChange={(e) => {
            const value = e.target.value;
            setEmail(value);
            persistRegister({ email: value });
            clearField('email');
            scheduleUsernameSuggestion(value);
          }}
          onBlur={handleEmailBlur}
          autoComplete="email"
          placeholder={placeholders?.registerEmail || 'you@example.com'}
          hasError={fieldErrors.email}
        />
      </LogixFastAuthField>

      {showUsernameField && (
        <LogixFastAuthField
          label={config.i18n.username || 'Username'}
          htmlFor="logixfast-auth-reg-username"
          required
          hasError={fieldErrors.username || usernameStatus === 'taken' || usernameStatus === 'invalid'}
        >
          <LogixFastAuthInput
            id="logixfast-auth-reg-username"
            icon={User}
            type="text"
            value={username}
            onChange={(e) => {
              const value = e.target.value;
              setUsername(value);
              usernameTouchedRef.current = true;
              setUsernameTouched(true);
              clearField('username');
              scheduleUsernameCheck(value);
            }}
            onBlur={() => scheduleUsernameCheck(username)}
            autoComplete="username"
            placeholder={placeholders?.registerUsername || 'Username'}
            hasError={fieldErrors.username || usernameStatus === 'taken' || usernameStatus === 'invalid'}
          />
          {usernameStatus === 'idle' && username.trim() && !usernameTouched && (
            <p className="logixfast-auth-field-hint">
              {config.i18n.usernameSuggested || 'Suggested from your email. You can change it if you like.'}
            </p>
          )}
          {usernameStatus === 'checking' && (
            <p className="logixfast-auth-field-hint">{config.i18n.loading || 'Loading...'}</p>
          )}
          {usernameStatus === 'available' && (
            <p className="logixfast-auth-field-hint logixfast-auth-field-hint--success">
              {config.i18n.usernameAvailable || 'Username is available.'}
            </p>
          )}
          {usernameStatus === 'taken' && (
            <p className="logixfast-auth-field-hint logixfast-auth-field-hint--error">
              {config.i18n.usernameTaken || 'Username is already taken.'}
            </p>
          )}
          {usernameStatus === 'invalid' && (
            <p className="logixfast-auth-field-hint logixfast-auth-field-hint--error">
              {config.i18n.usernameInvalid || 'Please enter a valid username.'}
            </p>
          )}
        </LogixFastAuthField>
      )}

      {config.auth.requirePhone && (
        <LogixFastAuthCountryPhone
          label={config.i18n.phone}
          value={phone}
          onChange={(value) => {
            setPhone(value);
            persistRegister({ phone: value });
          }}
          onLocalChange={() => clearField('phone')}
          required
          hasError={fieldErrors.phone}
        />
      )}

      <LogixFastAuthField label={config.i18n.password} htmlFor="logixfast-auth-reg-password" required hasError={fieldErrors.password}>
        <LogixFastAuthInput
          id="logixfast-auth-reg-password"
          icon={Lock}
          type={showPassword ? 'text' : 'password'}
          value={password}
          onChange={(e) => {
            setPassword(e.target.value);
            clearField('password');
          }}
          autoComplete="new-password"
          minLength={8}
          placeholder={placeholders?.registerPassword || 'Min 8 characters'}
          hasError={fieldErrors.password}
          suffix={
            <button
              type="button"
              className="logixfast-auth-input-action"
              onClick={() => setShowPassword((v) => !v)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
            >
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </LogixFastAuthField>

      <LogixFastAuthField
        label={config.i18n.confirmPassword}
        htmlFor="logixfast-auth-reg-confirm"
        required
        hasError={fieldErrors.confirmPassword}
      >
        <LogixFastAuthInput
          id="logixfast-auth-reg-confirm"
          icon={Lock}
          type={showConfirm ? 'text' : 'password'}
          value={confirmPassword}
          onChange={(e) => {
            setConfirmPassword(e.target.value);
            clearField('confirmPassword');
          }}
          autoComplete="new-password"
          placeholder={placeholders?.registerPassword || 'Repeat password'}
          hasError={fieldErrors.confirmPassword}
          suffix={
            <button
              type="button"
              className="logixfast-auth-input-action"
              onClick={() => setShowConfirm((v) => !v)}
              aria-label={showConfirm ? 'Hide password' : 'Show password'}
            >
              {showConfirm ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </LogixFastAuthField>

      {bothChannels && (
        <div className="logixfast-auth-otp-channel-section">
          <p className="logixfast-auth-otp-channel-label">
            {config.i18n.verifyVia || 'Verify your account via'}
          </p>
          <LogixFastAuthOtpChannelToggle
            channel={otpChannel}
            emailLabel={config.i18n.otpChannelEmail || 'Email'}
            phoneLabel={config.i18n.otpChannelPhone || 'Phone'}
            onChange={setOtpChannel}
          />
        </div>
      )}

      <input type="text" name="logixfast_auth_hp" className="logixfast-auth-hp" tabIndex={-1} autoComplete="off" aria-hidden="true" />

      <button type="submit" className="logixfast-auth-btn logixfast-auth-btn--primary" disabled={loading}>
        {loading ? (
          <>
            <span className="logixfast-auth-loading-spinner" aria-hidden="true" />
            {config.i18n.loading}
          </>
        ) : (
          <>
            {config.i18n.submitRegister}
            <ArrowRight size={18} className="logixfast-auth-btn-arrow" aria-hidden="true" />
          </>
        )}
      </button>
    </form>
  );
}
