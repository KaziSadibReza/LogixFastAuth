/**
 * Sync a clean production tree into the production git worktree.
 *
 * Usage:
 *   node scripts/sync-production.mjs
 *   node scripts/sync-production.mjs --message "release: v1.0.3"
 *   node scripts/sync-production.mjs --message "release: v1.0.3" --push
 */

import fs from 'fs';
import path from 'path';
import {
	ROOT,
	assertReadmeStableTag,
	buildAssets,
	copyPath,
	copyProductionTree,
	emptyDirectory,
	ensureDevelopmentBranch,
	installComposerDependencies,
	readPluginVersion,
	run,
	runOrNull,
} from './lib/release.mjs';

const OLD_WORKTREES = [
	path.join(ROOT, '.worktree-production'),
	path.resolve(ROOT, '..', '.slr-production-worktree'),
];
const WORKTREE = path.resolve(ROOT, '..', '.logixfast-auth-production-worktree');
const PRODUCTION_BRANCH = 'production';
const REMOTE = 'origin';

function parseArgs(argv) {
	const args = {
		message: '',
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

function migrateLegacyWorktree() {
	run('git worktree prune');

	for (const oldWorktree of OLD_WORKTREES) {
		if (!fs.existsSync(path.join(oldWorktree, '.git'))) {
			continue;
		}

		console.log(`Removing legacy production worktree (${path.basename(oldWorktree)})...`);
		try {
			run(`git worktree remove --force "${oldWorktree}"`);
		} catch {
			// Worktree metadata may already be stale; continue with directory cleanup.
		}

		if (fs.existsSync(oldWorktree)) {
			fs.rmSync(oldWorktree, { recursive: true, force: true });
		}
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
	const version = readPluginVersion();
	const message = args.message || `release: v${version}`;

	ensureGitRepo();
	ensureDevelopmentBranch();
	assertReadmeStableTag(version);
	buildAssets(args.skipBuild);
	ensureWorktree();
	emptyDirectory(WORKTREE, { preserve: ['.git'] });
	copyProductionTree(WORKTREE);
	installComposerDependencies(WORKTREE);

	let committed = false;
	if (args.skipCommit) {
		const status = run('git status --porcelain', { cwd: WORKTREE });
		if (status.trim()) {
			run('git add -A', { cwd: WORKTREE, stdio: 'inherit' });
			committed = true;
			console.log('Production files staged (--skip-commit). Commit manually in the worktree.');
		} else {
			console.log('Production worktree is already up to date.');
		}
	} else {
		committed = commitProduction(message);
	}

	if (args.push && committed) {
		pushProduction();
	}

	console.log(`Production branch synced in ${WORKTREE}`);
}

main();
