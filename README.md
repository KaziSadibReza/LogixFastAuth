# Smart Login Registration

WordPress login and registration plugin with React UI, OTP, passkeys, and WooCommerce/Tutor LMS integrations.

## Branches

This repository uses a two-branch workflow:

| Branch | Purpose |
| --- | --- |
| `development` | Daily work. Contains `src/`, build config, PHP source, and dev tooling. |
| `production` | Release branch. Contains only runtime PHP, compiled `assets/dist/`, `vendor/`, and install files. No `src/`. |

Set `production` as the default branch on GitHub if you want visitors to download a ready-to-install plugin copy.

## Development setup

```bash
git clone -b development https://github.com/KaziSadibReza/Smart-Login-Registration.git
cd Smart-Login-Registration
composer install
npm install
npm run dev
```

Build compiled assets:

```bash
npm run build
```

## Publish to production branch

After finishing a feature on `development`:

```bash
git checkout development
git add .
git commit -m "feat: your change"
git push origin development

npm run publish:production -- --message "release: v1.0.1" --push
```

What `publish:production` does:

1. Runs `npm run build`
2. Copies only production files into `.worktree-production/`
3. Installs Composer dependencies for runtime use
4. Commits to the `production` branch
5. Pushes to GitHub when `--push` is passed

Sync without pushing:

```bash
npm run sync:production
```

## Install from GitHub

For a clean plugin copy without React source files:

```bash
git clone -b production https://github.com/KaziSadibReza/Smart-Login-Registration.git smart-login-registration
```

Then upload or copy `smart-login-registration/` into `wp-content/plugins/`.

## Production branch contents

- `smart-login-registration.php`
- `includes/`
- `templates/`
- `languages/`
- `assets/dist/`
- `vendor/`
- `composer.json`
- `composer.lock`
- `readme.txt`

Excluded from `production`:

- `src/`
- `node_modules/`
- `package.json`
- `vite.config.ts`
- `tsconfig.json`
- `scripts/`
