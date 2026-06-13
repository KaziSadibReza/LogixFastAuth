import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { LogIn, Maximize2, Minimize2, Monitor, Smartphone, UserRoundPlus } from 'lucide-react';
import { applyStyleVarsToElement, previewStageBackground } from '@shared/styleVars';
import type { SlrStyle } from '@shared/types';
import { Icon } from '../ui';

export type PreviewFormMode = 'login' | 'register';
export type PreviewViewMode = 'popup' | 'page';
export type PreviewDevice = 'desktop' | 'mobile';

interface AppearancePreviewProps {
  appearance: SlrStyle;
}

function hexToRgb(hex: string): [number, number, number] | null {
  const normalized = hex.replace('#', '').trim();
  if (!/^[0-9a-f]{3,8}$/i.test(normalized)) return null;
  const full =
    normalized.length === 3
      ? normalized.split('').map((c) => c + c).join('')
      : normalized.slice(0, 6);
  return [parseInt(full.slice(0, 2), 16), parseInt(full.slice(2, 4), 16), parseInt(full.slice(4, 6), 16)];
}

function rgbToHex(rgb: [number, number, number]): string {
  return '#' + rgb.map((v) => v.toString(16).padStart(2, '0')).join('');
}

function mixWithWhite(hex: string, amount: number): string {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;
  return rgbToHex(rgb.map((v) => Math.round(v + (255 - v) * amount)) as [number, number, number]);
}

function darken(hex: string, amount = 0.18): string {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;
  return rgbToHex(rgb.map((v) => Math.max(0, Math.round(v * (1 - amount)))) as [number, number, number]);
}

function rgba(hex: string, alpha: number): string {
  const rgb = hexToRgb(hex);
  if (!rgb) return `rgba(214, 51, 108, ${alpha})`;
  return `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${alpha})`;
}

/** CSS variables injected into the preview iframe. */
function buildPreviewVars(appearance: SlrStyle): string {
  const primary = appearance.primary || '#d6336c';
  return `
    :root {
      --slr-primary: ${primary};
      --slr-primary-dark: ${darken(primary, 0.18)};
      --slr-primary-50: ${mixWithWhite(primary, 0.92)};
      --slr-primary-100: ${mixWithWhite(primary, 0.85)};
      --slr-background: ${appearance.background || '#ffffff'};
      --slr-text: ${appearance.text || '#111827'};
      --slr-text-muted: #6b7280;
      --slr-blur: ${appearance.blur || '24px'};
      --slr-radius: ${appearance.radius || '12px'};
      --slr-radius-sm: 8px;
      --slr-spacing: ${appearance.spacing || '1rem'};
      --slr-muted: #6b7280;
      --slr-border: #e5e7eb;
      --slr-border-strong: #d1d5db;
      --slr-error: #ef4444;
      --slr-success: #10b981;
      --slr-shadow-focus: 0 0 0 3px ${rgba(primary, 0.18)};
      --slr-shadow-primary: 0 8px 20px ${rgba(primary, 0.32)};
      --slr-font: 'Urbanist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      --slr-transition: 180ms cubic-bezier(0.4, 0, 0.2, 1);
    }
    html, body { margin: 0; padding: 0; background: transparent; }
    body { font-family: var(--slr-font); color: var(--slr-text); }
    .slr-preview-stage {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 24px;
      background:
        radial-gradient(circle at 20% 20%, ${rgba(primary, 0.06)}, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(79, 70, 229, 0.05), transparent 40%),
        #f4f6f8;
    }
  `;
}

/**
 * Injected LAST into iframe <head> so it beats async-loaded frontend CSS.
 * Fixes position:fixed overlay and max-height:100dvh card clipping.
 */
const PREVIEW_LAYOUT_FIX = `
  .slr-preview-stage .slr-overlay {
    position: relative !important;
    inset: auto !important;
    z-index: 1 !important;
    width: 100% !important;
    min-height: 0 !important;
    max-height: none !important;
    animation: none !important;
    padding: var(--slr-spacing) !important;
  }
  .slr-preview-stage .slr-overlay--page {
    min-height: auto !important;
  }
  .slr-preview-stage .slr-card {
    position: relative !important;
    max-height: none !important;
    max-width: min(440px, 100%) !important;
    overflow: visible !important;
    animation: none !important;
    margin: 0 auto !important;
  }
`;

const loginMarkup = `
  <h2 class="slr-title">Welcome back</h2>
  <p class="slr-subtitle">Sign in to continue.</p>
  <div class="slr-tabs">
    <span class="slr-tabs-indicator" data-active="login"></span>
    <button type="button" class="slr-tab slr-tab--active">Log In</button>
    <button type="button" class="slr-tab">Register</button>
  </div>
  <div class="slr-form-panel">
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Email</span> <span class="slr-required">*</span></label>
      <div class="slr-input-wrap">
        <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
        <input type="email" class="slr-input" value="you@example.com" readonly tabindex="-1" />
      </div>
    </div>
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Password</span> <span class="slr-required">*</span></label>
      <div class="slr-input-wrap has-suffix">
        <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <input type="password" class="slr-input" value="password123" readonly tabindex="-1" />
        <span class="slr-input-suffix"><button type="button" class="slr-input-action" tabindex="-1"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
      </div>
    </div>
    <div class="slr-login-meta">
      <label class="slr-checkbox-row"><input type="checkbox" tabindex="-1" /> Remember me</label>
      <button type="button" class="slr-link-btn" tabindex="-1">Forgot password?</button>
    </div>
    <button type="button" class="slr-btn slr-btn--primary" tabindex="-1">
      Sign In <svg class="slr-btn-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
    </button>
    <div class="slr-divider">or</div>
    <button type="button" class="slr-btn slr-btn--ghost slr-btn--otp-login" tabindex="-1">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
      Sign in with code
    </button>
  </div>
`;

const registerMarkup = `
  <h2 class="slr-title">Create your account</h2>
  <p class="slr-subtitle">Just a few details to get started.</p>
  <div class="slr-tabs">
    <span class="slr-tabs-indicator" data-active="register"></span>
    <button type="button" class="slr-tab">Log In</button>
    <button type="button" class="slr-tab slr-tab--active">Register</button>
  </div>
  <div class="slr-form-panel">
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Full Name</span> <span class="slr-required">*</span></label>
      <div class="slr-input-wrap">
        <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
        <input type="text" class="slr-input" placeholder="Jane Doe" readonly tabindex="-1" />
      </div>
    </div>
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Email</span> <span class="slr-required">*</span></label>
      <div class="slr-input-wrap">
        <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
        <input type="email" class="slr-input" placeholder="you@example.com" readonly tabindex="-1" />
      </div>
    </div>
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Phone Number</span> <span class="slr-required">*</span></label>
      <div class="slr-phone-row">
        <button type="button" class="slr-country-trigger" tabindex="-1">
          <span style="display:inline-block;width:22px;height:15px;border-radius:3px;background:linear-gradient(180deg,#006A4E 50%,#F42A41 50%);box-shadow:0 0 0 1px rgba(0,0,0,.08);"></span>
          <span class="slr-country-dial">+880</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="slr-phone-input-wrap">
          <div class="slr-input-wrap">
            <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
            <input type="tel" class="slr-input" placeholder="Phone number" readonly tabindex="-1" />
          </div>
        </div>
      </div>
    </div>
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Password</span> <span class="slr-required">*</span></label>
      <div class="slr-input-wrap has-suffix">
        <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <input type="password" class="slr-input" placeholder="Min 8 characters" readonly tabindex="-1" />
        <span class="slr-input-suffix"><button type="button" class="slr-input-action" tabindex="-1"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
      </div>
    </div>
    <div class="slr-field">
      <label class="slr-label"><span class="slr-label-text">Confirm Password</span> <span class="slr-required">*</span></label>
      <div class="slr-input-wrap has-suffix">
        <span class="slr-input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <input type="password" class="slr-input" placeholder="Repeat password" readonly tabindex="-1" />
        <span class="slr-input-suffix"><button type="button" class="slr-input-action" tabindex="-1"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></button></span>
      </div>
    </div>
    <button type="button" class="slr-btn slr-btn--primary" tabindex="-1">
      Create Account <svg class="slr-btn-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
    </button>
  </div>
`;

function buildIframeHtml(
  appearance: SlrStyle,
  formMode: PreviewFormMode,
  viewMode: PreviewViewMode,
  cssUrls: string[]
): string {
  const cssLinks = cssUrls.map((url) => `<link rel="stylesheet" href="${url}">`).join('\n');
  const formMarkup = formMode === 'login' ? loginMarkup : registerMarkup;
  const overlayClass = viewMode === 'popup' ? 'slr-overlay slr-overlay--glass' : 'slr-overlay slr-overlay--page';
  const stageClass = viewMode === 'popup' ? 'slr-preview-stage slr-preview-stage--popup' : 'slr-preview-stage slr-preview-stage--page';

  return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap">
  <style>${buildPreviewVars(appearance)}</style>
  ${cssLinks}
</head>
<body>
  <div class="${stageClass}">
    <div class="${overlayClass}">
      <div class="slr-card">
        <button type="button" class="slr-close" tabindex="-1" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
        ${formMarkup}
      </div>
    </div>
  </div>
</body>
</html>`;
}

function injectLayoutFix(doc: Document): void {
  let el = doc.getElementById('slr-preview-layout-fix') as HTMLStyleElement | null;
  if (!el) {
    el = doc.createElement('style');
    el.id = 'slr-preview-layout-fix';
    doc.head.appendChild(el);
  }
  el.textContent = PREVIEW_LAYOUT_FIX;
}

function applyPreviewAppearance(doc: Document, appearance: SlrStyle): void {
  applyStyleVarsToElement(doc.documentElement, appearance);

  const primary = appearance.primary || '#d6336c';
  const stage = doc.querySelector('.slr-preview-stage') as HTMLElement | null;
  if (stage) {
    stage.style.background = previewStageBackground(primary);
  }

  const card = doc.querySelector('.slr-card') as HTMLElement | null;
  if (card && appearance.background) {
    card.style.background = appearance.background;
  }
  if (card && appearance.text) {
    card.style.color = appearance.text;
  }
}

function measureAndResize(iframe: HTMLIFrameElement, appearance: SlrStyle): void {
  try {
    const doc = iframe.contentDocument;
    if (!doc) return;

    applyPreviewAppearance(doc, appearance);
    injectLayoutFix(doc);

    const stage = doc.querySelector('.slr-preview-stage');
    const height = stage
      ? Math.ceil(stage.scrollHeight)
      : Math.max(doc.documentElement.scrollHeight, doc.body?.scrollHeight ?? 0);

    iframe.style.height = `${Math.max(height, 320)}px`;
  } catch {
    // ignore
  }
}

function PreviewIframe({
  appearance,
  formMode,
  viewMode,
  device,
}: {
  appearance: SlrStyle;
  formMode: PreviewFormMode;
  viewMode: PreviewViewMode;
  device: PreviewDevice;
}) {
  const cssUrls = useMemo(() => window.SLR_ADMIN?.frontendCssUrls ?? [], []);
  const html = useMemo(
    () => buildIframeHtml(appearance, formMode, viewMode, cssUrls),
    [appearance, formMode, viewMode, cssUrls]
  );

  const iframeRef = useRef<HTMLIFrameElement | null>(null);

  useEffect(() => {
    const iframe = iframeRef.current;
    if (!iframe) return;

    const cleanups: (() => void)[] = [];
    let resizeObserver: ResizeObserver | null = null;

    const sync = () => measureAndResize(iframe, appearance);

    const bind = () => {
      const doc = iframe.contentDocument;
      if (!doc) return;

      sync();
      requestAnimationFrame(sync);

      const stage = doc.querySelector('.slr-preview-stage');
      if (stage && typeof ResizeObserver !== 'undefined') {
        resizeObserver?.disconnect();
        resizeObserver = new ResizeObserver(sync);
        resizeObserver.observe(stage);
      }

      doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
        const handler = () => sync();
        link.addEventListener('load', handler);
        link.addEventListener('error', handler);
        cleanups.push(() => {
          link.removeEventListener('load', handler);
          link.removeEventListener('error', handler);
        });
      });

      doc.fonts?.ready.then(sync).catch(() => undefined);
    };

    iframe.onload = bind;
    iframe.srcdoc = html;

    [50, 200, 500, 1000, 2000].forEach((ms) => {
      const t = window.setTimeout(sync, ms);
      cleanups.push(() => window.clearTimeout(t));
    });

    return () => {
      resizeObserver?.disconnect();
      cleanups.forEach((fn) => fn());
    };
  }, [html, appearance]);

  useEffect(() => {
    const iframe = iframeRef.current;
    const doc = iframe?.contentDocument;
    if (!doc?.documentElement) return;
    applyPreviewAppearance(doc, appearance);
  }, [appearance]);

  return (
    <div
      className={[
        'slr-appearance-preview__device',
        `slr-appearance-preview__device--${device}`,
        `slr-appearance-preview__device--${viewMode}`,
      ].join(' ')}
    >
      <iframe
        ref={iframeRef}
        title="Appearance preview"
        className="slr-appearance-preview__iframe"
        sandbox="allow-same-origin allow-scripts"
        scrolling="no"
      />
    </div>
  );
}

export function AppearancePreview({ appearance }: AppearancePreviewProps) {
  const [formMode, setFormMode] = useState<PreviewFormMode>('login');
  const [viewMode, setViewMode] = useState<PreviewViewMode>('popup');
  const [device, setDevice] = useState<PreviewDevice>('desktop');
  const [fullscreen, setFullscreen] = useState(false);

  useEffect(() => {
    if (!fullscreen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setFullscreen(false);
    };
    document.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [fullscreen]);

  const toolbar = (
    <div className="slr-appearance-preview-toolbar">
      <div className="slr-appearance-preview-toolbar__group">
        <span className="slr-appearance-preview-toolbar__label">Form</span>
        <div className="slr-tabs-inline">
          <button type="button" className={formMode === 'login' ? 'active' : ''} onClick={() => setFormMode('login')}>
            <Icon icon={LogIn} size={14} />
            Login
          </button>
          <button type="button" className={formMode === 'register' ? 'active' : ''} onClick={() => setFormMode('register')}>
            <Icon icon={UserRoundPlus} size={14} />
            Register
          </button>
        </div>
      </div>

      <div className="slr-appearance-preview-toolbar__group">
        <span className="slr-appearance-preview-toolbar__label">View</span>
        <div className="slr-tabs-inline">
          <button type="button" className={viewMode === 'popup' ? 'active' : ''} onClick={() => setViewMode('popup')}>
            Popup
          </button>
          <button type="button" className={viewMode === 'page' ? 'active' : ''} onClick={() => setViewMode('page')}>
            Dedicated page
          </button>
        </div>
      </div>

      <div className="slr-appearance-preview-toolbar__group">
        <span className="slr-appearance-preview-toolbar__label">Device</span>
        <div className="slr-tabs-inline">
          <button type="button" className={device === 'desktop' ? 'active' : ''} onClick={() => setDevice('desktop')}>
            <Icon icon={Monitor} size={14} />
            Desktop
          </button>
          <button type="button" className={device === 'mobile' ? 'active' : ''} onClick={() => setDevice('mobile')}>
            <Icon icon={Smartphone} size={14} />
            Mobile
          </button>
        </div>
      </div>

      <button
        type="button"
        className="slr-appearance-preview-toolbar__fullscreen"
        onClick={() => setFullscreen(true)}
        title="Fullscreen preview"
      >
        <Icon icon={Maximize2} size={16} />
        Fullscreen
      </button>
    </div>
  );

  return (
    <>
      {toolbar}
      <div className="slr-appearance-preview__stage">
        <PreviewIframe appearance={appearance} formMode={formMode} viewMode={viewMode} device={device} />
      </div>

      {fullscreen &&
        createPortal(
          <div className="slr-appearance-preview-fullscreen" role="dialog" aria-modal="true">
            <div className="slr-appearance-preview-fullscreen__bar">
              <span>Live preview — updates as you edit</span>
              <button type="button" onClick={() => setFullscreen(false)}>
                <Icon icon={Minimize2} size={16} />
                Exit fullscreen
              </button>
            </div>
            <div className="slr-appearance-preview-fullscreen__body">
              <PreviewIframe appearance={appearance} formMode={formMode} viewMode={viewMode} device={device} />
            </div>
          </div>,
          document.body
        )}
    </>
  );
}
