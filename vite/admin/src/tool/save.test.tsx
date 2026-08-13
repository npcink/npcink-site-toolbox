import { act, cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import {
  DataContext,
  emptySecretStatus,
  OptionContextType,
} from "@/tool/dataContext";
import DiffModal from "@/components/diff-modal";
import { defaultVarOption } from "@/tool/defaultVar";
import { Option } from "@/tool/interface";
import Save from "@/tool/save";

const saveMocks = vi.hoisted(() => ({
  saveOption: vi.fn(),
}));
const noticeMocks = vi.hoisted(() => ({
  success: vi.fn(),
  warning: vi.fn(),
  error: vi.fn(),
}));
const diffModalLoaderMocks = vi.hoisted(() => ({
  loadDiffModal: vi.fn(),
}));
const getComputedStyle = window.getComputedStyle.bind(window);
const SETTINGS_REVISION = "a".repeat(64);

vi.mock("@/axios/save", () => saveMocks);
vi.mock("@/tool/diffModalLoader", () => diffModalLoaderMocks);
vi.mock("@/tool/notice", () => ({ notice: noticeMocks }));

function cloneOption(): Option {
  return JSON.parse(JSON.stringify(defaultVarOption)) as Option;
}

function renderSave(overrides: Partial<OptionContextType> = {}) {
  const optionData = cloneOption();
  const value: OptionContextType = {
    optionData,
    updateOption: vi.fn(),
    refreshOption: vi.fn().mockResolvedValue(undefined),
    lastSavedOption: cloneOption(),
    setLastSavedOption: vi.fn(),
    secretStatus: emptySecretStatus(),
    secretChanges: {},
    setSecretChange: vi.fn(),
    clearSecretChanges: vi.fn(),
    settingsState: "ready",
    settingsError: null,
    settingsRevision: SETTINGS_REVISION,
    ...overrides,
  };

  return { value, ...render(<DataContext.Provider value={value}><Save /></DataContext.Provider>) };
}

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
  saveMocks.saveOption.mockReset();
  saveMocks.saveOption.mockResolvedValue({ success: true });
  noticeMocks.success.mockReset();
  noticeMocks.warning.mockReset();
  noticeMocks.error.mockReset();
  diffModalLoaderMocks.loadDiffModal.mockReset();
  diffModalLoaderMocks.loadDiffModal.mockResolvedValue({ default: DiffModal });
});

describe("Save", () => {
  it("读取中显示稳定状态并禁用保存", () => {
    renderSave({ settingsState: "loading" });

    expect(screen.getByRole("status")).toHaveTextContent("正在读取设置…");
    expect(screen.getByRole("button", { name: /保\s*存/ })).toBeDisabled();
  });

  it("设置读取失败时显示不可用状态并禁用保存", () => {
    renderSave({ settingsState: "error", settingsError: "network unavailable" });

    expect(screen.getByRole("status")).toHaveTextContent("设置不可用");
    expect(screen.getByRole("button", { name: /保\s*存/ })).toBeDisabled();
  });

  it("无改动时显示已保存、禁用按钮且不弹 toast", () => {
    renderSave();

    expect(screen.getByRole("status")).toHaveTextContent("已保存");
    const saveButton = screen.getByRole("button", { name: /保\s*存/ });
    expect(saveButton).toBeDisabled();
    fireEvent.click(saveButton);
    expect(noticeMocks.success).not.toHaveBeenCalled();
    expect(noticeMocks.warning).not.toHaveBeenCalled();
    expect(noticeMocks.error).not.toHaveBeenCalled();
    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
  });

  it("用普通设置与凭据 diff 的真实总数提示待保存项", async () => {
    const optionData = cloneOption();
    optionData.domestic.login_security.attempt_limit_count += 1;
    const secretChanges = {
      "domestic.wechat.appsecret": {
        operation: "replace" as const,
        value: "replacement-secret",
      },
    };

    renderSave({ optionData, secretChanges });

    expect(screen.getByRole("status")).toHaveTextContent("2 项待保存");
    const saveButton = screen.getByRole("button", { name: "查看并保存" });
    expect(saveButton).toBeEnabled();
    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    fireEvent.click(saveButton);

    expect(await screen.findByText("失败尝试上限")).toBeInTheDocument();
    expect(screen.getByText("微信 AppSecret")).toBeInTheDocument();
    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });

  it("确认界面慢加载时显示可见准备状态", async () => {
    let resolveLoader: ((module: { default: typeof DiffModal }) => void) | undefined;
    diffModalLoaderMocks.loadDiffModal.mockReturnValue(new Promise((resolve) => {
      resolveLoader = resolve;
    }));
    const secretChanges = {
      "domestic.wechat.appsecret": { operation: "clear" as const },
    };

    renderSave({ secretChanges });
    fireEvent.click(screen.getByRole("button", { name: "查看并保存" }));

    expect(screen.getByRole("status")).toHaveTextContent("正在准备确认…");
    expect(screen.getByRole("button", { name: "正在准备…" })).toBeDisabled();
    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();

    await act(async () => {
      resolveLoader?.({ default: DiffModal });
    });
    expect(await screen.findByRole("dialog")).toBeInTheDocument();
  });

  it("确认界面加载失败时保留待保存状态并允许重试", async () => {
    diffModalLoaderMocks.loadDiffModal
      .mockRejectedValueOnce(new Error("chunk unavailable"))
      .mockResolvedValueOnce({ default: DiffModal });
    const secretChanges = {
      "domestic.wechat.appsecret": { operation: "clear" as const },
    };

    renderSave({ secretChanges });
    fireEvent.click(screen.getByRole("button", { name: "查看并保存" }));

    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent("保存确认界面加载失败，请重试");
    });
    expect(noticeMocks.error).not.toHaveBeenCalled();
    expect(screen.getByRole("status")).toHaveTextContent("1 项待保存");
    expect(screen.getByRole("button", { name: "查看并保存" })).toBeEnabled();
    expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    expect(saveMocks.saveOption).not.toHaveBeenCalled();

    fireEvent.click(screen.getByRole("button", { name: "查看并保存" }));
    expect(await screen.findByRole("dialog")).toBeInTheDocument();
    expect(diffModalLoaderMocks.loadDiffModal).toHaveBeenCalledTimes(2);
    expect(screen.queryByText("保存确认界面加载失败，请重试")).not.toBeInTheDocument();
  });

  it("取消确认后恢复保存按钮焦点且复用已加载弹窗", async () => {
    const secretChanges = {
      "domestic.wechat.appsecret": { operation: "clear" as const },
    };
    renderSave({ secretChanges });
    const saveButton = screen.getByRole("button", { name: "查看并保存" });
    saveButton.focus();
    fireEvent.click(saveButton);
    fireEvent.click(await screen.findByRole("button", { name: /取\s*消/ }));

    await waitFor(() => expect(saveButton).toHaveFocus());
    fireEvent.click(saveButton);
    expect(await screen.findByRole("dialog")).toBeInTheDocument();
    expect(diffModalLoaderMocks.loadDiffModal).toHaveBeenCalledTimes(1);
  });

  it("确认后立即进入保存中状态，成功后清空凭据 draft 并回读", async () => {
    let resolveSave: ((value: { success: boolean; message: string }) => void) | undefined;
    let resolveRefresh: (() => void) | undefined;
    saveMocks.saveOption.mockReturnValue(new Promise((resolve) => {
      resolveSave = resolve;
    }));
    const clearSecretChanges = vi.fn();
    const refreshOption = vi.fn().mockReturnValue(new Promise<void>((resolve) => {
      resolveRefresh = resolve;
    }));
    const secretChanges = {
      "domestic.wechat.appsecret": {
        operation: "replace" as const,
        value: "replacement-secret",
      },
    };

    renderSave({ secretChanges, clearSecretChanges, refreshOption });

    fireEvent.click(screen.getByRole("button", { name: "查看并保存" }));
    fireEvent.click(await screen.findByRole("button", { name: "确认保存" }));

    expect(screen.getByRole("status")).toHaveTextContent("正在保存…");
    expect(screen.getByRole("button", { name: /正在保存/ })).toBeDisabled();
    await waitFor(() => expect(screen.getByRole("status")).toHaveFocus());

    await act(async () => {
      resolveSave?.({ success: true, message: "设置保存并校验成功" });
    });

    await waitFor(() => {
      expect(saveMocks.saveOption).toHaveBeenCalledWith(expect.any(Object), secretChanges, SETTINGS_REVISION);
      expect(clearSecretChanges).toHaveBeenCalledTimes(1);
      expect(refreshOption).toHaveBeenCalledTimes(1);
    });
    expect(noticeMocks.success).not.toHaveBeenCalled();

    await act(async () => {
      resolveRefresh?.();
    });
    await waitFor(() => {
      expect(noticeMocks.success).toHaveBeenCalledTimes(1);
      expect(noticeMocks.success).toHaveBeenCalledWith("设置保存并校验成功");
    });
  });

  it("设置已保存但回读失败时保留诚实警告", async () => {
    const refreshOption = vi.fn().mockRejectedValue(new Error("read failed"));
    const secretChanges = {
      "domestic.wechat.appsecret": { operation: "clear" as const },
    };

    renderSave({ secretChanges, refreshOption });
    fireEvent.click(screen.getByRole("button", { name: "查看并保存" }));
    fireEvent.click(await screen.findByRole("button", { name: "确认保存" }));

    await waitFor(() => {
      expect(screen.getByText(
        "设置已保存，但重新读取失败；保存功能已禁用，请重新读取后继续",
      )).toBeInTheDocument();
    });
    expect(noticeMocks.warning).not.toHaveBeenCalled();
  });

  it("写入失败时保留待保存内容并显示失败反馈", async () => {
    saveMocks.saveOption.mockRejectedValue(new Error("保存失败，已恢复为之前的设置"));
    const clearSecretChanges = vi.fn();
    const refreshOption = vi.fn();
    const secretChanges = {
      "domestic.wechat.appsecret": { operation: "clear" as const },
    };

    renderSave({ secretChanges, clearSecretChanges, refreshOption });
    fireEvent.click(screen.getByRole("button", { name: "查看并保存" }));
    fireEvent.click(await screen.findByRole("button", { name: "确认保存" }));

    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent("保存失败，已恢复为之前的设置");
    });
    expect(noticeMocks.error).not.toHaveBeenCalled();
    expect(clearSecretChanges).not.toHaveBeenCalled();
    expect(refreshOption).not.toHaveBeenCalled();
    expect(screen.getByRole("status")).toHaveTextContent("1 项待保存");
  });
});
