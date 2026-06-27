import type { ReactNode } from 'react';

interface LogixFastAuthFieldProps {
  label: string;
  htmlFor?: string;
  required?: boolean;
  hasError?: boolean;
  children: ReactNode;
}

export function LogixFastAuthField({ label, htmlFor, required, hasError, children }: LogixFastAuthFieldProps) {
  return (
    <div className={`logixfast-auth-field ${hasError ? 'logixfast-auth-field--error' : ''}`.trim()}>
      <label className="logixfast-auth-label" htmlFor={htmlFor}>
        <span className="logixfast-auth-label-text">{label}</span>
        {required && (
          <span className="logixfast-auth-required" title="Required" aria-label="required">
            *
          </span>
        )}
      </label>
      {children}
    </div>
  );
}
