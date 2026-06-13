import { FormEvent, useState } from 'react';
import { ArrowRight, Eye, EyeOff, Lock } from 'lucide-react';
import { resetPassword } from '../api/auth';
import { SlrField } from './SlrField';
import { SlrInput } from './SlrInput';
import { useSlrToast } from './SlrToaster';
import { clearOtpSession, clearResetToken } from '../utils/formStorage';
import type { SlrConfig } from '@shared/types';

interface ResetPasswordFormProps {
  config: SlrConfig;
  resetToken: string;
  onSuccess: () => void;
}

export function ResetPasswordForm({ config, resetToken, onSuccess }: ResetPasswordFormProps) {
  const toast = useSlrToast();
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
      await resetPassword(resetToken, password);
      clearOtpSession();
      clearResetToken();
      toast.success(config.i18n.passwordResetSuccess || 'Password updated. You can sign in now.');
      onSuccess();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : config.i18n.errorGeneric);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <h2 id="slr-title" className="slr-title">{config.i18n.newPassword || 'Set new password'}</h2>
      <p className="slr-subtitle slr-subtitle--compact">
        {config.i18n.newPasswordHint || 'Choose a strong password for your account.'}
      </p>

      <SlrField label={config.i18n.password} htmlFor="slr-reset-password" required>
        <SlrInput
          id="slr-reset-password"
          icon={Lock}
          type={showPassword ? 'text' : 'password'}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          autoComplete="new-password"
          minLength={8}
          placeholder="Min 8 characters"
          suffix={
            <button type="button" className="slr-input-action" onClick={() => setShowPassword((v) => !v)}>
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </SlrField>

      <SlrField label={config.i18n.confirmPassword} htmlFor="slr-reset-confirm" required>
        <SlrInput
          id="slr-reset-confirm"
          icon={Lock}
          type={showConfirm ? 'text' : 'password'}
          value={confirmPassword}
          onChange={(e) => setConfirmPassword(e.target.value)}
          autoComplete="new-password"
          placeholder="Repeat password"
          suffix={
            <button type="button" className="slr-input-action" onClick={() => setShowConfirm((v) => !v)}>
              {showConfirm ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          }
        />
      </SlrField>

      <button type="submit" className="slr-btn slr-btn--primary" disabled={loading}>
        {loading ? (
          <>
            <span className="slr-loading-spinner" aria-hidden="true" />
            {config.i18n.loading}
          </>
        ) : (
          <>
            {config.i18n.updatePassword || 'Update password'}
            <ArrowRight size={18} className="slr-btn-arrow" aria-hidden="true" />
          </>
        )}
      </button>
    </form>
  );
}
