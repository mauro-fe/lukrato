#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

const target = path.join(process.cwd(), 'storage', 'vite-check');
fs.mkdirSync(target, { recursive: true });
