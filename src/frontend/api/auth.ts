import { apiRequest } from '@shared/api';
import type { SlrConfig } from '@shared/types';

export interface AuthResponse {
  user_id?: number;
  nonce?: string;
  has_passkeys?: boolean;
  redirect?: string;
  redirect_action?: 'stay' | 'navigate';
  requiresOtp?: boolean;
  otpChannel?: string;
  pending_token?: string;
  email?: string;
  session_expires_at?: number;
  requiresPasswordReset?: boolean;
  reset_token?: string;
}

export interface RegisterData {
  full_name: string;
  email: string;
  phone: string;
  password: string;
  preferred_channel?: 'email' | 'phone';
  slr_hp?: string;
}

export interface LoginData {
  email?: string;
  phone?: string;
  password?: string;
  channel?: 'email' | 'phone';
  remember?: boolean;
  slr_hp?: string;
}

function getConfig(): SlrConfig {
  return window.SLR_CONFIG!;
}

export function register(data: RegisterData): Promise<AuthResponse> {
  const config = getConfig();
  return apiRequest(config.apiUrl, config.nonce, '/auth/register', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export function login(data: LoginData): Promise<AuthResponse> {
  const config = getConfig();
  return apiRequest(config.apiUrl, config.nonce, '/auth/login', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export interface VerifyOtpOptions {
  pendingToken?: string;
  purpose?: string;
}

export function verifyOtp(
  identifier: string,
  code: string,
  channel: string,
  options?: VerifyOtpOptions
): Promise<AuthResponse> {
  const config = getConfig();
  return apiRequest(config.apiUrl, config.nonce, '/otp/verify', {
    method: 'POST',
    body: JSON.stringify({
      identifier,
      code,
      channel,
      pending_token: options?.pendingToken,
      purpose: options?.purpose,
    }),
  });
}

export function forgotPassword(data: { email?: string; phone?: string }): Promise<{ sent: boolean; channel: string }> {
  const config = getConfig();
  return apiRequest(config.apiUrl, config.nonce, '/auth/forgot-password', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export function resetPassword(resetToken: string, password: string): Promise<AuthResponse> {
  const config = getConfig();
  return apiRequest(config.apiUrl, config.nonce, '/auth/reset-password', {
    method: 'POST',
    body: JSON.stringify({ reset_token: resetToken, password }),
  });
}

export function resendOtp(
  identifier: string,
  channel: string,
  purpose: string,
  pendingToken?: string
): Promise<{ sent: boolean; session_expires_at?: number; pending_token?: string }> {
  const config = getConfig();
  return apiRequest(config.apiUrl, config.nonce, '/otp/send', {
    method: 'POST',
    body: JSON.stringify({ identifier, channel, purpose, pending_token: pendingToken }),
  });
}

export async function webauthnRegister(): Promise<{ success: boolean }> {
  const config = getConfig();
  const { startRegistration } = await import('@simplewebauthn/browser');

  const optionsRes = await apiRequest<{
    challenge: string;
    rp: { name: string; id: string };
    user: { id: string; name: string; displayName: string };
    pubKeyCredParams: { type: string; alg: number }[];
    timeout: number;
    attestation: string;
  }>(config.apiUrl, config.nonce, '/webauthn/register/options', {
    method: 'POST',
  });

  const regResp = await startRegistration({ optionsJSON: optionsRes as never });

  return apiRequest(config.apiUrl, config.nonce, '/webauthn/register/verify', {
    method: 'POST',
    body: JSON.stringify(regResp),
  });
}

export async function webauthnLogin(email?: string): Promise<AuthResponse> {
  const config = getConfig();
  const { startAuthentication } = await import('@simplewebauthn/browser');

  const optionsRes = await apiRequest<{ options: PublicKeyCredentialRequestOptions; sessionKey: string }>(
    config.apiUrl,
    config.nonce,
    '/webauthn/login/options',
    { method: 'POST', body: JSON.stringify({ email }) }
  );

  const authResp = await startAuthentication({ optionsJSON: optionsRes.options as never });

  return apiRequest(config.apiUrl, config.nonce, '/webauthn/login/verify', {
    method: 'POST',
    body: JSON.stringify({ sessionKey: optionsRes.sessionKey, response: authResp }),
  });
}
