import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const adminRoot = dirname(dirname(fileURLToPath(import.meta.url)));
const distDirectory = join(adminRoot, 'dist');
const cssFiles = [];
const violations = [];
let selectorCount = 0;

const collectCssFiles = (directory) => {
  for (const entry of readdirSync(directory, { withFileTypes: true })) {
    const path = join(directory, entry.name);

    if (entry.isDirectory()) {
      collectCssFiles(path);
    } else if (entry.isFile() && entry.name.endsWith('.css')) {
      cssFiles.push(path);
    }
  }
};

const splitSelectorList = (selectorText) => {
  const selectors = [];
  let start = 0;
  let parenthesisDepth = 0;
  let bracketDepth = 0;
  let quote = null;
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

const collectStyleSelectors = (cssSource, file) => {
  const dom = new JSDOM('<!doctype html><html><head></head><body></body></html>');
  const style = dom.window.document.createElement('style');
  style.textContent = cssSource;
  dom.window.document.head.append(style);

  if (!style.sheet) {
    dom.window.close();
    throw new Error(`Built admin CSS could not be parsed: ${file}`);
  }

  const selectors = [];
  const walkRules = (rules) => {
    for (const rule of Array.from(rules)) {
      if (rule.type === 1) {
        selectors.push(...splitSelectorList(rule.selectorText));
      }

      if (rule.cssRules) {
        walkRules(rule.cssRules);
      }
    }
  };

  walkRules(style.sheet.cssRules);
  style.remove();
  dom.window.close();
  return selectors;
};

collectCssFiles(distDirectory);

for (const file of cssFiles) {
  const relativeFile = relative(distDirectory, file);
  const cssSource = readFileSync(file, 'utf8');

  for (const selector of collectStyleSelectors(cssSource, relativeFile)) {
    selectorCount += 1;
    if (!selector.startsWith('.mabox-')) {
      violations.push(`${relativeFile}: ${selector}`);
    }
  }
}

if (cssFiles.length === 0 || selectorCount === 0) {
  throw new Error('No built admin CSS selectors were found');
}

if (violations.length > 0) {
  throw new Error(`Admin CSS escaped the .mabox-* namespace:\n${violations.join('\n')}`);
}

console.log(`Admin CSS isolation passed: ${cssFiles.length} files, ${selectorCount} selectors`);
