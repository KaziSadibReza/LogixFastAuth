/**
 * Sync a clean production tree into the production git worktree.
 *
 * Usage:
 *   node scripts/sync-production.mjs
 *   node scripts/sync-production.mjs --message "release: v1.0.1"
 *   node scripts/sync-production.mjs --message "release: v1.0.1" --push
 */

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OLD_WORKTREE = path.join(ROOT, '.worktree-production');
const WORKTREE = path.resolve(ROOT, '..', '.slr-production-worktree');
const PRODUCTION_BRANCH = 'production';
const REMOTE = 'origin';

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
	const cwd = options.cwd || ROOT;
	return execSync(command, {
		cwd,
		stdio: options.stdio || 'pipe',
		encoding: 'utf8',
	});
}

function runOrNull(command, options = {}) {
	try {
		return run(command, options).trim();
	} catch {
		return null;
	}
}

function parseArgs(argv) {
	const args = {
		message: 'chore: sync production build',
		push: false,
		skipBuild: false,
		skipCommit: false,
	};

	for (let i = 0; i < argv.length; i += 1) {
		const arg = argv[i];
		if (arg === '--push') {
			args.push = true;
		} else if (arg === '--skip-build') {
			args.skipBuild = true;
		} else if (arg === '--skip-commit') {
			args.skipCommit = true;
		} else if (arg === '--message' && argv[i + 1]) {
			args.message = argv[++i];
		}
	}

	return args;
}

function branchExists(name) {
	try {
		run(`git show-ref --verify --quiet refs/heads/${name}`);
		return true;
	} catch {
		return false;
	}
}

function worktreeExists() {
	return fs.existsSync(path.join(WORKTREE, '.git'));
}

function ensureGitRepo() {
	if (!fs.existsSync(path.join(ROOT, '.git'))) {
		throw new Error('Git repository not initialized. Run git init first.');
	}
}

function ensureDevelopmentBranch() {
	const branch = runOrNull('git branch --show-current');
	if (!branch) {
		throw new Error('Detached HEAD detected. Checkout the development branch first.');
	}

	if (branch !== 'development') {
		console.log(`Current branch is "${branch}". Switching to development...`);
		run('git checkout development');
	}
}

function buildAssets(skipBuild) {
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

function migrateLegacyWorktree() {
	if (!fs.existsSync(path.join(OLD_WORKTREE, '.git'))) {
		return;
	}

	console.log('Removing legacy in-repo production worktree (.worktree-production)...');
	try {
		run(`git worktree remove --force "${OLD_WORKTREE}"`);
	} catch {
		// Worktree metadata may already be stale; continue with directory cleanup.
	}

	if (fs.existsSync(OLD_WORKTREE)) {
		fs.rmSync(OLD_WORKTREE, { recursive: true, force: true });
	}
}

function ensureWorktree() {
	migrateLegacyWorktree();

	if (worktreeExists()) {
		return;
	}

	fs.mkdirSync(WORKTREE, { recursive: true });

	if (branchExists(PRODUCTION_BRANCH)) {
		run(`git worktree add "${WORKTREE}" ${PRODUCTION_BRANCH}`);
		return;
	}

	run(`git worktree add -b ${PRODUCTION_BRANCH} "${WORKTREE}"`);
}

function emptyWorktree() {
	for (const entry of fs.readdirSync(WORKTREE)) {
		if (entry === '.git') {
			continue;
		}

		fs.rmSync(path.join(WORKTREE, entry), { recursive: true, force: true });
	}
}

function copyPath(relativePath) {
	const source = path.join(ROOT, relativePath);
	const target = path.join(WORKTREE, relativePath);

	if (!fs.existsSync(source)) {
		return;
	}

	fs.mkdirSync(path.dirname(target), { recursive: true });

	fs.cpSync(source, target, { recursive: true });
}

function copyProductionFiles() {
	console.log('Copying production files...');

	for (const relativePath of PRODUCTION_PATHS) {
		copyPath(relativePath);
	}
}

function installComposerDependencies() {
	const composerJson = path.join(WORKTREE, 'composer.json');
	if (!fs.existsSync(composerJson)) {
		return;
	}

	const hasComposer = runOrNull('composer --version');
	if (!hasComposer) {
		console.log('Composer not found. Copying vendor/ from development tree as fallback...');
		copyPath('vendor');
		return;
	}

	console.log('Installing PHP dependencies in production worktree...');
	try {
		run('composer install --no-dev --prefer-dist --optimize-autoloader', {
			cwd: WORKTREE,
			stdio: 'inherit',
		});
	} catch (error) {
		console.warn('Composer install failed. Copying vendor/ from development tree as fallback...');
		copyPath('vendor');
	}
}

function commitProduction(message) {
	const status = run('git status --porcelain', { cwd: WORKTREE });
	if (!status.trim()) {
		console.log('Production worktree is already up to date.');
		return false;
	}

	run('git add -A', { cwd: WORKTREE, stdio: 'inherit' });
	run(`git commit -m "${message.replace(/"/g, '\\"')}"`, {
		cwd: WORKTREE,
		stdio: 'inherit',
	});
	return true;
}

function pushProduction() {
	if (!runOrNull(`git remote get-url ${REMOTE}`)) {
		console.log(`Remote "${REMOTE}" is not configured. Skipping push.`);
		return;
	}

	console.log(`Pushing ${PRODUCTION_BRANCH} to ${REMOTE}...`);
	run(`git push -u ${REMOTE} ${PRODUCTION_BRANCH}`, {
		cwd: WORKTREE,
		stdio: 'inherit',
	});
}

function main() {
	const args = parseArgs(process.argv.slice(2));

	ensureGitRepo();
	ensureDevelopmentBranch();
	buildAssets(args.skipBuild);
	ensureWorktree();
	emptyWorktree();
	copyProductionFiles();
	installComposerDependencies();

	let committed = false;
	if (args.skipCommit) {
		const status = run('git status --porcelain', { cwd: WORKTREE });
		if (status.trim()) {
			run('git add -A', { cwd: WORKTREE, stdio: 'inherit' });
			committed = true;
			console.log('Production files staged (--skip-commit). Commit manually to avoid co-author trailers.');
		} else {
			console.log('Production worktree is already up to date.');
		}
	} else {
		committed = commitProduction(args.message);
	}

	if (args.push && committed) {
		pushProduction();
	}

	console.log(`Production branch synced in ${WORKTREE}`);
}

main();
