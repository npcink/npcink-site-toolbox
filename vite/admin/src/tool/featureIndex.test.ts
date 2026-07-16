import { describe, it, expect, vi, beforeEach } from "vitest";

import { searchIndex } from "@/tool/featureIndexData";
import { ADMIN_VIEWS } from "@/tool/navigation";
import settingsContract from "@/generated/settings-contract.json";

const mockFetchUiSchema = vi.fn();
const mockGetUiSchemaSync = vi.fn();

vi.mock("@/tool/uiSchema", () => ({
  fetchUiSchema: () => mockFetchUiSchema(),
  getUiSchemaSync: () => mockGetUiSchemaSync(),
}));

describe("featureIndex", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
  });

  describe("getFeatureIndexSync", () => {
    it("returns base index when no schema cached", async () => {
      mockGetUiSchemaSync.mockReturnValue(null);
      const { getFeatureIndexSync } = await import("@/tool/featureIndex");
      const index = getFeatureIndexSync();
      expect(index.length).toBeGreaterThanOrEqual(2);
      expect(index[0].id).toBe("optimize-site-hide_top_toolbar");
    });

    it("returns merged index when schema is cached", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "page-feature-reading_progress": {
          path: "page.feature.reading_progress",
          type: "boolean",
          label: "页顶阅读进度条",
          group: "外观",
          feature_id: "page-feature-reading_progress",
          risk_tags: ["仅前台"],
        },
      });
      const { getFeatureIndexSync } = await import("@/tool/featureIndex");
      const index = getFeatureIndexSync();
      const readingProgressItem = index.find((i) => i.id === "page-feature-reading_progress");
      expect(readingProgressItem).toBeDefined();
      expect(readingProgressItem!.label).toBe("页顶阅读进度条");
      expect(readingProgressItem!.tabKey).toBe("content");
    });

    it("does not recreate the retired login captcha entry from legacy schema", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "login-security-login_code": {
          path: "login.security.login_code",
          type: "string",
          label: "登录验证码",
          group: "安全",
          feature_id: "login-security-login_code",
        },
      });
      const { getFeatureIndexSync } = await import("@/tool/featureIndex");

      expect(getFeatureIndexSync().some((item) => item.id === "login-security-login_code")).toBe(false);
    });
  });

  describe("fetchFeatureIndex", () => {
    it("returns merged index after schema fetch", async () => {
      mockFetchUiSchema.mockResolvedValue({
        "domestic-login_security-attempt_limit_enabled": {
          path: "domestic.login_security.attempt_limit_enabled",
          type: "boolean",
          label: "登录尝试保护",
          group: "登录安全",
          feature_id: "domestic-login_security-attempt_limit_enabled",
          risk_tags: ["安全"],
          preset_tags: ["security"],
        },
      });
      mockGetUiSchemaSync.mockReturnValue(null);
      const { fetchFeatureIndex } = await import("@/tool/featureIndex");
      const index = await fetchFeatureIndex();
      const loginItem = index.find((i) => i.id === "domestic-login_security-attempt_limit_enabled");
      expect(loginItem).toBeDefined();
      expect(loginItem!.tags).toEqual(["安全"]);
      expect(loginItem!.tabKey).toBe("china");
      expect(index.filter((i) => i.id === "domestic-login_security-attempt_limit_enabled")).toHaveLength(1);
    });

    it("returns base index when schema fetch fails", async () => {
      mockFetchUiSchema.mockResolvedValue(null);
      mockGetUiSchemaSync.mockReturnValue(null);
      const { fetchFeatureIndex } = await import("@/tool/featureIndex");
      const index = await fetchFeatureIndex();
      expect(index.length).toBeGreaterThanOrEqual(2);
    });
  });

  describe("getFeatureLabelForPath", () => {
    it("优先使用已加载 schema 标签，并以静态功能索引覆盖已知路径", async () => {
      mockGetUiSchemaSync.mockReturnValue(null);
      const { getFeatureLabelForPath } = await import("@/tool/featureIndex");

      expect(getFeatureLabelForPath("optimize.site.hide_top_toolbar")).toBe("隐藏顶部工具条");

      mockGetUiSchemaSync.mockReturnValue({
        "optimize-test-display_name": {
          path: "optimize.test.display_name",
          type: "string",
          label: "显示名称",
          feature_id: "optimize-test-display_name",
        },
      });
      expect(getFeatureLabelForPath("optimize.test.display_name")).toBe("显示名称");
    });
  });

  describe("getFeatureRiskLevelForPath", () => {
    it("使用生成的 Schema fallback 识别当前真实风险等级", async () => {
      mockGetUiSchemaSync.mockReturnValue(settingsContract.uiSchema);
      const { getFeatureRiskLevelForPath } = await import("@/tool/featureIndex");

      expect(getFeatureRiskLevelForPath("performance.db_clean.enabled")).toBe("high");
      expect(getFeatureRiskLevelForPath("optimize.medium.no_auto_size")).toBe("low");
    });

    it("已加载时以 UI Schema 的精确 path 风险等级为权威", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "performance-db_clean-enabled": {
          path: "performance.db_clean.enabled",
          type: "boolean",
          label: "数据库清理优化",
          risk: {
            level: "high",
            title: "数据库清理",
            warning: "不可逆",
            suggestion: "先备份",
          },
        },
        "optimize-medium-no_auto_size": {
          path: "optimize.medium.no_auto_size",
          type: "boolean",
          label: "禁止缩略图",
          risk: {
            level: "low",
            title: "禁止缩略图",
            warning: "可能不兼容",
            suggestion: "先检查主题",
          },
        },
      });
      const { getFeatureRiskLevelForPath } = await import("@/tool/featureIndex");

      expect(getFeatureRiskLevelForPath("performance.db_clean.enabled")).toBe("high");
      expect(getFeatureRiskLevelForPath("optimize.medium.no_auto_size")).toBe("low");

      mockGetUiSchemaSync.mockReturnValue({
        "performance-db_clean-enabled": {
          path: "performance.db_clean.enabled",
          type: "boolean",
          label: "数据库清理优化",
        },
      });
      expect(getFeatureRiskLevelForPath("performance.db_clean.enabled")).toBe("none");

      mockGetUiSchemaSync.mockReturnValue({
        "performance-db_clean-enabled": {
          path: "performance.db_clean.enabled",
          type: "boolean",
          label: "数据库清理优化",
          risk: { level: "none" },
        },
      });
      expect(getFeatureRiskLevelForPath("performance.db_clean.enabled")).toBe("none");
    });
  });

  describe("risk_tags vs preset_tags display logic", () => {
    it("uses risk_tags as display tags when available", async () => {
      const schema = {
        "optimize-test-risk_item": {
          path: "optimize.test.risk_item",
          type: "boolean",
          label: "风险项",
          group: "测试",
          feature_id: "optimize-test-risk_item",
          risk_tags: ["谨慎"],
          preset_tags: ["fancy", "performance"],
        },
      };
      mockFetchUiSchema.mockResolvedValue(schema);
      mockGetUiSchemaSync.mockReturnValue(null);
      const { fetchFeatureIndex } = await import("@/tool/featureIndex");
      const index = await fetchFeatureIndex();
      const item = index.find((i) => i.id === "optimize-test-risk_item");
      expect(item).toBeDefined();
      expect(item!.tags).toEqual(["谨慎"]);
      expect(item!.tabKey).toBe("site");
    });

    it("maps preset_tags to display tags when no risk_tags", async () => {
      const schema = {
        "optimize-test-safe_item": {
          path: "optimize.test.safe_item",
          type: "boolean",
          label: "安全项",
          group: "测试",
          feature_id: "optimize-test-safe_item",
          preset_tags: ["performance", "security"],
        },
      };
      mockFetchUiSchema.mockResolvedValue(schema);
      mockGetUiSchemaSync.mockReturnValue(null);
      const { fetchFeatureIndex } = await import("@/tool/featureIndex");
      const index = await fetchFeatureIndex();
      const item = index.find((i) => i.id === "optimize-test-safe_item");
      expect(item).toBeDefined();
      expect(item!.tags).toEqual(["性能", "安全"]);
    });
  });

  describe("baseFeatureIndex", () => {
    it("is the static searchIndex", async () => {
      const { baseFeatureIndex } = await import("@/tool/featureIndex");
      expect(baseFeatureIndex.length).toBeGreaterThanOrEqual(2);
    });

    it("uses semantic destinations for every static search item", () => {
      const validViews = new Set<string>(ADMIN_VIEWS);
      expect(searchIndex.every((item) => validViews.has(item.tabKey))).toBe(true);
      expect(searchIndex.some((item) => /^\d+$/.test(item.tabKey))).toBe(false);
      expect(searchIndex.some((item) => item.id === "login-security-login_code")).toBe(false);
      expect(searchIndex.map((item) => item.id)).toEqual(expect.arrayContaining([
        "domestic-login_security-attempt_limit_enabled",
        "domestic-login_security-anonymous_author_guard_enabled",
      ]));
      expect(searchIndex.some((item) => [
        "domestic-login_security-fail_limit_enabled",
        "domestic-login_security-ip_lock_enabled",
        "domestic-login_security-custom_login_enabled",
        "domestic-login_security-ban_enumeration_enabled",
        "domestic-login_security-login_notify_enabled",
        "domestic-login_security-login_log_enabled",
        "domestic-login_security-ip_whitelist_enabled",
      ].includes(item.id))).toBe(false);
    });
  });
});
