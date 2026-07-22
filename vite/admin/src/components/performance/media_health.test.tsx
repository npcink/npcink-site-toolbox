import { act, cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { ConfigProvider, Modal } from "antd";

import MediaHealth from "@/components/performance/media_health";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";

const apiMocks = vi.hoisted(() => ({
  checkMedia: vi.fn(),
  fixMediaAlt: vi.fn(),
  convertMediaWebp: vi.fn(),
  restoreMediaWebp: vi.fn(),
}));

vi.mock("@/api", () => ({
  performanceApi: apiMocks,
}));

function renderMediaHealth() {
  render(
    <ConfigProvider theme={{ token: { motion: false } }}>
      <DataContext.Provider
        value={{
          optionData: defaultVarOption,
          updateOption: vi.fn(),
          refreshOption: vi.fn(),
          lastSavedOption: defaultVarOption,
          setLastSavedOption: vi.fn(),
          secretStatus: emptySecretStatus(),
          secretChanges: {},
          setSecretChange: vi.fn(),
          clearSecretChanges: vi.fn(),
          settingsState: "ready",
          settingsError: null,
        }}
      >
        <MediaHealth />
      </DataContext.Provider>
    </ConfigProvider>,
  );
}

const getComputedStyle = window.getComputedStyle.bind(window);

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
  apiMocks.checkMedia.mockReset().mockResolvedValue({
    success: true,
    data: {
      issues: [{ type: "缺少 Alt", severity: "warning", count: 4 }],
      attachment_scan: { checked: 160, total: 600, sampled: true },
      webp_assessment: {
        supported: true,
        checked: 150,
        sampled: true,
        missing_files: 1,
        formats: {
          jpeg: { count: 120, bytes: 262144000 },
          png: { count: 4, bytes: 1048576 },
          webp: { count: 25, bytes: 5242880 },
          other: { count: 1, bytes: 1024 },
        },
        sample: {
          attempted: 3,
          successful: 3,
          errors: 0,
          input_bytes: 3145728,
          output_bytes: 1808794,
          savings_bytes: 1336934,
          savings_percent: 42.5,
          temporary_files_cleaned: true,
          recommendation: "consider_batch",
        },
        thresholds: {
          candidate_count: 100,
          candidate_bytes: 209715200,
          savings_percent: 15,
        },
        batch: {
          candidate_ids: Array.from({ length: 50 }, (_, index) => 901 + index),
          restorable_ids: [],
          batch_size: 5,
          original_retained: true,
          restorable: true,
        },
      },
    },
  });
  apiMocks.fixMediaAlt.mockReset().mockResolvedValue({ success: true, data: { fixed: 4 } });
  apiMocks.convertMediaWebp.mockReset().mockImplementation(async (ids: number[]) => ({
    success: true,
    data: {
      processed: ids.length,
      converted: ids.length,
      skipped: 0,
      failed: 0,
      original_retained: true,
      results: ids.map((attachment_id) => ({
        attachment_id,
        status: "converted",
        message: "已转换",
      })),
    },
  }));
  apiMocks.restoreMediaWebp.mockReset().mockImplementation(async (ids: number[]) => ({
    success: true,
    data: {
      processed: ids.length,
      restored: ids.length,
      skipped: 0,
      failed: 0,
      original_retained: true,
      results: ids.map((attachment_id) => ({
        attachment_id,
        status: "restored",
        message: "已恢复",
      })),
    },
  }));
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("媒体库体检操作反馈", () => {
  it("在体检区保留检查摘要和问题列表", async () => {
    renderMediaHealth();
    fireEvent.click(screen.getByRole("button", { name: "开始体检" }));

    expect(await screen.findByRole("status")).toHaveTextContent("体检完成：发现 1 类问题。");
    expect(screen.getByText("4 个")).toBeInTheDocument();
    const assessment = screen.getByRole("region", { name: "WebP 转换预检" });
    expect(assessment).toHaveTextContent("JPEG 候选120 张250.0 MB");
    expect(assessment).toHaveTextContent("PNG 观察4 张1.0 MB");
    expect(assessment).toHaveTextContent("预计节省 42.5%");
    expect(assessment).toHaveTextContent("预检本身只读");
  });

  it("允许选择十张、二十张或五十张的连续处理目标", async () => {
    renderMediaHealth();
    fireEvent.click(screen.getByRole("button", { name: "开始体检" }));

    const targetSelect = await screen.findByRole("combobox", { name: "本次连续转换数量" });
    fireEvent.mouseDown(targetSelect);
    expect(await screen.findByText("最多 10 张")).toBeInTheDocument();
    expect(screen.getAllByText("最多 20 张").length).toBeGreaterThan(0);
    fireEvent.click(screen.getByText("最多 50 张"));

    expect(screen.getByRole("button", { name: "连续转换（最多 50 张）" })).toBeInTheDocument();
  });

  it("在体检区保留修复数量，并显示请求失败", async () => {
    renderMediaHealth();
    fireEvent.click(screen.getByRole("button", { name: "批量补全 Alt" }));
    expect(await screen.findByRole("status")).toHaveTextContent("已补全 4 张图片的 Alt。");

    apiMocks.checkMedia.mockRejectedValueOnce(new Error("network unavailable"));
    fireEvent.click(screen.getByRole("button", { name: "开始体检" }));
    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent("体检失败，请重试。");
    });
  });

  it("一次确认后按服务器上限连续转换并恢复本次记录", async () => {
    const confirm = vi.spyOn(Modal, "confirm").mockImplementation(() => undefined as never);
    renderMediaHealth();
    fireEvent.click(screen.getByRole("button", { name: "开始体检" }));

    fireEvent.click(await screen.findByRole("button", { name: "连续转换（最多 20 张）" }));
    expect(confirm).toHaveBeenCalledTimes(1);
    const convertOptions = confirm.mock.calls[0][0];
    await act(async () => {
      await convertOptions.onOk?.();
    });

    expect(apiMocks.convertMediaWebp).toHaveBeenCalledTimes(4);
    expect(apiMocks.convertMediaWebp).toHaveBeenNthCalledWith(1, [901, 902, 903, 904, 905]);
    expect(apiMocks.convertMediaWebp).toHaveBeenNthCalledWith(4, [916, 917, 918, 919, 920]);
    expect(await screen.findByRole("status")).toHaveTextContent("本次连续转换已完成：已转换 20/20 张");
    expect(screen.getByRole("progressbar", { name: "连续转换进度" })).toHaveAttribute("aria-valuenow", "100");

    fireEvent.click(screen.getByRole("button", { name: "恢复本次转换（20 张）" }));
    expect(confirm).toHaveBeenCalledTimes(2);
    const restoreOptions = confirm.mock.calls[1][0];
    await act(async () => {
      await restoreOptions.onOk?.();
    });

    expect(apiMocks.restoreMediaWebp).toHaveBeenCalledTimes(4);
    expect(apiMocks.restoreMediaWebp).toHaveBeenNthCalledWith(1, [901, 902, 903, 904, 905]);
    expect(apiMocks.restoreMediaWebp).toHaveBeenNthCalledWith(4, [916, 917, 918, 919, 920]);
    expect(await screen.findByRole("status")).toHaveTextContent("本次转换记录已恢复：已恢复 20/20 张");
  }, 30_000);

  it("任一小批失败后停止后续转换并保留已完成记录", async () => {
    const confirm = vi.spyOn(Modal, "confirm").mockImplementation(() => undefined as never);
    apiMocks.convertMediaWebp
      .mockImplementationOnce(async (ids: number[]) => ({
        success: true,
        data: {
          processed: ids.length,
          converted: ids.length,
          skipped: 0,
          failed: 0,
          original_retained: true,
          results: ids.map((attachment_id) => ({ attachment_id, status: "converted", message: "已转换" })),
        },
      }))
      .mockImplementationOnce(async (ids: number[]) => ({
        success: true,
        data: {
          processed: ids.length,
          converted: 4,
          skipped: 0,
          failed: 1,
          original_retained: true,
          results: ids.map((attachment_id, index) => ({
            attachment_id,
            status: index === ids.length - 1 ? "failed" : "converted",
            message: index === ids.length - 1 ? "转换失败" : "已转换",
          })),
        },
      }));

    renderMediaHealth();
    fireEvent.click(screen.getByRole("button", { name: "开始体检" }));
    fireEvent.click(await screen.findByRole("button", { name: "连续转换（最多 20 张）" }));
    await act(async () => {
      await confirm.mock.calls[0][0].onOk?.();
    });

    expect(apiMocks.convertMediaWebp).toHaveBeenCalledTimes(2);
    expect(await screen.findByRole("status")).toHaveTextContent("连续转换已停止：已转换 9/20 张");
    expect(screen.getByRole("button", { name: "恢复本次转换（9 张）" })).toBeInTheDocument();
  }, 30_000);

  it("停止请求会等待当前五张完成且不启动下一批", async () => {
    const confirm = vi.spyOn(Modal, "confirm").mockImplementation(() => undefined as never);
    const firstBatchResponse = {
      success: true,
      data: {
        processed: 5,
        converted: 5,
        skipped: 0,
        failed: 0,
        original_retained: true,
        results: [901, 902, 903, 904, 905].map((attachment_id) => ({
          attachment_id,
          status: "converted",
          message: "已转换",
        })),
      },
    };
    let resolveFirstBatch: ((value: typeof firstBatchResponse) => void) | undefined;
    apiMocks.convertMediaWebp.mockImplementationOnce(() => new Promise((resolve) => {
      resolveFirstBatch = resolve;
    }));

    renderMediaHealth();
    fireEvent.click(screen.getByRole("button", { name: "开始体检" }));
    fireEvent.click(await screen.findByRole("button", { name: "连续转换（最多 20 张）" }));

    let runPromise: Promise<unknown> | undefined;
    await act(async () => {
      runPromise = confirm.mock.calls[0][0].onOk?.() as Promise<unknown>;
      await Promise.resolve();
    });
    fireEvent.click(await screen.findByRole("button", { name: "完成当前小批后停止" }));

    await act(async () => {
      resolveFirstBatch?.(firstBatchResponse);
      await runPromise;
    });

    expect(apiMocks.convertMediaWebp).toHaveBeenCalledTimes(1);
    expect(await screen.findByRole("status")).toHaveTextContent("已按要求停止：已转换 5/20 张");
    expect(screen.getByRole("button", { name: "恢复本次转换（5 张）" })).toBeInTheDocument();
  }, 30_000);
});
