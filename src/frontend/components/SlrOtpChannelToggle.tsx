import { Mail, Smartphone } from 'lucide-react';

export type OtpChannel = 'email' | 'phone';

interface SlrOtpChannelToggleProps {
  channel: OtpChannel;
  emailLabel: string;
  phoneLabel: string;
  onChange: (channel: OtpChannel) => void;
}

export function SlrOtpChannelToggle({
  channel,
  emailLabel,
  phoneLabel,
  onChange,
}: SlrOtpChannelToggleProps) {
  return (
    <div className="slr-channel-toggle" role="group" aria-label="Verification channel">
      <button
        type="button"
        className={`slr-channel-toggle__btn ${channel === 'email' ? 'slr-channel-toggle__btn--active' : ''}`}
        aria-pressed={channel === 'email'}
        onClick={() => onChange('email')}
      >
        <Mail size={16} aria-hidden="true" />
        {emailLabel}
      </button>
      <button
        type="button"
        className={`slr-channel-toggle__btn ${channel === 'phone' ? 'slr-channel-toggle__btn--active' : ''}`}
        aria-pressed={channel === 'phone'}
        onClick={() => onChange('phone')}
      >
        <Smartphone size={16} aria-hidden="true" />
        {phoneLabel}
      </button>
    </div>
  );
}
