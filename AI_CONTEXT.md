# LogixFast Auth AI Context

This file gives AI coding assistants a fast, accurate map of the main plugin. Start here before editing.

## Plugin Summary

LogixFast Auth is a WordPress authentication plugin that provides a React login/registration UI, OTP verification, passkeys/WebAuthn, Google OAuth/mail support, and replacement login flows for WordPress, WooCommerce, Tutor LMS, and Elementor.

The public plugin bootstrap is `logixfast-auth.php`. It defines the `LOGIXFAST_AUTH_*` constants, loads Composer and the LogixFastAuth autoloader, registers activation/deactivation hooks, then starts the plugin with `LogixFastAuth\Plugin::instance()` on `plugins_loaded`.

## Runtime Flow

1. `logixfast-auth.php` loads constants, dependencies, and `LogixFastAuth\Plugin`.
2. `includes/class-logixfast-auth-plugin.php` runs `Activator::maybe_upgrade()` and constructs the main subsystems.
3. Admin-only code loads when `is_admin()` is true: menu, admin assets, admin shell, and plugin lifecycle notices.
4. Global runtime code always loads profiles, passkey assets, mail service, REST routes, frontend assets, popup, dedicated login page, redirects, third-party integrations, and login-page service.
5. React frontend/admin bundles talk to WordPress through REST routes under `logixfast-auth/v1`.

## Important PHP Entry Points

- `includes/class-logixfast-auth-plugin.php`: main object graph and hook registration.
- `includes/class-logixfast-auth-settings.php`: default settings, persisted `logixfast_auth_settings`, public frontend config, and frontend i18n strings.
- `includes/class-logixfast-auth-assets.php`: Vite manifest resolution, dev-server support, ES module script handling, and asset URL helpers.
- `includes/Frontend/Frontend_Assets.php`: registers/enqueues frontend bootstrap, popup, page bundles, font, and CSS custom properties.
- `includes/Frontend/Popup.php`: prints the popup root and early Tutor click guard.
- `includes/Frontend/Dedicated_Page.php`: renders the dedicated login page template.
- `includes/Admin/class-logixfast-auth-admin-menu.php`: adds the `LogixFastAuth` top-level admin menu and admin SPA mount.
- `includes/Admin/class-logixfast-auth-admin-assets.php`: loads the admin React SPA and localizes `window.LOGIXFAST_AUTH_ADMIN`.
- `includes/Api/Rest_Controller.php`: registers all REST controllers.
- `includes/Services/AuthService.php`: core user registration, login, phone/email validation, and authentication behavior.
- `includes/Services/OtpService.php`: OTP generation, persistence, delivery, verification rules.
- `includes/Services/WebAuthnService.php`: passkey/WebAuthn server-side logic.
- `includes/Services/RateLimiter.php` and `includes/Services/SpamProtection.php`: abuse controls.
- `includes/Integrations/Integration_Manager.php`: loads WordPress, WooCommerce, Tutor, and Elementor login replacement integrations.

## REST API Surface

Routes are registered by controllers in `includes/Api/` using the namespace `logixfast-auth/v1`.

- `ConfigController`: public frontend configuration.
- `AuthController`: register, login, logout, forgot password, reset password.
- `OtpController`: OTP send/verify flows.
- `WebAuthnController`: passkey registration, login, list, delete, and profile actions.
- `SettingsController`: admin settings, dashboard data, pages, mail/OAuth, integrations, and provider checks.
- `SecurityController`: security-related admin actions and checks.

Public auth endpoints usually allow unauthenticated access but must still sanitize input, rate-limit sensitive actions, verify nonces where appropriate, and avoid exposing whether an account exists unless the existing flow intentionally does so.

## Settings Model

Settings are stored in the single WordPress option `logixfast_auth_settings` and merged with defaults from `Settings::get_default_settings()`.

Main sections:

- `general`: dedicated page, default mode, redirect behavior, honeypot.
- `auth`: email OTP, phone OTP, WebAuthn, OTP login, required phone.
- `mail`: SMTP/Gmail OAuth/from-address settings.
- `integrations`: WordPress, WooCommerce, Tutor, Elementor replacement toggles.
- `appearance`: primary/background/text colors, blur, radius, spacing.
- `security`: rate limits, OTP TTL/cooldown/max attempts, WebAuthn RP ID.

Never expose secret settings through `Settings::get_public_config()` or localized frontend globals.

## Database Tables

Created/updated by `includes/class-logixfast-auth-activator.php` with `dbDelta()`:

- `{$wpdb->prefix}logixfast_auth_otp_codes`: OTP hashes, channel, purpose, expiry, attempts.
- `{$wpdb->prefix}logixfast_auth_pending_registrations`: encrypted staged registration data for OTP registration.
- `{$wpdb->prefix}logixfast_auth_webauthn_credentials`: passkey credential data, counters, transports, usage timestamps.

Repositories live in `includes/Database/` and should be preferred over direct table access when available.

## React And TypeScript Structure

- `src/frontend/bootstrap.ts`: lightweight public bootstrap used outside dedicated pages.
- `src/frontend/main-popup.tsx`: popup React entry, mounted into `#logixfast-auth-root`.
- `src/frontend/main-page.tsx`: dedicated login page React entry.
- `src/frontend/components/LogixFastAuthApp.tsx`: main frontend auth UI shell.
- `src/frontend/components/LoginForm.tsx`, `RegisterForm.tsx`, `OtpForm.tsx`, `ForgotPasswordForm.tsx`, `ResetPasswordForm.tsx`: primary auth steps.
- `src/frontend/components/LogixFastAuthCountryPhone.tsx`: custom country selector and phone input using `src/frontend/data/countries.ts` and flag rendering.
- `src/frontend/api/auth.ts`: frontend calls to auth REST endpoints.
- `src/admin/main.tsx`: admin SPA entry.
- `src/admin/pages/`: admin settings pages.
- `src/admin/context/SettingsContext.tsx`: admin settings state and save flow.
- `src/shared/`: shared API/types/style helpers.

Frontend runtime config is `window.LOGIXFAST_AUTH_CONFIG`; admin runtime config is `window.LOGIXFAST_AUTH_ADMIN`.

## Assets And Builds

Development mode:

- Run `npm run dev` in the plugin folder.
- Add `define( 'LOGIXFAST_AUTH_DEV', true );` and optionally `define( 'LOGIXFAST_AUTH_DEV_URL', 'http://localhost:5173' );` in local `wp-config.php`.
- PHP loads Vite entries from `src/` with HMR.

Production build:

- Run `npm run build`.
- Vite/TypeScript output goes to `assets/dist/` and is resolved through `assets/dist/manifest.json`.
- Admin production bundle is loaded as a classic script; frontend bundles are loaded as ES modules.

Packaging:

- `npm run package:org` creates `build/logixfast-auth/` for WordPress.org packaging.
- Do not edit generated `build/`, `assets/dist/`, or `vendor/` files unless the task explicitly targets generated release output.

## Dependencies

PHP:

- PHP 8.1+
- Composer packages include `giggsey/libphonenumber-for-php` and `web-auth/webauthn-lib`.

JavaScript:

- React 19, React DOM 19, TypeScript, Vite, Sass.
- UI helpers include `lucide-react`, `react-router-dom`, `react-phone-number-input`, and `@simplewebauthn/browser`.

## Development Commands

Run commands from the plugin root.

```bash
composer install
npm install
npm run dev
npm run build
npm run package:org
```

On Windows PowerShell, use semicolons for chained commands if needed.

## Coding Guidelines For Future AI Edits

- Keep changes scoped to the owning subsystem; avoid broad WordPress core or unrelated plugin edits.
- Prefer existing LogixFastAuth classes, services, repositories, and settings helpers over new global functions.
- Preserve the `LogixFastAuth` namespace/prefix and WordPress escaping/sanitization conventions.
- For frontend changes, edit `src/`, not compiled files in `assets/dist/`.
- For admin UI changes, use existing components in `src/admin/ui/` and patterns in `src/admin/pages/`.
- For frontend auth UI changes, keep behavior consistent across popup and dedicated page modes.
- For phone/country work, check `LogixFastAuthCountryPhone.tsx`, `LogixFastAuthPhoneField.tsx`, `LogixFastAuthFlag.tsx`, and `data/countries.ts` before adding another phone input implementation.
- For settings changes, update defaults, REST sanitization/persistence, admin UI, and public config only when the value is safe for the browser.
- For auth/security changes, validate nonce/capability rules, rate limiting, sanitization, account enumeration behavior, and translation strings.
- After PHP edits, run the narrowest available PHP syntax or coding-standard check. After TypeScript edits, run `npm run build` when practical.
