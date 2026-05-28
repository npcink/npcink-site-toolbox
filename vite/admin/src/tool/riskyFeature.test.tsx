import { describe, it, expect, vi, beforeEach } from "vitest";

const mockFetchUiSchema = vi.fn();
const mockGetUiSchemaSync = vi.fn();

vi.mock("@/tool/uiSchema", () => ({
  fetchUiSchema: () => mockFetchUiSchema(),
  getUiSchemaSync: () => mockGetUiSchemaSync(),
}));

vi.mock("antd", () => ({
  Modal: {
    confirm: vi.fn(),
    destroyAll: vi.fn(),
  },
}));

vi.mock("@ant-design/icons", () => ({
  ExclamationCircleOutlined: () => null,
}));

function createStorageMock() {
  let store: Record<string, string> = {};

  return {
    getItem: vi.fn((key: string) => store[key] ?? null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value;
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key];
    }),
    clear: vi.fn(() => {
      store = {};
    }),
  };
}

let storageMock: ReturnType<typeof createStorageMock>;

describe("riskyFeature", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();

    storageMock = createStorageMock();
    Object.defineProperty(globalThis, "localStorage", {
      value: storageMock,
      configurable: true,
    });
  });

  describe("checkRiskyFeature - schema cached", () => {
    it("uses schema risk info when cached", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "page-jurisdiction-ban_copy": {
          path: "page.jurisdiction.ban_copy",
          type: "boolean",
          feature_id: "page-jurisdiction-ban_copy",
          risk: {
            level: "low",
            title: "禁止复制",
            warning: "此功能可能影响正常用户复制内容。",
            suggestion: "内容站谨慎开启。",
          },
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("page-jurisdiction-ban_copy", true, onConfirm);
      expect(result).toBe(false);
    });

    it("returns true for non-risky feature when schema cached", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "optimize-site-hide_top_toolbar": {
          path: "optimize.site.hide_top_toolbar",
          type: "boolean",
          feature_id: "optimize-site-hide_top_toolbar",
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-site-hide_top_toolbar", true, onConfirm);
      expect(result).toBe(true);
    });
  });

  describe("checkRiskyFeature - schema not cached", () => {
    it("fetches schema when not cached and calls onConfirm if no risk found", async () => {
      mockGetUiSchemaSync.mockReturnValue(null);
      mockFetchUiSchema.mockResolvedValue({
        "optimize-test-nonexistent": {
          path: "optimize.test.nonexistent",
          type: "boolean",
          feature_id: "optimize-test-nonexistent",
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-test-nonexistent", true, onConfirm);
      expect(result).toBe(false);
      await vi.waitFor(() => {
        expect(mockFetchUiSchema).toHaveBeenCalled();
      });
    });

    it("fetches schema and shows risk modal when risk found after fetch", async () => {
      mockGetUiSchemaSync.mockReturnValue(null);
      mockFetchUiSchema.mockResolvedValue({
        "optimize-test-new_risk": {
          path: "optimize.test.new_risk",
          type: "boolean",
          feature_id: "optimize-test-new_risk",
          risk: {
            level: "low",
            title: "新风险功能",
            warning: "这是一个测试风险。",
            suggestion: "请谨慎。",
          },
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-test-new_risk", true, onConfirm);
      expect(result).toBe(false);
      expect(mockFetchUiSchema).toHaveBeenCalled();
    });
  });

  describe("checkRiskyFeature - static RISKY_FEATURES fallback", () => {
    it("uses static fallback when schema fetch returns null and feature is in RISKY_FEATURES", async () => {
      mockGetUiSchemaSync.mockReturnValue(null);
      mockFetchUiSchema.mockResolvedValue(null);
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("page-jurisdiction-ban_copy", true, onConfirm);
      expect(result).toBe(false);
    });
  });

  describe("checkRiskyFeature - disabling feature", () => {
    it("returns true when disabling a risky feature (no warning needed)", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "page-jurisdiction-ban_copy": {
          path: "page.jurisdiction.ban_copy",
          type: "boolean",
          feature_id: "page-jurisdiction-ban_copy",
          risk: {
            level: "low",
            title: "禁止复制",
            warning: "此功能可能影响正常用户复制内容。",
            suggestion: "内容站谨慎开启。",
          },
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("page-jurisdiction-ban_copy", false, onConfirm);
      expect(result).toBe(true);
    });
  });

  describe("dismissed features", () => {
    it("skips modal for dismissed low-risk feature", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "page-jurisdiction-ban_copy": {
          path: "page.jurisdiction.ban_copy",
          type: "boolean",
          feature_id: "page-jurisdiction-ban_copy",
          risk: {
            level: "low",
            title: "禁止复制",
            warning: "此功能可能影响正常用户复制内容。",
            suggestion: "内容站谨慎开启。",
          },
        },
      });
      localStorage.setItem("mabox_risky_dismissed", JSON.stringify(["page-jurisdiction-ban_copy"]));
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("page-jurisdiction-ban_copy", true, onConfirm);
      expect(result).toBe(true);
    });
  });
});