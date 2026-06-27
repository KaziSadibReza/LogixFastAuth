import type { InputHTMLAttributes, ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';

interface LogixFastAuthInputProps extends InputHTMLAttributes<HTMLInputElement> {
  icon?: LucideIcon;
  suffix?: ReactNode;
  hasError?: boolean;
}

export function LogixFastAuthInput({ icon: Icon, suffix, hasError, className = '', ...rest }: LogixFastAuthInputProps) {
  return (
    <div
      className={[
        'logixfast-auth-input-wrap',
        suffix ? 'has-suffix' : '',
        hasError ? 'logixfast-auth-input-wrap--error' : '',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {Icon && (
        <span className="logixfast-auth-input-icon" aria-hidden="true">
          <Icon size={18} strokeWidth={2} />
        </span>
      )}
      <input className="logixfast-auth-input" aria-invalid={hasError || undefined} {...rest} />
      {suffix && <span className="logixfast-auth-input-suffix">{suffix}</span>}
    </div>
  );
}
