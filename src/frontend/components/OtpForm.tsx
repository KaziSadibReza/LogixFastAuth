import { FormEvent, useEffect, useState } from 'react';
import { verifyOtp, resendOtp } from '../api/auth';
import { LogixFastAuthOtpInput } from './LogixFastAuthOtpInput';
import { useLogixFastAuthToast } from './LogixFastAuthToaster';
import type { LogixFastAuthConfig } from '@shared/types';
import {
  extendOtpSession,
  getStoredPendingToken,
  isSessionExpiredError,
  loadFormSession,
  saveOtpSession,
} from '../utils/formStorage';
import type { AuthRedirectResult } from '../utils/redirect';

interface OtpFormProps {
  config: LogixFastAuthConfig;
  identifier: string;
  channel: string;
  pendingToken?: string;
  purpose?: string;
  onSuccess: (result: AuthRedirectResult) => void;
  onBack: () => void;
  onSessionExpired?: () => void;
}

export function OtpForm({
  config,
  identifier,
  channel,
  pendingToken,
  purpose = 'verify',
  onSuccess,
  onBack,
  onSessionExpired,
}: OtpFormProps) {
  const toast = useLogixFastAuthToast();
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [cooldown, setCooldown] = useState(0);
  const effectivePendingToken = pendingToken || getStoredPendingToken(identifier, channel);

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setTimeout(() => setCooldown((c) => c - 1), 1000);
    return () => clearTimeout(t);
  }, [cooldown]);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (code.length < 6) {
      setHasError(true);
      toast.error(config.i18n.errorOtp || 'Please enter the 6-digit code.');
      return;
    }

    setLoading(true);
    setHasError(false);

    try {
      const result = await verifyOtp(identifier, code, channel, {
        pendingToken: effectivePendingToken,
        purpose,
      });
      onSuccess(result);
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      setHasError(true);
      if (purpose === 'register' && isSessionExpiredError(message)) {
        toast.error(config.i18n.sessionExpired || message);
        onSessionExpired?.();
        return;
      }
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (cooldown > 0) return;
    setResending(true);
    setHasError(false);
    try {
      const result = await resendOtp(identifier, channel, purpose, effectivePendingToken);
      if (result.session_expires_at) {
        extendOtpSession(result.session_expires_at * 1000);
      }
      if (result.pending_token) {
        const { otp } = loadFormSession();
        if (otp) {
          saveOtpSession({ ...otp, pendingToken: result.pending_token });
        }
      }
      toast.success(config.i18n.otpResent || 'A new code has been sent.');
      setCooldown(config.otpTtl ? Math.min(60, config.otpTtl) : 60);
      setCode('');
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      if (purpose === 'register' && isSessionExpiredError(message)) {
        toast.error(config.i18n.sessionExpired || message);
        onSessionExpired?.();
        return;
      }
      toast.error(message);
    } finally {
      setResending(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <h2 id="logixfast-auth-title" className="logixfast-auth-title">{config.i18n.verifyOtp}</h2>
      <p className="logixfast-auth-otp-info">
        {config.i18n.otpSent || 'We sent a 6-digit code to'} <strong>{identifier}</strong>
      </p>

      <div className={hasError ? 'logixfast-auth-field logixfast-auth-field--error' : 'logixfast-auth-field'}>
        <LogixFastAuthOtpInput
          value={code}
          onChange={(value) => {
            setCode(value);
            if (hasError) setHasError(false);
          }}
        />
      </div>
      <div className="logixfast-auth-otp-actions">
        <button type="submit" className="logixfast-auth-btn logixfast-auth-btn--primary" disabled={loading}>
          {loading ? (
            <>
              <span className="logixfast-auth-loading-spinner" aria-hidden="true" />
              {config.i18n.loading}
            </>
          ) : (
            config.i18n.verifyOtp
          )}
        </button>

        <button type="button" className="logixfast-auth-btn logixfast-auth-btn--ghost" onClick={handleResend} disabled={resending || cooldown > 0}>
          {resending ? (
            <>
              <span className="logixfast-auth-loading-spinner" aria-hidden="true" />
              {config.i18n.loading}
            </>
          ) : cooldown > 0 ? (
            `${config.i18n.resendOtp} (${cooldown}s)`
          ) : (
            config.i18n.resendOtp
          )}
        </button>

        <button type="button" className="logixfast-auth-btn logixfast-auth-btn--ghost" onClick={onBack}>
          ← Back
        </button>
      </div>
    </form>
  );
}
