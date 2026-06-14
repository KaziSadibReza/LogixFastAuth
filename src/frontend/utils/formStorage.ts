import type { SlrMode } from '@shared/types';

const FORM_STORAGE_KEY = 'slr_form_session';
const SECURE_STORAGE_KEY = 'slr_secure_session';

export type OtpPurpose = 'register' | 'login' | 'reset';

export interface SlrOtpSession {
  identifier: string;
  channel: string;
  pendingToken?: string;
  purpose: OtpPurpose;
  mode: SlrMode;
  expiresAt: number;
}

export interface SlrFormSession {
  loginEmail: string;
  register: {
    fullName: string;
    email: string;
    phone: string;
  };
  forgot: {
    email: string;
    phone: string;
    usePhone: boolean;
  };
}

interface SlrSecureSession {
  otp: SlrOtpSession | null;
  resetToken: string;
}

const defaultFormSession = (): SlrFormSession => ({
  loginEmail: '',
  register: { fullName: '', email: '', phone: '' },
  forgot: { email: '', phone: '', usePhone: false },
});

const defaultSecureSession = (): SlrSecureSession => ({
  otp: null,
  resetToken: '',
});

function readStorage<T>(key: string, storage: Storage, fallback: () => T): T {
  if (typeof window === 'undefined') {
    return fallback();
  }

  try {
    const raw = storage.getItem(key);
    if (!raw) {
      return fallback();
    }

    return { ...fallback(), ...(JSON.parse(raw) as Partial<T>) };
  } catch {
    return fallback();
  }
}

function writeStorage<T>(key: string, storage: Storage, value: T): void {
  if (typeof window === 'undefined') {
    return;
  }

  storage.setItem(key, JSON.stringify(value));
}

function loadSecureSession(): SlrSecureSession {
  return readStorage(SECURE_STORAGE_KEY, window.sessionStorage, defaultSecureSession);
}

function saveSecureSession(patch: Partial<SlrSecureSession>): SlrSecureSession {
  const next = { ...loadSecureSession(), ...patch };
  writeStorage(SECURE_STORAGE_KEY, window.sessionStorage, next);
  return next;
}

export function loadFormSession(): SlrFormSession & SlrSecureSession {
  const form = readStorage(FORM_STORAGE_KEY, window.localStorage, defaultFormSession);
  const secure = typeof window === 'undefined' ? defaultSecureSession() : loadSecureSession();

  return {
    ...form,
    register: { ...defaultFormSession().register, ...form.register },
    forgot: { ...defaultFormSession().forgot, ...form.forgot },
    otp: secure.otp,
    resetToken: secure.resetToken,
  };
}

export function saveFormSession(patch: Partial<SlrFormSession & SlrSecureSession>): SlrFormSession & SlrSecureSession {
  const current = loadFormSession();
  const next = { ...current, ...patch };

  if (patch.register) {
    next.register = { ...current.register, ...patch.register };
  }

  if (patch.forgot) {
    next.forgot = { ...current.forgot, ...patch.forgot };
  }

  writeStorage(FORM_STORAGE_KEY, window.localStorage, {
    loginEmail: next.loginEmail,
    register: next.register,
    forgot: next.forgot,
  });

  saveSecureSession({
    otp: 'otp' in patch ? patch.otp ?? null : current.otp,
    resetToken: 'resetToken' in patch ? patch.resetToken ?? '' : current.resetToken,
  });

  return next;
}

export function saveOtpSession(session: SlrOtpSession | null): void {
  saveSecureSession({ otp: session });
}

export function getActiveOtpSession(): SlrOtpSession | null {
  const { otp } = loadSecureSession();
  if (!otp || otp.expiresAt <= Date.now()) {
    if (otp) {
      saveOtpSession(null);
    }
    return null;
  }
  return otp;
}

export function getActiveOtpSessionForPurpose(purpose: OtpPurpose): SlrOtpSession | null {
  const session = getActiveOtpSession();
  return session?.purpose === purpose ? session : null;
}

export function extendOtpSession(expiresAt: number): void {
  const { otp } = loadSecureSession();
  if (!otp) {
    return;
  }
  saveOtpSession({ ...otp, expiresAt });
}

export function isSessionExpiredError(message: string): boolean {
  const lower = message.toLowerCase();
  return (
    lower.includes('registration session expired') ||
    lower.includes('session expired') ||
    lower.includes('please sign up again') ||
    lower.includes('please create your account again')
  );
}

export function getStoredPendingToken(identifier: string, channel: string): string | undefined {
  const { otp } = loadSecureSession();
  if (!otp || otp.identifier !== identifier || otp.channel !== channel) {
    return undefined;
  }
  return otp.pendingToken;
}

export function clearOtpSession(): void {
  saveOtpSession(null);
}

export function clearResetToken(): void {
  saveSecureSession({ resetToken: '' });
}
