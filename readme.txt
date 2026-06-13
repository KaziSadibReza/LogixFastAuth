=== Smart Login Registration (SLR) ===
Contributors: kazisadibreza
Tags: login, registration, otp, webauthn, woocommerce, tutor
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Ultra-fast, secure login/registration with React UI, OTP, WebAuthn, and WooCommerce/Tutor integration.

== Description ==

Smart Login Registration replaces WordPress, WooCommerce, Tutor, and Elementor login forms with a modern React-powered experience.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/smart-login-registration/`
2. Activate through the 'Plugins' menu
3. Go to SLR admin menu and configure settings
4. Select a dedicated login page

== Development ==

npm install && npm run build

This generates assets/dist/manifest.json — PHP reads this to enqueue the correct JS/CSS files.

Composer (optional, for libphonenumber phone validation):
1. cd wp-content/plugins/smart-login-registration
2. composer install

If you see "zip extension and unzip/7z commands are both missing" on Windows, either:
- Enable zip in php.ini: uncomment `extension=zip` and restart terminal, OR
- Run: composer install --prefer-source
- Or: composer run install:win

Without Composer, phone validation uses a basic fallback — the plugin still works.

== Changelog ==

= 1.0.0 =
* Initial release
