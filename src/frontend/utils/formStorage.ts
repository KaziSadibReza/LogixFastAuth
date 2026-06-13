import type { SlrMode } from '@shared/types';

const STORAGE_KEY = 'slr_form_session';

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
  otp: SlrOtpSession | null;
  resetToken: string;
}

const defaultSession = (): SlrFormSession => ({
  loginEmail: '',
  register: { fullName: '', email: '', phone: '' },
  forgot: { email: '', phone: '', usePhone: false },
  otp: null,
  resetToken: '',
});

export function loadFormSession(): SlrFormSession {
  if (typeof window === 'undefined') return defaultSession();
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return defaultSession();
    const parsed = JSON.parse(raw) as Partial<SlrFormSession>;
    return {
      ...defaultSession(),
      ...parsed,
      register: { ...defaultSession().register, ...parsed.register },
      forgot: { ...defaultSession().forgot, ...parsed.forgot },
    };
  } catch {
    return defaultSession();
  }
}

export function saveFormSession(patch: Partial<SlrFormSession>): SlrFormSession {
  const next = { ...loadFormSession(), ...patch };
  if (patch.register) {
    next.register = { ...loadFormSession().register, ...patch.register };
  }
  if (patch.forgot) {
    next.forgot = { ...loadFormSession().forgot, ...patch.forgot };
  }
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  return next;
}

export function saveOtpSession(session: SlrOtpSession | null): void {
  saveFormSession({ otp: session });
}

export function getActiveOtpSession(): SlrOtpSession | null {
  const { otp } = loadFormSession();
  if (!otp || otp.expiresAt <= Date.now()) {
    if (otp) saveOtpSession(null);
    return null;
  }
  return otp;
}

export function getActiveOtpSessionForPurpose(purpose: OtpPurpose): SlrOtpSession | null {
  const session = getActiveOtpSession();
  return session?.purpose === purpose ? session : null;
}

export function extendOtpSession(expiresAt: number): void {
  const { otp } = loadFormSession();
  if (!otp) return;
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
  const { otp } = loadFormSession();
  if (!otp || otp.identifier !== identifier || otp.channel !== channel) {
    return undefined;
  }
  return otp.pendingToken;
}

export function clearOtpSession(): void {
  saveOtpSession(null);
}

export function clearResetToken(): void {
  saveFormSession({ resetToken: '' });
}
