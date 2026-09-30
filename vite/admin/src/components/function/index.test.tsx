import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import FunctionSettings from "@/components/function";
import { DataContext } from "@/tool/dataContext";
import { emptySecretStatus } from "@/tool/settingsSource";
import { defaultVarOption } from "@/tool/defaultVar";

function renderFunctionSettings(updateOption = vi.fn()) {
  render(
    <DataContext.Provider
      value={{
        optionData: defaultVarOption,
        updateOption,
        refreshOption: vi.fn(),
      discardChanges: vi.fn(),
        lastSavedOption: defaultVarOption,
        setLastSavedOption: vi.fn(),
        secretStatus: emptySecretStatus(),
        secretChanges: {},
        setSecretChange: vi.fn(),
        clearSecretChanges: vi.fn(),
        settingsState: "ready",
        settingsError: null,
      configEpoch: 0,
      }}
    >
      <FunctionSettings />
    </DataContext.Provider>,
  );
}

const getComputedStyle = window.getComputedStyle.bind(window);

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("站点验证代码解析", () => {
  it("将无效 Google 标记错误显示在字段下，并在有效标记后清除", async () => {
    const updateOption = vi.fn();
    renderFunctionSettings(updateOption);
    fireEvent.click(screen.getByRole("button", { name: "配置：辅助功能" }));

    const input = await screen.findByLabelText("Google 站点验证");
    fireEvent.change(input, { target: { value: "invalid-markup" } });
    expect(await screen.findByText(/未识别验证码，请粘贴 Google Search Console/)).toBeInTheDocument();
    expect(input).toHaveValue("");

    fireEvent.change(input, {
      target: {
        value: '<meta name="google-site-verification" content="abc_123-def">',
      },
    });

    await waitFor(() => {
      expect(screen.queryByText(/未识别验证码，请粘贴 Google Search Console/)).not.toBeInTheDocument();
      expect(input).toHaveValue("abc_123-def");
    });
    expect(updateOption).toHaveBeenLastCalledWith(
      "function",
      "auxiliary",
      expect.objectContaining({ google_tonji: "abc_123-def" }),
    );
  });

  it("在百度和 Bing 字段下分别说明无法识别的输入", async () => {
    renderFunctionSettings();
    fireEvent.click(screen.getByRole("button", { name: "配置：辅助功能" }));

    fireEvent.change(await screen.findByLabelText("百度统计"), { target: { value: "invalid" } });
    expect(await screen.findByText(/未识别统计 ID/)).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Bing 站点验证"), { target: { value: "invalid" } });
    expect(await screen.findByText(/未识别验证码，请粘贴 Bing Webmaster Tools/)).toBeInTheDocument();
  });
});
