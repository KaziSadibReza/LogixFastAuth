import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { SlrApp } from './components/SlrApp';
import { ensureSlrBridge } from './slr-bridge';
import { applyStyleVars } from './utils/applyStyleVars';
import './styles/main.scss';

ensureSlrBridge();

const config = window.SLR_CONFIG;
const rootEl = document.getElementById('slr-root');

if (config && rootEl) {
  applyStyleVars(config.style);

  createRoot(rootEl).render(
    <StrictMode>
      <SlrApp config={config} />
    </StrictMode>
  );
}
