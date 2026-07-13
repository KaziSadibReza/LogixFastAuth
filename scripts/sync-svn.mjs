/**
 * Sync a production build to the WordPress.org SVN repository.
 *
 * Usage:
 *   node scripts/sync-svn.mjs
 *   node scripts/sync-svn.mjs --commit --message "Release 1.0.3"
 *   node scripts/sync-svn.mjs --skip-build
 */

import fs from 'fs';
import path from 'path';
import {
	ROOT,
	assertReadmeStableTag,
	buildAssets,
	copyProductionTree,
	emptyDirectory,
	ensureDevelopmentBranch,
	installComposerDependencies,
	readPluginVersion,
	run,
	runOrNull,
} from './lib/release.mjs';

const SVN_URL = 'https://plugins.svn.wordpress.org/logixfast-auth';
const SVN_DIR = path.resolve(ROOT, '..', '.logixfast-auth-svn');
const WORDPRESS_ORG_ASSETS = path.join(ROOT, '.wordpress-org');

function parseArgs(argv) {
	const args = {
		message: '',
		commit: false,
		skipBuild: false,
	};

	for (let i = 0; i < argv.length; i += 1) {
		const arg = argv[i];
		if (arg === '--commit') {
			args.commit = true;
		} else if (arg === '--skip-build') {
			args.skipBuild = true;
		} else if (arg === '--message' && argv[i + 1]) {
			args.message = argv[++i];
		}
	}

	return args;
}

function ensureSvnAvailable() {
	if (!runOrNull('svn --version --quiet')) {
		throw new Error('Subversion (svn) is not installed or not on PATH.');
	}
}

function ensureSvnCheckout() {
	if (fs.existsSync(path.join(SVN_DIR, '.svn'))) {
		console.log('Updating SVN checkout...');
		run('svn update', { cwd: SVN_DIR, stdio: 'inherit' });
		return;
	}

	fs.mkdirSync(path.dirname(SVN_DIR), { recursive: true });
	console.log(`Checking out ${SVN_URL} ...`);
	run(`svn checkout ${SVN_URL} "${SVN_DIR}"`, { stdio: 'inherit' });
}

function syncPluginDirectory(targetDir) {
	emptyDirectory(targetDir, { preserve: ['.svn'] });
	copyProductionTree(targetDir);
	installComposerDependencies(targetDir);
}

function syncAssets() {
	const assetsDir = path.join(SVN_DIR, 'assets');
	emptyDirectory(assetsDir, { preserve: ['.svn'] });

	if (!fs.existsSync(WORDPRESS_ORG_ASSETS)) {
		console.log('No .wordpress-org folder found. Skipping assets sync.');
		return;
	}

	for (const entry of fs.readdirSync(WORDPRESS_ORG_ASSETS)) {
		if (entry === 'README.md') {
			continue;
		}

		const source = path.join(WORDPRESS_ORG_ASSETS, entry);
		if (!fs.statSync(source).isFile()) {
			continue;
		}

		fs.copyFileSync(source, path.join(assetsDir, entry));
	}
}

function stageSvnChanges() {
	run('svn add --force .', { cwd: SVN_DIR, stdio: 'inherit' });
}

function commitSvn(message, version) {
	const commitMessage = message || `Release ${version}`;
	console.log(`Committing to WordPress.org SVN: ${commitMessage}`);
	run(`svn commit -m "${commitMessage.replace(/"/g, '\\"')}"`, {
		cwd: SVN_DIR,
		stdio: 'inherit',
	});
}

function main() {
	const args = parseArgs(process.argv.slice(2));
	const version = readPluginVersion();

	ensureSvnAvailable();
	ensureDevelopmentBranch();
	assertReadmeStableTag(version);
	buildAssets(args.skipBuild);
	ensureSvnCheckout();

	const trunkDir = path.join(SVN_DIR, 'trunk');
	const tagDir = path.join(SVN_DIR, 'tags', version);

	console.log(`Syncing trunk for v${version}...`);
	syncPluginDirectory(trunkDir);

	console.log(`Syncing tags/${version}...`);
	fs.mkdirSync(path.dirname(tagDir), { recursive: true });
	syncPluginDirectory(tagDir);

	console.log('Syncing wordpress.org assets...');
	syncAssets();

	if (args.commit) {
		stageSvnChanges();
		commitSvn(args.message, version);
	} else {
		console.log('SVN working copy updated. Review changes, then commit with:');
		console.log(`  npm run publish:svn -- --message "Release ${version}"`);
	}

	console.log(`WordPress.org SVN working copy: ${SVN_DIR}`);
}

main();
