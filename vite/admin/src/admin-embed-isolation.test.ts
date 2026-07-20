import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const readRelativeFile = (relativePath: string): string =>
  readFileSync(fileURLToPath(new URL(relativePath, import.meta.url)), 'utf8');

const splitSelectorList = (selectorText: string): string[] => {
  const selectors: string[] = [];
  let start = 0;
  let parenthesisDepth = 0;
  let bracketDepth = 0;
  let quote: '"' | "'" | null = null;
  let escaped = false;

  for (let index = 0; index < selectorText.length; index += 1) {
    const character = selectorText[index];

    if (escaped) {
      escaped = false;
      continue;
    }
    if (character === '\\') {
      escaped = true;
      continue;
    }
    if (quote) {
      if (character === quote) quote = null;
      continue;
    }
    if (character === '"' || character === "'") {
      quote = character;
      continue;
    }
    if (character === '(') parenthesisDepth += 1;
    else if (character === ')') parenthesisDepth = Math.max(0, parenthesisDepth - 1);
    else if (character === '[') bracketDepth += 1;
    else if (character === ']') bracketDepth = Math.max(0, bracketDepth - 1);
    else if (character === ',' && parenthesisDepth === 0 && bracketDepth === 0) {
      selectors.push(selectorText.slice(start, index).trim());
      start = index + 1;
    }
  }

  selectors.push(selectorText.slice(start).trim());
  return selectors.filter(Boolean);
};

const collectStyleSelectors = (cssSource: string): string[] => {
  const style = document.createElement('style');
  style.textContent = cssSource;
  document.head.append(style);

  const selectors: string[] = [];
  const walkRules = (rules: CSSRuleList): void => {
    Array.from(rules).forEach((rule) => {
      if (rule.type === 1) {
        selectors.push(...splitSelectorList((rule as CSSStyleRule).selectorText));
      }

      const nestedRules = (rule as CSSMediaRule).cssRules;
      if (nestedRules) {
        walkRules(nestedRules);
      }
    });
  };

  if (!style.sheet) {
    style.remove();
    throw new Error('App.css could not be parsed');
  }

  walkRules(style.sheet.cssRules);
  style.remove();
  return selectors;
};

const collectMediaRuleDeclarations = (
  cssSource: string,
  mediaText: string,
  selectorText: string,
): Record<string, string> => {
  const style = document.createElement('style');
  style.textContent = cssSource;
  document.head.append(style);

  if (!style.sheet) {
    style.remove();
    throw new Error('App.css could not be parsed');
  }

  let declarations: Record<string, string> | null = null;

  Array.from(style.sheet.cssRules).forEach((rule) => {
    if (rule.type !== 4) return;

    const mediaRule = rule as CSSMediaRule;
    if (mediaRule.media.mediaText !== mediaText) return;

    Array.from(mediaRule.cssRules).forEach((nestedRule) => {
      if (nestedRule.type !== 1) return;

      const styleRule = nestedRule as CSSStyleRule;
      if (styleRule.selectorText !== selectorText) return;

      declarations = Object.fromEntries(
        Array.from(styleRule.style).map((property) => [
          property,
          styleRule.style.getPropertyValue(property),
        ]),
      );
    });
  });

  style.remove();

  if (!declarations) {
    throw new Error(`Missing ${selectorText} inside @media ${mediaText}`);
  }

  return declarations;
};

describe('WordPress admin embed isolation', () => {
  it('uses only mabox-namespaced selectors without a Tailwind/PostCSS pipeline', () => {
    const appStyleSource = readRelativeFile('./App.css');
    const appSource = readRelativeFile('./App.tsx');
    const selectors = collectStyleSelectors(appStyleSource);
    const packageManifest = JSON.parse(
      readRelativeFile('../../package.json'),
    ) as { devDependencies?: Record<string, string> };

    expect(appStyleSource).not.toMatch(/@(tailwind|apply)\b/);
    expect(appStyleSource).not.toContain('#root');
    expect(appSource).toContain('colorPrimary: "#3858e9"');
    expect(appSource).toContain('colorTextLightSolid: "#fff"');
    expect(appStyleSource).toContain(
      '.mabox-shell .ant-btn-primary,\n' +
      '.mabox-detail-drawer .ant-btn-primary,\n' +
      '.mabox-admin-modal .ant-btn-primary {\n' +
      '  color: #fff;\n' +
      '  background-color: #3858e9;\n' +
      '}',
    );
    expect(selectors.length).toBeGreaterThan(0);
    expect(selectors.filter((selector) => !selector.startsWith('.mabox-'))).toEqual([]);
    expect(selectors).toEqual(expect.arrayContaining([
      '.mabox-shell *',
      '.mabox-shell *::before',
      '.mabox-shell :where(h1, h2, h3, h4, h5, h6)',
      '.mabox-shell :where(p)',
      '.mabox-shell :where(pre)',
      '.mabox-shell :where(ol, ul, menu)',
      '.mabox-shell :where(button, input, optgroup, select, textarea)',
      '.mabox-detail-drawer :where(p)',
      '.mabox-admin-modal :where(p)',
      '.mabox-shell .ant-form-item-label',
      '.mabox-detail-drawer .ant-form-item-label',
      '.mabox-admin-modal .ant-form-item-label',
      '.mabox-module-grid',
    ]));
    expect(appStyleSource).toContain(
      '.mabox-module-grid {\n    grid-template-columns: minmax(0, 1fr);\n  }',
    );
    expect(appStyleSource.lastIndexOf(
      '.mabox-feature-switch-control,\n  .mabox-favorite-action {\n    min-height: 44px;\n  }',
    )).toBeGreaterThan(appStyleSource.indexOf(
      '.mabox-feature-switch-control {\n  min-width: 44px;\n  min-height: 32px;',
    ));
    expect(
      collectStyleSelectors(`${appStyleSource}\n.fixed { position: fixed; }`)
        .filter((selector) => !selector.startsWith('.mabox-')),
    ).toEqual(['.fixed']);
    expect(existsSync(new URL('../tailwind.config.js', import.meta.url))).toBe(false);
    expect(existsSync(new URL('../postcss.config.js', import.meta.url))).toBe(false);
    expect(packageManifest.devDependencies).not.toHaveProperty('tailwindcss');
    expect(packageManifest.devDependencies).not.toHaveProperty('autoprefixer');
    expect(packageManifest.devDependencies).not.toHaveProperty('postcss');
  });

  it('splits only top-level selector-list commas', () => {
    expect(collectStyleSelectors('.mabox-shell :where(.a, .b) { color: inherit; }')).toEqual([
      '.mabox-shell :where(.a, .b)',
    ]);
    expect(
      collectStyleSelectors('.mabox-shell :where(.a, .b), .evil { color: inherit; }')
        .filter((selector) => !selector.startsWith('.mabox-')),
    ).toEqual(['.evil']);
  });

  it('keeps all responsive admin CSS in the scanned bundle', () => {
    const adminPhpSource = readRelativeFile('../../../admin/class-npcink-toolbox-admin.php');
    const detailDrawerSource = readRelativeFile('./components/settings-ui/DetailDrawer.tsx');
    const diffModalSource = readRelativeFile('./components/diff-modal.tsx');
    const previewSource = readRelativeFile('./basic/preview.tsx');
    const fixedImageSource = readRelativeFile('./basic/fixedImage.tsx');
    const selectImageSource = readRelativeFile('./basic/selectImage.tsx');
    const riskyFeatureSource = readRelativeFile('./tool/riskyFeature.tsx');
    const dbCleanSource = readRelativeFile('./components/performance/db_clean.tsx');

    expect(adminPhpSource).not.toMatch(/wp_add_inline_style\s*\(/);
    expect(adminPhpSource).not.toContain('#root');
    expect(adminPhpSource).not.toMatch(/\.ant-[a-z-]+/);
    expect(detailDrawerSource).toContain('rootClassName="mabox-detail-drawer"');
    expect(diffModalSource).toContain('rootClassName="mabox-admin-modal"');
    expect(previewSource).toContain('rootClassName="mabox-admin-modal"');
    expect(fixedImageSource).toContain('rootClassName="mabox-admin-modal"');
    expect(selectImageSource).toContain('rootClassName="mabox-admin-modal"');
    expect(riskyFeatureSource).toContain('rootClassName: "mabox-admin-modal"');
    expect(dbCleanSource).toContain('rootClassName: "mabox-admin-modal"');
  });

  it('keeps the mobile workspace within the admin shell width', () => {
    const appStyleSource = readRelativeFile('./App.css');
    const bodyDeclarations = collectMediaRuleDeclarations(
      appStyleSource,
      '(max-width: 782px)',
      '.mabox-body',
    );
    const mainDeclarations = collectMediaRuleDeclarations(
      appStyleSource,
      '(max-width: 782px)',
      '.mabox-main',
    );

    expect(bodyDeclarations).toMatchObject({
      'align-items': 'stretch',
      'flex-direction': 'column',
    });
    expect(mainDeclarations).toMatchObject({
      'max-width': '100%',
      width: '100%',
    });
  });

  it('does not patch document events from the admin entry point', () => {
    const mainEntry = readRelativeFile('./main.tsx');
    const packageManifest = JSON.parse(
      readRelativeFile('../../package.json'),
    ) as { dependencies?: Record<string, string> };

    expect(mainEntry).not.toContain('default-passive-events');
    expect(packageManifest.dependencies).not.toHaveProperty('default-passive-events');
  });
});
