import { ensureLogixFastAuthBridge, preloadPopup } from './logixfast-auth-bridge';

ensureLogixFastAuthBridge();
preloadPopup();

// Dedicated pages use main-page.tsx directly — not bootstrap + lazy popup.
