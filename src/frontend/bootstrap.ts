import { ensureSlrBridge, preloadPopup } from './slr-bridge';

ensureSlrBridge();
preloadPopup();

// Dedicated pages use main-page.tsx directly — not bootstrap + lazy popup.
