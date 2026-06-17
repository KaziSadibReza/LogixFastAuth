import { useCallback, useEffect, useRef, useState } from 'react';
import { X } from 'lucide-react';
import type { SlrConfig, SlrMode } from '@shared/types';
import { LoginForm } from './LoginForm';
import { RegisterForm } from './RegisterForm';
import { OtpForm } from './OtpForm';
import { ForgotPasswordForm } from './ForgotPasswordForm';
import { ResetPasswordForm } from './ResetPasswordForm';
import { PasskeySetup } from './PasskeySetup';
import { SlrTabSwitch } from './SlrTabSwitch';
import { SlrToastProvider } from './SlrToaster';
import { applyAuthRedirect, type AuthRedirectResult } from '../utils/redirect';
import { consumePendingOpen, markPopupReady } from '../slr-bridge';
import {
  clearOtpSession,
  getActiveOtpSession,
  getActiveOtpSessionForPurpose,
  loadFormSession,
  saveFormSession,
  saveOtpSession,
  type OtpPurpose,
  type SlrOtpSession,
} from '../utils/formStorage';

interface SlrAppProps {
  config: SlrConfig;
  initialOpen?: boolean;
  initialMode?: SlrMode;
}

type View = 'form' | 'otp' | 'forgot' | 'reset' | 'passkey-setup';

const PASSKEY_DISMISSED_KEY = 'slr_passkey_dismissed';

async function browserSupportsPasskey(): Promise<boolean> {
  if (
    typeof window === 'undefined' ||
    !window.PublicKeyCredential ||
    typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function'
  ) {
    return false;
  }
  try {
    return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
  } catch {
    return false;
  }
}

function wasPasskeyDismissed(): boolean {
  try {
    return window.localStorage.getItem(PASSKEY_DISMISSED_KEY) === '1';
  } catch {
    return false;
  }
}

function dismissPasskey(): void {
  try {
    window.localStorage.setItem(PASSKEY_DISMISSED_KEY, '1');
  } catch {
    // ignore
  }
}

function buildOtpSession(
  identifier: string,
  channel: string,
  purpose: OtpPurpose,
  mode: SlrMode,
  config: SlrConfig,
  pendingToken?: string,
  sessionExpiresAt?: number
): SlrOtpSession {
  const loginMs = (config.otpTtl || 600) * 1000;
  const registerMs = (config.registrationSessionTtl || config.otpTtl * 6 || 3600) * 1000;
  const fallbackMs = purpose === 'login' || purpose === 'reset' ? loginMs : registerMs;
  return {
    identifier,
    channel,
    pendingToken,
    purpose,
    mode,
    expiresAt: sessionExpiresAt ? sessionExpiresAt * 1000 : Date.now() + fallbackMs,
  };
}

export function SlrApp({ config, initialOpen = false, initialMode }: SlrAppProps) {
  const saved = loadFormSession();
  const activeOtp = getActiveOtpSession();

  const [open, setOpen] = useState(initialOpen);
  const [mode, setMode] = useState<SlrMode>(activeOtp?.mode || initialMode || config.defaultMode || 'login');
  const [view, setView] = useState<View>(activeOtp ? 'otp' : 'form');
  const [otpState, setOtpState] = useState<SlrOtpSession | null>(activeOtp);
  const [resetToken, setResetToken] = useState(saved.resetToken || '');
  const [loginFormKey, setLoginFormKey] = useState(0);
  const pendingRedirectRef = useRef<{ result: AuthRedirectResult; kind: 'login' | 'register' } | null>(null);
  const overlayRef = useRef<HTMLDivElement>(null);
  const cardRef = useRef<HTMLDivElement>(null);

  const persistOtp = useCallback((session: SlrOtpSession | null) => {
    setOtpState(session);
    saveOtpSession(session);
  }, []);

  const closePopup = useCallback(() => {
    setOpen(false);
    window.SLR?.close();
  }, []);

  const handlePasskeyDone = useCallback(() => {
    const pending = pendingRedirectRef.current;
    pendingRedirectRef.current = null;
    if (pending) {
      applyAuthRedirect(config, pending.result, pending.kind, closePopup);
      return;
    }
    closePopup();
    window.location.reload();
  }, [config, closePopup]);

  const handlePasskeySkip = useCallback(() => {
    dismissPasskey();
    handlePasskeyDone();
  }, [handlePasskeyDone]);

  const handleClose = useCallback(() => {
    if (view === 'passkey-setup' && pendingRedirectRef.current) {
      handlePasskeyDone();
      return;
    }
    if (config.isDedicated) {
      window.location.href = config.homeUrl;
      return;
    }
    closePopup();
  }, [config, closePopup, view, handlePasskeyDone]);

  const doRedirect = useCallback(
    (result: AuthRedirectResult, kind: 'login' | 'register') => {
      applyAuthRedirect(config, result, kind, closePopup);
    },
    [config, closePopup]
  );

  const maybeOfferPasskey = useCallback(
    async (result: AuthRedirectResult, kind: 'login' | 'register') => {
      clearOtpSession();

      if (result.nonce && window.SLR_CONFIG) {
        window.SLR_CONFIG.nonce = result.nonce;
      }

      if (!config.auth.webauthn || wasPasskeyDismissed() || result.has_passkeys) {
        doRedirect(result, kind);
        return;
      }

      const supported = await browserSupportsPasskey();
      if (!supported) {
        doRedirect(result, kind);
        return;
      }

      pendingRedirectRef.current = { result, kind };
      setView('passkey-setup');
    },
    [config, doRedirect]
  );

  const handleLoginSuccess = useCallback(
    (result: AuthRedirectResult) => {
      maybeOfferPasskey(result, 'login');
    },
    [maybeOfferPasskey]
  );

  const handleRegisterSuccess = useCallback(
    (result: AuthRedirectResult) => {
      maybeOfferPasskey(result, 'register');
    },
    [maybeOfferPasskey]
  );

  const resumeOtp = useCallback((session: SlrOtpSession) => {
    setOtpState(session);
    setMode(session.mode);
    persistOtp(session);
    setView('otp');
  }, [persistOtp]);

  const handleOtpRequired = useCallback(
    (identifier: string, channel: string, purpose: OtpPurpose, pendingToken?: string, sessionExpiresAt?: number) => {
      const session = buildOtpSession(identifier, channel, purpose, mode, config, pendingToken, sessionExpiresAt);
      persistOtp(session);
      setView('otp');
    },
    [config, mode, persistOtp]
  );

  const handleForgotOtpRequired = useCallback(
    (identifier: string, channel: string) => {
      const existing = getActiveOtpSessionForPurpose('reset');
      if (existing && existing.identifier === identifier && existing.channel === channel) {
        resumeOtp(existing);
        return;
      }
      handleOtpRequired(identifier, channel, 'reset');
    },
    [handleOtpRequired, resumeOtp]
  );

  const handleForgotPassword = useCallback(() => {
    const pending = getActiveOtpSessionForPurpose('reset');
    if (pending) {
      resumeOtp(pending);
      return;
    }
    setView('forgot');
  }, [resumeOtp]);

  const handleSessionExpired = useCallback(() => {
    clearOtpSession();
    persistOtp(null);
    setOtpState(null);
    setMode('register');
    setView('form');
  }, [persistOtp]);

  const handleOtpSuccess = useCallback(
    (result: AuthRedirectResult) => {
      if (result.requiresPasswordReset && result.reset_token) {
        clearOtpSession();
        persistOtp(null);
        setResetToken(result.reset_token);
        saveFormSession({ resetToken: result.reset_token });
        setView('reset');
        return;
      }

      if (otpState?.purpose === 'register') {
        handleRegisterSuccess(result);
        return;
      }

      handleLoginSuccess(result);
    },
    [handleLoginSuccess, handleRegisterSuccess, otpState?.purpose, persistOtp]
  );

  useEffect(() => {
    if (config.isDedicated) return;

    markPopupReady();
    const pending = consumePendingOpen();
    if (pending) {
      setMode(pending);
      setView('form');
      setOpen(true);
    }
  }, [config.isDedicated]);

  useEffect(() => {
    const onOpen = (e: Event) => {
      const detail = (e as CustomEvent).detail as { mode?: SlrMode };
      const pending = getActiveOtpSession();

      if (pending) {
        resumeOtp(pending);
      } else {
        setMode(detail?.mode || 'login');
        setView('form');
      }

      setOpen(true);
    };
    const onClose = () => closePopup();

    window.addEventListener('slr:open', onOpen);
    window.addEventListener('slr:close', onClose);
    return () => {
      window.removeEventListener('slr:open', onOpen);
      window.removeEventListener('slr:close', onClose);
    };
  }, [closePopup, resumeOtp]);

  useEffect(() => {
    if (!open) return;

    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') handleClose();
    };

    document.addEventListener('keydown', handleKey);
    document.body.style.overflow = 'hidden';
    setTimeout(() => cardRef.current?.querySelector<HTMLElement>('input, button')?.focus(), 50);

    return () => {
      document.removeEventListener('keydown', handleKey);
      document.body.style.overflow = '';
    };
  }, [open, handleClose]);

  useEffect(() => {
    const handleTrap = (e: KeyboardEvent) => {
      if (e.key !== 'Tab' || !cardRef.current) return;
      const focusable = cardRef.current.querySelectorAll<HTMLElement>(
        'button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    };

    if (open) document.addEventListener('keydown', handleTrap);
    return () => document.removeEventListener('keydown', handleTrap);
  }, [open]);

  if (!open && !config.isDedicated) {
    return null;
  }

  const overlayClass = [
    'slr-overlay',
    'slr-overlay--glass',
    config.isDedicated ? 'slr-overlay--page' : '',
  ]
    .filter(Boolean)
    .join(' ');

  const pendingOtp = view === 'form' ? getActiveOtpSession() : null;

  return (
    <SlrToastProvider>
    <div
      ref={overlayRef}
      className={overlayClass}
      role="dialog"
      aria-modal="true"
      aria-labelledby="slr-title"
      aria-hidden={!open && !config.isDedicated ? 'true' : 'false'}
      onClick={(e) => { if (e.target === overlayRef.current && !config.isDedicated) handleClose(); }}
    >
      <div ref={cardRef} className="slr-card">
        <button type="button" className="slr-close" onClick={handleClose} aria-label={config.i18n.close}>
          <X size={20} strokeWidth={2} aria-hidden="true" />
        </button>

        {view === 'form' && (
          <>
            <h2 id="slr-title" className="slr-title">
              {mode === 'login' ? (config.i18n.loginTitle || 'Welcome back') : (config.i18n.registerTitle || 'Create your account')}
            </h2>
            <p className="slr-subtitle">
              {mode === 'login'
                ? (config.i18n.loginSubtitle || 'Sign in to continue.')
                : (config.i18n.registerSubtitle || 'Just a few details to get started.')}
            </p>

            {pendingOtp && (
              <div className="slr-otp-resume">
                <p>
                  {config.i18n.otpInProgress || 'Verification in progress for'}{' '}
                  <strong>{pendingOtp.identifier}</strong>
                </p>
                <button
                  type="button"
                  className="slr-btn slr-btn--ghost slr-btn--sm"
                  onClick={() => resumeOtp(pendingOtp)}
                >
                  {config.i18n.continueOtp || 'Continue verification'}
                </button>
              </div>
            )}

            <SlrTabSwitch
              loginLabel={config.i18n.login}
              registerLabel={config.i18n.register}
              mode={mode}
              onChange={setMode}
            />

            <div key={mode} className="slr-form-panel">
              {mode === 'login' ? (
                <LoginForm
                  key={`login-${loginFormKey}`}
                  config={config}
                  passwordAutoComplete={loginFormKey > 0 ? 'new-password' : 'current-password'}
                  onOtpRequired={(id, ch) => {
                    const existing = getActiveOtpSessionForPurpose('login');
                    if (existing && existing.identifier === id && existing.channel === ch) {
                      resumeOtp(existing);
                      return;
                    }
                    handleOtpRequired(id, ch, 'login');
                  }}
                  onForgotPassword={handleForgotPassword}
                  onSuccess={handleLoginSuccess}
                />
              ) : (
                <RegisterForm
                  config={config}
                  onOtpRequired={(id, ch, token, expiresAt) => handleOtpRequired(id, ch, 'register', token, expiresAt)}
                  onSuccess={handleRegisterSuccess}
                />
              )}
            </div>
          </>
        )}

        {view === 'forgot' && (
          <ForgotPasswordForm
            config={config}
            initialEmail={loadFormSession().loginEmail}
            onOtpRequired={handleForgotOtpRequired}
            onResumeOtp={() => {
              const pending = getActiveOtpSessionForPurpose('reset');
              if (pending) resumeOtp(pending);
            }}
            onBack={() => { setMode('login'); setView('form'); }}
          />
        )}

        {view === 'otp' && otpState && (
          <OtpForm
            config={config}
            identifier={otpState.identifier}
            channel={otpState.channel}
            pendingToken={otpState.pendingToken}
            purpose={otpState.purpose}
            onSuccess={handleOtpSuccess}
            onBack={() => {
              if (otpState.purpose === 'reset') {
                setView('forgot');
                return;
              }
              setView('form');
            }}
            onSessionExpired={handleSessionExpired}
          />
        )}

        {view === 'reset' && resetToken && (
          <ResetPasswordForm
            config={config}
            resetToken={resetToken}
            onSuccess={(result) => {
              setResetToken('');
              saveFormSession({ resetToken: '' });
              if (result.user_id) {
                handleLoginSuccess(result);
                return;
              }
              setLoginFormKey((key) => key + 1);
              setMode('login');
              setView('form');
            }}
          />
        )}

        {view === 'passkey-setup' && (
          <PasskeySetup
            config={config}
            onDone={handlePasskeyDone}
            onSkip={handlePasskeySkip}
          />
        )}
      </div>
    </div>
    </SlrToastProvider>
  );
}
