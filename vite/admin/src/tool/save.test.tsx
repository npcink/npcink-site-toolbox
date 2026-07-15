import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import Save from "@/tool/save";

const saveMocks = vi.hoisted(() => ({
  saveOption: vi.fn(),
}));
const getComputedStyle = window.getComputedStyle.bind(window);

vi.mock("@/axios/save", () => saveMocks);

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
  saveMocks.saveOption.mockReset();
  saveMocks.saveOption.mockResolvedValue({ success: true });
});

describe("Save", () => {
  it("设置读取失败时禁用保存", () => {
    render(
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
          settingsState: "error",
          settingsError: "network unavailable",
        }}
      >
        <Save />
      </DataContext.Provider>,
    );

    expect(screen.getByRole("button", { name: /保\s*存/ })).toBeDisabled();
  });

  it("凭据保存成功后清空 draft 并重新读取服务端状态", async () => {
    const clearSecretChanges = vi.fn();
    const refreshOption = vi.fn().mockResolvedValue(undefined);
    const secretChanges = {
      "domestic.wechat.appsecret": {
        operation: "replace" as const,
        value: "replacement-secret",
      },
    };

    render(
      <DataContext.Provider
        value={{
          optionData: defaultVarOption,
          updateOption: vi.fn(),
          refreshOption,
          lastSavedOption: defaultVarOption,
          setLastSavedOption: vi.fn(),
          secretStatus: emptySecretStatus(),
          secretChanges,
          setSecretChange: vi.fn(),
          clearSecretChanges,
          settingsState: "ready",
          settingsError: null,
        }}
      >
        <Save />
      </DataContext.Provider>,
    );

    fireEvent.click(screen.getByRole("button", { name: /保\s*存/ }));
    fireEvent.click(await screen.findByRole("button", { name: /确认保存/ }));

    await waitFor(() => {
      expect(saveMocks.saveOption).toHaveBeenCalledWith(defaultVarOption, secretChanges);
      expect(clearSecretChanges).toHaveBeenCalledTimes(1);
      expect(refreshOption).toHaveBeenCalledTimes(1);
    });
  });
});
