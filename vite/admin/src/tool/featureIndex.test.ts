import { describe, it, expect, vi, beforeEach } from "vitest";

import { searchIndex } from "@/tool/featureIndexData";
import { ADMIN_VIEWS } from "@/tool/navigation";

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
  });

  describe("fetchFeatureIndex", () => {
    it("returns merged index after schema fetch", async () => {
      mockFetchUiSchema.mockResolvedValue({
        "domestic-login_security-custom_login_enabled": {
          path: "domestic.login_security.custom_login_enabled",
          type: "boolean",
          label: "自定义登录地址",
          group: "登录安全",
          feature_id: "domestic-login_security-custom_login_enabled",
          risk_tags: ["安全"],
          preset_tags: ["security"],
        },
      });
      mockGetUiSchemaSync.mockReturnValue(null);
      const { fetchFeatureIndex } = await import("@/tool/featureIndex");
      const index = await fetchFeatureIndex();
      const loginItem = index.find((i) => i.id === "domestic-login_security-custom_login_enabled");
      expect(loginItem).toBeDefined();
      expect(loginItem!.tags).toEqual(["安全"]);
      expect(loginItem!.tabKey).toBe("china");
    });

    it("returns base index when schema fetch fails", async () => {
      mockFetchUiSchema.mockResolvedValue(null);
      mockGetUiSchemaSync.mockReturnValue(null);
      const { fetchFeatureIndex } = await import("@/tool/featureIndex");
      const index = await fetchFeatureIndex();
      expect(index.length).toBeGreaterThanOrEqual(2);
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
    });
  });
});
