import { describe, it, expect, vi, beforeEach } from "vitest";
import settingsContract from "@/generated/settings-contract.json";

const mockFetchUiSchema = vi.fn();
const mockGetUiSchemaSync = vi.fn();
const mockHasFetchedUiSchemaSync = vi.fn();

vi.mock("@/tool/uiSchema", () => ({
  fetchUiSchema: () => mockFetchUiSchema(),
  getUiSchemaSync: () => mockGetUiSchemaSync(),
  hasFetchedUiSchemaSync: () => mockHasFetchedUiSchemaSync(),
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
    mockHasFetchedUiSchemaSync.mockReturnValue(true);

    storageMock = createStorageMock();
    Object.defineProperty(globalThis, "localStorage", {
      value: storageMock,
      configurable: true,
    });
  });

  describe("checkRiskyFeature - schema cached", () => {
    it("uses schema risk info when cached", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "optimize-medium-no_auto_size": {
          path: "optimize.medium.no_auto_size",
          type: "boolean",
          feature_id: "optimize-medium-no_auto_size",
          risk: {
            level: "low",
            title: "禁止缩略图",
            warning: "此功能可能与部分主题不兼容。",
            suggestion: "开启前请确认主题支持。",
          },
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-medium-no_auto_size", true, onConfirm);
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

    it("treats an explicit level none entry as non-risky", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "domestic-login_security-anonymous_author_guard_enabled": {
          path: "domestic.login_security.anonymous_author_guard_enabled",
          type: "boolean",
          feature_id: "domestic-login_security-anonymous_author_guard_enabled",
          risk: { level: "none" },
        },
      });
      const { Modal } = await import("antd");
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();

      const result = checkRiskyFeature(
        "domestic-login_security-anonymous_author_guard_enabled",
        true,
        onConfirm,
      );

      expect(result).toBe(true);
      expect(Modal.confirm).not.toHaveBeenCalled();
      expect(onConfirm).not.toHaveBeenCalled();
    });
  });

  describe("checkRiskyFeature - schema not cached", () => {
    it("fetches schema when not cached and calls onConfirm if no risk found", async () => {
      mockHasFetchedUiSchemaSync.mockReturnValue(false);
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
      mockHasFetchedUiSchemaSync.mockReturnValue(false);
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

    it("fetches a module-only risk missing from the generated fallback before proceeding", async () => {
      let schema: Record<string, unknown> = settingsContract.uiSchema;
      mockHasFetchedUiSchemaSync.mockReturnValue(false);
      mockGetUiSchemaSync.mockImplementation(() => schema);
      mockFetchUiSchema.mockImplementation(async () => {
        schema = {
          ...schema,
          "module-only-risk": {
            path: "module.only.risk",
            type: "module",
            feature_id: "module-only-risk",
            risk: {
              level: "low",
              title: "模块风险",
              warning: "需要确认。",
              suggestion: "确认配置后再开启。",
            },
          },
        };
        return schema;
      });
      const { Modal } = await import("antd");
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();

      const result = checkRiskyFeature("module-only-risk", true, onConfirm);

      expect(result).toBe(false);
      expect(mockFetchUiSchema).toHaveBeenCalledTimes(1);
      await vi.waitFor(() => {
        expect(Modal.confirm).toHaveBeenCalledWith(expect.objectContaining({
          title: "您正在开启「模块风险」",
        }));
      });
      expect(onConfirm).not.toHaveBeenCalled();
    });

    it("continues without a modal when fetched schema marks the feature as level none", async () => {
      mockHasFetchedUiSchemaSync.mockReturnValue(false);
      let cachedSchema: Record<string, unknown> | null = null;
      const fetchedSchema = {
        "domestic-login_security-anonymous_author_guard_enabled": {
          path: "domestic.login_security.anonymous_author_guard_enabled",
          type: "boolean",
          feature_id: "domestic-login_security-anonymous_author_guard_enabled",
          risk: { level: "none" },
        },
      };
      mockGetUiSchemaSync.mockImplementation(() => cachedSchema);
      mockFetchUiSchema.mockImplementation(async () => {
        cachedSchema = fetchedSchema;
        return fetchedSchema;
      });
      const { Modal } = await import("antd");
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();

      const result = checkRiskyFeature(
        "domestic-login_security-anonymous_author_guard_enabled",
        true,
        onConfirm,
      );

      expect(result).toBe(false);
      await vi.waitFor(() => {
        expect(onConfirm).toHaveBeenCalledTimes(1);
      });
      expect(Modal.confirm).not.toHaveBeenCalled();
    });

    it("continues a non-risky change when schema fetch resolves without data", async () => {
      mockHasFetchedUiSchemaSync.mockReturnValue(false);
      mockGetUiSchemaSync.mockReturnValue(null);
      mockFetchUiSchema.mockResolvedValue(null);
      const { Modal } = await import("antd");
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();

      const result = checkRiskyFeature("optimize-test-fetch-failure", true, onConfirm);

      expect(result).toBe(false);
      await vi.waitFor(() => {
        expect(onConfirm).toHaveBeenCalledTimes(1);
      });
      expect(Modal.confirm).not.toHaveBeenCalled();
    });
  });

  describe("checkRiskyFeature - generated Schema fallback", () => {
    it("uses generated risk metadata without fetching", async () => {
      mockGetUiSchemaSync.mockReturnValue(settingsContract.uiSchema);
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-medium-no_auto_size", true, onConfirm);
      expect(result).toBe(false);
      expect(mockFetchUiSchema).not.toHaveBeenCalled();
    });

    it("keeps login protection confirmation available from the generated contract", async () => {
      mockGetUiSchemaSync.mockReturnValue(settingsContract.uiSchema);
      const { Modal } = await import("antd");
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();

      const result = checkRiskyFeature(
        "domestic-login_security-attempt_limit_enabled",
        true,
        onConfirm,
      );

      expect(result).toBe(false);
      expect(mockFetchUiSchema).not.toHaveBeenCalled();
      expect(Modal.confirm).toHaveBeenCalledWith(expect.objectContaining({
        title: "您正在开启「登录尝试保护」",
      }));

      const confirmOptions = vi.mocked(Modal.confirm).mock.calls[0][0];
      expect(JSON.stringify(confirmOptions.content)).toContain(
        "确认开启后请在保存前核对可信代理；如发生误锁，可在 wp-config.php 中将 MABOX_DISABLE_LOGIN_PROTECTION 定义为 true 后恢复。",
      );
      confirmOptions.onOk?.();
      expect(onConfirm).toHaveBeenCalledTimes(1);
    });
  });

  describe("checkRiskyFeature - disabling feature", () => {
    it("returns true when disabling a risky feature (no warning needed)", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "optimize-medium-no_auto_size": {
          path: "optimize.medium.no_auto_size",
          type: "boolean",
          feature_id: "optimize-medium-no_auto_size",
          risk: {
            level: "low",
            title: "禁止缩略图",
            warning: "此功能可能与部分主题不兼容。",
            suggestion: "开启前请确认主题支持。",
          },
        },
      });
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-medium-no_auto_size", false, onConfirm);
      expect(result).toBe(true);
    });
  });

  describe("dismissed features", () => {
    it("skips modal for dismissed low-risk feature", async () => {
      mockGetUiSchemaSync.mockReturnValue({
        "optimize-medium-no_auto_size": {
          path: "optimize.medium.no_auto_size",
          type: "boolean",
          feature_id: "optimize-medium-no_auto_size",
          risk: {
            level: "low",
            title: "禁止缩略图",
            warning: "此功能可能与部分主题不兼容。",
            suggestion: "开启前请确认主题支持。",
          },
        },
      });
      localStorage.setItem("mabox_risky_dismissed", JSON.stringify(["optimize-medium-no_auto_size"]));
      const { checkRiskyFeature } = await import("@/tool/riskyFeature");
      const onConfirm = vi.fn();
      const result = checkRiskyFeature("optimize-medium-no_auto_size", true, onConfirm);
      expect(result).toBe(true);
    });
  });
});
