export interface LogixFastAuthStyle {
  primary: string;
  background: string;
  text: string;
  blur: string;
  radius: string;
  spacing: string;
}

export interface LogixFastAuthAuthConfig {
  emailOtp: boolean;
  phoneOtp: boolean;
  webauthn: boolean;
  otpLogin: boolean;
  requirePhone: boolean;
  hasSmsProvider: boolean;
}

export interface LogixFastAuthConfig {
  apiUrl: string;
  nonce: string;
  homeUrl: string;
  assets?: {
    popupJs: string;
    popupCss: string;
  };
  isDedicated: boolean;
  dedicatedLoginUrl?: string;
  redirectTo?: string;
  integrations?: {
    replaceTutor: boolean;
    replaceElementor: boolean;
  };
  defaultMode: 'login' | 'register';
  auth: LogixFastAuthAuthConfig;
  otpTtl: number;
  registrationSessionTtl: number;
  style: LogixFastAuthStyle;
  redirects: {
    login: LogixFastAuthRedirectRule;
    register: LogixFastAuthRedirectRule;
  };
  i18n: Record<string, string>;
}

export interface LogixFastAuthAdminConfig {
  apiUrl: string;
  nonce: string;
  homeUrl: string;
  profilePasskeysUrl: string;
  pages: string;
  frontendCssUrls?: string[];
  i18n: Record<string, string>;
}

export interface LogixFastAuthSettings {
  general: {
    dedicated_page_id: number;
    default_mode: string;
    login_redirect_type: LogixFastAuthRedirectType;
    login_redirect_page_id: number;
    login_redirect_url: string;
    register_redirect_type: LogixFastAuthRedirectType;
    register_redirect_page_id: number;
    register_redirect_url: string;
    login_page_logged_in_redirect_type: LogixFastAuthLoggedInRedirectType;
    login_page_logged_in_redirect_page_id: number;
    login_page_logged_in_redirect_url: string;
    honeypot_enabled: boolean;
  };
  auth: {
    email_otp_enabled: boolean;
    phone_otp_enabled: boolean;
    webauthn_enabled: boolean;
    otp_login_enabled: boolean;
    require_phone: boolean;
    has_sms_provider?: boolean;
    has_tutor_lms?: boolean;
  };
  mail: {
    transport: string;
    smtp_host: string;
    smtp_port: number;
    smtp_encryption: string;
    smtp_user: string;
    smtp_pass: string;
    from_email: string;
    from_name: string;
    google_connected: boolean;
    google_refresh_token: string;
    google_client_id?: string;
    google_client_secret?: string;
    google_account_email?: string;
    google_configured?: boolean;
    google_redirect_uri?: string;
  };
  mail_smtp_conflicts?: Array<{ slug: string; name: string }>;
  integrations: {
    replace_wp_login: boolean;
    replace_woocommerce: boolean;
    replace_tutor: boolean;
    replace_elementor: boolean;
  };
  appearance: LogixFastAuthStyle;
  security: {
    rate_limit_attempts: number;
    rate_limit_window: number;
    otp_ttl: number;
    otp_resend_cooldown: number;
    otp_max_attempts: number;
    webauthn_rp_id: string;
  };
  integration_plugins?: {
    wordpress: boolean;
    woocommerce: boolean;
    tutor: boolean;
    elementor: boolean;
  };
  passkey_manage_urls?: {
    profile: string;
    tutor?: string;
    woocommerce?: string;
  };
  phone_sync_preview?: {
    users_with_phone: number;
    woocommerce: { available: boolean; pending: number };
    tutor: { available: boolean; pending: number };
  };
}

export type LogixFastAuthRedirectType = 'stay' | 'default' | 'page' | 'url';

export type LogixFastAuthLoggedInRedirectType = 'default' | 'page' | 'url';

export interface LogixFastAuthRedirectRule {
  type: LogixFastAuthRedirectType;
  url: string;
  page_id: number;
  page_url: string;
}

export type LogixFastAuthMode = 'login' | 'register';

declare global {
  interface Window {
    LOGIXFAST_AUTH_CONFIG?: LogixFastAuthConfig;
    LOGIXFAST_AUTH_ADMIN?: LogixFastAuthAdminConfig;
    LogixFastAuth?: {
      open: (mode: LogixFastAuthMode) => void;
      close: () => void;
    };
  }
}
