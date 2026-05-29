import { describe, it, expect } from 'vitest';
import { defaultVarOption, defaultVarData } from './tool/defaultVar';

describe('defaultVar', () => {
  it('should have all required module keys', () => {
    expect(defaultVarOption).toHaveProperty('optimize');
    expect(defaultVarOption).toHaveProperty('page');
    expect(defaultVarOption).toHaveProperty('function');
    expect(defaultVarOption).toHaveProperty('login');
    expect(defaultVarOption).toHaveProperty('domestic');
    expect(defaultVarOption).toHaveProperty('performance');
    expect(defaultVarOption).toHaveProperty('ai_review');
  });

  it('should have correct optimize sub-modules', () => {
    expect(defaultVarOption.optimize).toHaveProperty('site');
    expect(defaultVarOption.optimize).toHaveProperty('medium');
    expect(defaultVarOption.optimize).toHaveProperty('admin');
  });

  it('should have correct page sub-modules', () => {
    expect(defaultVarOption.page).toHaveProperty('comment');
    expect(defaultVarOption.page).toHaveProperty('feature');
    expect(defaultVarOption.page).toHaveProperty('function');
    expect(defaultVarOption.page).toHaveProperty('jurisdiction');
  });

  it('should have valid url_site format', () => {
    expect(defaultVarData.url_site).toMatch(/^https?:\/\/.+/);
  });

  it('should have boolean values for feature toggles', () => {
    expect(typeof defaultVarOption.optimize.site.hide_top_toolbar).toBe('boolean');
    expect(typeof defaultVarOption.page.comment.comment_emote).toBe('boolean');
    expect(typeof defaultVarOption.ai_review.enabled).toBe('boolean');
  });

  it('should have valid countdown array format', () => {
    const { countdown } = defaultVarOption.page.function;
    expect(Array.isArray(countdown)).toBe(true);
    expect(countdown.length).toBe(2);
  });

  it('should have valid batch_replace_pairs array', () => {
    expect(Array.isArray(defaultVarOption.page.function.batch_replace_pairs)).toBe(true);
  });


});
