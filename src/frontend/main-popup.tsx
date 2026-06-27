import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { LogixFastAuthApp } from './components/LogixFastAuthApp';
import { ensureLogixFastAuthBridge } from './logixfast-auth-bridge';
import { applyStyleVars } from './utils/applyStyleVars';
import './styles/main.scss';

ensureLogixFastAuthBridge();

const config = window.LOGIXFAST_AUTH_CONFIG;
const rootEl = document.getElementById('logixfast-auth-root');

if (config && rootEl) {
  applyStyleVars(config.style);

  createRoot(rootEl).render(
    <StrictMode>
      <LogixFastAuthApp config={config} />
    </StrictMode>
  );
}
