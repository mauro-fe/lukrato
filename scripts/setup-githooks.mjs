import { execSync } from 'node:child_process';

function run(command) {
    return execSync(command, { stdio: 'pipe' }).toString().trim();
}

try {
    run('git rev-parse --is-inside-work-tree');
} catch {
    console.log('Skipping git hooks setup: not inside a git repository.');
    process.exit(0);
}

try {
    const current = run('git config --get core.hooksPath');
    if (current === '.githooks') {
        console.log('Git hooks already configured (.githooks).');
        process.exit(0);
    }
} catch {
    // No hooksPath configured yet; continue.
}

try {
    run('git config core.hooksPath .githooks');
    console.log('Configured git hooks path: .githooks');
} catch (error) {
    console.warn('Could not configure git hooks automatically.');
    console.warn(error?.message || String(error));
}
