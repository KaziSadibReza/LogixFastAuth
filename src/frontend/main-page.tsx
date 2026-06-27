import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { LogixFastAuthApp } from './components/LogixFastAuthApp';
import { applyStyleVars } from './utils/applyStyleVars';
import './styles/main.scss';

const config = window.LOGIXFAST_AUTH_CONFIG;
const rootEl = document.getElementById('logixfast-auth-root');

if (config && rootEl) {
  applyStyleVars(config.style);

  const params = new URLSearchParams(window.location.search);
  const mode = (params.get('mode') || config.defaultMode || 'login') as 'login' | 'register';

  createRoot(rootEl).render(
    <StrictMode>
      <LogixFastAuthApp config={config} initialOpen={true} initialMode={mode} />
    </StrictMode>
  );
}
