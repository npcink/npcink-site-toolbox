import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import SeoChecker from "@/components/performance/seo_checker";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";

const apiMocks = vi.hoisted(() => ({
  checkSeo: vi.fn(),
}));

vi.mock("@/api", () => ({
  performanceApi: apiMocks,
}));

function renderSeoChecker() {
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
        settingsState: "ready",
        settingsError: null,
      }}
    >
      <SeoChecker />
    </DataContext.Provider>,
  );
}

const getComputedStyle = window.getComputedStyle.bind(window);

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
  apiMocks.checkSeo.mockReset().mockResolvedValue({
    success: true,
    data: {
      issues: [{ type: "缺少描述", severity: "warning", message: "首页缺少描述" }],
      total: 1,
    },
  });
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("SEO 检查操作反馈", () => {
  it("在检查区保留检查摘要和问题列表", async () => {
    renderSeoChecker();
    fireEvent.click(screen.getByRole("button", { name: "开始检查" }));

    expect(await screen.findByRole("status")).toHaveTextContent("检查完成：发现 1 项需要关注。");
    expect(screen.getByText("首页缺少描述")).toBeInTheDocument();
  });

  it("保留 Alt 缺失检测但不提供写入入口，并显示请求失败", async () => {
    renderSeoChecker();
    expect(screen.queryByRole("button", { name: /补全 Alt/ })).not.toBeInTheDocument();

    apiMocks.checkSeo.mockRejectedValueOnce(new Error("network unavailable"));
    fireEvent.click(screen.getByRole("button", { name: "开始检查" }));
    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent("检查失败，请重试。");
    });
  });
});
