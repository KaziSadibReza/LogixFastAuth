export interface SlrStyle {
  primary: string;
  background: string;
  text: string;
  blur: string;
  radius: string;
  spacing: string;
}

export interface SlrAuthConfig {
  emailOtp: boolean;
  phoneOtp: boolean;
  webauthn: boolean;
  otpLogin: boolean;
  requirePhone: boolean;
  hasSmsProvider: boolean;
}

export interface SlrConfig {
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
  };
  defaultMode: 'login' | 'register';
  auth: SlrAuthConfig;
  otpTtl: number;
  registrationSessionTtl: number;
  style: SlrStyle;
  redirects: {
    login: SlrRedirectRule;
    register: SlrRedirectRule;
  };
  i18n: Record<string, string>;
}

export interface SlrAdminConfig {
  apiUrl: string;
  nonce: string;
  homeUrl: string;
  pages: string;
  frontendCssUrls?: string[];
  i18n: Record<string, string>;
}

export interface SlrSettings {
  general: {
    dedicated_page_id: number;
    default_mode: string;
    login_redirect_type: SlrRedirectType;
    login_redirect_page_id: number;
    login_redirect_url: string;
    register_redirect_type: SlrRedirectType;
    register_redirect_page_id: number;
    register_redirect_url: string;
    login_page_logged_in_redirect_type: SlrLoggedInRedirectType;
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
  integrations: {
    replace_wp_login: boolean;
    replace_woocommerce: boolean;
    replace_tutor: boolean;
    replace_elementor: boolean;
  };
  appearance: SlrStyle;
  security: {
    rate_limit_attempts: number;
    rate_limit_window: number;
    otp_ttl: number;
    otp_resend_cooldown: number;
    otp_max_attempts: number;
    webauthn_rp_id: string;
  };
}

export type SlrRedirectType = 'stay' | 'default' | 'page' | 'url';

export type SlrLoggedInRedirectType = 'default' | 'page' | 'url';

export interface SlrRedirectRule {
  type: SlrRedirectType;
  url: string;
  page_id: number;
  page_url: string;
}

export type SlrMode = 'login' | 'register';

declare global {
  interface Window {
    SLR_CONFIG?: SlrConfig;
    SLR_ADMIN?: SlrAdminConfig;
    SLR?: {
      open: (mode: SlrMode) => void;
      close: () => void;
    };
  }
}
