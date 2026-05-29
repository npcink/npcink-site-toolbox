import { describe, it, expect, vi, beforeEach } from "vitest";

const mockFetchUiSchema = vi.fn();
const mockGetUiSchemaSync = vi.fn();

vi.mock("@/tool/uiSchema", () => ({
  fetchUiSchema: () => mockFetchUiSchema(),
  getUiSchemaSync: () => mockGetUiSchemaSync(),
}));

const staticIndex = [
  { id: "optimize-site-hide_top_toolbar", label: "隐藏顶部工具条", tabKey: "2", tabLabel: "优化", section: "站点", keywords: ["toolbar"], tags: ["推荐", "仅后台"] },
  { id: "optimize-medium-no_auto_size", label: "禁止缩略图", tabKey: "2", tabLabel: "优化", section: "媒体", keywords: ["thumbnail"], tags: ["谨慎"] },
];

vi.mock("@/tool/featureIndexData", () => ({
  searchIndex: staticIndex,
  SearchItem: {} as any,
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
  });
});
