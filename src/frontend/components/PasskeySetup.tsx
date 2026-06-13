import { useState } from 'react';
import { Fingerprint, ArrowRight, X } from 'lucide-react';
import { webauthnRegister } from '../api/auth';
import { useSlrToast } from './SlrToaster';
import type { SlrConfig } from '@shared/types';

interface PasskeySetupProps {
  config: SlrConfig;
  onDone: () => void;
  onSkip: () => void;
}

export function PasskeySetup({ config, onDone, onSkip }: PasskeySetupProps) {
  const toast = useSlrToast();
  const [loading, setLoading] = useState(false);

  const handleSetup = async () => {
    setLoading(true);
    try {
      await webauthnRegister();
      toast.success(config.i18n.passkeyRegistered || 'Passkey registered! You can use it to sign in next time.');
      setTimeout(onDone, 1200);
    } catch (err) {
      const message = err instanceof Error ? err.message : config.i18n.errorGeneric;
      if (message.toLowerCase().includes('cancelled') || message.toLowerCase().includes('abort')) {
        return;
      }
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="slr-passkey-setup">
      <div className="slr-passkey-setup__icon">
        <Fingerprint size={32} strokeWidth={1.5} />
      </div>

      <h2 className="slr-passkey-setup__title">
        {config.i18n.passkeySetupTitle || 'Set up a passkey'}
      </h2>

      <p className="slr-passkey-setup__desc">
        {config.i18n.passkeySetupDesc || 'Sign in faster next time with Face ID, Touch ID, or your device PIN. No password needed.'}
      </p>

      <button
        type="button"
        className="slr-btn slr-btn--primary"
        onClick={handleSetup}
        disabled={loading}
      >
        {loading ? (
          <>
            <span className="slr-loading-spinner" aria-hidden="true" />
            {config.i18n.loading}
          </>
        ) : (
          <>
            <Fingerprint size={18} aria-hidden="true" />
            {config.i18n.passkeySetupBtn || 'Set up passkey'}
            <ArrowRight size={18} className="slr-btn-arrow" aria-hidden="true" />
          </>
        )}
      </button>

      <button
        type="button"
        className="slr-btn slr-btn--ghost"
        onClick={onSkip}
        disabled={loading}
      >
        <X size={16} aria-hidden="true" />
        {config.i18n.passkeySkip || 'Not now'}
      </button>
    </div>
  );
}
