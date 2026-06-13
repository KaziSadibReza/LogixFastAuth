import { apiRequest } from '@shared/api';
import type { SlrSettings } from '@shared/types';

function getAdmin() {
  return window.SLR_ADMIN!;
}

export function fetchSettings(): Promise<SlrSettings> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings');
}

export function saveSettings(settings: Partial<SlrSettings>): Promise<SlrSettings> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings', {
    method: 'PUT',
    body: JSON.stringify(settings),
  });
}

export function testSmtp(email: string): Promise<{ success: boolean }> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/test-smtp', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });
}

export function getGoogleOAuthUrl(): Promise<{ url: string }> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/google/oauth-url');
}

export function disconnectGoogle(): Promise<{ success: boolean }> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/google/disconnect', {
    method: 'POST',
  });
}

export interface PageOption {
  id: number;
  title: string;
  url: string;
  is_slr?: boolean;
}

export interface LoginPageResult {
  id: number;
  title: string;
  url: string;
  created: boolean;
}

export function ensureLoginPage(): Promise<LoginPageResult> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/login-page', { method: 'POST' });
}

export function fetchPages(): Promise<PageOption[]> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/pages');
}

export function fetchSmsProviders(): Promise<{ name: string }[]> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/sms-providers');
}

export interface DashboardStats {
  total_users: number;
  new_users_today: number;
  slr_logins_total: number;
  slr_logins_today: number;
  slr_registrations_total: number;
  slr_registrations_today: number;
  active_integrations: number;
  dedicated_page_set: boolean;
  dedicated_page_title: string;
  plugin_version: string;
}

export function fetchStats(): Promise<DashboardStats> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/stats');
}
