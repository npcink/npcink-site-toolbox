import { beforeEach, describe, expect, it, vi } from "vitest";
import settingsContract from "@/generated/settings-contract.json";

const mockGetSchema = vi.fn();

vi.mock("@/api/index", () => ({
  settingsApi: {
    getSchema: () => mockGetSchema(),
  },
}));

describe("uiSchema generated fallback", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.resetModules();
  });

  it("provides schema-owned risk metadata synchronously", async () => {
    const { getUiSchemaSync, hasFetchedUiSchemaSync } = await import("@/tool/uiSchema");

    expect(getUiSchemaSync()).toEqual(settingsContract.uiSchema);
    expect(hasFetchedUiSchemaSync()).toBe(false);
  });

  it("still fetches the REST schema and merges module metadata", async () => {
    mockGetSchema.mockResolvedValue({
      data: {
        uiSchema: {
          "performance-db_clean-enabled": {
            path: "performance.db_clean.enabled",
            type: "boolean",
            feature_id: "performance-db_clean-enabled",
          },
          "module-only-feature": {
            path: "module.only",
            type: "module",
            feature_id: "module-only-feature",
            label: "模块元数据功能",
          },
        },
      },
    });
    const { fetchUiSchema, getUiSchemaSync, hasFetchedUiSchemaSync } = await import("@/tool/uiSchema");

    const fetched = await fetchUiSchema();

    expect(mockGetSchema).toHaveBeenCalledTimes(1);
    expect(fetched?.["module-only-feature"].label).toBe("模块元数据功能");
    expect(fetched?.["performance-db_clean-enabled"].risk?.level).toBe("high");
    expect(getUiSchemaSync()).toEqual(fetched);
    expect(hasFetchedUiSchemaSync()).toBe(true);
  });
});
