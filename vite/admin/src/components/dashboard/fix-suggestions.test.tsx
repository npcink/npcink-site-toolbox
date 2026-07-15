import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import Dashboard from "@/components/dashboard";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { DiagnosticSummary, SearchHealthSummary } from "@/tool/interface";

const apiMocks = vi.hoisted(() => ({
  getDiagnosticsSummary: vi.fn(),
  getSearchSummary: vi.fn(),
}));

vi.mock("@/api", () => ({
  diagnosticsApi: { getSummary: apiMocks.getDiagnosticsSummary },
  searchHealthApi: { getSummary: apiMocks.getSearchSummary },
}));

const diagnosticSummary: DiagnosticSummary = {
  score: 92,
  status: "good",
  items: [
    { id: "rest", title: "REST API", status: "good", message: "可用" },
    { id: "cache", title: "对象缓存", status: "warning", message: "未启用" },
  ],
  recommendations: [],
  risks: [],
  service_hints: [],
  generated_at: "2026-07-15 10:30:00",
};

const searchSummary: SearchHealthSummary = {
  range_days: 30,
  total_searches: 128,
  unique_terms: 24,
  top_terms: [{ term: "WordPress", count: 18, no_result_count: 0 }],
  no_result_terms: [
    { term: "站点地图", count: 7, no_result_count: 7 },
    { term: "对象缓存", count: 3, no_result_count: 3 },
  ],
  suspicious_terms: [],
  recommendations: [],
};

const emptySearchSummary: SearchHealthSummary = {
  range_days: 30,
  total_searches: 0,
  unique_terms: 0,
  top_terms: [],
  no_result_terms: [],
  suspicious_terms: [],
  recommendations: [],
};

function renderDashboard(onNavigate = vi.fn()) {
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
      <Dashboard onNavigate={onNavigate} />
    </DataContext.Provider>,
  );
  return onNavigate;
}

beforeEach(() => {
  apiMocks.getDiagnosticsSummary.mockReset();
  apiMocks.getSearchSummary.mockReset();
});

afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

describe("现代概览页", () => {
  it("分别展示站点诊断和搜索健康的加载状态", () => {
    apiMocks.getDiagnosticsSummary.mockReturnValue(new Promise(() => {}));
    apiMocks.getSearchSummary.mockReturnValue(new Promise(() => {}));

    renderDashboard();

    expect(screen.getByText("正在读取站点诊断")).toBeInTheDocument();
    expect(screen.getByText("正在汇总搜索数据")).toBeInTheDocument();
  });

  it("使用接口返回的真实诊断分数和搜索统计", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({ success: true, data: diagnosticSummary });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: searchSummary });

    renderDashboard();

    expect(await screen.findByLabelText("站点诊断得分 92 分")).toBeInTheDocument();
    expect(screen.getByText("状态良好")).toBeInTheDocument();
    expect(screen.getByText("128")).toBeInTheDocument();
    expect(screen.getByText("24")).toBeInTheDocument();
    expect(screen.getByText("2", { selector: "dd" })).toBeInTheDocument();
  });

  it("接口失败时明确标记不可用且不显示默认 60 分", async () => {
    apiMocks.getDiagnosticsSummary.mockRejectedValue(new Error("diagnostics unavailable"));
    apiMocks.getSearchSummary.mockRejectedValue(new Error("search unavailable"));

    renderDashboard();

    expect(await screen.findByText("站点诊断暂时不可用")).toBeInTheDocument();
    expect(screen.getByText("搜索健康暂时不可用")).toBeInTheDocument();
    expect(screen.queryByLabelText("站点诊断得分 60 分")).not.toBeInTheDocument();
  });

  it("成功响应没有有效数据时分别展示空状态", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({ success: true });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });

    renderDashboard();

    expect(await screen.findByText("暂无诊断数据")).toBeInTheDocument();
    expect(screen.getByText("暂无搜索数据")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "检查搜索设置" })).toBeInTheDocument();
  });

  it("成功响应缺少必需数组时降级为空状态而不是渲染崩溃", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({
      success: true,
      data: { score: 88, status: "good" },
    });
    apiMocks.getSearchSummary.mockResolvedValue({
      success: true,
      data: { total_searches: 9, unique_terms: 3 },
    });

    renderDashboard();

    expect(await screen.findByText("暂无诊断数据")).toBeInTheDocument();
    expect(screen.getByText("暂无搜索数据")).toBeInTheDocument();
  });

  it("错误状态可以分别重试", async () => {
    apiMocks.getDiagnosticsSummary.mockRejectedValueOnce(new Error("offline"));
    apiMocks.getSearchSummary.mockRejectedValueOnce(new Error("offline"));

    renderDashboard();
    fireEvent.click(await screen.findAllByRole("button", { name: "重新获取" }).then((buttons) => buttons[0]));

    await waitFor(() => expect(apiMocks.getDiagnosticsSummary).toHaveBeenCalledTimes(2));
    expect(apiMocks.getSearchSummary).toHaveBeenCalledTimes(1);
  });

  it("下一步与维护入口只发送语义化 view", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({ success: true, data: diagnosticSummary });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });
    const onNavigate = renderDashboard();

    fireEvent.click(await screen.findByRole("button", { name: "前往安全设置" }));
    fireEvent.click(screen.getByRole("button", { name: "打开维护工具" }));
    fireEvent.click(screen.getByRole("button", { name: "检查搜索设置" }));

    expect(onNavigate).toHaveBeenNthCalledWith(1, "security");
    expect(onNavigate).toHaveBeenNthCalledWith(2, "maintenance");
    expect(onNavigate).toHaveBeenNthCalledWith(3, "content");
    expect(onNavigate).not.toHaveBeenCalledWith("13", expect.anything());
  });
});
