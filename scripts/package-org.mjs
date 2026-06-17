/**
 * Build a WordPress.org-ready plugin folder (runtime only).
 *
 * Frontend source lives on GitHub — see readme.txt (Guideline #4).
 *
 * Usage: node scripts/package-org.mjs
 * Output: build/smart-login-registration/
 */

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUTPUT = path.join(ROOT, 'build', 'smart-login-registration');

const PRODUCTION_PATHS = [
	'smart-login-registration.php',
	'uninstall.php',
	'readme.txt',
	'includes',
	'templates',
	'languages',
	'assets/dist',
	'assets/css',
	'assets/js',
	'assets/images',
	'composer.json',
	'composer.lock',
];

function run(command, options = {}) {
	execSync(command, {
		cwd: options.cwd || ROOT,
		stdio: options.stdio || 'inherit',
	});
}

function copyPath(relativePath) {
	const source = path.join(ROOT, relativePath);
	const target = path.join(OUTPUT, relativePath);

	if (!fs.existsSync(source)) {
		return;
	}

	fs.mkdirSync(path.dirname(target), { recursive: true });
	fs.cpSync(source, target, { recursive: true });
}

function emptyOutput() {
	if (fs.existsSync(OUTPUT)) {
		fs.rmSync(OUTPUT, { recursive: true, force: true });
	}

	fs.mkdirSync(OUTPUT, { recursive: true });
}

function installComposerDependencies() {
	const composerJson = path.join(OUTPUT, 'composer.json');
	if (!fs.existsSync(composerJson)) {
		return;
	}

	try {
		run('composer install --no-dev --prefer-dist --optimize-autoloader', {
			cwd: OUTPUT,
		});
	} catch {
		copyPath('vendor');
	}
}

console.log('Building assets...');
run('npm run build');

console.log('Packaging WordPress.org folder...');
emptyOutput();

for (const relativePath of PRODUCTION_PATHS) {
	copyPath(relativePath);
}

installComposerDependencies();

console.log(`\nWordPress.org package ready:\n${OUTPUT}`);
console.log('Runtime only: no src/, no vite/phpcs dev files.');
console.log('Source + build steps: readme.txt → GitHub development branch.');
console.log('Run Plugin Check on that folder, then ZIP for upload.');
