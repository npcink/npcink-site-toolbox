import { describe, it, expect } from 'vitest';
import { defaultVarOption, defaultVarData } from './tool/defaultVar';

describe('defaultVar', () => {
  it('should have all required module keys', () => {
    expect(defaultVarOption).toHaveProperty('optimize');
    expect(defaultVarOption).toHaveProperty('page');
    expect(defaultVarOption).toHaveProperty('function');
    expect(defaultVarOption).not.toHaveProperty('login');
    expect(defaultVarOption).toHaveProperty('domestic');
    expect(defaultVarOption).toHaveProperty('performance');
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

  it('should keep real login protections under domestic settings', () => {
    expect(defaultVarOption.domestic.login_security).toEqual({
      attempt_limit_enabled: false,
      attempt_limit_count: 5,
      attempt_window_minutes: 15,
      lock_duration_minutes: 30,
      trusted_proxies: '',
      anonymous_author_guard_enabled: false,
    });

    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('fail_limit_enabled');
    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('ip_lock_enabled');
    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('custom_login_enabled');
    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('ban_enumeration_enabled');
    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('login_notify_enabled');
    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('login_log_enabled');
    expect(defaultVarOption.domestic.login_security).not.toHaveProperty('ip_whitelist_enabled');
  });

  it('should have valid url_site format', () => {
    expect(defaultVarData.url_site).toMatch(/^https?:\/\/.+/);
  });

  it('should have boolean values for feature toggles', () => {
    expect(typeof defaultVarOption.optimize.site.hide_top_toolbar).toBe('boolean');
    expect(typeof defaultVarOption.page.comment.interval).toBe('boolean');
  });

  it('should have valid countdown array format', () => {
    const { countdown } = defaultVarOption.page.function;
    expect(Array.isArray(countdown)).toBe(true);
    expect(countdown).toEqual([]);
  });

  it('should have valid batch_replace_pairs array', () => {
    expect(Array.isArray(defaultVarOption.page.function.batch_replace_pairs)).toBe(true);
  });

  it('does not expose sensitive schema fields', () => {
    expect(defaultVarOption.domestic.wechat).not.toHaveProperty('appsecret');
    expect(defaultVarOption.performance.oss).not.toHaveProperty('access_key');
    expect(defaultVarOption.performance.oss).not.toHaveProperty('secret_key');
  });


});
