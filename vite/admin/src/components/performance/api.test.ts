import { beforeEach, describe, expect, it, vi } from "vitest";

import {
  diagnosticsApi,
  domesticApi,
  performanceApi,
  searchHealthApi,
  settingsApi,
} from "@/api";
import { defaultVarOption } from "@/tool/defaultVar";
import type { SettingsSavePayload } from "@/tool/interface";

const restMocks = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
}));

vi.mock("@/axios/public", () => ({
  restInstance: restMocks,
}));

describe("performanceApi", () => {
  beforeEach(() => {
    restMocks.get.mockReset();
    restMocks.post.mockReset();
    restMocks.get.mockResolvedValue({ success: true, data: {} });
    restMocks.post.mockResolvedValue({ success: true, data: {} });
  });

  it("数据库预览始终显式使用 dry-run", async () => {
    await performanceApi.previewDb("revisions");

    expect(restMocks.post).toHaveBeenCalledWith("/performance/db/preview", {
      type: "revisions",
      dry_run: true,
    }, { maboxNotify: false });
  });

  it("数据库清理默认 dry-run，只有显式传 false 才执行", async () => {
    await performanceApi.cleanDb("spam");
    await performanceApi.cleanDb("spam", false);

    expect(restMocks.post).toHaveBeenNthCalledWith(1, "/performance/db/clean", {
      type: "spam",
      dry_run: true,
    }, { maboxNotify: false });
    expect(restMocks.post).toHaveBeenNthCalledWith(2, "/performance/db/clean", {
      type: "spam",
      dry_run: false,
    }, { maboxNotify: false });
  });

  it("对象存储连接测试关闭全局通知并使用设置凭据契约", async () => {
    const payload: SettingsSavePayload = {
      settings: defaultVarOption,
      secretChanges: {
        "performance.oss.access_key": { operation: "replace", value: "access-key" },
      },
    };

    await performanceApi.testOssConnection(payload);

    expect(restMocks.post).toHaveBeenCalledWith(
      "/performance/oss/test",
      payload,
      { maboxNotify: false },
    );
  });

  it.each([
    ["checkMedia", "/performance/media/check"],
    ["fixMediaAlt", "/performance/media/fix-alt"],
    ["checkSeo", "/performance/seo/check"],
    ["fixSeoAlt", "/performance/seo/fix-alt"],
  ] as const)("%s 由调用界面独占反馈", async (method, path) => {
    await performanceApi[method]();

    expect(restMocks.post).toHaveBeenCalledWith(
      path,
      { post_id: undefined },
      { maboxNotify: false },
    );
  });

  it("数据库统计由调用界面独占反馈", async () => {
    await performanceApi.getDbStats();

    expect(restMocks.get).toHaveBeenCalledWith(
      "/performance/db/stats",
      { maboxNotify: false },
    );
  });

  it("历史媒体 WebP 转换和恢复仅提交显式附件 ID", async () => {
    await performanceApi.convertMediaWebp([11, 12]);
    await performanceApi.restoreMediaWebp([11, 12]);

    expect(restMocks.post).toHaveBeenNthCalledWith(1,
      "/performance/media/webp/convert",
      { attachment_ids: [11, 12] },
      { maboxNotify: false },
    );
    expect(restMocks.post).toHaveBeenNthCalledWith(2,
      "/performance/media/webp/restore",
      { attachment_ids: [11, 12] },
      { maboxNotify: false },
    );
  });

  it("已有局部状态的查询和建议接口关闭传输层通知", async () => {
    await domesticApi.checkEnvironment();
    await domesticApi.applyEnvironmentFix(["gravatar"]);
    await diagnosticsApi.getSummary();
    await diagnosticsApi.getFeatureStatus();
    await diagnosticsApi.getSupportReport();
    await diagnosticsApi.analyzeSupportReport("后台偶发 500");
    await diagnosticsApi.getReviewPack("performance");
    await diagnosticsApi.createReview({ scenario: "performance", problem: "检查缓存" });
    await diagnosticsApi.createFollowUp({
      scenario: "troubleshooting",
      question: "依据是什么？",
      context: {
        contract_version: "ai_follow_up_context.v1",
        scenario: "troubleshooting",
        source_pack: {
          contract_version: "diagnostic_pack.v1",
          scope: "manual_support",
          generated_at: "2026-07-23 10:00:00",
          sections: [{ id: "wp-core", title: "WordPress", facts: [{ id: "version", label: "版本", value: "7.0" }] }],
          limitations: [],
          privacy: { external_requests_performed: false, persisted: false, review_before_sharing: true },
        },
      },
      initial_analysis: "首次回答",
      turns: [],
    });
    await searchHealthApi.getSummary(30);
    await settingsApi.getSchema();

    expect(restMocks.get).toHaveBeenCalledWith(
      "/domestic/environment/check",
      { maboxNotify: false },
    );
    expect(restMocks.post).toHaveBeenCalledWith(
      "/domestic/environment/apply",
      { fixes: ["gravatar"] },
      { maboxNotify: false },
    );
    expect(restMocks.get).toHaveBeenCalledWith(
      "/diagnostics/summary",
      { maboxNotify: false },
    );
    expect(restMocks.get).toHaveBeenCalledWith(
      "/diagnostics/features",
      { maboxNotify: false },
    );
    expect(restMocks.get).toHaveBeenCalledWith(
      "/diagnostics/support-report",
      { maboxNotify: false },
    );
    expect(restMocks.post).toHaveBeenCalledWith(
      "/diagnostics/analyses",
      { problem: "后台偶发 500" },
      { maboxNotify: false },
    );
    expect(restMocks.get).toHaveBeenCalledWith(
      "/diagnostics/review-packs?scope=performance",
      { maboxNotify: false },
    );
    expect(restMocks.post).toHaveBeenCalledWith(
      "/diagnostics/reviews",
      { scenario: "performance", problem: "检查缓存" },
      { maboxNotify: false },
    );
    expect(restMocks.post).toHaveBeenCalledWith(
      "/diagnostics/follow-ups",
      expect.objectContaining({ scenario: "troubleshooting", question: "依据是什么？", turns: [] }),
      { maboxNotify: false },
    );
    expect(restMocks.get).toHaveBeenCalledWith(
      "/search-health/summary?days=30",
      { maboxNotify: false },
    );
    expect(restMocks.get).toHaveBeenCalledWith(
      "/settings/schema",
      { maboxNotify: false },
    );
  });
});
