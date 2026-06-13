import type { InputHTMLAttributes, ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';

interface SlrInputProps extends InputHTMLAttributes<HTMLInputElement> {
  icon?: LucideIcon;
  suffix?: ReactNode;
  hasError?: boolean;
}

export function SlrInput({ icon: Icon, suffix, hasError, className = '', ...rest }: SlrInputProps) {
  return (
    <div
      className={[
        'slr-input-wrap',
        suffix ? 'has-suffix' : '',
        hasError ? 'slr-input-wrap--error' : '',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {Icon && (
        <span className="slr-input-icon" aria-hidden="true">
          <Icon size={18} strokeWidth={2} />
        </span>
      )}
      <input className="slr-input" aria-invalid={hasError || undefined} {...rest} />
      {suffix && <span className="slr-input-suffix">{suffix}</span>}
    </div>
  );
}
