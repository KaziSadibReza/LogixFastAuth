import type { SlrMode } from '@shared/types';

const config = window.SLR_CONFIG;

const TUTOR_LOGIN_SELECTORS =
  '.tutor-open-login-modal, .tutor-course-entry-box-login button, .tutor-course-entry-box-login a';

const PENDING_CART_KEY = 'slr_pending_tutor_add_to_cart';

interface PendingCartAction {
  productId: string;
  formAction: string;
}

let pendingMode: SlrMode | null = null;
let bridgeInitialized = false;

function loadPopupAssets(): void {
  const base =
    document.querySelector<HTMLScriptElement>('script[src*="bootstrap"]')?.src.replace(/bootstrap\.js.*$/, '') ||
    '';
  const assets = config?.assets;

  if (!document.querySelector('link[data-slr-popup-css]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = assets?.popupCss || `${base}frontend/main.css`;
    link.setAttribute('data-slr-popup-css', '1');
    document.head.appendChild(link);
  }

  if (!document.querySelector('script[data-slr-popup-js]')) {
    const script = document.createElement('script');
    script.src = assets?.popupJs || `${base}frontend/popup.js`;
    script.type = 'module';
    script.setAttribute('data-slr-popup-js', '1');
    document.body.appendChild(script);
  }
}

function dispatchOpen(mode: SlrMode): void {
  window.dispatchEvent(new CustomEvent('slr:open', { detail: { mode } }));
}

function isPopupReady(): boolean {
  return Boolean((window as Window & { __SLR_POPUP_READY__?: boolean }).__SLR_POPUP_READY__);
}

export function openPopup(mode: SlrMode): void {
  if (isPopupReady()) {
    dispatchOpen(mode);
    return;
  }

  pendingMode = mode;
  loadPopupAssets();
}

export function closePopup(): void {
  window.dispatchEvent(new CustomEvent('slr:close'));
}

export function markPopupReady(): void {
  (window as Window & { __SLR_POPUP_READY__?: boolean }).__SLR_POPUP_READY__ = true;
  window.dispatchEvent(new CustomEvent('slr:ready'));
}

export function consumePendingOpen(): SlrMode | null {
  if (!pendingMode) return null;
  const mode = pendingMode;
  pendingMode = null;
  return mode;
}

export function preloadPopup(): void {
  if (isPopupReady() || document.querySelector('script[data-slr-popup-js]')) {
    return;
  }
  loadPopupAssets();
}

function parseModeFromHref(href: string): SlrMode | null {
  const raw = href.includes('#') ? href.split('#').pop() || '' : href;
  const hash = raw.startsWith('/') ? raw.slice(1) : raw;
  if (hash === 'slr-open-login') return 'login';
  if (hash === 'slr-open-register') return 'register';
  return null;
}

function isTutorPopupTrigger(el: HTMLElement): boolean {
  return Boolean(
    el.closest('.slr-tutor-login-replace, .slr-tutor-login-replace-wrap, .slr-tutor-login-popup-only')
  );
}

function isElementorPopupTrigger(el: HTMLElement): boolean {
  return Boolean(el.closest('.slr-elementor-login-replace'));
}

function canOpenSlrTrigger(el: HTMLElement): boolean {
  const integrations = config?.integrations;
  if (!integrations) {
    return false;
  }

  if (isTutorPopupTrigger(el)) {
    return Boolean(integrations.replaceTutor);
  }

  if (isElementorPopupTrigger(el)) {
    return Boolean(integrations.replaceElementor);
  }

  return Boolean(integrations.replaceTutor || integrations.replaceElementor);
}

function canOpenSlrHashLink(): boolean {
  return Boolean(config?.integrations?.replaceElementor);
}

function openFromLocationHash(): void {
  if (!canOpenSlrHashLink()) {
    return;
  }

  const mode = parseModeFromHref(window.location.hash);
  if (mode) openPopup(mode);
}

function storePendingCartAction(el: HTMLElement): void {
  const trigger = el.closest<HTMLElement>('[data-slr-pending-product-id]') || el;
  const productId =
    trigger.getAttribute('data-slr-pending-product-id') ||
    trigger.getAttribute('data-product-id') ||
    '';
  if (!productId) return;

  const formAction =
    trigger.getAttribute('data-slr-pending-action-url') ||
    trigger.closest('form')?.getAttribute('action') ||
    window.location.href;

  const payload: PendingCartAction = {
    productId,
    formAction,
  };
  sessionStorage.setItem(PENDING_CART_KEY, JSON.stringify(payload));
}

function resumePendingCartAction(): void {
  if (!document.body.classList.contains('logged-in')) return;

  const raw = sessionStorage.getItem(PENDING_CART_KEY);
  if (!raw) return;

  sessionStorage.removeItem(PENDING_CART_KEY);

  try {
    const data = JSON.parse(raw) as PendingCartAction;
    if (!data.productId) return;

    const form = document.createElement('form');
    form.method = 'post';
    form.action = data.formAction || window.location.href;

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'add-to-cart';
    input.value = data.productId;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  } catch {
    // Ignore invalid stored state.
  }
}

function handleTutorLoginTrigger(event: Event, el: HTMLElement): void {
  if (el.tagName === 'A') {
    const href = el.getAttribute('href') || '';
    if (href && href !== '#' && !href.startsWith('javascript')) {
      return;
    }
  }

  event.preventDefault();
  event.stopPropagation();
  event.stopImmediatePropagation();

  storePendingCartAction(el);
  openPopup('login');
}

function initTutorLoginIntercept(): void {
  if (!config?.integrations?.replaceTutor) return;

  document.addEventListener(
    'click',
    (event) => {
      const el = (event.target as HTMLElement).closest<HTMLElement>(TUTOR_LOGIN_SELECTORS);
      if (!el) return;
      handleTutorLoginTrigger(event, el);
    },
    true
  );
}

export function ensureSlrBridge(): void {
  if (bridgeInitialized) return;
  bridgeInitialized = true;

  window.SLR = { open: openPopup, close: closePopup };
  initTutorLoginIntercept();
  resumePendingCartAction();

  const earlyOpen = (window as Window & { __SLR_EARLY_OPEN__?: SlrMode }).__SLR_EARLY_OPEN__;
  if (earlyOpen) {
    delete (window as Window & { __SLR_EARLY_OPEN__?: SlrMode }).__SLR_EARLY_OPEN__;
    openPopup(earlyOpen);
  }

  window.addEventListener('slr:ready', () => {
    if (!pendingMode || !isPopupReady()) return;
    const mode = pendingMode;
    pendingMode = null;
    dispatchOpen(mode);
  });

  window.addEventListener('hashchange', () => {
    openFromLocationHash();
  });

  if (window.location.hash) {
    openFromLocationHash();
  }

  document.addEventListener('click', (event) => {
    const target = (event.target as HTMLElement).closest<HTMLElement>('[data-slr-open]');
    if (target) {
      if (!canOpenSlrTrigger(target)) {
        return;
      }

      event.preventDefault();
      const mode = (target.getAttribute('data-slr-open') || 'login') as SlrMode;
      openPopup(mode);
      return;
    }

    const link = (event.target as HTMLElement).closest<HTMLAnchorElement>('a[href*="slr-open-"]');
    if (!link || !canOpenSlrHashLink()) return;

    const mode = parseModeFromHref(link.getAttribute('href') || '');
    if (!mode) return;

    event.preventDefault();
    openPopup(mode);
  });
}
