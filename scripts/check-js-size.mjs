#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

const ROOT = process.cwd();
const JS_ROOT = path.join(ROOT, 'resources', 'js');
const ALLOWLIST_PATH = path.join(ROOT, 'docs', 'frontend-size-allowlist.json');

function toPosix(filePath) {
    return filePath.replace(/\\/g, '/');
}

function countLines(content) {
    if (!content) return 0;
    return content.split(/\r?\n/).length;
}

function walkJsFiles(dirPath) {
    if (!fs.existsSync(dirPath)) return [];

    const entries = fs.readdirSync(dirPath, { withFileTypes: true });
    const files = [];

    entries.forEach((entry) => {
        const fullPath = path.join(dirPath, entry.name);
        if (entry.isDirectory()) {
            files.push(...walkJsFiles(fullPath));
            return;
        }

        if (entry.isFile() && fullPath.endsWith('.js')) {
            files.push(fullPath);
        }
    });

    return files;
}

function loadAllowlist() {
    const fallback = {
        indexMaxLines: 150,
        fileMaxLines: 800,
        allowOverLimit: [],
    };

    if (!fs.existsSync(ALLOWLIST_PATH)) {
        return fallback;
    }

    try {
        const payload = JSON.parse(fs.readFileSync(ALLOWLIST_PATH, 'utf8'));
        return {
            indexMaxLines: Number(payload.indexMaxLines) || fallback.indexMaxLines,
            fileMaxLines: Number(payload.fileMaxLines) || fallback.fileMaxLines,
            allowOverLimit: Array.isArray(payload.allowOverLimit)
                ? payload.allowOverLimit.map((item) => toPosix(String(item)))
                : fallback.allowOverLimit,
        };
    } catch (error) {
        console.error(`[check-js-size] failed to parse ${toPosix(path.relative(ROOT, ALLOWLIST_PATH))}: ${error.message}`);
        process.exit(1);
    }
}

const { indexMaxLines, fileMaxLines, allowOverLimit } = loadAllowlist();
const allowSet = new Set(allowOverLimit);
const oversizedAllowed = [];
const oversizedBlocked = [];
const staleAllowlist = [];

const jsFiles = walkJsFiles(JS_ROOT);

jsFiles.forEach((absolutePath) => {
    const relativePath = toPosix(path.relative(ROOT, absolutePath));
    const content = fs.readFileSync(absolutePath, 'utf8');
    const lines = countLines(content);
    const limit = path.basename(absolutePath) === 'index.js' ? indexMaxLines : fileMaxLines;

    if (lines <= limit) {
        return;
    }

    if (allowSet.has(relativePath)) {
        oversizedAllowed.push({ path: relativePath, lines, limit });
        return;
    }

    oversizedBlocked.push({ path: relativePath, lines, limit });
});

allowOverLimit.forEach((allowedPath) => {
    const absolutePath = path.join(ROOT, allowedPath);
    if (!fs.existsSync(absolutePath)) {
        staleAllowlist.push(`${allowedPath} (file removed)`);
        return;
    }

    const content = fs.readFileSync(absolutePath, 'utf8');
    const lines = countLines(content);
    const limit = path.basename(absolutePath) === 'index.js' ? indexMaxLines : fileMaxLines;
    if (lines <= limit) {
        staleAllowlist.push(`${allowedPath} (${lines}/${limit})`);
    }
});

console.log(`[check-js-size] evaluated ${jsFiles.length} JS file(s).`);

if (oversizedAllowed.length > 0) {
    console.log('[check-js-size] allowed over-limit baseline:');
    oversizedAllowed
        .sort((a, b) => a.path.localeCompare(b.path))
        .forEach((entry) => {
            console.log(`  - ${entry.path}: ${entry.lines} lines (limit ${entry.limit})`);
        });
}

if (staleAllowlist.length > 0) {
    console.log('[check-js-size] stale allowlist entries (safe to remove):');
    staleAllowlist
        .sort((a, b) => a.localeCompare(b))
        .forEach((line) => {
            console.log(`  - ${line}`);
        });
}

if (oversizedBlocked.length > 0) {
    console.error('[check-js-size] failed: over-limit files not in allowlist.');
    oversizedBlocked
        .sort((a, b) => a.path.localeCompare(b.path))
        .forEach((entry) => {
            console.error(`  - ${entry.path}: ${entry.lines} lines (limit ${entry.limit})`);
        });
    process.exit(1);
}

console.log('[check-js-size] ok.');
