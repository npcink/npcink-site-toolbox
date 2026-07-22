import { act, cleanup, fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { ConfigProvider, Modal } from "antd";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import DbClean from "@/components/performance/db_clean";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";

const apiMocks = vi.hoisted(() => ({
  getDbStats: vi.fn(),
  previewDb: vi.fn(),
  cleanDb: vi.fn(),
}));

vi.mock("@/api", () => ({
  performanceApi: apiMocks,
}));

function renderDbClean() {
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
        <DbClean />
      </DataContext.Provider>
    </ConfigProvider>,
  );
}

const getComputedStyle = window.getComputedStyle.bind(window);

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
  apiMocks.getDbStats.mockReset().mockResolvedValue({
    success: true,
    data: {
      revisions: 12,
      drafts: 3,
      spam: 2,
      transients: 7,
      db_size: "8 MB",
    },
  });
  apiMocks.previewDb.mockReset().mockResolvedValue({
    success: true,
    data: { affected: 12, dry_run: true },
  });
  apiMocks.cleanDb.mockReset().mockResolvedValue({ success: true, data: { deleted: 12, dry_run: false } });
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("数据库清理操作链", () => {
  it("不提供全量清理入口，并要求每种数据先预览再清理", async () => {
    renderDbClean();

    expect(screen.queryByRole("button", { name: "预览清理" })).not.toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "执行清理" })).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "查看统计" }));

    const revisionCount = await screen.findByText("12 条");
    const revisionRow = revisionCount.closest("tr");
    expect(revisionRow).not.toBeNull();

    const row = within(revisionRow as HTMLElement);
    const cleanButton = row.getByRole("button", { name: /清\s*理/ });
    expect(cleanButton).toBeDisabled();

    fireEvent.click(row.getByRole("button", { name: /预\s*览/ }));

    await waitFor(() => {
      expect(apiMocks.previewDb).toHaveBeenCalledWith("revisions");
      expect(cleanButton).toBeEnabled();
    });
    expect(screen.getByRole("status")).toHaveTextContent("预览完成：预计影响 12 条数据。");
  });

  it("清理后在表格附近保留删除数量并刷新统计", async () => {
    const confirm = vi.spyOn(Modal, "confirm").mockImplementation(() => undefined as never);
    renderDbClean();
    fireEvent.click(screen.getByRole("button", { name: "查看统计" }));

    const revisionRow = (await screen.findByText("12 条")).closest("tr");
    const row = within(revisionRow as HTMLElement);
    fireEvent.click(row.getByRole("button", { name: /预\s*览/ }));
    await waitFor(() => expect(row.getByRole("button", { name: /清\s*理/ })).toBeEnabled());
    fireEvent.click(row.getByRole("button", { name: /清\s*理/ }));
    expect(confirm).toHaveBeenCalledTimes(1);
    expect(confirm.mock.calls[0][0].okText).toBe("确认清理");
    await act(async () => {
      await confirm.mock.calls[0][0].onOk?.();
    });

    await waitFor(() => {
      expect(apiMocks.cleanDb).toHaveBeenCalledWith("revisions", false);
      expect(screen.getByRole("status")).toHaveTextContent("清理完成，删除 12 条数据。");
    });
    expect(apiMocks.getDbStats).toHaveBeenCalledTimes(2);
  }, 30_000);

  it("清理失败时在操作区域保留错误", async () => {
    const confirm = vi.spyOn(Modal, "confirm").mockImplementation(() => undefined as never);
    apiMocks.cleanDb.mockRejectedValueOnce(new Error("network unavailable"));
    renderDbClean();
    fireEvent.click(screen.getByRole("button", { name: "查看统计" }));

    const revisionRow = (await screen.findByText("12 条")).closest("tr");
    const row = within(revisionRow as HTMLElement);
    fireEvent.click(row.getByRole("button", { name: /预\s*览/ }));
    await waitFor(() => expect(row.getByRole("button", { name: /清\s*理/ })).toBeEnabled());
    fireEvent.click(row.getByRole("button", { name: /清\s*理/ }));
    expect(confirm).toHaveBeenCalledTimes(1);
    await act(async () => {
      await confirm.mock.calls[0][0].onOk?.();
    });

    const failureMessage = await screen.findByText("清理失败，请重试。");
    expect(failureMessage.closest('[role="alert"]')).not.toBeNull();
  }, 30_000);
});
