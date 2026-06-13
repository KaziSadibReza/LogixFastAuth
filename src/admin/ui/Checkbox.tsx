import type { InputHTMLAttributes, ReactNode } from 'react';

interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: ReactNode;
}

export function Checkbox({ label, className = '', ...rest }: CheckboxProps) {
  return (
    <label className={`slr-checkbox ${className}`}>
      <input type="checkbox" {...rest} />
      <span className="slr-checkbox-box" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path
            d="M2 6.5L4.8 9.3L10 3.3"
            stroke="#fff"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        </svg>
      </span>
      {label && <span>{label}</span>}
    </label>
  );
}
