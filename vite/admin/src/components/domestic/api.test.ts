import { beforeEach, describe, expect, it, vi } from "vitest";

import { domesticApi, runBaiduBatchPush } from "@/api";

const restMocks = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
}));

vi.mock("@/axios/public", () => ({
  restInstance: restMocks,
}));

describe("domesticApi", () => {
  beforeEach(() => {
    restMocks.get.mockReset();
    restMocks.post.mockReset();
    restMocks.get.mockResolvedValue({ success: true, data: {} });
    restMocks.post.mockResolvedValue({ success: true, data: {} });
  });

  it("百度批量推送通过统一 REST 客户端传递偏移量", async () => {
    await domesticApi.baiduPush(undefined, 100);

    expect(restMocks.post).toHaveBeenCalledWith("/domestic/baidu/push", {
      urls: undefined,
      offset: 100,
    });
  });

  it("批次完成前持续使用后端返回的递增偏移量", async () => {
    const pushBatch = vi
      .fn()
      .mockResolvedValueOnce({ success: true, data: { done: false, offset: 100 } })
      .mockResolvedValueOnce({ success: true, data: { done: true, message: "完成" } });

    const response = await runBaiduBatchPush(pushBatch);

    expect(pushBatch).toHaveBeenNthCalledWith(1, 0);
    expect(pushBatch).toHaveBeenNthCalledWith(2, 100);
    expect(response.data?.done).toBe(true);
  });

  it("拒绝不前进的偏移量，避免无限请求", async () => {
    const pushBatch = vi.fn().mockResolvedValue({
      success: true,
      data: { done: false, offset: 0 },
    });

    await expect(runBaiduBatchPush(pushBatch)).rejects.toThrow("没有推进");
    expect(pushBatch).toHaveBeenCalledTimes(1);
  });
});
