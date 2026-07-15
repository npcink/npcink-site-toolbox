import { beforeEach, describe, expect, it, vi } from "vitest";

import { performanceApi } from "@/api";

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
    });
  });

  it("数据库清理默认 dry-run，只有显式传 false 才执行", async () => {
    await performanceApi.cleanDb("spam");
    await performanceApi.cleanDb("spam", false);

    expect(restMocks.post).toHaveBeenNthCalledWith(1, "/performance/db/clean", {
      type: "spam",
      dry_run: true,
    });
    expect(restMocks.post).toHaveBeenNthCalledWith(2, "/performance/db/clean", {
      type: "spam",
      dry_run: false,
    });
  });

  it.each([
    ["checkMedia", "/performance/media/check"],
    ["fixMediaAlt", "/performance/media/fix-alt"],
    ["checkSeo", "/performance/seo/check"],
    ["fixSeoAlt", "/performance/seo/fix-alt"],
  ] as const)("%s 使用统一 REST 客户端", async (method, path) => {
    await performanceApi[method]();

    expect(restMocks.post).toHaveBeenCalledWith(path, { post_id: undefined });
  });
});
