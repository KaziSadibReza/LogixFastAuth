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
      <div className="slr-field slr-field--inline">
        <div className="slr-field-text">
          {label && (
            <div className="slr-field-label">
              {label}
              {required && <span className="slr-field-required" aria-hidden="true">*</span>}
            </div>
          )}
          {help && <p className="slr-field-help">{help}</p>}
        </div>
        {children}
      </div>
    );
  }

  return (
    <div className="slr-field">
      {label && (
        <label className="slr-field-label" htmlFor={htmlFor}>
          {label}
          {required && <span className="slr-field-required" aria-hidden="true">*</span>}
        </label>
      )}
      {children}
      {error && <p className="slr-field-error" role="alert">{error}</p>}
      {!error && help && <p className="slr-field-help">{help}</p>}
    </div>
  );
}
