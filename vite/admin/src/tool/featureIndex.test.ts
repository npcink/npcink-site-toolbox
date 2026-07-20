import { beforeEach, describe, expect, it, vi } from "vitest";

import settingsContract from "@/generated/settings-contract.json";
import { ADMIN_VIEWS } from "@/tool/navigation";

const mockFetchUiSchema = vi.fn();
const mockGetUiSchemaSync = vi.fn();

vi.mock("@/tool/uiSchema", () => ({
  fetchUiSchema: () => mockFetchUiSchema(),
  getUiSchemaSync: () => mockGetUiSchemaSync(),
}));

describe("featureIndex", () => {
  beforeEach(() => {
    mockFetchUiSchema.mockReset();
    mockGetUiSchemaSync.mockReset();
    vi.resetModules();
  });

  it("uses the generated 32-item search index as its only search authority", async () => {
    const { baseFeatureIndex, searchIndex } = await import("@/tool/featureIndex");
    const ids = searchIndex.map((item) => item.id);
    const validViews = new Set<string>(ADMIN_VIEWS);

    expect(searchIndex).toEqual(settingsContract.searchIndex);
    expect(searchIndex).toHaveLength(32);
    expect(baseFeatureIndex).toBe(searchIndex);
    expect(new Set(ids).size).toBe(32);
    expect(searchIndex.every((item) => validViews.has(item.tabKey))).toBe(true);
    expect(searchIndex.some((item) => /^\d+$/.test(item.tabKey))).toBe(false);

    const maintenanceItems = searchIndex.filter((item) => item.tabKey === "maintenance");
    expect(maintenanceItems.length).toBeGreaterThan(0);
    expect(maintenanceItems.every((item) => item.tabLabel === "存储与维护")).toBe(true);

    const byId = new Map(searchIndex.map((item) => [item.id, item]));
    expect(byId.get("page-function-maintenance_tips")?.aliases).toContain(
      "page-feature-maintenance_tips",
    );
    expect(byId.get("domestic-compliance-police_enabled")?.aliases).toContain(
      "domestic-compliance-police",
    );
    expect(byId.get("domestic-comment-blacklist")?.aliases).toContain(
      "domestic-comment_security-blacklist_enabled",
    );
    expect(byId.get("domestic-comment-ip-rate")?.aliases).toContain(
      "domestic-comment_security-ip_rate_limit",
    );

    const retiredIds = [
      "login-security-login_code",
      "page-function-batch_replace",
      "page-function-batch_replace_pairs",
      "domestic-login_security-fail_limit_enabled",
      "domestic-login_security-ip_lock_enabled",
      "domestic-login_security-custom_login_enabled",
      "domestic-login_security-ban_enumeration_enabled",
      "domestic-login_security-login_notify_enabled",
      "domestic-login_security-login_log_enabled",
      "domestic-login_security-ip_whitelist_enabled",
      "optimize-site-renew",
    ];
    expect(ids.filter((id) => retiredIds.includes(id))).toEqual([]);
  });

  it("keeps sync and Promise APIs identical without merging server schema entries", async () => {
    const serverOnlyItem = {
      "page-test-server_only": {
        path: "page.test.server_only",
        type: "boolean",
        label: "服务端临时项",
        feature_id: "page-test-server_only",
      },
    };
    mockGetUiSchemaSync.mockReturnValue(serverOnlyItem);
    mockFetchUiSchema.mockResolvedValue(serverOnlyItem);

    const {
      fetchFeatureIndex,
      getFeatureIndexSync,
      searchIndex,
    } = await import("@/tool/featureIndex");

    expect(getFeatureIndexSync()).toBe(searchIndex);
    await expect(fetchFeatureIndex()).resolves.toBe(searchIndex);
    expect(searchIndex.some((item) => item.id === "page-test-server_only")).toBe(false);
    expect(mockGetUiSchemaSync).not.toHaveBeenCalled();
    expect(mockFetchUiSchema).not.toHaveBeenCalled();
  });

  it("prefers a cached UI Schema label, then falls back to generated ids and aliases", async () => {
    mockGetUiSchemaSync.mockReturnValue({
      "optimize-site-hide_top_toolbar": {
        path: "optimize.site.hide_top_toolbar",
        type: "boolean",
        label: "缓存的顶部工具条标签",
        feature_id: "optimize-site-hide_top_toolbar",
      },
    });
    const { getFeatureLabelForPath } = await import("@/tool/featureIndex");

    expect(getFeatureLabelForPath("optimize.site.hide_top_toolbar")).toBe(
      "缓存的顶部工具条标签",
    );

    mockGetUiSchemaSync.mockReturnValue(null);
    expect(getFeatureLabelForPath("optimize.site.hide_top_toolbar")).toBe("隐藏顶部工具条");
    expect(getFeatureLabelForPath("page.feature.maintenance_tips")).toBe("维护提示页");
    expect(getFeatureLabelForPath("page.unknown.missing")).toBeNull();
  });

  it("keeps risk resolution scoped to the cached UI Schema", async () => {
    mockGetUiSchemaSync.mockReturnValue(settingsContract.uiSchema);
    const { getFeatureRiskLevelForPath } = await import("@/tool/featureIndex");

    expect(getFeatureRiskLevelForPath("performance.db_clean.enabled")).toBe("high");
    expect(getFeatureRiskLevelForPath("optimize.medium.no_auto_size")).toBe("low");
    expect(getFeatureRiskLevelForPath("optimize.site.hide_top_toolbar")).toBe("none");

    mockGetUiSchemaSync.mockReturnValue({
      invalid: {
        path: "custom.invalid.risk",
        type: "boolean",
        risk: { level: "critical" },
      },
    });
    expect(getFeatureRiskLevelForPath("custom.invalid.risk")).toBe("none");
  });
});
