import type { ReactNode } from 'react';

interface SlrFieldProps {
  label: string;
  htmlFor?: string;
  required?: boolean;
  hasError?: boolean;
  children: ReactNode;
}

export function SlrField({ label, htmlFor, required, hasError, children }: SlrFieldProps) {
  return (
    <div className={`slr-field ${hasError ? 'slr-field--error' : ''}`.trim()}>
      <label className="slr-label" htmlFor={htmlFor}>
        <span className="slr-label-text">{label}</span>
        {required && (
          <span className="slr-required" title="Required" aria-label="required">
            *
          </span>
        )}
      </label>
      {children}
    </div>
  );
}
