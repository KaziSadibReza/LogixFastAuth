import type { SlrConfig } from '@shared/types';

export type RedirectAction = 'stay' | 'navigate';

export interface AuthRedirectResult {
  user_id?: number;
  redirect?: string;
  redirect_action?: RedirectAction;
  nonce?: string;
  has_passkeys?: boolean;
  requiresPasswordReset?: boolean;
  reset_token?: string;
}

const REDIRECT_TYPES = ['stay', 'default', 'page', 'url'] as const;
export type RedirectType = (typeof REDIRECT_TYPES)[number];

function isSamePageUrl(url: string): boolean {
  try {
    const dest = new URL(url, window.location.origin);
    return dest.origin === window.location.origin && dest.pathname === window.location.pathname;
  } catch {
    return false;
  }
}

export function applyAuthRedirect(
  config: SlrConfig,
  result: AuthRedirectResult,
  kind: 'login' | 'register',
  onClosePopup?: () => void
): void {
  if (kind === 'login' && config.redirectTo) {
    window.location.href = config.redirectTo;
    return;
  }

  const action = result.redirect_action || 'navigate';
  const rule = config.redirects[kind];

  if (action === 'stay' || rule?.type === 'stay') {
    if (config.isDedicated) {
      window.location.reload();
      return;
    }
    onClosePopup?.();
    window.location.reload();
    return;
  }

  let target = result.redirect || '';

  if (!target && rule) {
    if (rule.type === 'page' && rule.page_url) {
      target = rule.page_url;
    } else if (rule.type === 'url' && rule.url) {
      target = rule.url;
    }
  }

  const destination = target || config.homeUrl;

  if (!config.isDedicated && isSamePageUrl(destination)) {
    onClosePopup?.();
    window.location.reload();
    return;
  }

  window.location.href = destination;
}
