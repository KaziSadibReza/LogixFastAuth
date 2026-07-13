=== LogixFast Auth ===
Contributors: kazisadibreza
Tags: login, registration, otp, webauthn, woocommerce
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern WordPress login and registration with OTP, passkeys, Gmail SMTP, and deep WooCommerce / Tutor LMS integration.

== Description ==

LogixFast Auth (LogixFastAuth) replaces default WordPress, WooCommerce, Tutor LMS, and Elementor login forms with a fast, mobile-friendly React interface.

**Features**

* Email, phone, and username login
* OTP verification (email and SMS)
* Passkeys (WebAuthn)
* Gmail SMTP via Google OAuth (admin mail settings)
* Popup and dedicated login page modes
* WooCommerce checkout and My Account login replacement
* Tutor LMS dashboard passkey management
* Elementor dynamic tags and popup triggers
* Rate limiting, spam protection, and security controls
* Admin settings UI with integrations toggles

**How it works**

PHP handles WordPress integration, REST API, and security. The frontend UI is built with React and loaded from `assets/dist/`.

== External services ==

This plugin contacts external services only when a site administrator enables and configures them:

* **Google (Gmail SMTP)** — If you choose Google mail transport and connect a Google account in LogixFastAuth settings, the plugin sends OAuth requests to Google and may send email through Gmail. Data sent includes OAuth tokens and message content you choose to send. See [Google's Privacy Policy](https://policies.google.com/privacy).
* **SMS providers** — If you register an SMS provider with the `logixfast_auth_sms_providers` filter, OTP codes are sent to that provider using API credentials you supply. What data is sent depends on the provider you configure.

LogixFastAuth does not send site usage data to the plugin author. Login and registration counters are stored locally in your WordPress database.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/logixfast-auth/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Open **LogixFastAuth** in the admin sidebar
4. Configure general settings and choose a dedicated login page (optional)
5. Enable integrations (WooCommerce, Tutor LMS, Elementor) as needed

**Requirements**

* WordPress 6.8 or later
* PHP 8.1 or later
* HTTPS recommended for passkeys and OAuth

== Frequently Asked Questions ==

= Does this work without WooCommerce or Tutor LMS? =

Yes. Core login, registration, OTP, and passkeys work on any WordPress site. WooCommerce and Tutor features activate only when those plugins are installed and enabled in LogixFastAuth settings.

= Do I need Node.js on my server? =

No. The plugin ships with pre-built assets in `assets/dist/`. Node.js is only needed if you want to rebuild the frontend from source.

= Where is the source code? =

The public development repository (React/TypeScript source in `src/`, build config, and full history):

https://github.com/KaziSadibReza/LogixFastAuth/tree/development

= How do I rebuild the frontend? =

Clone the development branch, then:

1. `npm install`
2. `npm run build`

This updates `assets/dist/` and `assets/dist/manifest.json`.

= What data does this plugin send externally? =

Nothing by default. External requests happen only when you configure Gmail SMTP or register an SMS provider. See the **External services** section above.

== Screenshots ==

1. Admin settings dashboard
2. Login popup on the frontend
3. Dedicated login page

== Changelog ==

= 1.0.3 =
* Login identifiers (email, phone, username) and optional username registration
* Custom auth placeholders and email OTP default-on
* Tutor LMS 4 passkeys settings tab compatibility
* WooCommerce and Tutor compat helpers for passkey surfaces
* Release workflow: development branch for dev, production git branch and WordPress.org SVN for releases

= 1.0.2 =
* Prepared release metadata for WordPress.org re-review
* Bundled country flag assets locally and removed stale external asset references
* Updated package metadata and translation template version

= 1.0.1 =
* Plugin Check and WordPress.org packaging improvements
* Security hardening for redirects and integrations
* Admin menu icon and styling fixes
* Readme privacy and external services documentation

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.3 =
Login identifiers, username registration, Tutor 4 passkeys fix, and release tooling updates.

= 1.0.2 =
Release metadata and WordPress.org package readiness update.

= 1.0.1 =
Maintenance release with security and WordPress.org compliance updates.
