import React, { useCallback, useContext, useEffect, useMemo, useState } from "react";

import { diagnosticsApi, searchHealthApi } from "@/api";
import { DataContext } from "@/tool/dataContext";
import type { DiagnosticSummary, Option, SearchHealthSummary } from "@/tool/interface";

import "./overview.css";

type OverviewView = "site" | "content" | "seo" | "security" | "china" | "maintenance";

interface DashboardProps {
  onNavigate?: (view: OverviewView, itemId?: string) => void;
}

type RemoteState<T> =
  | { status: "loading"; data: null }
  | { status: "success"; data: T }
  | { status: "empty"; data: null }
  | { status: "error"; data: null };

interface ToggleStats {
  enabled: number;
  total: number;
}

interface SecurityCheck {
  label: string;
  detail: string;
  status: "good" | "partial" | "attention";
}

interface NextStep {
  id: string;
  title: string;
  description: string;
  view: OverviewView;
  action: string;
}

const diagnosticStatusLabels: Record<DiagnosticSummary["status"], string> = {
  good: "状态良好",
  warning: "需要关注",
  critical: "需要处理",
};

function countBooleanToggles(value: unknown): ToggleStats {
  const stats: ToggleStats = { enabled: 0, total: 0 };

  const visit = (current: unknown) => {
    if (typeof current === "boolean") {
      stats.total += 1;
      if (current) stats.enabled += 1;
      return;
    }

    if (!current || typeof current !== "object" || Array.isArray(current)) return;
    Object.values(current).forEach(visit);
  };

  visit(value);
  return stats;
}

function isEnabled(value: unknown): boolean {
  return value === true || (typeof value === "string" && value !== "" && value !== "false");
}

function getSecurityChecks(optionData: Option): SecurityCheck[] {
  const loginProtected =
    isEnabled(optionData.login?.security?.login_code) ||
    Boolean(optionData.domestic?.login_security?.fail_limit_enabled) ||
    Boolean(optionData.domestic?.login_security?.ban_enumeration_enabled);

  const commentProtections = [
    optionData.page?.comment?.interval,
    optionData.page?.comment?.words_number,
    optionData.page?.comment?.sensitive_words,
    optionData.domestic?.comment_security?.blacklist_enabled,
    optionData.domestic?.comment_security?.ip_rate_enabled,
  ].filter(Boolean).length;

  const exposureProtections = [
    optionData.optimize?.site?.remove_RSS_version,
    optionData.optimize?.site?.remove_sitemap_users,
  ].filter(Boolean).length;

  return [
    {
      label: "登录保护",
      detail: loginProtected ? "已启用至少一项登录防护" : "尚未启用登录防护",
      status: loginProtected ? "good" : "attention",
    },
    {
      label: "评论防护",
      detail:
        commentProtections >= 2
          ? "评论限制与过滤已配置"
          : commentProtections === 1
            ? "已启用一项基础防护"
            : "尚未启用评论防护",
      status: commentProtections >= 2 ? "good" : commentProtections === 1 ? "partial" : "attention",
    },
    {
      label: "信息暴露",
      detail:
        exposureProtections === 2
          ? "版本与作者站点地图均已隐藏"
          : exposureProtections === 1
            ? "仍有一项暴露面可收紧"
            : "建议隐藏版本和作者站点地图",
      status: exposureProtections === 2 ? "good" : exposureProtections === 1 ? "partial" : "attention",
    },
  ];
}

function buildNextSteps(
  optionData: Option,
  diagnosticState: RemoteState<DiagnosticSummary>,
  searchState: RemoteState<SearchHealthSummary>,
): NextStep[] {
  const steps: NextStep[] = [];
  const loginProtected =
    isEnabled(optionData.login?.security?.login_code) ||
    Boolean(optionData.domestic?.login_security?.fail_limit_enabled);

  if (!loginProtected) {
    steps.push({
      id: "login-protection",
      title: "补齐登录防护",
      description: "启用验证码或失败次数限制，降低后台暴力破解风险。",
      view: "security",
      action: "前往安全设置",
    });
  }

  if (!optionData.optimize?.medium?.img_add_tag) {
    steps.push({
      id: "media-alt",
      title: "检查媒体基础设置",
      description: "确认图片替代文本策略，减少内容可访问性与 SEO 缺口。",
      view: "site",
      action: "检查站点与媒体",
    });
  }

  if (!optionData.function?.seo?.seo_single) {
    steps.push({
      id: "content-seo",
      title: "确认内容 SEO 策略",
      description: "按站点实际情况决定是否启用文章级 SEO，而不是套用预设方案。",
      view: "seo",
      action: "管理内容与 SEO",
    });
  }

  if (diagnosticState.status === "success" && diagnosticState.data.status !== "good") {
    steps.push({
      id: "diagnostics",
      title: "处理站点诊断项",
      description: `当前诊断为“${diagnosticStatusLabels[diagnosticState.data.status]}”，请逐项核对后再调整设置。`,
      view: "maintenance",
      action: "查看维护工具",
    });
  }

  if (
    searchState.status === "success" &&
    (searchState.data.no_result_terms.length > 0 || searchState.data.suspicious_terms.length > 0)
  ) {
    steps.push({
      id: "search-health",
      title: "处理站内搜索问题",
      description: "存在无结果或可疑搜索词，建议检查内容覆盖与搜索限制。",
      view: "content",
      action: "查看内容工具",
    });
  }

  if (steps.length < 2) {
    steps.push({
      id: "china-services",
      title: "核对国内访问与合规",
      description: "按实际业务检查备案、Cookie、百度推送和微信能力，不必全部开启。",
      view: "china",
      action: "查看国内生态",
    });
  }

  if (steps.length < 2) {
    steps.push({
      id: "maintenance-review",
      title: "定期检查维护状态",
      description: "查看数据库、媒体与站点服务状态，只执行已经确认影响范围的维护任务。",
      view: "maintenance",
      action: "检查维护状态",
    });
  }

  return steps.slice(0, 4);
}

function StateIcon({ name }: { name: "loading" | "success" | "empty" | "error" }) {
  const icon = {
    loading: "dashicons-update",
    success: "dashicons-yes-alt",
    empty: "dashicons-info-outline",
    error: "dashicons-warning",
  }[name];

  return <span className={`dashicons ${icon} mabox-overview__state-icon`} aria-hidden="true" />;
}

const Dashboard: React.FC<DashboardProps> = ({ onNavigate }) => {
  const { optionData } = useContext(DataContext);
  const [diagnosticState, setDiagnosticState] = useState<RemoteState<DiagnosticSummary>>({
    status: "loading",
    data: null,
  });
  const [searchState, setSearchState] = useState<RemoteState<SearchHealthSummary>>({
    status: "loading",
    data: null,
  });

  const loadDiagnostics = useCallback(async () => {
    setDiagnosticState({ status: "loading", data: null });
    try {
      const response = await diagnosticsApi.getSummary();
      if (!response?.success) {
        setDiagnosticState({ status: "error", data: null });
        return;
      }
      if (
        !response.data ||
        typeof response.data.score !== "number" ||
        !["good", "warning", "critical"].includes(response.data.status) ||
        !Array.isArray(response.data.items) ||
        !Array.isArray(response.data.risks)
      ) {
        setDiagnosticState({ status: "empty", data: null });
        return;
      }
      setDiagnosticState({ status: "success", data: response.data });
    } catch {
      setDiagnosticState({ status: "error", data: null });
    }
  }, []);

  const loadSearchHealth = useCallback(async () => {
    setSearchState({ status: "loading", data: null });
    try {
      const response = await searchHealthApi.getSummary(30);
      if (!response?.success) {
        setSearchState({ status: "error", data: null });
        return;
      }
      if (
        !response.data ||
        typeof response.data.total_searches !== "number" ||
        typeof response.data.unique_terms !== "number" ||
        !Array.isArray(response.data.no_result_terms) ||
        !Array.isArray(response.data.suspicious_terms)
      ) {
        setSearchState({ status: "empty", data: null });
        return;
      }
      if (response.data.total_searches === 0) {
        setSearchState({ status: "empty", data: null });
        return;
      }
      setSearchState({ status: "success", data: response.data });
    } catch {
      setSearchState({ status: "error", data: null });
    }
  }, []);

  useEffect(() => {
    void loadDiagnostics();
    void loadSearchHealth();
  }, [loadDiagnostics, loadSearchHealth]);

  const stats = useMemo(() => countBooleanToggles(optionData), [optionData]);
  const securityChecks = useMemo(() => getSecurityChecks(optionData), [optionData]);
  const nextSteps = useMemo(
    () => buildNextSteps(optionData, diagnosticState, searchState),
    [diagnosticState, optionData, searchState],
  );
  const securityReady = securityChecks.filter((item) => item.status === "good").length;

  const navigate = (view: OverviewView) => {
    onNavigate?.(view);
  };

  return (
    <div className="mabox-overview">
      <header className="mabox-overview__intro">
        <div>
          <p className="mabox-overview__eyebrow">站点概览</p>
          <h2>先处理重要事项，再进入具体设置</h2>
          <p>
            这里展示当前配置和站点服务的真实状态。所有调整仍需进入对应页面确认并保存。
          </p>
        </div>
        <button className="mabox-overview__quiet-button" type="button" onClick={() => navigate("maintenance")}>
          <span className="dashicons dashicons-admin-tools" aria-hidden="true" />
          打开维护工具
        </button>
      </header>

      <section className="mabox-overview__summary" aria-label="配置摘要">
        <article className="mabox-overview__metric">
          <span className="mabox-overview__metric-label">已启用配置</span>
          <strong>{stats.enabled}</strong>
          <span>共 {stats.total} 个布尔开关</span>
        </article>
        <article className="mabox-overview__metric">
          <span className="mabox-overview__metric-label">安全检查</span>
          <strong>{securityReady} / {securityChecks.length}</strong>
          <span>{securityReady === securityChecks.length ? "基础防护完整" : "仍有项目需要确认"}</span>
        </article>
        <article className="mabox-overview__metric">
          <span className="mabox-overview__metric-label">下一步</span>
          <strong>{nextSteps.length}</strong>
          <span>按当前状态生成的建议</span>
        </article>
      </section>

      <div className="mabox-overview__status-grid">
        <section className="mabox-overview__panel" aria-labelledby="diagnostic-heading">
          <div className="mabox-overview__panel-heading">
            <div>
              <p className="mabox-overview__eyebrow">站点服务</p>
              <h3 id="diagnostic-heading">站点诊断</h3>
            </div>
            {diagnosticState.status === "success" && (
              <span className={`mabox-overview__badge mabox-overview__badge--${diagnosticState.data.status}`}>
                {diagnosticStatusLabels[diagnosticState.data.status]}
              </span>
            )}
          </div>

          {diagnosticState.status === "loading" && (
            <div className="mabox-overview__state" role="status">
              <StateIcon name="loading" />
              <div><strong>正在读取站点诊断</strong><span>请稍候，当前不会显示估算分数。</span></div>
            </div>
          )}
          {diagnosticState.status === "error" && (
            <div className="mabox-overview__state mabox-overview__state--error" role="alert">
              <StateIcon name="error" />
              <div><strong>站点诊断暂时不可用</strong><span>请求失败，当前没有可展示的诊断分数。</span></div>
              <button type="button" onClick={() => void loadDiagnostics()}>重新获取</button>
            </div>
          )}
          {diagnosticState.status === "empty" && (
            <div className="mabox-overview__state">
              <StateIcon name="empty" />
              <div><strong>暂无诊断数据</strong><span>服务已响应，但没有返回有效诊断结果。</span></div>
              <button type="button" onClick={() => void loadDiagnostics()}>重新检查</button>
            </div>
          )}
          {diagnosticState.status === "success" && (
            <div className="mabox-overview__diagnostic-result">
              <div className="mabox-overview__score" aria-label={`站点诊断得分 ${diagnosticState.data.score} 分`}>
                <strong>{diagnosticState.data.score}</strong><span>/ 100</span>
              </div>
              <div>
                <p>{diagnosticState.data.items.length} 个检查项，{diagnosticState.data.risks.length} 个风险提示。</p>
                {diagnosticState.data.generated_at && <span>生成于 {diagnosticState.data.generated_at}</span>}
              </div>
            </div>
          )}
        </section>

        <section className="mabox-overview__panel" aria-labelledby="search-heading">
          <div className="mabox-overview__panel-heading">
            <div>
              <p className="mabox-overview__eyebrow">近 30 天</p>
              <h3 id="search-heading">搜索健康</h3>
            </div>
            {searchState.status === "success" && <span className="mabox-overview__badge">已有数据</span>}
          </div>

          {searchState.status === "loading" && (
            <div className="mabox-overview__state" role="status">
              <StateIcon name="loading" />
              <div><strong>正在汇总搜索数据</strong><span>搜索健康与站点诊断分别加载。</span></div>
            </div>
          )}
          {searchState.status === "error" && (
            <div className="mabox-overview__state mabox-overview__state--error" role="alert">
              <StateIcon name="error" />
              <div><strong>搜索健康暂时不可用</strong><span>诊断其他区域不受影响，可以单独重试。</span></div>
              <button type="button" onClick={() => void loadSearchHealth()}>重新获取</button>
            </div>
          )}
          {searchState.status === "empty" && (
            <div className="mabox-overview__state">
              <StateIcon name="empty" />
              <div><strong>暂无搜索数据</strong><span>近 30 天没有可汇总的站内搜索记录。</span></div>
              <button type="button" onClick={() => navigate("content")}>检查搜索设置</button>
            </div>
          )}
          {searchState.status === "success" && (
            <dl className="mabox-overview__search-metrics">
              <div><dt>总搜索量</dt><dd>{searchState.data.total_searches}</dd></div>
              <div><dt>关键词</dt><dd>{searchState.data.unique_terms}</dd></div>
              <div><dt>无结果词</dt><dd>{searchState.data.no_result_terms.length}</dd></div>
            </dl>
          )}
        </section>
      </div>

      <div className="mabox-overview__work-grid">
        <section className="mabox-overview__panel" aria-labelledby="next-steps-heading">
          <div className="mabox-overview__panel-heading">
            <div>
              <p className="mabox-overview__eyebrow">建议操作</p>
              <h3 id="next-steps-heading">接下来可以做什么</h3>
            </div>
          </div>
          <ol className="mabox-overview__steps">
            {nextSteps.map((step, index) => (
              <li key={step.id}>
                <span className="mabox-overview__step-index" aria-hidden="true">{index + 1}</span>
                <div><strong>{step.title}</strong><p>{step.description}</p></div>
                <button type="button" onClick={() => navigate(step.view)}>
                  {step.action}<span className="dashicons dashicons-arrow-right-alt2" aria-hidden="true" />
                </button>
              </li>
            ))}
          </ol>
        </section>

        <section className="mabox-overview__panel" aria-labelledby="security-heading">
          <div className="mabox-overview__panel-heading">
            <div>
              <p className="mabox-overview__eyebrow">基础防护</p>
              <h3 id="security-heading">安全状态</h3>
            </div>
          </div>
          <ul className="mabox-overview__security-list">
            {securityChecks.map((check) => (
              <li key={check.label}>
                <span className={`mabox-overview__status-dot mabox-overview__status-dot--${check.status}`} aria-hidden="true" />
                <div><strong>{check.label}</strong><span>{check.detail}</span></div>
                <span className="screen-reader-text">
                  {check.status === "good" ? "状态良好" : check.status === "partial" ? "部分配置" : "需要关注"}
                </span>
              </li>
            ))}
          </ul>
          <button className="mabox-overview__full-button" type="button" onClick={() => navigate("security")}>
            管理安全设置
          </button>
        </section>
      </div>
    </div>
  );
};

export default Dashboard;
