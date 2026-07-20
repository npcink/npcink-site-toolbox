import { cleanup, fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import Dashboard from "@/components/dashboard";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { DiagnosticSummary, Option, SearchHealthSummary } from "@/tool/interface";

const apiMocks = vi.hoisted(() => ({
  getDiagnosticsSummary: vi.fn(),
  getSearchSummary: vi.fn(),
}));

vi.mock("@/api", () => ({
  diagnosticsApi: { getSummary: apiMocks.getDiagnosticsSummary },
  searchHealthApi: { getSummary: apiMocks.getSearchSummary },
}));

const diagnosticSummary: DiagnosticSummary = {
  status: "good",
  items: [
    { id: "php", title: "PHP 版本", status: "good", message: "满足要求" },
    { id: "wp", title: "WordPress 版本", status: "good", message: "满足要求" },
  ],
  module_risks: [],
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

function renderDashboard(onNavigate = vi.fn(), optionData: Option = defaultVarOption) {
  render(
    <DataContext.Provider
      value={{
        optionData,
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

  it("使用接口返回的真实检查计数和搜索统计", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({ success: true, data: diagnosticSummary });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: searchSummary });

    renderDashboard();

    expect(await screen.findByLabelText("站点诊断 2 / 2 项通过")).toBeInTheDocument();
    expect(screen.getByText("0 项待关注，0 项需要处理；未启用高风险或实验性模块。")).toBeInTheDocument();
    expect(screen.getByText("状态良好")).toBeInTheDocument();
    expect(screen.getByText("128")).toBeInTheDocument();
    expect(screen.getByText("24")).toBeInTheDocument();
    expect(screen.getByText("2", { selector: "dd" })).toBeInTheDocument();
  });

  it("没有模块风险但存在待关注检查项时解释需要关注的原因", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({
      success: true,
      data: {
        ...diagnosticSummary,
        status: "warning",
        items: [
          { id: "php", title: "PHP 版本", status: "good", message: "满足要求" },
          { id: "wp", title: "WordPress 版本", status: "warning", message: "建议升级" },
        ],
      },
    });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });

    renderDashboard();

    expect(await screen.findByLabelText("站点诊断 1 / 2 项通过")).toBeInTheDocument();
    expect(screen.getByText("1 项待关注，0 项需要处理；未启用高风险或实验性模块。")).toBeInTheDocument();
    expect(screen.getByText("待核对：WordPress 版本")).toBeInTheDocument();
    expect(screen.queryByLabelText("站点诊断得分 54 分")).not.toBeInTheDocument();
  });

  it("关键问题优先于普通待关注项展示", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({
      success: true,
      data: {
        ...diagnosticSummary,
        status: "critical",
        items: [
          { id: "wp", title: "WordPress 版本", status: "warning", message: "建议升级" },
          { id: "php", title: "PHP 版本", status: "critical", message: "版本过低" },
          { id: "runtime", title: "运行环境", status: "good", message: "正常" },
        ],
      },
    });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });

    renderDashboard();

    expect(await screen.findByLabelText("站点诊断 1 / 3 项通过")).toBeInTheDocument();
    expect(screen.getByText("1 项待关注，1 项需要处理；未启用高风险或实验性模块。")).toBeInTheDocument();
    expect(screen.getByText("待核对：PHP 版本、WordPress 版本")).toBeInTheDocument();
    expect(screen.getByText("需要处理")).toBeInTheDocument();
  });

  it("检查项通过但启用风险模块时明确说明模块风险", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({
      success: true,
      data: {
        ...diagnosticSummary,
        status: "warning",
        module_risks: [
          {
            module_id: "performance.db_clean",
            tier: "high_risk",
            title: "数据库清理",
            message: "该模块被标记为高风险",
          },
        ],
      },
    });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });

    renderDashboard();

    expect(await screen.findByLabelText("站点诊断 2 / 2 项通过，1 个模块风险")).toBeInTheDocument();
    expect(screen.getByText("0 项待关注，0 项需要处理；1 个高风险或实验性模块已启用。")).toBeInTheDocument();
    expect(screen.getByText("需评估模块：数据库清理")).toBeInTheDocument();
    const diagnosticPanel = screen.getByRole("heading", { name: "站点诊断" }).closest("section");
    expect(diagnosticPanel).not.toBeNull();
    expect(within(diagnosticPanel as HTMLElement).getByText("需要关注")).toBeInTheDocument();
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
      data: { ...diagnosticSummary, items: [] },
    });
    apiMocks.getSearchSummary.mockResolvedValue({
      success: true,
      data: { total_searches: 9, unique_terms: 3 },
    });

    renderDashboard();

    expect(await screen.findByText("暂无诊断数据")).toBeInTheDocument();
    expect(screen.getByText("暂无搜索数据")).toBeInTheDocument();
  });

  it("诊断数组包含无效成员时降级为空状态", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({
      success: true,
      data: { ...diagnosticSummary, items: [null] },
    });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });

    renderDashboard();

    expect(await screen.findByText("暂无诊断数据")).toBeInTheDocument();
    expect(screen.queryByText("NaN")).not.toBeInTheDocument();
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

    fireEvent.click(await screen.findByRole("button", { name: "前往国内生态" }));
    fireEvent.click(screen.getByRole("button", { name: "打开存储与维护" }));
    fireEvent.click(screen.getByRole("button", { name: "检查搜索设置" }));

    expect(onNavigate).toHaveBeenNthCalledWith(
      1,
      "china",
      "domestic-login_security-attempt_limit_enabled",
    );
    expect(onNavigate).toHaveBeenNthCalledWith(2, "maintenance");
    expect(onNavigate).toHaveBeenNthCalledWith(3, "content");
    expect(onNavigate).not.toHaveBeenCalledWith("13", expect.anything());
  });

  it("提供编辑器工具说明和不会丢失当前页面的文章页面入口", () => {
    apiMocks.getDiagnosticsSummary.mockReturnValue(new Promise(() => {}));
    apiMocks.getSearchSummary.mockReturnValue(new Promise(() => {}));

    renderDashboard();

    const editorTools = screen.getByRole("region", { name: "编辑器工具" });
    expect(within(editorTools).getByText("3 个可编辑样板和 2 个实时数据区块，只在文章或页面编辑器中使用。")).toBeInTheDocument();
    expect(within(editorTools).getByText("资源下载、文章结论、来源与版权说明")).toBeInTheDocument();
    expect(within(editorTools).getByText("站点数据；GitHub 项目（描述、语言、Stars 与 Forks）")).toBeInTheDocument();

    const postLink = within(editorTools).getByRole("link", { name: "新建文章使用" });
    const pageLink = within(editorTools).getByRole("link", { name: "新建页面" });
    expect(postLink).toHaveAttribute("href", "post-new.php");
    expect(pageLink).toHaveAttribute("href", "post-new.php?post_type=page");
    expect(postLink).toHaveAttribute("target", "_blank");
    expect(pageLink).toHaveAttribute("target", "_blank");
    expect(postLink).toHaveAttribute("rel", "noopener noreferrer");
    expect(pageLink).toHaveAttribute("rel", "noopener noreferrer");
  });

  it("安全状态入口仍指向国内生态中的真实登录保护", async () => {
    apiMocks.getDiagnosticsSummary.mockResolvedValue({ success: true, data: diagnosticSummary });
    apiMocks.getSearchSummary.mockResolvedValue({ success: true, data: emptySearchSummary });
    const onNavigate = renderDashboard();

    fireEvent.click(await screen.findByRole("button", { name: "管理国内安全设置" }));

    expect(onNavigate).toHaveBeenCalledWith("china", "domestic-login_security-attempt_limit_enabled");
  });

  it("只开启匿名作者保护时诚实显示 1/2 并继续建议登录尝试保护", () => {
    apiMocks.getDiagnosticsSummary.mockReturnValue(new Promise(() => {}));
    apiMocks.getSearchSummary.mockReturnValue(new Promise(() => {}));
    const optionData: Option = {
      ...defaultVarOption,
      domestic: {
        ...defaultVarOption.domestic,
        login_security: {
          ...defaultVarOption.domestic.login_security,
          anonymous_author_guard_enabled: true,
        },
      },
    };

    renderDashboard(vi.fn(), optionData);

    const securityPanel = screen.getByRole("heading", { name: "安全状态" }).closest("section");
    expect(securityPanel).not.toBeNull();
    expect(within(securityPanel as HTMLElement).getByText("登录安全配置")).toBeInTheDocument();
    expect(screen.getByText("已启用 1/2 项登录安全配置")).toBeInTheDocument();
    expect(screen.getByText("已启用 1/2 项")).toBeInTheDocument();
    expect(screen.getByText("启用登录尝试保护")).toBeInTheDocument();
    expect(screen.getByText("限制同一已存在账号与来源 IP 组合的连续失败尝试，并使用固定统计窗口和锁定时长。")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "前往国内生态" })).toBeInTheDocument();
    expect(screen.queryByText("基础防护完整")).not.toBeInTheDocument();
  });

  it("两项登录安全配置均启用时显示 2/2 且不再给出补齐建议", () => {
    apiMocks.getDiagnosticsSummary.mockReturnValue(new Promise(() => {}));
    apiMocks.getSearchSummary.mockReturnValue(new Promise(() => {}));
    const optionData: Option = {
      ...defaultVarOption,
      domestic: {
        ...defaultVarOption.domestic,
        login_security: {
          ...defaultVarOption.domestic.login_security,
          attempt_limit_enabled: true,
          anonymous_author_guard_enabled: true,
        },
      },
    };

    renderDashboard(vi.fn(), optionData);

    expect(screen.getByText("已启用 2/2 项登录安全配置")).toBeInTheDocument();
    expect(screen.getByText("已启用 2/2 项")).toBeInTheDocument();
    expect(screen.queryByText("启用登录尝试保护")).not.toBeInTheDocument();
  });
});
