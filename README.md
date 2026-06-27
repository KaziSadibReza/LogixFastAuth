# LogixFast Auth

WordPress login and registration plugin with a React UI, OTP, passkeys, Google OAuth, and integrations for WooCommerce, Tutor LMS, and Elementor.

| Audience | Document |
| --- | --- |
| **Site owners & WordPress.org visitors** | [`readme.txt`](readme.txt) — shown on the [plugin directory page](https://wordpress.org/plugins/) |
| **Developers & contributors** | This file (`README.md`) — GitHub development docs |

You need **both files**: WordPress.org only reads `readme.txt` (fixed format). GitHub displays `README.md` for the repository.

---

## Requirements

| Runtime (production) | Development |
| --- | --- |
| WordPress 6.0+ | Node.js 18+ |
| PHP 8.0+ | npm |
| HTTPS (recommended for passkeys/OAuth) | Composer (optional, phone validation) |

---

## Quick start (site owners)

**From WordPress.org** — install from the plugin directory (recommended).

**From GitHub (production branch)** — runtime-only copy without dev tooling:

```bash
git clone -b production https://github.com/KaziSadibReza/LogixFastAuth.git logixfast-auth
```

Copy `logixfast-auth/` into `wp-content/plugins/` and activate.

---

## Development setup

```bash
git clone -b development https://github.com/KaziSadibReza/LogixFastAuth.git
cd LogixFastAuth
composer install
npm install
```

### Production assets (`npm run build`)

Compiles React/TypeScript from `src/` into `assets/dist/` (used on live sites and in WordPress.org releases):

```bash
npm run build
```

PHP loads scripts from `assets/dist/` using `assets/dist/manifest.json`.

### Live development (`npm run dev`)

Use this while editing the **frontend popup/page UI** or the **admin settings SPA**. It starts the Vite dev server with hot module replacement (HMR) so you see changes instantly without rebuilding.

**Why use it**

| Without `npm run dev` | With `npm run dev` |
| --- | --- |
| Edit `src/` → run `npm run build` after every change | Edit `src/` → browser updates automatically |
| WordPress loads compiled files from `assets/dist/` | WordPress loads modules from the Vite server |

**How to run**

1. In the plugin folder, start Vite:

```bash
npm run dev
```

Default URL: `http://localhost:5173` (port is fixed in `vite.config.ts`).

2. Tell WordPress to use the dev server. Add to `wp-config.php` (local only — never on production):

```php
define( 'LOGIXFAST_AUTH_DEV', true );
define( 'LOGIXFAST_AUTH_DEV_URL', 'http://localhost:5173' );
```

Or use the filter instead of `LOGIXFAST_AUTH_DEV_URL`:

```php
define( 'LOGIXFAST_AUTH_DEV', true );
add_filter( 'logixfast_auth_dev_server_url', fn () => 'http://localhost:5173' );
```

3. Open your local site:
   - **Admin SPA:** WordPress admin → **LogixFastAuth**
   - **Frontend:** any page with the LogixFastAuth popup or dedicated login page

4. Edit files under `src/admin/` or `src/frontend/` and save — the browser reloads the changed module.

**What `npm run dev` runs**

- `package.json` → `"dev": "vite"` → `vite.config.ts`
- Serves `src/frontend/*` entry points (popup, page, bootstrap)
- Admin uses the same dev server via `Assets::enqueue_vite_dev_entry()` when `LOGIXFAST_AUTH_DEV` is on
- CORS is enabled so your local WordPress domain (e.g. `bhashaskool.local`) can load scripts from port 5173

**When to stop dev mode**

- Remove `LOGIXFAST_AUTH_DEV` from `wp-config.php` (or set it to `false`)
- Run `npm run build` before committing or packaging for WordPress.org
- Never ship with `LOGIXFAST_AUTH_DEV` enabled

**Troubleshooting**

| Problem | Fix |
| --- | --- |
| Blank admin or frontend | Confirm `npm run dev` is running and `LOGIXFAST_AUTH_DEV_URL` matches |
| Port 5173 in use | Stop the other process or change `server.port` in `vite.config.ts` and update `LOGIXFAST_AUTH_DEV_URL` |
| Changes not appearing | Hard-refresh; ensure you edited `src/`, not `assets/dist/` |
| Production site broken | Disable `LOGIXFAST_AUTH_DEV` and run `npm run build` |

---

## Project structure

```
logixfast-auth/
├── logixfast-auth.php   # Bootstrap
├── includes/                      # PHP (API, services, integrations)
├── templates/                     # PHP templates
├── src/                           # React / TypeScript source
├── assets/dist/                   # Compiled JS/CSS (generated)
├── assets/images/                 # Static images
├── vendor/                        # Composer dependencies
├── readme.txt                     # WordPress.org public readme
└── scripts/                       # Release automation
```

---

## npm scripts

| Script | Purpose |
| --- | --- |
| `npm run dev` | Vite dev server with HMR — requires `LOGIXFAST_AUTH_DEV` in `wp-config.php` (see above) |
| `npm run build` | Compile `src/` → `assets/dist/` |
| `npm run sync:production` | Sync runtime build to `production` git branch |
| `npm run publish:production -- --message "..." --push` | Build, commit, and push `production` |
| `npm run package:org` | Build WordPress.org ZIP folder (includes `src/` per Guideline #4) |

---

## Git branches

| Branch | Contents | Use |
| --- | --- | --- |
| `development` | Full source, `src/`, build config, scripts | Daily development |
| `production` | PHP, `assets/dist/`, `vendor/`, `readme.txt` only | Lean installs from GitHub |

Publish flow:

```bash
git checkout development
git add .
git commit -m "feat: your change"
git push origin development

npm run publish:production -- --message "release: v1.0.1" --push
```

---

## WordPress.org release

WordPress.org requires **human-readable source** for compiled JavaScript ([Guideline #4](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable)).

```bash
npm run package:org
```

Output: `build/logixfast-auth/` — **runtime only** (same as the `production` branch).

| Included | Not included |
| --- | --- |
| `includes/`, `templates/`, `assets/dist/`, `vendor/` | `src/`, `vite.config.ts`, `phpcs.xml`, `package.json` |

Source for reviewers is linked in **`readme.txt`** → GitHub `development` branch ([Guideline #4](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable)).

1. Run **Plugin Check** on `build/logixfast-auth/`
2. ZIP `logixfast-auth/`
3. Upload to WordPress.org

**Do not** run Plugin Check on the development folder root — it contains `.gitignore`, `.distignore`, and other dev-only files.

---

## License

GPLv2 or later. See [readme.txt](readme.txt).
