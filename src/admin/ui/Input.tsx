import { forwardRef, type InputHTMLAttributes, type ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Icon } from './Icon';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  icon?: LucideIcon;
  suffix?: ReactNode;
  hasError?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { icon, suffix, hasError, className = '', ...rest },
  ref
) {
  const inputEl = (
    <input
      ref={ref}
      className={`logixfast-auth-input ${hasError ? 'logixfast-auth-input--error' : ''} ${className}`}
      {...rest}
    />
  );

  if (!icon && !suffix) return inputEl;

  const wrapperClass = ['logixfast-auth-input-wrapper', icon ? 'has-icon' : '', suffix ? 'has-suffix' : '']
    .filter(Boolean)
    .join(' ');

  return (
    <div className={wrapperClass}>
      {icon && (
        <span className="logixfast-auth-input-icon" aria-hidden="true">
          <Icon icon={icon} size={16} />
        </span>
      )}
      {inputEl}
      {suffix && <span className="logixfast-auth-input-suffix">{suffix}</span>}
    </div>
  );
});
