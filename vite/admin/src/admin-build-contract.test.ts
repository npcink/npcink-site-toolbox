import { spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { fileURLToPath } from 'node:url';
import { afterEach, describe, expect, it } from 'vitest';

const readRelativeFile = (relativePath: string): string =>
  readFileSync(fileURLToPath(new URL(relativePath, import.meta.url)), 'utf8');
const scannerRelativePath = './check-admin-build-contract.mjs';
const scanner = fileURLToPath(new URL(scannerRelativePath, import.meta.url));
const temporaryDirectories: string[] = [];

type ManifestRecord = {
  file: string;
  isEntry?: boolean;
  isDynamicEntry?: boolean;
  imports?: string[];
  dynamicImports?: string[];
};

const createFixture = (overrides?: {
  html?: string;
  manifest?: Record<string, ManifestRecord>;
  files?: Record<string, string | Buffer>;
}): string => {
  const directory = mkdtempSync(join(tmpdir(), 'mabox-build-contract-'));
  temporaryDirectories.push(directory);
  const defaultHtml = `<!doctype html><script type="module" src="./index.js"></script>
    <link rel="modulepreload" href="./assets/shared-abcdef12.js">
    <link rel="stylesheet" href="./index.css">`;
  const defaultManifest: Record<string, ManifestRecord> = {
    'index.html': {
      file: 'index.js',
      isEntry: true,
      dynamicImports: ['app'],
    },
    app: {
      file: 'assets/main-fedcba98.js',
      isDynamicEntry: true,
      imports: ['shared'],
      dynamicImports: ['lazy'],
    },
    shared: { file: 'assets/shared-abcdef12.js' },
    lazy: { file: 'assets/lazy-12345678.js', isDynamicEntry: true },
  };
  const defaultFiles: Record<string, string | Buffer> = {
    'index.html': overrides?.html ?? defaultHtml,
    'index.js': 'void import("./assets/main-fedcba98.js");\n',
    'index.css': '.mabox-shell{display:block}',
    'assets/main-fedcba98.js': 'import "./shared-abcdef12.js"; import("./lazy-12345678.js");',
    'assets/shared-abcdef12.js': 'export const shared = true;',
    'assets/lazy-12345678.js': 'export const lazy = true;',
    '.vite/manifest.json': JSON.stringify(overrides?.manifest ?? defaultManifest),
    ...overrides?.files,
  };
  for (const [path, contents] of Object.entries(defaultFiles)) {
    const file = join(directory, path);
    mkdirSync(join(file, '..'), { recursive: true });
    writeFileSync(file, contents);
  }
  return directory;
};

const scan = (directory: string): string => {
  const result = spawnSync(process.execPath, [scanner, '--dist', directory], { encoding: 'utf8' });
  if (result.status !== 0) throw new Error(result.stderr);
  return result.stdout;
};

afterEach(() => {
  temporaryDirectories.splice(0).forEach((directory) => rmSync(directory, { recursive: true, force: true }));
});

describe('admin build contract scanner', () => {
  it('locks relative base, manifest graph, fixed entry/CSS and hashed lazy assets', () => {
    const config = readRelativeFile('../vite.config.ts');
    const packageManifest = readRelativeFile('../../package.json');
    const adminPhp = readRelativeFile('../../../admin/class-npcink-toolbox-admin.php');
    const pluginPhp = readRelativeFile('../../../includes/class-npcink-site-toolbox.php');
    const htmlSource = readRelativeFile('../index.html');
    const bootstrapSource = readRelativeFile('./bootstrap.ts');

    expect(config).toContain('base: "./"');
    expect(config).toContain('manifest: ".vite/manifest.json"');
    expect(config).toContain('cssCodeSplit: false');
    expect(config).toContain('modulePreload: false');
    expect(config).toContain('chunkSizeWarningLimit: 400');
    expect(config).toContain('chunkInfo.name === "index"');
    expect(config).toContain('path.resolve(__dirname, "src/main.tsx")');
    expect(config).toContain('chunkFileNames: "assets/[name]-[hash].js"');
    expect(config).not.toContain('manualChunks');
    expect(config).not.toContain('/wp-content/plugins/');
    expect(packageManifest).toContain('node admin/src/check-admin-build-contract.mjs');
    expect(packageManifest).toContain('"test:coverage": "vitest run --root admin --coverage --maxWorkers=1 --minWorkers=1"');
    expect(packageManifest).not.toContain('--maxWorkers=2 --minWorkers=2');
    expect(adminPhp).toContain("filemtime($index_js_path)");
    expect(adminPhp).toContain("filemtime($index_css_path)");
    expect(adminPhp).toContain("wp_enqueue_script($name, $index_js, array(), $index_js_version, true)");
    expect(adminPhp).toContain("wp_enqueue_style($name, $index_css, array(), $index_css_version, false)");
    expect(pluginPhp).toContain("str_replace('<script', '<script type=\"module\"', $tag)");
    expect(htmlSource).toContain('src="/src/bootstrap.ts"');
    expect(bootstrapSource.trim()).toBe("void import('./main.tsx')");
  });

  it('counts HTML modulepreloads and recursive static imports but keeps dynamic chunks lazy', () => {
    const output = scan(createFixture());

    expect(output).toContain('modulepreload [assets/shared-abcdef12.js]');
    expect(output).toContain('app entry assets/main-fedcba98.js');
    expect(output).toContain('dynamic chunks 1');
    expect(output).toContain('JS files 4');
  });

  it('rejects an empty fixed bootstrap even when the manifest graph is valid', () => {
    expect(() => scan(createFixture({
      files: { 'index.js': '/* empty bootstrap */' },
    }))).toThrow(/must only import the manifest app entry/);
  });

  it('rejects a fixed bootstrap that imports the wrong hashed app entry', () => {
    expect(() => scan(createFixture({
      files: { 'index.js': 'void import("./assets/wrong-deadbeef.js");\n' },
    }))).toThrow(/must only import the manifest app entry assets\/main-fedcba98\.js/);
  });

  it('fails closed when HTML escapes dist or a manifest reference is missing', () => {
    expect(() => scan(createFixture({
      html: '<script type="module" src="../index.js"></script>',
    }))).toThrow(/escapes the dist directory/);

    expect(() => scan(createFixture({
      html: '<script type="module" src="./index.js"></script><link rel="stylesheet" href="./index.css">',
      manifest: {
        'index.html': { file: 'index.js', isEntry: true, dynamicImports: ['missing'] },
      },
    }))).toThrow(/references missing manifest key: missing/);
  });

  it('rejects a dynamic entry promoted into initial modulepreload', () => {
    expect(() => scan(createFixture({
      html: `<!doctype html><script type="module" src="./index.js"></script>
        <link rel="modulepreload" href="./assets/lazy-12345678.js">
        <link rel="stylesheet" href="./index.css">`,
    }))).toThrow(/Dynamic import leaked into the initial JS closure/);
  });

  it('rejects a hashed app chunk that statically imports the queried fixed bootstrap', () => {
    expect(() => scan(createFixture({
      manifest: {
        'index.html': { file: 'index.js', isEntry: true, dynamicImports: ['app'] },
        app: {
          file: 'assets/main-fedcba98.js',
          isDynamicEntry: true,
          imports: ['index.html'],
          dynamicImports: ['lazy'],
        },
        shared: { file: 'assets/shared-abcdef12.js' },
        lazy: { file: 'assets/lazy-12345678.js', isDynamicEntry: true },
      },
    }))).toThrow(/Hashed chunk imports fixed bootstrap index\.js/);
  });

  it('rejects over-budget initial JS and non-hashed lazy chunks', () => {
    expect(() => scan(createFixture({
      files: { 'assets/main-fedcba98.js': Buffer.alloc(401 * 1024, 1) },
    }))).toThrow(/Initial JS raw .* exceeds/);

    expect(() => scan(createFixture({
      files: { 'assets/main-fedcba98.js': randomBytes(200 * 1024) },
    }))).toThrow(/Initial JS gzip .* exceeds/);

    expect(() => scan(createFixture({
      files: {
        'assets/main-fedcba98.js': Buffer.alloc(100 * 1024, 1),
        'assets/lazy-12345678.js': randomBytes(200 * 1024),
      },
    }))).toThrow(/Largest JS gzip .* exceeds/);

    expect(() => scan(createFixture({
      html: '<script type="module" src="./index.js"></script><link rel="stylesheet" href="./index.css">',
      manifest: {
        'index.html': { file: 'index.js', isEntry: true, dynamicImports: ['app'] },
        app: { file: 'assets/main-fedcba98.js', isDynamicEntry: true, dynamicImports: ['lazy'] },
        lazy: { file: 'assets/lazy.js', isDynamicEntry: true },
      },
      files: { 'assets/lazy.js': 'export const lazy = true;' },
    }))).toThrow(/Non-entry JS must use an assets\/name-hash\.js filename/);
  });

  it('rejects orphan empty vendor chunks and hardcoded plugin install paths', () => {
    expect(() => scan(createFixture({
      files: { 'assets/vendor-deadbeef.js': 'export{};' },
    }))).toThrow(/Orphan JS artifact is unreachable|Empty vendor chunk found/);

    expect(() => scan(createFixture({
      files: { 'index.css': 'url(/wp-content/plugins/npcink-site-toolbox/image.png)' },
    }))).toThrow(/Hardcoded \/wp-content\/plugins\/ path/);
  });

  it('rejects split CSS, a non-fixed stylesheet link and unhashed assets', () => {
    expect(() => scan(createFixture({
      files: { 'assets/lazy-deadbeef.css': '.mabox-lazy{}' },
    }))).toThrow(/Expected exactly one built CSS file index\.css/);

    expect(() => scan(createFixture({
      html: '<script type="module" src="./index.js"></script><link rel="stylesheet" href="./assets/admin-deadbeef.css">',
      files: { 'assets/admin-deadbeef.css': '.mabox-shell{}' },
    }))).toThrow(/Expected one fixed stylesheet index\.css/);

    expect(() => scan(createFixture({
      files: { 'assets/logo.svg': '<svg />' },
    }))).toThrow(/Non-fixed artifact must use an assets\/name-hash\.ext filename/);
  });
});
