import type { InputHTMLAttributes } from 'react';

interface ToggleProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  ariaLabel?: string;
}

export function Toggle({ ariaLabel, className = '', ...rest }: ToggleProps) {
  return (
    <label className={`slr-toggle ${className}`}>
      <input type="checkbox" aria-label={ariaLabel} {...rest} />
      <span className="slr-toggle-track" aria-hidden="true">
        <span className="slr-toggle-thumb" />
      </span>
    </label>
  );
}
