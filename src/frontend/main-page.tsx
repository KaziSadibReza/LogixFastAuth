import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { SlrApp } from './components/SlrApp';
import { applyStyleVars } from './utils/applyStyleVars';
import './styles/main.scss';

const config = window.SLR_CONFIG;
const rootEl = document.getElementById('slr-root');

if (config && rootEl) {
  applyStyleVars(config.style);

  const params = new URLSearchParams(window.location.search);
  const mode = (params.get('mode') || config.defaultMode || 'login') as 'login' | 'register';

  createRoot(rootEl).render(
    <StrictMode>
      <SlrApp config={config} initialOpen={true} initialMode={mode} />
    </StrictMode>
  );
}
