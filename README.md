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
| `npm run sync:production` | Build and sync runtime files to the local `production` git worktree |
| `npm run publish:production` | Build, commit, and push the `production` git branch to GitHub |
| `npm run sync:svn` | Build and sync runtime files to the local WordPress.org SVN checkout |
| `npm run publish:svn` | Build, sync, and commit to `https://plugins.svn.wordpress.org/logixfast-auth` |
| `npm run release` | Publish to GitHub `production` and WordPress.org SVN in one step |

---

## Git branches and release targets

| Target | Contents | Use |
| --- | --- | --- |
| `development` | Full source, `src/`, Vite/TS config, scripts | Daily development with `npm run dev` |
| `production` (git) | PHP, `assets/dist/`, `vendor/`, `readme.txt` only | Lean installs from GitHub |
| WordPress.org SVN | Same runtime tree as `production` + `assets/` banners/icons | Official plugin directory releases |

**Development workflow**

```bash
git checkout development
npm install
composer install
npm run dev   # local HMR — enable LOGIXFAST_AUTH_DEV in wp-config.php
```

**Release workflow (v1.0.3 example)**

```bash
git checkout development
# bump version in logixfast-auth.php, readme.txt Stable tag, package.json, composer.json
git add .
git commit -m "release: v1.0.3"
git push origin development

npm run release
# or separately:
npm run publish:production -- --message "release: v1.0.3"
npm run publish:svn -- --message "Release 1.0.3"
```

Local checkouts created by the scripts (outside this repo):

| Path | Purpose |
| --- | --- |
| `../.logixfast-auth-production-worktree` | Git `production` branch worktree |
| `../.logixfast-auth-svn` | WordPress.org SVN checkout |

---

## WordPress.org release

Runtime plugin files are synced to SVN `trunk/` and `tags/{version}/`. Directory banners and icons live in `.wordpress-org/` in the development repo and are copied to SVN `assets/` (not shipped inside the plugin ZIP).

Source for reviewers is linked in **`readme.txt`** → GitHub `development` branch ([Guideline #4](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable)).

1. Run **Plugin Check** on the SVN `trunk/` checkout (or the production worktree)
2. `npm run publish:svn -- --message "Release x.y.z"`
3. WordPress.org serves updates from the SVN tag matching `Stable tag` in `readme.txt`

**Do not** run Plugin Check on the development folder root — it contains `.gitignore`, `.distignore`, and other dev-only files.

---

## License

GPLv2 or later. See [readme.txt](readme.txt).
