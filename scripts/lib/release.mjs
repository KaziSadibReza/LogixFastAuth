/**
 * Shared release utilities for production git branch and WordPress.org SVN.
 */

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export const ROOT = path.resolve(__dirname, '../..');

export const PRODUCTION_PATHS = [
	'logixfast-auth.php',
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

export function run(command, options = {}) {
	const cwd = options.cwd || ROOT;
	return execSync(command, {
		cwd,
		stdio: options.stdio || 'pipe',
		encoding: 'utf8',
	});
}

export function runOrNull(command, options = {}) {
	try {
		return run(command, options).trim();
	} catch {
		return null;
	}
}

export function readPluginVersion() {
	const main = fs.readFileSync(path.join(ROOT, 'logixfast-auth.php'), 'utf8');
	const match = main.match(/define\s*\(\s*'LOGIXFAST_AUTH_VERSION'\s*,\s*'([^']+)'\s*\)/);

	if (!match) {
		throw new Error('Could not read LOGIXFAST_AUTH_VERSION from logixfast-auth.php');
	}

	return match[1];
}

export function ensureDevelopmentBranch() {
	const branch = runOrNull('git branch --show-current');
	if (!branch) {
		throw new Error('Detached HEAD detected. Checkout the development branch first.');
	}

	if (branch !== 'development') {
		console.log(`Current branch is "${branch}". Switching to development...`);
		run('git checkout development');
	}
}

export function buildAssets(skipBuild = false) {
	if (skipBuild) {
		console.log('Skipping npm build (--skip-build).');
		return;
	}

	console.log('Building frontend assets...');
	run('npm run build', { stdio: 'inherit' });

	if (!fs.existsSync(path.join(ROOT, 'assets/dist/manifest.json'))) {
		throw new Error('Build failed: assets/dist/manifest.json is missing.');
	}
}

export function copyPath(sourceRoot, targetRoot, relativePath) {
	const source = path.join(sourceRoot, relativePath);
	const target = path.join(targetRoot, relativePath);

	if (!fs.existsSync(source)) {
		return;
	}

	fs.mkdirSync(path.dirname(target), { recursive: true });
	fs.cpSync(source, target, { recursive: true });
}

export function copyProductionTree(targetRoot) {
	for (const relativePath of PRODUCTION_PATHS) {
		copyPath(ROOT, targetRoot, relativePath);
	}
}

export function emptyDirectory(directory, { preserve = [] } = {}) {
	if (!fs.existsSync(directory)) {
		fs.mkdirSync(directory, { recursive: true });
		return;
	}

	for (const entry of fs.readdirSync(directory)) {
		if (preserve.includes(entry)) {
			continue;
		}

		fs.rmSync(path.join(directory, entry), { recursive: true, force: true });
	}
}

export function installComposerDependencies(targetRoot) {
	const composerJson = path.join(targetRoot, 'composer.json');
	if (!fs.existsSync(composerJson)) {
		return;
	}

	const hasComposer = runOrNull('composer --version');
	if (!hasComposer) {
		console.log('Composer not found. Copying vendor/ from development tree as fallback...');
		copyPath(ROOT, targetRoot, 'vendor');
		return;
	}

	console.log('Installing PHP dependencies...');
	try {
		run('composer install --no-dev --prefer-dist --optimize-autoloader', {
			cwd: targetRoot,
			stdio: 'inherit',
		});
	} catch {
		console.warn('Composer install failed. Copying vendor/ from development tree as fallback...');
		copyPath(ROOT, targetRoot, 'vendor');
	}
}

export function assertReadmeStableTag(version) {
	const readme = fs.readFileSync(path.join(ROOT, 'readme.txt'), 'utf8');
	const match = readme.match(/^Stable tag:\s*(.+)$/m);

	if (!match || match[1].trim() !== version) {
		throw new Error(
			`readme.txt Stable tag (${match?.[1]?.trim() || 'missing'}) does not match plugin version ${version}`
		);
	}
}
