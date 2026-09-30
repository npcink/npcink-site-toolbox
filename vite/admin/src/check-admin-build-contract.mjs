import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { dirname, extname, join, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';
import { JSDOM } from 'jsdom';

const KIB = 1024;
const DEFAULT_BUDGETS = Object.freeze({
  initialRaw: 400 * KIB,
  initialGzip: 140 * KIB,
  chunkRaw: 400 * KIB,
  chunkGzip: 140 * KIB,
  // 首屏样式预算：入口 index.css 只允许外壳样式，视图样式必须随 chunk 拆分
  initialCssRaw: 32 * KIB,
  initialCssGzip: 9 * KIB,
});
const BOOTSTRAP_BUDGETS = Object.freeze({ raw: 10 * KIB, gzip: 5 * KIB });
const HASHED_CHUNK_PATTERN = /^assets\/.+-[A-Za-z0-9_-]{8}\.js$/;
const HASHED_ASSET_PATTERN = /^assets\/.+-[A-Za-z0-9_-]{8}(?:\.[^/]+)+$/;
const HARDCODED_PLUGIN_PATH = Buffer.from('/wp-content/plugins/');

const adminRoot = dirname(dirname(fileURLToPath(import.meta.url)));
const defaultDistDirectory = join(adminRoot, 'dist');

const formatSize = (bytes) => `${bytes} B (${(bytes / KIB).toFixed(2)} KiB)`;

const collectFiles = (directory) => {
  const files = [];
  for (const entry of readdirSync(directory, { withFileTypes: true })) {
    const file = join(directory, entry.name);
    if (entry.isDirectory()) files.push(...collectFiles(file));
    else if (entry.isFile()) files.push(file);
  }
  return files;
};

const normalizeArtifactPath = (value) => value.split(sep).join('/');

const resolveArtifact = (distDirectory, artifact, context) => {
  if (typeof artifact !== 'string' || artifact.length === 0) {
    throw new Error(`${context} has an empty artifact path`);
  }
  if (/^(?:[a-z]+:)?\/\//i.test(artifact) || artifact.startsWith('/')) {
    throw new Error(`${context} must use a relative artifact path: ${artifact}`);
  }

  const cleanArtifact = artifact.split(/[?#]/, 1)[0];
  const resolved = resolve(distDirectory, cleanArtifact);
  const relativePath = relative(distDirectory, resolved);
  if (relativePath === '..' || relativePath.startsWith(`..${sep}`) || relativePath === '') {
    throw new Error(`${context} escapes the dist directory: ${artifact}`);
  }
  if (!existsSync(resolved) || !statSync(resolved).isFile()) {
    throw new Error(`${context} references a missing file: ${artifact}`);
  }
  return normalizeArtifactPath(relativePath);
};

const parseHtmlResources = (distDirectory) => {
  const htmlFile = join(distDirectory, 'index.html');
  if (!existsSync(htmlFile)) throw new Error('Missing dist/index.html');

  const dom = new JSDOM(readFileSync(htmlFile, 'utf8'));
  const scripts = Array.from(dom.window.document.querySelectorAll('script[type="module"][src]'))
    .map((element) => resolveArtifact(distDirectory, element.getAttribute('src'), 'HTML module script'));
  const preloads = Array.from(dom.window.document.querySelectorAll('link[rel="modulepreload"][href]'))
    .map((element) => resolveArtifact(distDirectory, element.getAttribute('href'), 'HTML modulepreload'));
  const stylesheets = Array.from(dom.window.document.querySelectorAll('link[rel="stylesheet"][href]'))
    .map((element) => resolveArtifact(distDirectory, element.getAttribute('href'), 'HTML stylesheet'));
  dom.window.close();

  if (scripts.length === 0) throw new Error('index.html has no module entry script');
  return { scripts, preloads, stylesheets };
};

const loadManifest = (distDirectory) => {
  const manifestFile = join(distDirectory, '.vite', 'manifest.json');
  if (!existsSync(manifestFile)) throw new Error('Missing dist/.vite/manifest.json');
  const manifest = JSON.parse(readFileSync(manifestFile, 'utf8'));
  if (!manifest || Array.isArray(manifest) || typeof manifest !== 'object') {
    throw new Error('Vite manifest must be an object');
  }
  return manifest;
};

const buildManifestIndex = (distDirectory, manifest) => {
  const byFile = new Map();
  for (const [key, record] of Object.entries(manifest)) {
    if (!record || typeof record !== 'object') throw new Error(`Invalid manifest record: ${key}`);
    const file = resolveArtifact(distDirectory, record.file, `Manifest record ${key}`);
    if (byFile.has(file)) throw new Error(`Manifest maps more than one record to ${file}`);
    byFile.set(file, { key, ...record, file });
  }
  return byFile;
};

const resolveManifestReference = (distDirectory, manifest, key, context) => {
  const record = manifest[key];
  if (!record || typeof record !== 'object') {
    throw new Error(`${context} references missing manifest key: ${key}`);
  }
  return resolveArtifact(distDirectory, record.file, `Manifest record ${key}`);
};

const collectGraph = (distDirectory, manifest, byFile, roots, includeDynamic) => {
  const visited = new Set();
  const visit = (file) => {
    if (visited.has(file)) return;
    const record = byFile.get(file);
    if (!record) throw new Error(`Built JS is missing from the Vite manifest: ${file}`);
    if (extname(file) !== '.js') throw new Error(`Module graph contains a non-JS artifact: ${file}`);
    visited.add(file);

    const references = [...(record.imports ?? [])];
    if (includeDynamic) references.push(...(record.dynamicImports ?? []));
    for (const key of references) {
      visit(resolveManifestReference(distDirectory, manifest, key, `Manifest record ${record.key}`));
    }
  };
  roots.forEach(visit);
  return visited;
};

const measureFiles = (distDirectory, files) => {
  let raw = 0;
  let gzip = 0;
  for (const file of files) {
    const contents = readFileSync(join(distDirectory, file));
    raw += contents.length;
    gzip += gzipSync(contents).length;
  }
  return { raw, gzip };
};

const auditBuild = (distDirectory = defaultDistDirectory, budgets = DEFAULT_BUDGETS) => {
  const violations = [];
  const html = parseHtmlResources(distDirectory);
  const manifest = loadManifest(distDirectory);
  const byFile = buildManifestIndex(distDirectory, manifest);
  if (html.scripts.length !== 1 || html.scripts[0] !== 'index.js') {
    throw new Error(`Expected one fixed module bootstrap index.js, found: ${html.scripts.join(', ') || 'none'}`);
  }

  const bootstrapRecord = byFile.get('index.js');
  if (!bootstrapRecord) throw new Error('Fixed bootstrap index.js is missing from the Vite manifest');
  if ((bootstrapRecord.imports ?? []).length !== 0) {
    throw new Error('Fixed bootstrap index.js must not own static shared imports');
  }
  if ((bootstrapRecord.dynamicImports ?? []).length !== 1) {
    throw new Error('Fixed bootstrap index.js must import exactly one hashed app entry');
  }
  const appEntry = resolveManifestReference(
    distDirectory,
    manifest,
    bootstrapRecord.dynamicImports[0],
    'Fixed bootstrap index.js',
  );
  if (!HASHED_CHUNK_PATTERN.test(appEntry)) {
    throw new Error(`Bootstrap app entry must use an assets/name-hash.js filename: ${appEntry}`);
  }
  const expectedBootstrap = `void import(${JSON.stringify(`./${appEntry}`)});\n`;
  const actualBootstrap = readFileSync(join(distDirectory, 'index.js'), 'utf8');
  if (actualBootstrap !== expectedBootstrap) {
    throw new Error(`Fixed bootstrap index.js must only import the manifest app entry ${appEntry}`);
  }

  const initialRoots = [...new Set([...html.scripts, ...html.preloads, appEntry])];
  const initialFiles = collectGraph(distDirectory, manifest, byFile, initialRoots, false);
  const allReachableFiles = collectGraph(distDirectory, manifest, byFile, html.scripts, true);
  const allFiles = collectFiles(distDirectory);
  const allJs = allFiles
    .filter((file) => extname(file) === '.js')
    .map((file) => normalizeArtifactPath(relative(distDirectory, file)))
    .sort();

  if (html.stylesheets.length !== 0) {
    violations.push(`index.html must not link stylesheets (CSS loads with chunks), found: ${html.stylesheets.join(', ')}`);
  }

  // CSS 全部随 chunk 拆分：每个 CSS 必须是 assets/name-hash.css 且被 manifest 的 css 字段引用
  const allCss = allFiles
    .filter((file) => extname(file) === '.css')
    .map((file) => normalizeArtifactPath(relative(distDirectory, file)))
    .sort();
  if (allCss.length === 0) {
    violations.push('No built CSS artifacts were found');
  }
  const cssOnDisk = new Set(allCss);
  const manifestCssFiles = new Set();
  for (const record of byFile.values()) {
    for (const cssFile of record.css ?? []) {
      if (typeof cssFile !== 'string') {
        violations.push(`Manifest record ${record.key} has a non-string css reference`);
        continue;
      }
      const resolvedCss = resolveArtifact(distDirectory, cssFile, `Manifest record ${record.key} css`);
      manifestCssFiles.add(resolvedCss);
    }
  }
  for (const cssFile of allCss) {
    if (!HASHED_ASSET_PATTERN.test(cssFile)) {
      violations.push(`Built CSS must use an assets/name-hash.css filename: ${cssFile}`);
    }
    if (!manifestCssFiles.has(cssFile)) {
      violations.push(`CSS artifact is not referenced by the Vite manifest: ${cssFile}`);
    }
  }
  for (const cssFile of manifestCssFiles) {
    if (!cssOnDisk.has(cssFile)) {
      violations.push(`Manifest css reference is missing on disk: ${cssFile}`);
    }
  }

  // 首屏 CSS = app 入口 chunk 携带的样式（外壳层），必须在预算内
  const appRecord = byFile.get(appEntry);
  const initialCssFiles = [...new Set(
    (appRecord?.css ?? []).filter((cssFile) => typeof cssFile === 'string')
      .map((cssFile) => resolveArtifact(distDirectory, cssFile, 'App entry css')),
  )].sort();
  if (initialCssFiles.length === 0) {
    violations.push('App entry chunk carries no CSS; the shell stylesheet is missing');
  }
  const initialCss = measureFiles(distDirectory, initialCssFiles);
  if (initialCss.raw > budgets.initialCssRaw) {
    violations.push(`Initial CSS raw ${formatSize(initialCss.raw)} exceeds ${formatSize(budgets.initialCssRaw)}`);
  }
  if (initialCss.gzip > budgets.initialCssGzip) {
    violations.push(`Initial CSS gzip ${formatSize(initialCss.gzip)} exceeds ${formatSize(budgets.initialCssGzip)}`);
  }

  const fixedArtifacts = new Set(['index.html', 'index.js', 'index.css', '.vite/manifest.json']);
  for (const file of allFiles.map((path) => normalizeArtifactPath(relative(distDirectory, path)))) {
    if (!fixedArtifacts.has(file) && !HASHED_ASSET_PATTERN.test(file)) {
      violations.push(`Non-fixed artifact must use an assets/name-hash.ext filename: ${file}`);
    }
  }

  const manifestJs = new Set(
    [...byFile.keys()].filter((file) => extname(file) === '.js'),
  );
  for (const file of allJs) {
    if (!manifestJs.has(file)) violations.push(`JS artifact is absent from manifest: ${file}`);
    if (!allReachableFiles.has(file)) violations.push(`Orphan JS artifact is unreachable from index.js: ${file}`);
    if (file !== 'index.js' && !HASHED_CHUNK_PATTERN.test(file)) {
      violations.push(`Non-entry JS must use an assets/name-hash.js filename: ${file}`);
    }
  }

  const dynamicFiles = new Set();
  for (const record of byFile.values()) {
    for (const key of record.imports ?? []) {
      const importedFile = resolveManifestReference(distDirectory, manifest, key, `Manifest record ${record.key}`);
      if (record.file !== 'index.js' && importedFile === 'index.js') {
        violations.push(`Hashed chunk imports fixed bootstrap index.js: ${record.file}`);
      }
    }
    if (record.file === 'index.js') continue;
    for (const key of record.dynamicImports ?? []) {
      dynamicFiles.add(resolveManifestReference(distDirectory, manifest, key, `Manifest record ${record.key}`));
    }
  }
  if (dynamicFiles.size === 0) violations.push('Manifest contains no dynamic imports');
  for (const file of dynamicFiles) {
    if (initialFiles.has(file)) violations.push(`Dynamic import leaked into the initial JS closure: ${file}`);
    if (html.preloads.includes(file)) violations.push(`Dynamic import was emitted as HTML modulepreload: ${file}`);
  }

  const initial = measureFiles(distDirectory, initialFiles);
  const bootstrapSize = measureFiles(distDirectory, ['index.js']);
  if (bootstrapSize.raw > BOOTSTRAP_BUDGETS.raw) {
    violations.push(`Bootstrap JS raw ${formatSize(bootstrapSize.raw)} exceeds ${formatSize(BOOTSTRAP_BUDGETS.raw)}`);
  }
  if (bootstrapSize.gzip > BOOTSTRAP_BUDGETS.gzip) {
    violations.push(`Bootstrap JS gzip ${formatSize(bootstrapSize.gzip)} exceeds ${formatSize(BOOTSTRAP_BUDGETS.gzip)}`);
  }
  if (initial.raw > budgets.initialRaw) {
    violations.push(`Initial JS raw ${formatSize(initial.raw)} exceeds ${formatSize(budgets.initialRaw)}`);
  }
  if (initial.gzip > budgets.initialGzip) {
    violations.push(`Initial JS gzip ${formatSize(initial.gzip)} exceeds ${formatSize(budgets.initialGzip)}`);
  }

  let largestRawChunk = { file: '', raw: 0, gzip: 0 };
  let largestGzipChunk = { file: '', raw: 0, gzip: 0 };
  for (const file of allJs) {
    const measured = measureFiles(distDirectory, [file]);
    if (measured.raw > largestRawChunk.raw) largestRawChunk = { file, ...measured };
    if (measured.gzip > largestGzipChunk.gzip) largestGzipChunk = { file, ...measured };
  }
  if (largestRawChunk.raw > budgets.chunkRaw) {
    violations.push(`Largest JS raw ${formatSize(largestRawChunk.raw)} exceeds ${formatSize(budgets.chunkRaw)}`);
  }
  if (largestGzipChunk.gzip > budgets.chunkGzip) {
    violations.push(`Largest JS gzip ${formatSize(largestGzipChunk.gzip)} exceeds ${formatSize(budgets.chunkGzip)}`);
  }

  for (const file of allFiles) {
    const contents = readFileSync(file);
    if (contents.includes(HARDCODED_PLUGIN_PATH)) {
      violations.push(`Hardcoded /wp-content/plugins/ path found in ${normalizeArtifactPath(relative(distDirectory, file))}`);
    }
  }

  const emptyVendors = allJs.filter((file) => {
    const basename = file.split('/').at(-1);
    return /^vendor(?:[-.].*)?\.js$/.test(basename) && statSync(join(distDirectory, file)).size <= KIB;
  });
  if (emptyVendors.length > 0) violations.push(`Empty vendor chunk found: ${emptyVendors.join(', ')}`);

  if (violations.length > 0) {
    throw new Error(`Admin build contract failed:\n${violations.join('\n')}`);
  }

  return {
    initial,
    bootstrapSize,
    initialCss,
    cssFiles: allCss,
    initialCssFiles,
    initialFiles: [...initialFiles].sort(),
    appEntry,
    largestRawChunk,
    largestGzipChunk,
    preloads: [...html.preloads].sort(),
    dynamicFiles: [...dynamicFiles].sort(),
    jsFiles: allJs,
  };
};

const cliDistIndex = process.argv.indexOf('--dist');
const cliDist = cliDistIndex >= 0 ? resolve(process.argv[cliDistIndex + 1] ?? '') : defaultDistDirectory;
const isCli = process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url);

if (isCli) {
  const result = auditBuild(cliDist);
  console.log([
    `Admin build contract passed: initial ${formatSize(result.initial.raw)} raw / ${formatSize(result.initial.gzip)} gzip`,
    `bootstrap ${formatSize(result.bootstrapSize.raw)} raw / ${formatSize(result.bootstrapSize.gzip)} gzip`,
    `app entry ${result.appEntry}`,
    `largest raw ${result.largestRawChunk.file} ${formatSize(result.largestRawChunk.raw)}`,
    `largest gzip ${result.largestGzipChunk.file} ${formatSize(result.largestGzipChunk.gzip)}`,
    `modulepreload [${result.preloads.join(', ')}], dynamic chunks ${result.dynamicFiles.length}, JS files ${result.jsFiles.length}`,`css files ${result.cssFiles.length} (initial ${formatSize(result.initialCss.raw)} raw / ${formatSize(result.initialCss.gzip)} gzip)`,
  ].join('; '));
}

export { DEFAULT_BUDGETS, auditBuild };
