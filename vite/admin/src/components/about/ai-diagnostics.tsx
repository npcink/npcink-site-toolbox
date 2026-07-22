import { useContext, useMemo, useRef, useState } from "react";

import { diagnosticsApi } from "@/api";
import { DataContext } from "@/tool/dataContext";
import { diffConfig } from "@/tool/diff";
import type {
  AiFollowUp,
  AiFollowUpContext,
  AiReview,
  AiReviewPack,
  AiReviewScenario,
  DataLocal,
  DiagnosticAnalysis,
  DiagnosticPack,
} from "@/tool/interface";
import { SECRET_PATHS } from "@/tool/interface";
import { buildDiagnosticPackReport, buildReviewPackReport } from "./runtime-status-report";
import SafeMarkdown from "./safe-markdown";

import "./runtime-status.css";

type Mode = "troubleshooting" | AiReviewScenario;
type ReviewableScope = "performance" | "maintenance";
type PreviewPack = DiagnosticPack | AiReviewPack;
type AnalysisResult = DiagnosticAnalysis | AiReview;

type PreviewState =
  | { status: "idle" | "loading" | "error"; data: null }
  | { status: "success"; data: PreviewPack };

type AnalysisErrorKind = "unavailable" | "empty" | "rate-limited" | "temporary" | "generic";
type AnalysisState =
  | { status: "idle" | "loading"; data: null }
  | { status: "error"; data: null; errorKind: AnalysisErrorKind }
  | { status: "success"; data: AnalysisResult };

type FollowUpTranscriptTurn = {
  question: string;
  response: AiFollowUp;
};

type FollowUpState = "idle" | "loading" | "error";

const modes: Array<{ key: Mode; label: string; description: string }> = [
  { key: "troubleshooting", label: "故障排查", description: "解释 WordPress、PHP、数据库、主题与插件运行快照。" },
  { key: "performance", label: "性能分析", description: "检查自动加载、缓存、Cron 与数据库单次往返等性能风险信号。" },
  { key: "maintenance", label: "维护解读", description: "归纳数据库、SEO、媒体、搜索健康与对象存储配置状态。" },
  { key: "settings_risk", label: "设置风险", description: "解释当前尚未保存的普通设置差异，不包含任何凭据字段。" },
  { key: "verification", label: "修复复验", description: "对比同范围的前后快照，区分改善、恶化与证据不足。" },
];

async function copyText(text: string): Promise<boolean> {
  if (navigator.clipboard?.writeText) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch {
      // Local HTTP sites may expose the Clipboard API but reject the write.
    }
  }

  const textarea = document.createElement("textarea");
  textarea.value = text;
  textarea.setAttribute("readonly", "");
  textarea.style.position = "fixed";
  textarea.style.opacity = "0";
  document.body.appendChild(textarea);
  textarea.select();
  try {
    return document.execCommand("copy");
  } catch {
    return false;
  } finally {
    textarea.remove();
  }
}

function hasValidSections(value: { sections?: unknown }): boolean {
  return Array.isArray(value.sections) && value.sections.length > 0 && value.sections.every(
    (section) =>
      typeof section?.id === "string" && section.id.length > 0 &&
      typeof section?.title === "string" && section.title.length > 0 &&
      Array.isArray(section.facts) && section.facts.length > 0 && section.facts.every(
        (fact: { id?: unknown; label?: unknown; value?: unknown }) =>
          typeof fact.id === "string" && fact.id.length > 0 &&
          typeof fact.label === "string" && fact.label.length > 0 &&
          typeof fact.value === "string" && fact.value.length > 0,
      ),
  );
}

function isDiagnosticPack(value: unknown): value is DiagnosticPack {
  if (!value || typeof value !== "object") return false;
  const data = value as Partial<DiagnosticPack>;
  return Boolean(
    data.contract_version === "diagnostic_pack.v1" && data.scope === "manual_support" &&
    typeof data.generated_at === "string" && hasValidSections(data) &&
    Array.isArray(data.limitations) &&
    data.privacy?.external_requests_performed === false && data.privacy.persisted === false,
  );
}

function isReviewPack(value: unknown): value is AiReviewPack {
  if (!value || typeof value !== "object") return false;
  const data = value as Partial<AiReviewPack>;
  return Boolean(
    data.contract_version === "site_review_pack.v1" &&
    ["performance", "maintenance", "settings_risk"].includes(data.scope || "") &&
    typeof data.generated_at === "string" && hasValidSections(data) &&
    Array.isArray(data.limitations) &&
    data.privacy?.external_requests_performed === false && data.privacy.persisted === false,
  );
}

function isDiagnosticAnalysis(value: unknown): value is DiagnosticAnalysis {
  if (!value || typeof value !== "object") return false;
  const data = value as Partial<DiagnosticAnalysis>;
  return Boolean(
    data.contract_version === "diagnostic_analysis.v1" && typeof data.generated_at === "string" &&
    data.source?.contract_version === "diagnostic_pack.v1" && data.provider?.id === "deepseek" &&
    typeof data.analysis === "string" && data.analysis.trim().length > 0 &&
    data.privacy?.persisted === false && data.privacy.automated_changes === false,
  );
}

function isAiReview(value: unknown): value is AiReview {
  if (!value || typeof value !== "object") return false;
  const data = value as Partial<AiReview>;
  return Boolean(
    data.contract_version === "ai_review.v1" &&
    ["performance", "maintenance", "settings_risk", "verification"].includes(data.scenario || "") &&
    typeof data.generated_at === "string" && data.provider?.id === "deepseek" &&
    typeof data.analysis === "string" && data.analysis.trim().length > 0 &&
    data.privacy?.persisted === false && data.privacy.automated_changes === false,
  );
}

function isFollowUpContext(value: unknown): value is AiFollowUpContext {
  if (!value || typeof value !== "object") return false;
  const context = value as Partial<AiFollowUpContext>;
  const pack = context.source_pack;
  return Boolean(
    context.contract_version === "ai_follow_up_context.v1" &&
    ["troubleshooting", "performance", "maintenance", "settings_risk", "verification"].includes(context.scenario || "") &&
    pack && typeof pack.generated_at === "string" && typeof pack.scope === "string" &&
    ["diagnostic_pack.v1", "site_review_pack.v1", "site_review_comparison.v1"].includes(pack.contract_version || "") &&
    hasValidSections(pack) && Array.isArray(pack.limitations) &&
    pack.privacy?.external_requests_performed === false && pack.privacy.persisted === false,
  );
}

function isAiFollowUp(value: unknown): value is AiFollowUp {
  if (!value || typeof value !== "object") return false;
  const data = value as Partial<AiFollowUp>;
  return Boolean(
    data.contract_version === "ai_follow_up.v1" &&
    ["troubleshooting", "performance", "maintenance", "settings_risk", "verification"].includes(data.scenario || "") &&
    [1, 2, 3].includes(data.turn || 0) && data.provider?.id === "deepseek" &&
    typeof data.answer === "string" && data.answer.trim().length > 0 &&
    data.privacy?.persisted === false && data.privacy.automated_changes === false,
  );
}

function getAnalysisErrorKind(error: unknown): AnalysisErrorKind {
  const code = (error as { response?: { data?: { code?: string } } })?.response?.data?.code;
  if (["diagnostic_ai_unavailable", "diagnostic_deepseek_unavailable", "diagnostic_ai_auth_error"].includes(code || "")) return "unavailable";
  if (code === "diagnostic_ai_empty_response") return "empty";
  if (code === "diagnostic_ai_rate_limited") return "rate-limited";
  if (["diagnostic_ai_network_error", "diagnostic_ai_upstream_error"].includes(code || "")) return "temporary";
  return "generic";
}

function getAnalysisErrorMessage(errorKind: AnalysisErrorKind): string {
  if (errorKind === "unavailable") return "请检查 WordPress 7.0+ 的 DeepSeek Connector 是否已连接；数据未由本插件保存。";
  if (errorKind === "empty") return "模型在自动重试后仍未生成可展示正文；可稍后重试。";
  if (errorKind === "rate-limited") return "DeepSeek 当前请求过多或额度受限；请稍后重试。";
  if (errorKind === "temporary") return "暂时无法连接 DeepSeek 服务；请稍后重试。";
  return "没有保存不完整回答，也没有执行任何修改。可稍后重试。";
}

function previewText(pack: PreviewPack): string {
  return pack.contract_version === "diagnostic_pack.v1"
    ? buildDiagnosticPackReport(pack)
    : buildReviewPackReport(pack);
}

const AiDiagnostics = () => {
  const { optionData, lastSavedOption } = useContext(DataContext);
  const [mode, setMode] = useState<Mode>("troubleshooting");
  const [previewState, setPreviewState] = useState<PreviewState>({ status: "idle", data: null });
  const [analysisState, setAnalysisState] = useState<AnalysisState>({ status: "idle", data: null });
  const [copyState, setCopyState] = useState<"idle" | "success" | "error">("idle");
  const [problem, setProblem] = useState("");
  const [baselineScope, setBaselineScope] = useState<ReviewableScope>("performance");
  const [baseline, setBaseline] = useState<AiReviewPack | null>(null);
  const [followUpQuestion, setFollowUpQuestion] = useState("");
  const [followUpTurns, setFollowUpTurns] = useState<FollowUpTranscriptTurn[]>([]);
  const [followUpState, setFollowUpState] = useState<FollowUpState>("idle");
  const followUpRequestEpoch = useRef(0);

  const settingDiffs = useMemo(
    () => diffConfig(lastSavedOption, optionData).filter((item) => !SECRET_PATHS.includes(item.path as never)),
    [lastSavedOption, optionData],
  );
  const activeMode = modes.find((item) => item.key === mode) || modes[0];

  const resetFollowUp = () => {
    followUpRequestEpoch.current += 1;
    setFollowUpQuestion("");
    setFollowUpTurns([]);
    setFollowUpState("idle");
  };

  const resetTransientState = () => {
    setPreviewState({ status: "idle", data: null });
    setAnalysisState({ status: "idle", data: null });
    setCopyState("idle");
    setProblem("");
    resetFollowUp();
  };

  const selectMode = (nextMode: Mode) => {
    setMode(nextMode);
    resetTransientState();
  };

  const generatePreview = async () => {
    setPreviewState({ status: "loading", data: null });
    setAnalysisState({ status: "idle", data: null });
    setCopyState("idle");
    resetFollowUp();
    try {
      if (mode === "troubleshooting") {
        const response = await diagnosticsApi.getSupportReport();
        setPreviewState(response?.success && isDiagnosticPack(response.data)
          ? { status: "success", data: response.data }
          : { status: "error", data: null });
        return;
      }
      if (mode === "performance" || mode === "maintenance") {
        const response = await diagnosticsApi.getReviewPack(mode);
        setPreviewState(response?.success && isReviewPack(response.data)
          ? { status: "success", data: response.data }
          : { status: "error", data: null });
      }
    } catch {
      setPreviewState({ status: "error", data: null });
    }
  };

  const captureBaseline = async () => {
    setPreviewState({ status: "loading", data: null });
    setAnalysisState({ status: "idle", data: null });
    resetFollowUp();
    try {
      const response = await diagnosticsApi.getReviewPack(baselineScope);
      if (!response?.success || !isReviewPack(response.data)) {
        setPreviewState({ status: "error", data: null });
        return;
      }
      setBaseline(response.data);
      setPreviewState({ status: "success", data: response.data });
    } catch {
      setPreviewState({ status: "error", data: null });
    }
  };

  const analyze = async () => {
    if (mode === "troubleshooting" && previewState.status !== "success") return;
    if ((mode === "performance" || mode === "maintenance") && previewState.status !== "success") return;
    if (mode === "settings_risk" && settingDiffs.length === 0) return;
    if (mode === "verification" && !baseline) return;

    setAnalysisState({ status: "loading", data: null });
    resetFollowUp();
    try {
      if (mode === "troubleshooting") {
        const response = await diagnosticsApi.analyzeSupportReport(problem.trim());
        setAnalysisState(response?.success && isDiagnosticAnalysis(response.data)
          ? { status: "success", data: response.data }
          : { status: "error", data: null, errorKind: "generic" });
        return;
      }

      const response = await diagnosticsApi.createReview({
        scenario: mode,
        problem: problem.trim(),
        changes: mode === "settings_risk"
          ? settingDiffs.map(({ path, before, after }) => ({ path, before, after }))
          : undefined,
        baseline: mode === "verification" ? baseline || undefined : undefined,
      });
      setAnalysisState(response?.success && isAiReview(response.data)
        ? { status: "success", data: response.data }
        : { status: "error", data: null, errorKind: "generic" });
    } catch (error) {
      setAnalysisState({ status: "error", data: null, errorKind: getAnalysisErrorKind(error) });
    }
  };

  const submitFollowUp = async () => {
    if (analysisState.status !== "success" || followUpTurns.length >= 3) return;
    const context = analysisState.data.follow_up_context;
    const question = followUpQuestion.trim();
    if (!isFollowUpContext(context) || question.length === 0 || question.length > 1000) return;

    const requestEpoch = ++followUpRequestEpoch.current;
    setFollowUpState("loading");
    try {
      const response = await diagnosticsApi.createFollowUp({
        scenario: context.scenario,
        question,
        context,
        initial_analysis: analysisState.data.analysis,
        turns: followUpTurns.map((turn) => ({
          question: turn.question,
          answer: turn.response.answer,
        })),
      });
      if (requestEpoch !== followUpRequestEpoch.current) return;
      if (
        !response?.success || !isAiFollowUp(response.data) ||
        response.data.scenario !== context.scenario || response.data.turn !== followUpTurns.length + 1
      ) {
        setFollowUpState("error");
        return;
      }
      const followUpResponse = response.data;
      setFollowUpTurns((current) => [...current, { question, response: followUpResponse }]);
      setFollowUpQuestion("");
      setFollowUpState("idle");
    } catch {
      if (requestEpoch !== followUpRequestEpoch.current) return;
      setFollowUpState("error");
    }
  };

  const copyPreview = async () => {
    if (previewState.status !== "success") return;
    setCopyState(await copyText(previewText(previewState.data)) ? "success" : "error");
  };

  const actionLabel = mode === "troubleshooting"
    ? "生成诊断报告"
    : mode === "performance"
      ? "生成性能快照"
      : "生成维护快照";

  return (
    <div className="mabox-runtime-status mabox-ai-diagnostics">
      <header className="mabox-runtime-status__header">
        <div>
          <h2>AI 诊断与分析</h2>
          <p>管理员先检查白名单快照，再显式发送给 DeepSeek；AI 只解释，不修改站点。</p>
        </div>
      </header>

      <nav className="mabox-ai-diagnostics__modes" aria-label="AI 分析能力">
        {modes.map((item) => (
          <button
            key={item.key}
            type="button"
            aria-pressed={mode === item.key}
            className={mode === item.key ? "is-active" : ""}
            onClick={() => selectMode(item.key)}
          >
            {item.label}
          </button>
        ))}
      </nav>

      <section className="mabox-runtime-status__report mabox-ai-diagnostics__workspace">
        <div className="mabox-runtime-status__section-heading">
          <div>
            <h3>{activeMode.label}</h3>
            <p>{activeMode.description}</p>
          </div>
          {(mode === "troubleshooting" || mode === "performance" || mode === "maintenance") && (
            <button
              type="button"
              className="button button-primary"
              disabled={previewState.status === "loading"}
              onClick={() => void generatePreview()}
            >
              {previewState.status === "loading" ? "正在采集" : actionLabel}
            </button>
          )}
        </div>

        {mode === "settings_risk" && (
          <div className="mabox-ai-diagnostics__settings-diff">
            <p><strong>{settingDiffs.length} 项普通设置待保存</strong>。凭据变更不会进入本分析。</p>
            {settingDiffs.length > 0 ? (
              <ul>{settingDiffs.slice(0, 50).map((item) => <li key={item.path}><code>{item.path}</code> · {item.label}</li>)}</ul>
            ) : <p>当前没有普通设置差异。请先在其他标签页调整设置，再回到这里分析。</p>}
          </div>
        )}

        {mode === "verification" && (
          <div className="mabox-ai-diagnostics__baseline">
            <label htmlFor="ai-verification-scope"><strong>基线范围</strong></label>
            <select
              id="ai-verification-scope"
              value={baselineScope}
              disabled={previewState.status === "loading"}
              onChange={(event) => {
                setBaselineScope(event.target.value as ReviewableScope);
                setBaseline(null);
                resetTransientState();
              }}
            >
              <option value="performance">性能快照</option>
              <option value="maintenance">维护快照</option>
            </select>
            <button type="button" className="button button-primary" disabled={previewState.status === "loading"} onClick={() => void captureBaseline()}>
              {previewState.status === "loading" ? "正在记录" : baseline ? "重新记录基线" : "记录当前基线"}
            </button>
            {baseline && <p role="status">已在本页暂存 {baseline.generated_at} 的{baselineScope === "performance" ? "性能" : "维护"}基线；刷新或离开页面后不会保留。</p>}
          </div>
        )}

        {previewState.status === "error" && (
          <div className="mabox-runtime-state mabox-runtime-state--error" role="alert">
            <div><strong>快照生成失败</strong><span>没有生成、保存或发送不完整信息，请稍后重试。</span></div>
            <button
              type="button"
              className="button"
              onClick={() => void (mode === "verification" ? captureBaseline() : generatePreview())}
            >
              重新生成
            </button>
          </div>
        )}

        {previewState.status === "success" && (
          <div className="mabox-ai-diagnostics__preview">
            <div className="mabox-runtime-status__section-heading">
              <div><h4>发送前预览</h4><p>当前内容尚未发送给任何 AI 服务。</p></div>
              <button type="button" className="button" onClick={() => void copyPreview()}>
                {mode === "troubleshooting" ? "复制报告" : "复制快照"}
              </button>
            </div>
            <textarea className="mabox-runtime-status__report-preview" aria-label="诊断报告预览内容" readOnly rows={16} value={previewText(previewState.data)} />
          </div>
        )}

        {(mode === "settings_risk" || mode === "verification" || previewState.status === "success") && (
          <div className="mabox-runtime-status__analysis-controls">
            <label htmlFor="ai-diagnostic-problem">
              <strong>{mode === "troubleshooting" ? "排查目标（可选）" : "分析目标（可选）"}</strong>
              <span>补充你关心的现象或本次调整目的；不要填写密码、密钥或个人信息。</span>
            </label>
            <textarea id="ai-diagnostic-problem" rows={3} maxLength={2000} value={problem} onChange={(event) => setProblem(event.target.value)} />
            <p>
              点击后会向 DeepSeek 发送同范围最新白名单事实。
              {mode === "troubleshooting" ? " 若模型只返回空正文，最多自动重试一次。" : ""}
              API Key 由 WordPress Connectors 管理，本插件不读取、不保存，也不会执行模型建议。
            </p>
            <button
              type="button"
              className="button button-primary"
              disabled={analysisState.status === "loading" || (mode === "settings_risk" && settingDiffs.length === 0) || (mode === "verification" && !baseline)}
              onClick={() => void analyze()}
            >
              {analysisState.status === "loading"
                ? "DeepSeek 正在分析"
                : mode === "verification" ? "重新采集并对比" : "使用 DeepSeek 分析"}
            </button>
          </div>
        )}

        {analysisState.status === "error" && (
          <div className="mabox-runtime-state mabox-runtime-state--error" role="alert">
            <div><strong>DeepSeek 分析失败</strong><span>{getAnalysisErrorMessage(analysisState.errorKind)}</span></div>
            {analysisState.errorKind === "unavailable" && (
              <a className="button" href={(window as Window & { dataLocal?: DataLocal }).dataLocal?.connectorsUrl || "/wp-admin/options-connectors.php"}>前往 Connectors</a>
            )}
          </div>
        )}

        {analysisState.status === "success" && (
          <section className="mabox-runtime-status__analysis-result" aria-labelledby="ai-diagnostic-result-heading">
            <div>
              <h4 id="ai-diagnostic-result-heading">DeepSeek 分析结果</h4>
              <p>Provider：{analysisState.data.provider.id}{analysisState.data.provider.model ? ` · 模型：${analysisState.data.provider.model}` : ""}。结果未保存，也未自动修改站点。</p>
            </div>
            <SafeMarkdown className="mabox-runtime-status__markdown" markdown={analysisState.data.analysis} />

            {isFollowUpContext(analysisState.data.follow_up_context) && (
              <div className="mabox-ai-diagnostics__follow-up">
                <div className="mabox-ai-diagnostics__follow-up-heading">
                  <div>
                    <h5>继续追问</h5>
                    <p>{followUpTurns.length}/3 轮 · 仅保存在当前页面，切换模式或刷新后清除。</p>
                  </div>
                </div>

                {followUpTurns.length > 0 && (
                  <ol className="mabox-ai-diagnostics__transcript" aria-label="临时追问记录">
                    {followUpTurns.map((turn, index) => (
                      <li key={`${index}-${turn.question}`}>
                        <div className="mabox-ai-diagnostics__question">
                          <span>追问 {index + 1}</span>
                          <p>{turn.question}</p>
                        </div>
                        <SafeMarkdown
                          className="mabox-runtime-status__markdown mabox-ai-diagnostics__answer"
                          markdown={turn.response.answer}
                        />
                      </li>
                    ))}
                  </ol>
                )}

                {followUpTurns.length < 3 ? (
                  <div className="mabox-ai-diagnostics__follow-up-controls">
                    <div className="mabox-ai-diagnostics__quick-questions" aria-label="快捷追问">
                      {["依据是什么？", "还缺哪些证据？", "给出安全的验证步骤。"].map((question) => (
                        <button
                          key={question}
                          type="button"
                          className="button button-small"
                          disabled={followUpState === "loading"}
                          onClick={() => setFollowUpQuestion(question)}
                        >
                          {question}
                        </button>
                      ))}
                    </div>
                    <label htmlFor="ai-follow-up-question">
                      <strong>当前追问</strong>
                      <span>最多 1000 字，不要填写密码、密钥或个人信息。</span>
                    </label>
                    <textarea
                      id="ai-follow-up-question"
                      rows={3}
                      maxLength={1000}
                      value={followUpQuestion}
                      disabled={followUpState === "loading"}
                      onChange={(event) => setFollowUpQuestion(event.target.value)}
                    />
                    <p>提交时会再次发送原白名单事实、首次回答和本页已完成的追问；这些内容不会持久化。</p>
                    <button
                      type="button"
                      className="button button-primary"
                      disabled={followUpState === "loading" || followUpQuestion.trim().length === 0}
                      onClick={() => void submitFollowUp()}
                    >
                      {followUpState === "loading" ? "DeepSeek 正在回答" : "提交追问"}
                    </button>
                  </div>
                ) : (
                  <p className="mabox-ai-diagnostics__follow-up-limit" role="status">
                    已完成 3 轮追问。若需要新的上下文，请重新生成快照并开始一次新分析。
                  </p>
                )}

                {followUpState === "error" && (
                  <div className="mabox-runtime-state mabox-runtime-state--error" role="alert">
                    <div><strong>追问失败</strong><span>本轮未加入临时记录，也没有保存任何内容；请稍后重试。</span></div>
                  </div>
                )}
              </div>
            )}
          </section>
        )}
      </section>

      {copyState !== "idle" && (
        <p className={`mabox-runtime-status__copy mabox-runtime-status__copy--${copyState}`} role="status">
          {copyState === "success"
            ? mode === "troubleshooting" ? "已复制脱敏诊断报告，请在分享前再次检查。" : "已复制白名单快照，请在分享前再次检查。"
            : "浏览器未允许复制，请刷新页面后重试。"}
        </p>
      )}
    </div>
  );
};

export default AiDiagnostics;
