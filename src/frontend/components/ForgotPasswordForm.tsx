import { FormEvent, useState } from 'react';
import { ArrowRight, Mail } from 'lucide-react';
import { forgotPassword } from '../api/auth';
import { SlrCountryPhone } from './SlrCountryPhone';
import { SlrField } from './SlrField';
import { SlrInput } from './SlrInput';
import { useSlrToast } from './SlrToaster';
import {
  getActiveOtpSessionForPurpose,
  loadFormSession,
  saveFormSession,
} from '../utils/formStorage';
import type { SlrConfig } from '@shared/types';

interface ForgotPasswordFormProps {
  config: SlrConfig;
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
  const toast = useSlrToast();
  const saved = loadFormSession();
  const pendingReset = getActiveOtpSessionForPurpose('reset');

  const [email, setEmail] = useState(saved.forgot.email || saved.loginEmail || initialEmail);
  const [phone, setPhone] = useState(saved.forgot.phone);
  const [usePhone, setUsePhone] = useState(saved.forgot.usePhone);
  const [loading, setLoading] = useState(false);

  const persistForgotFields = (nextEmail: string, nextPhone: string, nextUsePhone: boolean) => {
    const current = loadFormSession();
    saveFormSession({
      forgot: { email: nextEmail, phone: nextPhone, usePhone: nextUsePhone },
      loginEmail: nextUsePhone ? current.loginEmail : nextEmail,
    });
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!usePhone && !email.trim()) {
      toast.error(config.i18n.errorInvalidEmail || 'Please enter a valid email address.');
      return;
    }

    if (usePhone && !phone.trim()) {
      toast.error(config.i18n.errorPhone || 'Please enter a valid phone number.');
      return;
    }

    const identifier = usePhone ? phone.trim() : email.trim();
    const channel = usePhone ? 'phone' : 'email';

    if (pendingReset && pendingReset.identifier === identifier && pendingReset.channel === channel) {
      onResumeOtp();
      return;
    }

    setLoading(true);
    try {
      const result = await forgotPassword(usePhone ? { phone: identifier } : { email: identifier });
      persistForgotFields(usePhone ? '' : identifier, usePhone ? identifier : '', usePhone);
      onOtpRequired(identifier, result.channel || channel);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : config.i18n.errorGeneric);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <h2 id="slr-title" className="slr-title">{config.i18n.forgotPassword || 'Forgot password'}</h2>
      <p className="slr-subtitle slr-subtitle--compact">
        {config.i18n.forgotPasswordHint || 'Enter your email or phone and we will send a verification code.'}
      </p>

      {pendingReset && (
        <div className="slr-otp-resume">
          <p>
            {config.i18n.otpInProgress || 'Verification in progress for'}{' '}
            <strong>{pendingReset.identifier}</strong>
          </p>
          <button type="button" className="slr-btn slr-btn--ghost slr-btn--sm" onClick={onResumeOtp}>
            {config.i18n.continueOtp || 'Continue verification'}
          </button>
        </div>
      )}

      {!usePhone ? (
        <SlrField label={config.i18n.email} htmlFor="slr-forgot-email" required>
          <SlrInput
            id="slr-forgot-email"
            icon={Mail}
            type="email"
            value={email}
            onChange={(e) => {
              const value = e.target.value;
              setEmail(value);
              persistForgotFields(value, phone, usePhone);
            }}
            autoComplete="email"
            placeholder="you@example.com"
          />
        </SlrField>
      ) : (
        <SlrCountryPhone
          label={config.i18n.phone}
          value={phone}
          onChange={(value) => {
            setPhone(value);
            persistForgotFields(email, value, usePhone);
          }}
          required
        />
      )}

      {config.auth.phoneOtp && (
        <button
          type="button"
          className="slr-link-btn"
          onClick={() => {
            const nextUsePhone = !usePhone;
            setUsePhone(nextUsePhone);
            persistForgotFields(email, phone, nextUsePhone);
          }}
        >
          {usePhone
            ? (config.i18n.useEmailInstead || 'Use email instead')
            : (config.i18n.usePhoneInstead || 'Use phone instead')}
        </button>
      )}

      <div className="slr-otp-actions flex-column">
        <button type="submit" className="slr-btn slr-btn--primary" disabled={loading}>
          {loading ? (
            <>
              <span className="slr-loading-spinner" aria-hidden="true" />
              {config.i18n.loading}
            </>
          ) : pendingReset ? (
            <>
              {config.i18n.continueOtp || 'Continue verification'}
              <ArrowRight size={18} className="slr-btn-arrow" aria-hidden="true" />
            </>
          ) : (
            <>
              {config.i18n.sendResetCode || 'Send reset code'}
              <ArrowRight size={18} className="slr-btn-arrow" aria-hidden="true" />
            </>
          )}
        </button>

        <button type="button" className="slr-btn slr-btn--ghost" onClick={onBack}>
          ← {config.i18n.backToLogin || 'Back to sign in'}
        </button>
      </div>
    </form>
  );
}
