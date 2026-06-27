interface LogixFastAuthTabSwitchProps {
  loginLabel: string;
  registerLabel: string;
  mode: 'login' | 'register';
  onChange: (mode: 'login' | 'register') => void;
}

export function LogixFastAuthTabSwitch({ loginLabel, registerLabel, mode, onChange }: LogixFastAuthTabSwitchProps) {
  return (
    <div className="logixfast-auth-tabs" role="tablist" aria-label="Authentication mode">
      <span
        className="logixfast-auth-tabs-indicator"
        data-active={mode}
        aria-hidden="true"
      />
      <button
        type="button"
        role="tab"
        aria-selected={mode === 'login'}
        className={`logixfast-auth-tab ${mode === 'login' ? 'logixfast-auth-tab--active' : ''}`}
        onClick={() => onChange('login')}
      >
        {loginLabel}
      </button>
      <button
        type="button"
        role="tab"
        aria-selected={mode === 'register'}
        className={`logixfast-auth-tab ${mode === 'register' ? 'logixfast-auth-tab--active' : ''}`}
        onClick={() => onChange('register')}
      >
        {registerLabel}
      </button>
    </div>
  );
}
