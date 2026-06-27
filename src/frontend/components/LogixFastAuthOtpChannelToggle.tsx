import { Mail, Smartphone } from 'lucide-react';

export type OtpChannel = 'email' | 'phone';

interface LogixFastAuthOtpChannelToggleProps {
  channel: OtpChannel;
  emailLabel: string;
  phoneLabel: string;
  onChange: (channel: OtpChannel) => void;
}

export function LogixFastAuthOtpChannelToggle({
  channel,
  emailLabel,
  phoneLabel,
  onChange,
}: LogixFastAuthOtpChannelToggleProps) {
  return (
    <div className="logixfast-auth-channel-toggle" role="group" aria-label="Verification channel">
      <button
        type="button"
        className={`logixfast-auth-channel-toggle__btn ${channel === 'email' ? 'logixfast-auth-channel-toggle__btn--active' : ''}`}
        aria-pressed={channel === 'email'}
        onClick={() => onChange('email')}
      >
        <Mail size={16} aria-hidden="true" />
        {emailLabel}
      </button>
      <button
        type="button"
        className={`logixfast-auth-channel-toggle__btn ${channel === 'phone' ? 'logixfast-auth-channel-toggle__btn--active' : ''}`}
        aria-pressed={channel === 'phone'}
        onClick={() => onChange('phone')}
      >
        <Smartphone size={16} aria-hidden="true" />
        {phoneLabel}
      </button>
    </div>
  );
}
