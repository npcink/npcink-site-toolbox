import { describe, it, expect } from "vitest";
import { diffConfig, getDiffSummary, hasConfigChanged } from "./diff";

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

  it("高风险功能从关闭到开启时标记为 high", () => {
    const before = { page: { jurisdiction: { ban_copy: false } } };
    const after = { page: { jurisdiction: { ban_copy: true } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("page.jurisdiction.ban_copy");
    expect(diffs[0].riskLevel).toBe("high");
  });

  it("高风险功能从开启到关闭时标记为 none", () => {
    const before = { page: { jurisdiction: { ban_copy: true } } };
    const after = { page: { jurisdiction: { ban_copy: false } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].riskLevel).toBe("none");
  });

  it("字符串 'false' 到非 false 视为开启", () => {
    const before = { page: { jurisdiction: { ban_copy: "false" } } };
    const after = { page: { jurisdiction: { ban_copy: true } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("page.jurisdiction.ban_copy");
    expect(diffs[0].riskLevel).toBe("high");
  });

  it("普通字符串变化标记为 none", () => {
    const before = { function: { seo: { seo_home: "" } } };
    const after = { function: { seo: { seo_home: "My Blog" } } };
    const diffs = diffConfig(before, after);
    expect(diffs).toHaveLength(1);
    expect(diffs[0].path).toBe("function.seo.seo_home");
    expect(diffs[0].riskLevel).toBe("none");
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
      page: { jurisdiction: { ban_copy: false } },
    };
    const after = {
      optimize: { site: { remove_RSS_version: true } },
      page: { jurisdiction: { ban_copy: true } },
    };
    const diffs = diffConfig(before, after);
    expect(diffs[0].path).toBe("page.jurisdiction.ban_copy");
    expect(diffs[0].riskLevel).toBe("high");
    expect(diffs[1].path).toBe("optimize.site.remove_RSS_version");
    expect(diffs[1].riskLevel).toBe("none");
  });

  it("所有已知高风险路径都被识别", () => {
    const riskyPaths = [
      { path: ["page", "jurisdiction", "ban_copy"], before: false, after: true },
      { path: ["optimize", "medium", "no_auto_size"], before: false, after: true },
    ];

    riskyPaths.forEach(({ path, before, after }) => {
      const beforeObj = path.reduceRight(
        (acc, key) => ({ [key]: acc }),
        before as any
      );
      const afterObj = path.reduceRight(
        (acc, key) => ({ [key]: acc }),
        after as any
      );
      const diffs = diffConfig(beforeObj, afterObj);
      expect(diffs).toHaveLength(1);
      expect(diffs[0].riskLevel).toBe("high");
    });
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
