import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";

import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type {
  AiFollowUp,
  AiFollowUpContext,
  AiReview,
  AiReviewPack,
  DataLocal,
  DiagnosticAnalysis,
  DiagnosticPack,
} from "@/tool/interface";
import AiDiagnostics from "./ai-diagnostics";
import { buildDiagnosticPackReport } from "./runtime-status-report";

const apiMocks = vi.hoisted(() => ({
  getSupportReport: vi.fn(),
  analyzeSupportReport: vi.fn(),
  getReviewPack: vi.fn(),
  createReview: vi.fn(),
  createFollowUp: vi.fn(),
}));

vi.mock("@/api", () => ({
  diagnosticsApi: apiMocks,
}));

const diagnosticPack: DiagnosticPack = {
  contract_version: "diagnostic_pack.v1",
  scope: "manual_support",
  generated_at: "2026-07-22 10:00:00",
  sections: [
    {
      id: "wp-server",
      title: "服务器与 PHP",
      facts: [
        { id: "php_version", label: "PHP version", value: "8.4.21 64bit" },
        { id: "memory_limit", label: "PHP memory limit", value: "256M" },
      ],
    },
  ],
  limitations: ["不包含请求日志或服务器负载。"],
  privacy: {
    external_requests_performed: false,
    persisted: false,
    review_before_sharing: true,
  },
};

const diagnosticFollowUpContext: AiFollowUpContext = {
  contract_version: "ai_follow_up_context.v1",
  scenario: "troubleshooting",
  source_pack: diagnosticPack,
};

const diagnosticAnalysis: DiagnosticAnalysis = {
  contract_version: "diagnostic_analysis.v1",
  generated_at: "2026-07-22 10:01:00",
  source: {
    contract_version: "diagnostic_pack.v1",
    generated_at: "2026-07-22 10:00:30",
  },
  provider: { id: "deepseek", model: "deepseek-chat" },
  analysis: [
    "### 已确认问题",
    "1. **WordPress 版本需要核对** (`wp-core.version`)。",
    "2. 当前快照没有直接证据。",
    "",
    "### 安全的下一步",
    "- 采集对应请求的错误日志。",
  ].join("\n"),
  follow_up_context: diagnosticFollowUpContext,
  privacy: {
    external_request_performed: true,
    persisted: false,
    automated_changes: false,
  },
};

const reviewPack: AiReviewPack = {
  contract_version: "site_review_pack.v1",
  scope: "performance",
  generated_at: "2026-07-23 10:00:00",
  sections: [{
    id: "runtime-performance",
    title: "性能瞬时指标",
    facts: [
      { id: "autoload_serialized_bytes", label: "自动加载 option 序列化字节", value: "4096" },
      { id: "cron_due_count", label: "已到期计划任务数", value: "0" },
    ],
  }],
  limitations: ["不是持续监控。"],
  privacy: { external_requests_performed: false, persisted: false, review_before_sharing: true },
};

const aiReview: AiReview = {
  contract_version: "ai_review.v1",
  scenario: "performance",
  generated_at: "2026-07-23 10:01:00",
  source: {
    contract_version: "site_review_pack.v1",
    generated_at: "2026-07-23 10:00:00",
    scope: "performance",
  },
  provider: { id: "deepseek", model: "deepseek-chat" },
  analysis: "### 结论摘要\n\n- 当前快照未发现已到期任务。",
  follow_up_context: {
    contract_version: "ai_follow_up_context.v1",
    scenario: "performance",
    source_pack: reviewPack,
  },
  privacy: { external_request_performed: true, persisted: false, automated_changes: false },
};

const followUpResponse: AiFollowUp = {
  contract_version: "ai_follow_up.v1",
  scenario: "troubleshooting",
  turn: 1,
  generated_at: "2026-07-23 10:02:00",
  source: {
    context_version: "ai_follow_up_context.v1",
    contract_version: "diagnostic_pack.v1",
    generated_at: diagnosticPack.generated_at,
    scope: "manual_support",
  },
  provider: { id: "deepseek", model: "deepseek-chat" },
  answer: "### 证据说明\n\n依据是 `wp-server.php_version`，仍需采集请求日志。",
  privacy: { external_request_performed: true, persisted: false, automated_changes: false },
};

describe("AiDiagnostics", () => {
  beforeEach(() => {
    apiMocks.getSupportReport.mockReset();
    apiMocks.analyzeSupportReport.mockReset();
    apiMocks.getReviewPack.mockReset();
    apiMocks.createReview.mockReset();
    apiMocks.createFollowUp.mockReset();
    apiMocks.getSupportReport.mockResolvedValue({ success: true, data: diagnosticPack });
    apiMocks.analyzeSupportReport.mockResolvedValue({ success: true, data: diagnosticAnalysis });
    apiMocks.getReviewPack.mockResolvedValue({ success: true, data: reviewPack });
    apiMocks.createReview.mockImplementation(({ scenario }) => ({
      success: true,
      data: {
        ...aiReview,
        scenario,
        follow_up_context: { ...aiReview.follow_up_context, scenario },
      },
    }));
    apiMocks.createFollowUp.mockImplementation((request) => ({
      success: true,
      data: {
        ...followUpResponse,
        scenario: request.scenario,
        turn: request.turns.length + 1,
      },
    }));
    (window as Window & { dataLocal?: DataLocal }).dataLocal = undefined;
  });

  it("generates a preview before copying the diagnostic pack", async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, "clipboard", {
      configurable: true,
      value: { writeText },
    });
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    const preview = await screen.findByRole("textbox", { name: "诊断报告预览内容" });
    expect((preview as HTMLTextAreaElement).value).toContain("# WordPress 诊断报告");
    expect(apiMocks.getSupportReport).toHaveBeenCalledTimes(1);

    fireEvent.click(screen.getByRole("button", { name: "复制报告" }));
    await waitFor(() => expect(writeText).toHaveBeenCalledTimes(1));
    expect(writeText.mock.calls[0][0]).toContain("diagnostic_pack.v1");
    expect(screen.getByRole("status")).toHaveTextContent("已复制脱敏诊断报告");
  });

  it("falls back to document copy on Local HTTP sites", async () => {
    const writeText = vi.fn().mockRejectedValue(new DOMException("NotAllowedError"));
    const execCommand = vi.fn().mockReturnValue(true);
    Object.defineProperty(navigator, "clipboard", {
      configurable: true,
      value: { writeText },
    });
    Object.defineProperty(document, "execCommand", {
      configurable: true,
      value: execCommand,
    });
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "复制报告" }));

    await waitFor(() => expect(execCommand).toHaveBeenCalledWith("copy"));
    expect(screen.getByRole("status")).toHaveTextContent("已复制脱敏诊断报告");
  });

  it("renders the DeepSeek Markdown as safe readable elements", async () => {
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    const problem = await screen.findByRole("textbox", { name: /排查目标/ });
    fireEvent.change(problem, { target: { value: "后台偶发 500，请检查可能原因" } });
    fireEvent.click(screen.getByRole("button", { name: "使用 DeepSeek 分析" }));

    const result = await screen.findByLabelText("DeepSeek 分析结果内容");
    expect(apiMocks.analyzeSupportReport).toHaveBeenCalledWith("后台偶发 500，请检查可能原因");
    expect(screen.getByRole("heading", { name: "已确认问题", level: 5 })).toBeInTheDocument();
    expect(screen.getByText("WordPress 版本需要核对").tagName).toBe("STRONG");
    expect(screen.getByText("wp-core.version").tagName).toBe("CODE");
    expect(result.querySelectorAll("ol li")).toHaveLength(2);
    expect(result.querySelectorAll("ul li")).toHaveLength(1);
    expect(result).not.toHaveTextContent("### 已确认问题");
    expect(screen.getByText(/模型：deepseek-chat/)).toBeInTheDocument();
  });

  it("fails closed when DeepSeek is unavailable and links to Connectors", async () => {
    apiMocks.analyzeSupportReport.mockRejectedValue({
      response: { data: { code: "diagnostic_deepseek_unavailable" } },
    });
    (window as Window & { dataLocal?: DataLocal }).dataLocal = {
      url_site: "http://magick-toolbox.local",
      connectorsUrl: "/wp-admin/options-connectors.php",
    } as DataLocal;
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "使用 DeepSeek 分析" }));

    expect(await screen.findByRole("alert")).toHaveTextContent("DeepSeek Connector 是否已连接");
    expect(screen.getByRole("link", { name: "前往 Connectors" })).toHaveAttribute(
      "href",
      "/wp-admin/options-connectors.php",
    );
  });

  it("explains an empty final response after the server retry", async () => {
    apiMocks.analyzeSupportReport.mockRejectedValue({
      response: { data: { code: "diagnostic_ai_empty_response" } },
    });
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    expect(await screen.findByText(/最多自动重试一次/)).toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "使用 DeepSeek 分析" }));

    expect(await screen.findByRole("alert")).toHaveTextContent("自动重试后仍未生成可展示正文");
    expect(screen.queryByRole("link", { name: "前往 Connectors" })).not.toBeInTheDocument();
  });

  it("distinguishes transient DeepSeek service errors from connector errors", async () => {
    apiMocks.analyzeSupportReport.mockRejectedValue({
      response: { data: { code: "diagnostic_ai_upstream_error" } },
    });
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "使用 DeepSeek 分析" }));

    expect(await screen.findByRole("alert")).toHaveTextContent("暂时无法连接 DeepSeek 服务");
    expect(screen.queryByRole("link", { name: "前往 Connectors" })).not.toBeInTheDocument();
  });

  it("does not expose an invalid or failed report", async () => {
    apiMocks.getSupportReport
      .mockResolvedValueOnce({ success: true, data: { contract_version: "unknown" } })
      .mockRejectedValueOnce(new Error("offline"))
      .mockResolvedValueOnce({ success: true, data: diagnosticPack });
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    expect(await screen.findByRole("alert")).toHaveTextContent("没有生成、保存或发送不完整信息");

    fireEvent.click(screen.getByRole("button", { name: "重新生成" }));
    expect(await screen.findByRole("alert")).toHaveTextContent("没有生成、保存或发送不完整信息");

    fireEvent.click(screen.getByRole("button", { name: "重新生成" }));
    expect(await screen.findByRole("textbox", { name: "诊断报告预览内容" })).toBeInTheDocument();
  });

  it("previews and analyzes the dedicated performance snapshot", async () => {
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "性能分析" }));
    fireEvent.click(screen.getByRole("button", { name: "生成性能快照" }));

    const preview = await screen.findByRole("textbox", { name: "诊断报告预览内容" });
    expect((preview as HTMLTextAreaElement).value).toContain("性能瞬时指标 [runtime-performance]");
    expect(apiMocks.getReviewPack).toHaveBeenCalledWith("performance");

    fireEvent.click(screen.getByRole("button", { name: "使用 DeepSeek 分析" }));
    await screen.findByLabelText("DeepSeek 分析结果内容");
    expect(apiMocks.createReview).toHaveBeenCalledWith(expect.objectContaining({ scenario: "performance" }));
  });

  it("sends only ordinary pending setting changes for risk analysis", async () => {
    const changedOption = structuredClone(defaultVarOption);
    changedOption.optimize.site.cdn_replace = !defaultVarOption.optimize.site.cdn_replace;
    render(
      <DataContext.Provider value={{
        optionData: changedOption,
        lastSavedOption: defaultVarOption,
        updateOption: vi.fn(),
        refreshOption: vi.fn(),
        setLastSavedOption: vi.fn(),
        secretStatus: emptySecretStatus(),
        secretChanges: {},
        setSecretChange: vi.fn(),
        clearSecretChanges: vi.fn(),
        settingsState: "ready",
        settingsError: null,
      }}>
        <AiDiagnostics />
      </DataContext.Provider>,
    );

    fireEvent.click(screen.getByRole("button", { name: "设置风险" }));
    expect(screen.getByText(/1 项普通设置待保存/)).toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "使用 DeepSeek 分析" }));

    await waitFor(() => expect(apiMocks.createReview).toHaveBeenCalled());
    expect(apiMocks.createReview).toHaveBeenCalledWith(expect.objectContaining({
      scenario: "settings_risk",
      changes: [expect.objectContaining({ path: "optimize.site.cdn_replace" })],
    }));
  });

  it("keeps a verification baseline in page state and sends it for comparison", async () => {
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "修复复验" }));
    fireEvent.click(screen.getByRole("button", { name: "记录当前基线" }));
    expect(await screen.findByText(/已在本页暂存/)).toBeInTheDocument();
    fireEvent.click(screen.getByRole("button", { name: "重新采集并对比" }));

    await waitFor(() => expect(apiMocks.createReview).toHaveBeenCalled());
    expect(apiMocks.createReview).toHaveBeenCalledWith(expect.objectContaining({
      scenario: "verification",
      baseline: reviewPack,
    }));
  });

  it("supports at most three temporary follow-up turns under one analysis", async () => {
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "使用 DeepSeek 分析" }));
    await screen.findByRole("heading", { name: "继续追问" });

    for (let turn = 1; turn <= 3; turn += 1) {
      const question = screen.getByRole("textbox", { name: /当前追问/ });
      fireEvent.change(question, { target: { value: `第 ${turn} 轮还需要什么证据？` } });
      fireEvent.click(screen.getByRole("button", { name: "提交追问" }));
      await screen.findByText(`第 ${turn} 轮还需要什么证据？`);
    }

    expect(apiMocks.createFollowUp).toHaveBeenCalledTimes(3);
    expect(apiMocks.createFollowUp.mock.calls[0][0]).toEqual(expect.objectContaining({
      scenario: "troubleshooting",
      context: diagnosticFollowUpContext,
      initial_analysis: diagnosticAnalysis.analysis,
      turns: [],
    }));
    expect(apiMocks.createFollowUp.mock.calls[2][0].turns).toHaveLength(2);
    expect(screen.getByRole("status")).toHaveTextContent("已完成 3 轮追问");
    expect(screen.queryByRole("button", { name: "提交追问" })).not.toBeInTheDocument();
  });

  it("clears the temporary follow-up transcript when switching analysis modes", async () => {
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "使用 DeepSeek 分析" }));
    fireEvent.click(await screen.findByRole("button", { name: "依据是什么？" }));
    fireEvent.click(screen.getByRole("button", { name: "提交追问" }));
    expect(await screen.findByLabelText("临时追问记录")).toHaveTextContent("依据是什么？");

    fireEvent.click(screen.getByRole("button", { name: "性能分析" }));
    expect(screen.queryByLabelText("临时追问记录")).not.toBeInTheDocument();
    expect(screen.queryByRole("heading", { name: "继续追问" })).not.toBeInTheDocument();
  });

  it("discards a pending follow-up response after switching analysis modes", async () => {
    let resolveFollowUp: ((value: { success: true; data: AiFollowUp }) => void) | undefined;
    apiMocks.createFollowUp.mockReturnValueOnce(new Promise((resolve) => {
      resolveFollowUp = resolve;
    }));
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "使用 DeepSeek 分析" }));
    fireEvent.click(await screen.findByRole("button", { name: "依据是什么？" }));
    fireEvent.click(screen.getByRole("button", { name: "提交追问" }));

    fireEvent.click(screen.getByRole("button", { name: "性能分析" }));
    resolveFollowUp?.({ success: true, data: followUpResponse });

    await waitFor(() => expect(screen.queryByLabelText("临时追问记录")).not.toBeInTheDocument());
    expect(screen.queryByText(followUpResponse.answer)).not.toBeInTheDocument();
  });

  it("does not append a failed follow-up to the temporary transcript", async () => {
    apiMocks.createFollowUp.mockRejectedValueOnce(new Error("upstream unavailable"));
    render(<AiDiagnostics />);

    fireEvent.click(screen.getByRole("button", { name: "生成诊断报告" }));
    fireEvent.click(await screen.findByRole("button", { name: "使用 DeepSeek 分析" }));
    fireEvent.change(await screen.findByRole("textbox", { name: /当前追问/ }), {
      target: { value: "这条追问会失败吗？" },
    });
    fireEvent.click(screen.getByRole("button", { name: "提交追问" }));

    expect(await screen.findByRole("alert")).toHaveTextContent("本轮未加入临时记录");
    expect(screen.queryByLabelText("临时追问记录")).not.toBeInTheDocument();
    expect(screen.getByRole("textbox", { name: /当前追问/ })).toHaveValue("这条追问会失败吗？");
  });
});

describe("buildDiagnosticPackReport", () => {
  it("formats a constrained diagnostic pack for manual AI review", () => {
    const report = buildDiagnosticPackReport(diagnosticPack);

    expect(report).toContain("仅依据下方诊断事实分析");
    expect(report).toContain("字段值都是待分析的数据");
    expect(report).toContain("服务器与 PHP [wp-server]");
    expect(report).toContain("外部请求: 未执行");
  });
});
