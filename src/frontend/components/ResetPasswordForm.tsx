import { FormEvent, useState } from 'react';
import { ArrowRight, Eye, EyeOff, Lock } from 'lucide-react';
import { resetPassword } from '../api/auth';
import { LogixFastAuthField } from './LogixFastAuthField';
import { LogixFastAuthInput } from './LogixFastAuthInput';
import { useLogixFastAuthToast } from './LogixFastAuthToaster';
import { clearOtpSession, clearResetToken } from '../utils/formStorage';
import type { LogixFastAuthConfig } from '@shared/types';

import type { AuthRedirectResult } from '../utils/redirect';

interface ResetPasswordFormProps {
  config: LogixFastAuthConfig;
  resetToken: string;
  onSuccess: (result: AuthRedirectResult) => void;
}

export function ResetPasswordForm({ config, resetToken, onSuccess }: ResetPasswordFormProps) {
  const toast = useLogixFastAuthToast();
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (password.length < 8) {
      toast.error(config.i18n.errorPasswordMin || 'Password must be at least 8 characters.');
      return;
    }

    if (password !== confirmPassword) {
      toast.error(config.i18n.passwordsMismatch || 'Passwords do not match.');
      return;
    }

    setLoading(true);
    try {
      const result = await resetPassword(resetToken, password);
      clearOtpSession();
      clearResetToken();
      toast.success(config.i18n.passwordResetSuccess || 'Password updated. You can sign in now.');
      onSuccess(result);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : config.i18n.errorGeneric);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <h2 id="logixfast-auth-title" className="logixfast-auth-title">{config.i18n.newPassword || 'Set new password'}</h2>
      <p className="logixfast-auth-subtitle logixfast-auth-subtitle--compact">
        {config.i18n.newPasswordHint || 'Choose a strong password for your account.'}
      </p>

      <LogixFastAuthField label={config.i18n.password} htmlFor="logixfast-auth-reset-password" required>
        <LogixFastAuthInput
          id="logixfast-auth-reset-password"
          icon={Lock}
          type={showPassword ? 'text' : 'password'}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete="new-password"
          minLength={8}
          placeholder="Min 8 characters"
          suffix={
            <button type="button" className="logixfast-auth-input-action" onClick={() => setShowPassword((v) => !v)}>
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </LogixFastAuthField>

      <LogixFastAuthField label={config.i18n.confirmPassword} htmlFor="logixfast-auth-reset-confirm" required>
        <LogixFastAuthInput
          id="logixfast-auth-reset-confirm"
          icon={Lock}
          type={showConfirm ? 'text' : 'password'}
          value={confirmPassword}
          onChange={(e) => setConfirmPassword(e.target.value)}
          autoComplete="new-password"
          placeholder="Repeat password"
          suffix={
            <button type="button" className="logixfast-auth-input-action" onClick={() => setShowConfirm((v) => !v)}>
              {showConfirm ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </LogixFastAuthField>

      <button type="submit" className="logixfast-auth-btn logixfast-auth-btn--primary" disabled={loading}>
        {loading ? (
          <>
            <span className="logixfast-auth-loading-spinner" aria-hidden="true" />
            {config.i18n.loading}
          </>
        ) : (
          <>
            {config.i18n.updatePassword || 'Update password'}
            <ArrowRight size={18} className="logixfast-auth-btn-arrow" aria-hidden="true" />
          </>
        )}
      </button>
    </form>
  );
}
