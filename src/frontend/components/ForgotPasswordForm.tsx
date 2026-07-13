import { FormEvent, useState } from 'react';
import { ArrowRight, Mail } from 'lucide-react';
import { forgotPassword } from '../api/auth';
import { LogixFastAuthCountryPhone } from './LogixFastAuthCountryPhone';
import { LogixFastAuthField } from './LogixFastAuthField';
import { LogixFastAuthInput } from './LogixFastAuthInput';
import { useLogixFastAuthToast } from './LogixFastAuthToaster';
import {
  getActiveOtpSessionForPurpose,
  loadFormSession,
  saveFormSession,
} from '../utils/formStorage';
import type { LogixFastAuthConfig } from '@shared/types';

interface ForgotPasswordFormProps {
  config: LogixFastAuthConfig;
  initialEmail?: string;
  onOtpRequired: (identifier: string, channel: string) => void;
  onResumeOtp: () => void;
  onBack: () => void;
}

export function ForgotPasswordForm({
  config,
  initialEmail = '',
  onOtpRequired,
  onResumeOtp,
  onBack,
}: ForgotPasswordFormProps) {
  const toast = useLogixFastAuthToast();
  const saved = loadFormSession();
  const pendingReset = getActiveOtpSessionForPurpose('reset');

  const [identifier, setIdentifier] = useState(saved.forgot.email || saved.loginEmail || initialEmail);
  const [phone, setPhone] = useState(saved.forgot.phone);
  const [usePhone, setUsePhone] = useState(saved.forgot.usePhone);
  const [loading, setLoading] = useState(false);

  const loginLabel = config.i18n.loginIdentifier || config.i18n.emailOrPhone || 'Email or phone number';
  const loginPlaceholder =
    config.i18n.placeholders?.loginIdentifier || config.i18n.loginIdentifier || 'Email or phone number';

  const persistForgotFields = (nextIdentifier: string, nextPhone: string, nextUsePhone: boolean) => {
    const current = loadFormSession();
    saveFormSession({
      forgot: { email: nextIdentifier, phone: nextPhone, usePhone: nextUsePhone },
      loginEmail: nextUsePhone ? current.loginEmail : nextIdentifier,
    });
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!usePhone && !identifier.trim()) {
      toast.error(config.i18n.errorLoginRequired || 'Please enter your email, phone, or username.');
      return;
    }

    if (usePhone && !phone.trim()) {
      toast.error(config.i18n.errorPhone || 'Please enter a valid phone number.');
      return;
    }

    const activeIdentifier = usePhone ? phone.trim() : identifier.trim();
    const channel = usePhone ? 'phone' : 'email';

    if (pendingReset && pendingReset.identifier === activeIdentifier && pendingReset.channel === channel) {
      onResumeOtp();
      return;
    }

    setLoading(true);
    try {
      const result = await forgotPassword(
        usePhone ? { phone: activeIdentifier } : { email: activeIdentifier }
      );
      persistForgotFields(usePhone ? '' : activeIdentifier, usePhone ? activeIdentifier : '', usePhone);
      onOtpRequired(result.identifier || activeIdentifier, result.channel || channel);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : config.i18n.errorGeneric);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <h2 id="logixfast-auth-title" className="logixfast-auth-title">{config.i18n.forgotPassword || 'Forgot password'}</h2>
      <p className="logixfast-auth-subtitle logixfast-auth-subtitle--compact">
        {config.i18n.forgotPasswordHint || 'Enter your email or phone and we will send a verification code.'}
      </p>

      {pendingReset && (
        <div className="logixfast-auth-otp-resume">
          <p>
            {config.i18n.otpInProgress || 'Verification in progress for'}{' '}
            <strong>{pendingReset.identifier}</strong>
          </p>
          <button type="button" className="logixfast-auth-btn logixfast-auth-btn--ghost logixfast-auth-btn--sm" onClick={onResumeOtp}>
            {config.i18n.continueOtp || 'Continue verification'}
          </button>
        </div>
      )}

      {!usePhone ? (
        <LogixFastAuthField label={loginLabel} htmlFor="logixfast-auth-forgot-identifier" required>
          <LogixFastAuthInput
            id="logixfast-auth-forgot-identifier"
            icon={Mail}
            type="text"
            value={identifier}
            onChange={(e) => {
              const value = e.target.value;
              setIdentifier(value);
              persistForgotFields(value, phone, usePhone);
            }}
            autoComplete="username"
            placeholder={loginPlaceholder}
          />
        </LogixFastAuthField>
      ) : (
        <LogixFastAuthCountryPhone
          label={config.i18n.phone}
          value={phone}
          onChange={(value) => {
            setPhone(value);
            persistForgotFields(identifier, value, usePhone);
          }}
          required
        />
      )}

      {config.auth.phoneOtp && (
        <button
          type="button"
          className="logixfast-auth-link-btn"
          onClick={() => {
            const nextUsePhone = !usePhone;
            setUsePhone(nextUsePhone);
            persistForgotFields(identifier, phone, nextUsePhone);
          }}
        >
          {usePhone
            ? (config.i18n.useEmailInstead || 'Use email instead')
            : (config.i18n.usePhoneInstead || 'Use phone instead')}
        </button>
      )}

      <div className="logixfast-auth-otp-actions flex-column">
        <button type="submit" className="logixfast-auth-btn logixfast-auth-btn--primary" disabled={loading}>
          {loading ? (
            <>
              <span className="logixfast-auth-loading-spinner" aria-hidden="true" />
              {config.i18n.loading}
            </>
          ) : pendingReset ? (
            <>
              {config.i18n.continueOtp || 'Continue verification'}
              <ArrowRight size={18} className="logixfast-auth-btn-arrow" aria-hidden="true" />
            </>
          ) : (
            <>
              {config.i18n.sendResetCode || 'Send reset code'}
              <ArrowRight size={18} className="logixfast-auth-btn-arrow" aria-hidden="true" />
            </>
          )}
        </button>

        <button type="button" className="logixfast-auth-btn logixfast-auth-btn--ghost" onClick={onBack}>
          ← {config.i18n.backToLogin || 'Back to sign in'}
        </button>
      </div>
    </form>
  );
}
