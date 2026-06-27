import type { InputHTMLAttributes } from 'react';

interface ToggleProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  ariaLabel?: string;
}

export function Toggle({ ariaLabel, className = '', ...rest }: ToggleProps) {
  return (
    <label className={`logixfast-auth-toggle ${className}`}>
      <input type="checkbox" aria-label={ariaLabel} {...rest} />
      <span className="logixfast-auth-toggle-track" aria-hidden="true">
        <span className="logixfast-auth-toggle-thumb" />
      </span>
    </label>
  );
}
