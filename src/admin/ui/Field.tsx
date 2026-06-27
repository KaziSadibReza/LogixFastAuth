import type { ReactNode } from 'react';

interface FieldProps {
  label?: ReactNode;
  htmlFor?: string;
  help?: ReactNode;
  error?: ReactNode;
  required?: boolean;
  children: ReactNode;
  inline?: boolean;
}

export function Field({ label, htmlFor, help, error, required, children, inline }: FieldProps) {
  if (inline) {
    return (
      <div className="logixfast-auth-field logixfast-auth-field--inline">
        <div className="logixfast-auth-field-text">
          {label && (
            <div className="logixfast-auth-field-label">
              {label}
              {required && <span className="logixfast-auth-field-required" aria-hidden="true">*</span>}
            </div>
          )}
          {help && <p className="logixfast-auth-field-help">{help}</p>}
        </div>
        {children}
      </div>
    );
  }

  return (
    <div className="logixfast-auth-field">
      {label && (
        <label className="logixfast-auth-field-label" htmlFor={htmlFor}>
          {label}
          {required && <span className="logixfast-auth-field-required" aria-hidden="true">*</span>}
        </label>
      )}
      {children}
      {error && <p className="logixfast-auth-field-error" role="alert">{error}</p>}
      {!error && help && <p className="logixfast-auth-field-help">{help}</p>}
    </div>
  );
}
