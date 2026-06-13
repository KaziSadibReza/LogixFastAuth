interface SlrTabSwitchProps {
  loginLabel: string;
  registerLabel: string;
  mode: 'login' | 'register';
  onChange: (mode: 'login' | 'register') => void;
}

export function SlrTabSwitch({ loginLabel, registerLabel, mode, onChange }: SlrTabSwitchProps) {
  return (
    <div className="slr-tabs" role="tablist" aria-label="Authentication mode">
      <span
        className="slr-tabs-indicator"
        data-active={mode}
        aria-hidden="true"
      />
      <button
        type="button"
        role="tab"
        aria-selected={mode === 'login'}
        className={`slr-tab ${mode === 'login' ? 'slr-tab--active' : ''}`}
        onClick={() => onChange('login')}
      >
        {loginLabel}
      </button>
      <button
        type="button"
        role="tab"
        aria-selected={mode === 'register'}
        className={`slr-tab ${mode === 'register' ? 'slr-tab--active' : ''}`}
        onClick={() => onChange('register')}
      >
        {registerLabel}
      </button>
    </div>
  );
}
