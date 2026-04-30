import { execFileSync } from 'node:child_process';
import { TextDecoder } from 'node:util';
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';

const args = new Set(process.argv.slice(2));
const checkStaged = args.has('--staged');

const decoder = new TextDecoder('utf-8', { fatal: true });

const TEXT_EXTENSIONS = new Set([
    '.php',
    '.js',
    '.mjs',
    '.cjs',
    '.ts',
    '.tsx',
    '.css',
    '.scss',
    '.html',
    '.md',
    '.json',
    '.xml',
    '.yml',
    '.yaml',
    '.txt',
    '.ini',
    '.env',
    '.sql',
    '.sh',
    '.bat',
    '.ps1'
]);

const TEXT_FILENAMES = new Set([
    '.gitignore',
    '.gitattributes',
    '.editorconfig',
    '.npmrc'
]);

const SKIP_PREFIXES = [
    'vendor/',
    'node_modules/',
    '.git/',
    'storage/',
    'public/build/',
    'resources/css/admin/vendor/'
];

// Typical UTF-8 mojibake sequences after Latin-1/Windows-1252 decoding.
const MOJIBAKE_RE = /(?:\u00C3(?:[\u0080-\u00BF]|\u0192[^\u0000-\u007F])|\u00C2[^\u0000-\u007F]|\u00E2(?:[\u0080-\u00BF]{1,2}|\u20AC[\u0080-\u00BF\u2018-\u2026])|\u00F0(?:[\u0080-\u00BF]{2,3}|\u0178[^\u0000-\u007F]{0,2})|\uFFFD)/u;

function toPosix(filePath) {
    return filePath.replace(/\\/g, '/');
}

function shouldSkip(filePath) {
    const posixPath = toPosix(filePath);
    return SKIP_PREFIXES.some((prefix) => posixPath.startsWith(prefix));
}

function isTextFile(filePath) {
    const base = path.basename(filePath);
    if (TEXT_FILENAMES.has(base)) return true;
    const ext = path.extname(filePath).toLowerCase();
    return TEXT_EXTENSIONS.has(ext);
}

function listFiles() {
    const args = checkStaged
        ? ['diff', '--cached', '--name-only', '--diff-filter=ACMRTUXB']
        : ['ls-files'];

    const output = execFileSync('git', args, { encoding: 'utf8' });
    return output
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean);
}

function findLine(text, regex) {
    const lines = text.split(/\r?\n/);
    for (let index = 0; index < lines.length; index++) {
        if (regex.test(lines[index])) {
            return index + 1;
        }
    }

    return null;
}

function checkFile(filePath) {
    if (shouldSkip(filePath) || !isTextFile(filePath) || !existsSync(filePath)) {
        return null;
    }

    const bytes = readFileSync(filePath);

    let text = '';
    try {
        text = decoder.decode(bytes);
    } catch {
        return { file: filePath, type: 'invalid_utf8' };
    }

    if (MOJIBAKE_RE.test(text)) {
        return {
            file: filePath,
            type: 'mojibake',
            line: findLine(text, MOJIBAKE_RE)
        };
    }

    return null;
}

function printIssues(issues) {
    const invalid = issues.filter((i) => i.type === 'invalid_utf8');
    const mojibake = issues.filter((i) => i.type === 'mojibake');

    console.error('\nEncoding check failed.\n');

    if (invalid.length > 0) {
        console.error(`Invalid UTF-8 (${invalid.length}):`);
        invalid.forEach((item) => console.error(` - ${item.file}`));
        console.error('');
    }

    if (mojibake.length > 0) {
        console.error(`Mojibake patterns (${mojibake.length}):`);
        mojibake.forEach((item) => {
            const suffix = item.line ? `:${item.line}` : '';
            console.error(` - ${item.file}${suffix}`);
        });
        console.error('');
    }

    console.error('Fix encoding issues before commit/merge.\n');
}

try {
    const files = listFiles();
    if (files.length === 0) {
        console.log('Encoding check skipped: no files to validate.');
        process.exit(0);
    }

    const issues = [];
    for (const file of files) {
        const issue = checkFile(file);
        if (issue) issues.push(issue);
    }

    if (issues.length > 0) {
        printIssues(issues);
        process.exit(1);
    }

    console.log('Encoding check passed.');
    process.exit(0);
} catch (error) {
    console.error('Encoding check failed to run:', error?.message || error);
    process.exit(2);
}
