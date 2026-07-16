import { describe, it, expect } from "vitest";
import { diffConfig, diffSecretChanges, getDiffSummary, hasConfigChanged } from "./diff";
import { emptySecretStatus } from "./dataContext";

describe("diffConfig", () => {
  it("返回空数组当配置完全相同时", () => {
    const before = { optimize: { site: { remove_RSS_version: true } } };
    const after = { optimize: { site: { remove_RSS_version: true } } };
    expect(diffConfig(before, after)).toEqual([]);
  });

  it("检测 boolean 值变化", () => {
    const before = { optimize: { site: { remove_RSS_version: false } } };
    const after = { optimize: { site: { remove_RSS_version: true } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("optimize.site.remove_RSS_version");
    expect(diffs[0].before).toBe(false);
    expect(diffs[0].after).toBe(true);
    expect(diffs[0].riskLevel).toBe("none");
  });

  it("真实设置路径使用功能索引中的用户标签而不是内部 path", () => {
    const before = { optimize: { site: { hide_top_toolbar: false } } };
    const after = { optimize: { site: { hide_top_toolbar: true } } };

    const diffs = diffConfig(before, after);

    expect(diffs).toEqual([
      expect.objectContaining({
        path: "optimize.site.hide_top_toolbar",
        label: "隐藏顶部工具条",
        before: false,
        after: true,
      }),
    ]);
    expect(diffs[0].label).not.toBe(diffs[0].path);
  });

  it("未知设置不以内部 path 作为用户标签", () => {
    const diffs = diffConfig(
      { optimize: { unknown_group: { private_key: false } } },
      { optimize: { unknown_group: { private_key: true } } },
    );

    expect(diffs[0]).toEqual(expect.objectContaining({
      path: "optimize.unknown_group.private_key",
      label: "设置项",
    }));
  });

  it("使用生成 Schema fallback 标记低风险功能", () => {
    const before = { optimize: { medium: { no_auto_size: false } } };
    const after = { optimize: { medium: { no_auto_size: true } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("optimize.medium.no_auto_size");
    expect(diffs[0].riskLevel).toBe("low");
  });

  it("风险功能从开启到关闭时标记为 none", () => {
    const before = { optimize: { medium: { no_auto_size: true } } };
    const after = { optimize: { medium: { no_auto_size: false } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].riskLevel).toBe("none");
  });

  it("字符串 'false' 到非 false 视为开启", () => {
    const before = { optimize: { medium: { no_auto_size: "false" } } };
    const after = { optimize: { medium: { no_auto_size: true } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("optimize.medium.no_auto_size");
    expect(diffs[0].riskLevel).toBe("low");
  });

  it("普通字符串变化标记为 none", () => {
    const before = { function: { seo: { seo_home: "" } } };
    const after = { function: { seo: { seo_home: "My Blog" } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("function.seo.seo_home");
    expect(diffs[0].riskLevel).toBe("none");
  });

  it("登录安全字段在保存摘要中使用可读标签", () => {
    const before = {
      domestic: {
        login_security: {
          attempt_limit_enabled: false,
          attempt_window_minutes: 15,
        },
      },
    };
    const after = {
      domestic: {
        login_security: {
          attempt_limit_enabled: true,
          attempt_window_minutes: 30,
        },
      },
    };

    const diffs = diffConfig(before, after);

    expect(diffs).toEqual(expect.arrayContaining([
      expect.objectContaining({
        path: "domestic.login_security.attempt_limit_enabled",
        label: "登录尝试保护",
        riskLevel: "low",
      }),
      expect.objectContaining({
        path: "domestic.login_security.attempt_window_minutes",
        label: "统计窗口（分钟）",
      }),
    ]));
  });

  it("嵌套对象变化生成多条 diff", () => {
    const before = {
      optimize: { site: { remove_RSS_version: false, hide_top_toolbar: false } },
    };
    const after = {
      optimize: { site: { remove_RSS_version: true, hide_top_toolbar: true } },
    };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(2);
    expect(diffs[0].module).toBe("optimize");
    expect(diffs[1].module).toBe("optimize");
  });

  it("新增字段视为变化", () => {
    const before = { optimize: { site: {} } };
    const after = { optimize: { site: { remove_RSS_version: true } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].before).toBeUndefined();
    expect(diffs[0].after).toBe(true);
  });

  it("删除字段视为变化", () => {
    const before = { optimize: { site: { remove_RSS_version: true } } };
    const after = { optimize: { site: {} } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].before).toBe(true);
    expect(diffs[0].after).toBeUndefined();
  });

  it("数组变化视为变化", () => {
    const before = { page: { function: { batch_replace: true } } };
    const after = { page: { function: { batch_replace: false } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].before).toBe(true);
    expect(diffs[0].after).toBe(false);
  });

  it("高风险项排序在前", () => {
    const before = {
      optimize: { site: { remove_RSS_version: false } },
      performance: { db_clean: { enabled: false } },
    };
    const after = {
      optimize: { site: { remove_RSS_version: true } },
      performance: { db_clean: { enabled: true } },
    };
    const diffs = diffConfig(before, after);
    expect(diffs[0].path).toBe("performance.db_clean.enabled");
    expect(diffs[0].riskLevel).toBe("high");
    expect(diffs[1].path).toBe("optimize.site.remove_RSS_version");
    expect(diffs[1].riskLevel).toBe("none");
  });

  it("Schema 未缓存时仍以静态最小权威镜像识别真实 high", () => {
    const before = { performance: { db_clean: { enabled: false } } };
    const after = { performance: { db_clean: { enabled: true } } };

    const diffs = diffConfig(before, after);

    expect(diffs).toEqual([
      expect.objectContaining({
        path: "performance.db_clean.enabled",
        label: "数据库清理优化",
        riskLevel: "high",
      }),
    ]);
  });
});

describe("getDiffSummary", () => {
  it("空 diff 返回 hasChanges false", () => {
    const summary = getDiffSummary([]);
    expect(summary.hasChanges).toBe(false);
    expect(summary.totalCount).toBe(0);
    expect(summary.highRiskCount).toBe(0);
    expect(summary.requiresConfirmation).toBe(false);
  });

  it("统计总变更数和高风险数", () => {
    const diffs = [
      { path: "a", label: "A", module: "mod", before: false, after: true, riskLevel: "high" as const },
      { path: "b", label: "B", module: "mod", before: false, after: true, riskLevel: "none" as const },
      { path: "c", label: "C", module: "mod2", before: false, after: true, riskLevel: "high" as const },
    ];
    const summary = getDiffSummary(diffs);
    expect(summary.totalCount).toBe(3);
    expect(summary.highRiskCount).toBe(2);
    expect(summary.hasChanges).toBe(true);
    expect(summary.requiresConfirmation).toBe(true);
    expect(summary.modulesChanged).toEqual(["mod", "mod2"]);
  });
});

describe("hasConfigChanged", () => {
  it("相同配置返回 false", () => {
    const config = { a: 1 };
    expect(hasConfigChanged(config, config)).toBe(false);
  });

  it("不同配置返回 true", () => {
    expect(hasConfigChanged({ a: 1 }, { a: 2 })).toBe(true);
  });
});

describe("diffSecretChanges", () => {
  it("凭据 diff 只包含状态词，不包含替换值", () => {
    const canary = "canary-must-not-leak";
    const status = emptySecretStatus();
    status["domestic.wechat.appsecret"] = { configured: true };

    const diffs = diffSecretChanges(status, {
      "domestic.wechat.appsecret": { operation: "replace", value: canary },
      "performance.oss.access_key": { operation: "clear" },
    });

    expect(diffs).toEqual([
      expect.objectContaining({ before: "已配置", after: "将替换" }),
      expect.objectContaining({ before: "未配置", after: "将清除" }),
    ]);
    expect(JSON.stringify(diffs)).not.toContain(canary);
  });
});
