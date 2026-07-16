import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Script } from 'node:vm';
import { gzipSync } from 'node:zlib';
import { JSDOM } from 'jsdom';

const countRoot = dirname(dirname(fileURLToPath(import.meta.url)));
const distDirectory = join(countRoot, 'dist');
const sourceDirectory = join(countRoot, 'src');

const collectFiles = (directory) => readdirSync(directory, { withFileTypes: true })
  .flatMap((entry) => {
    const file = join(directory, entry.name);
    return entry.isDirectory() ? collectFiles(file) : [file];
  });

const requireFile = (file) => {
  if (!existsSync(file)) throw new Error(`Missing count build artifact: ${relative(countRoot, file)}`);
};

const formatSize = (bytes) => `${bytes} B (${(bytes / 1024).toFixed(2)} KiB)`;

['index.html', 'index.js', 'index.css'].forEach((file) => requireFile(join(distDirectory, file)));

const distFiles = collectFiles(distDirectory);
const javascriptFiles = distFiles
  .filter((file) => file.endsWith('.js'))
  .map((file) => relative(distDirectory, file));
const cssFiles = distFiles
  .filter((file) => file.endsWith('.css'))
  .map((file) => relative(distDirectory, file));

if (javascriptFiles.length !== 1 || javascriptFiles[0] !== 'index.js') {
  throw new Error(`Count must emit only fixed index.js, found: ${javascriptFiles.join(', ') || 'none'}`);
}
if (cssFiles.length !== 1 || cssFiles[0] !== 'index.css') {
  throw new Error(`Count must emit only fixed index.css, found: ${cssFiles.join(', ') || 'none'}`);
}

for (const fileName of ['index.js', 'index.css']) {
  if (readFileSync(join(distDirectory, fileName)).length === 0) {
    throw new Error(`Count build artifact must not be empty: ${fileName}`);
  }
}

const htmlSource = readFileSync(join(distDirectory, 'index.html'), 'utf8');
if (!htmlSource.includes('src="./index.js"') || !htmlSource.includes('href="./index.css"')) {
  throw new Error('Count HTML must reference relative fixed index.js and index.css');
}

const mainSource = readFileSync(join(sourceDirectory, 'main.tsx'), 'utf8');
const dataContextSource = readFileSync(join(sourceDirectory, 'components/tool/dataContext.tsx'), 'utf8');
if (!mainSource.includes('document.getElementById("mabox_census_count")')) {
  throw new Error('Count mount contract #mabox_census_count is missing');
}
if (!dataContextSource.includes('window.dataLocal')) {
  throw new Error('Count data contract window.dataLocal is missing');
}
if (!dataContextSource.includes('getDataLocal()?.countData')) {
  throw new Error('Count data contract must continue consuming dataLocal.countData');
}

const javascriptSource = readFileSync(join(distDirectory, 'index.js'), 'utf8');
if (/(?:^|[;}])\s*import\s+(?!\()/m.test(javascriptSource)) {
  throw new Error('Count index.js must remain a self-contained classic-script-compatible bundle');
}
try {
  new Script(javascriptSource, { filename: 'count/dist/index.js' });
} catch (error) {
  const message = error instanceof Error ? error.message : String(error);
  throw new Error(`Count index.js must remain classic-script-compatible: ${message}`);
}

const cssSource = readFileSync(join(distDirectory, 'index.css'), 'utf8');
const dom = new JSDOM('<!doctype html><html><head></head><body></body></html>');
const style = dom.window.document.createElement('style');
style.textContent = cssSource;
dom.window.document.head.append(style);
if (!style.sheet) throw new Error('Count index.css could not be parsed');

const invalidSelectors = [];
const walkRules = (rules) => {
  for (const rule of Array.from(rules)) {
    if (rule.type === 1) {
      for (const selector of rule.selectorText.split(',')) {
        if (!selector.trim().startsWith('#mabox_census_count')) invalidSelectors.push(selector.trim());
      }
    }
    if (rule.cssRules) walkRules(rule.cssRules);
  }
};
walkRules(style.sheet.cssRules);
dom.window.close();
if (invalidSelectors.length > 0) {
  throw new Error(`Count CSS escaped #mabox_census_count: ${invalidSelectors.join(', ')}`);
}

const sourceFiles = collectFiles(sourceDirectory)
  .filter((file) => /\.(?:tsx?|css)$/.test(file));
for (const file of sourceFiles) {
  const source = readFileSync(file, 'utf8');
  if (/from ["']antd(?:\/|["'])|default-passive-events/.test(source)) {
    throw new Error(`Count source reintroduced an unused UI/event dependency: ${relative(countRoot, file)}`);
  }
}

for (const file of distFiles) {
  if (readFileSync(file).includes(Buffer.from('/wp-content/plugins/'))) {
    throw new Error(`Count build hardcodes a WordPress plugin path: ${relative(distDirectory, file)}`);
  }
}

const describeArtifact = (fileName) => {
  const contents = readFileSync(join(distDirectory, fileName));
  return `${fileName} ${formatSize(contents.length)} raw / ${formatSize(gzipSync(contents).length)} gzip`;
};

console.log(`Count build contract passed: ${describeArtifact('index.js')}; ${describeArtifact('index.css')}`);
