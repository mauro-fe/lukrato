// import { execSync } from 'node:child_process';
// import { TextDecoder } from 'node:util';
// import { existsSync, readFileSync } from 'node:fs';
// import path from 'node:path';

// const args = new Set(process.argv.slice(2));
// const checkStaged = args.has('--staged');

// const decoder = new TextDecoder('utf-8', { fatal: true });

// const TEXT_EXTENSIONS = new Set([
//     '.php',
//     '.js',
//     '.mjs',
//     '.cjs',
//     '.ts',
//     '.tsx',
//     '.css',
//     '.scss',
//     '.html',
//     '.md',
//     '.json',
//     '.xml',
//     '.yml',
//     '.yaml',
//     '.txt',
//     '.ini',
//     '.env',
//     '.sql',
//     '.sh',
//     '.bat',
//     '.ps1'
// ]);

// const TEXT_FILENAMES = new Set([
//     '.gitignore',
//     '.gitattributes',
//     '.editorconfig',
//     '.npmrc'
// ]);

// const SKIP_PREFIXES = [
//     'vendor/',
//     'node_modules/',
//     '.git/',
//     'storage/',
//     'public/build/',
//     'resources/css/admin/vendor/'
// ];

// const MOJIBAKE_RE = /(?:Ã[\u0080-\u00BF]|Â[\u0080-\u00BF]|â[\u0080-\u00BF]{1,2}|ð[\u0080-\u00BF]{2,3}|ï[\u0080-\u00BF]{1,2}|�)/u;

// function toPosix(p) {
//     return p.replace(/\\/g, '/');
// }

// function shouldSkip(filePath) {
//     const posixPath = toPosix(filePath);
//     return SKIP_PREFIXES.some((prefix) => posixPath.startsWith(prefix));
// }

// function isTextFile(filePath) {
//     const base = path.basename(filePath);
//     if (TEXT_FILENAMES.has(base)) return true;
//     const ext = path.extname(filePath).toLowerCase();
//     return TEXT_EXTENSIONS.has(ext);
// }

// function listFiles() {
//     const command = checkStaged
//         ? 'git diff --cached --name-only --diff-filter=ACMRTUXB'
//         : 'git ls-files';

//     const output = execSync(command, { encoding: 'utf8' });
//     return output
//         .split(/\r?\n/)
//         .map((line) => line.trim())
//         .filter(Boolean);
// }

// function checkFile(filePath) {
//     if (shouldSkip(filePath) || !isTextFile(filePath) || !existsSync(filePath)) {
//         return null;
//     }

//     const bytes = readFileSync(filePath);

//     let text = '';
//     try {
//         text = decoder.decode(bytes);
//     } catch {
//         return { file: filePath, type: 'invalid_utf8' };
//     }

//     if (MOJIBAKE_RE.test(text)) {
//         return { file: filePath, type: 'mojibake' };
//     }

//     return null;
// }

// function printIssues(issues) {
//     const invalid = issues.filter((i) => i.type === 'invalid_utf8');
//     const mojibake = issues.filter((i) => i.type === 'mojibake');

//     console.error('\nEncoding check failed.\n');

//     if (invalid.length > 0) {
//         console.error(`Invalid UTF-8 (${invalid.length}):`);
//         invalid.forEach((item) => console.error(` - ${item.file}`));
//         console.error('');
//     }

//     if (mojibake.length > 0) {
//         console.error(`Mojibake patterns (${mojibake.length}):`);
//         mojibake.forEach((item) => console.error(` - ${item.file}`));
//         console.error('');
//     }

//     console.error('Fix encoding issues before commit/merge.\n');
// }

// try {
//     const files = listFiles();
//     if (files.length === 0) {
//         console.log('Encoding check skipped: no files to validate.');
//         process.exit(0);
//     }

//     const issues = [];
//     for (const file of files) {
//         const issue = checkFile(file);
//         if (issue) issues.push(issue);
//     }

//     if (issues.length > 0) {
//         printIssues(issues);
//         process.exit(1);
//     }

//     console.log('Encoding check passed.');
//     process.exit(0);
// } catch (error) {
//     console.error('Encoding check failed to run:', error?.message || error);
//     process.exit(2);
// }
