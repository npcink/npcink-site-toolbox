import React, { useCallback, useContext, useEffect, useMemo, useState } from "react";

import { diagnosticsApi, searchHealthApi } from "@/api";
import { DataContext } from "@/tool/dataContext";
import type { DiagnosticSummary, Option, SearchHealthSummary } from "@/tool/interface";

import "./overview.css";

type OverviewView = "site" | "content" | "seo" | "china" | "maintenance";

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

interface DiagnosticCounts {
  total: number;
  good: number;
  warning: number;
  critical: number;
  moduleRisks: number;
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
  itemId?: string;
  action: string;
}

const diagnosticStatusLabels: Record<DiagnosticSummary["status"], string> = {
  good: "状态良好",
  warning: "需要关注",
  critical: "需要处理",
};

const diagnosticStatuses = ["good", "warning", "critical"] as const;
const moduleRiskTiers = ["high_risk", "experimental"] as const;

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function isDiagnosticItem(value: unknown): boolean {
  if (!isRecord(value)) return false;

  return (
    typeof value.id === "string" &&
    typeof value.title === "string" &&
    typeof value.message === "string" &&
    diagnosticStatuses.includes(value.status as DiagnosticSummary["status"])
  );
}

function isDiagnosticModuleRisk(value: unknown): boolean {
  if (!isRecord(value)) return false;

  return (
    typeof value.module_id === "string" &&
    typeof value.title === "string" &&
    typeof value.message === "string" &&
    moduleRiskTiers.includes(value.tier as "high_risk" | "experimental")
  );
}

function isDiagnosticSummary(value: unknown): value is DiagnosticSummary {
  if (!isRecord(value)) return false;

  return (
    diagnosticStatuses.includes(value.status as DiagnosticSummary["status"]) &&
    Array.isArray(value.items) &&
    value.items.length > 0 &&
    value.items.every(isDiagnosticItem) &&
    Array.isArray(value.module_risks) &&
    value.module_risks.every(isDiagnosticModuleRisk) &&
    typeof value.generated_at === "string" &&
    value.generated_at.trim() !== ""
  );
}

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

function getLoginSecurityEnabledCount(optionData: Option): number {
  const loginSecurity = optionData.domestic?.login_security;
  return [
    loginSecurity?.attempt_limit_enabled,
    loginSecurity?.anonymous_author_guard_enabled,
  ].filter(Boolean).length;
}

function getDiagnosticCounts(summary: DiagnosticSummary): DiagnosticCounts {
  return summary.items.reduce<DiagnosticCounts>(
    (counts, item) => ({
      ...counts,
      [item.status]: counts[item.status] + 1,
    }),
    {
      total: summary.items.length,
      good: 0,
      warning: 0,
      critical: 0,
      moduleRisks: summary.module_risks.length,
    },
  );
}

function getDiagnosticAttention(summary: DiagnosticSummary): string | null {
  const itemTitles = [
    ...summary.items.filter((item) => item.status === "critical"),
    ...summary.items.filter((item) => item.status === "warning"),
  ].map((item) => item.title);

  if (itemTitles.length > 0) {
    const displayedTitles = itemTitles.slice(0, 3).join("、");
    return `待核对：${displayedTitles}${itemTitles.length > 3 ? `等 ${itemTitles.length} 项` : ""}`;
  }

  if (summary.module_risks.length > 0) {
    const displayedTitles = summary.module_risks.slice(0, 3).map((risk) => risk.title).join("、");
    return `需评估模块：${displayedTitles}${summary.module_risks.length > 3 ? `等 ${summary.module_risks.length} 项` : ""}`;
  }

  return null;
}

function getSecurityChecks(optionData: Option): SecurityCheck[] {
  const loginSecurityEnabledCount = getLoginSecurityEnabledCount(optionData);

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
      label: "登录安全配置",
      detail: `已启用 ${loginSecurityEnabledCount}/2 项登录安全配置`,
      status: loginSecurityEnabledCount === 2 ? "good" : loginSecurityEnabledCount === 1 ? "partial" : "attention",
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
  searchState: RemoteState<SearchHealthSummary>,
): NextStep[] {
  const steps: NextStep[] = [];
  const attemptLimitEnabled = Boolean(optionData.domestic?.login_security?.attempt_limit_enabled);

  if (!attemptLimitEnabled) {
    steps.push({
      id: "login-protection",
      title: "启用登录尝试保护",
      description: "限制同一已存在账号与来源 IP 组合的连续失败尝试，并使用固定统计窗口和锁定时长。",
      view: "china",
      itemId: "domestic-login_security-attempt_limit_enabled",
      action: "前往国内生态",
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
      description: "按实际业务检查备案、Cookie、微信与评论安全能力，不必全部开启。",
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
      if (!isDiagnosticSummary(response.data)) {
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
  const loginSecurityEnabledCount = useMemo(() => getLoginSecurityEnabledCount(optionData), [optionData]);
  const diagnosticCounts = useMemo(
    () => diagnosticState.status === "success" ? getDiagnosticCounts(diagnosticState.data) : null,
    [diagnosticState],
  );
  const diagnosticAttention = useMemo(
    () => diagnosticState.status === "success" ? getDiagnosticAttention(diagnosticState.data) : null,
    [diagnosticState],
  );
  const nextSteps = useMemo(
    () => buildNextSteps(optionData, searchState),
    [optionData, searchState],
  );
  const navigate = (view: OverviewView, itemId?: string) => {
    if (itemId) {
      onNavigate?.(view, itemId);
      return;
    }
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
          打开存储与维护
        </button>
      </header>

      <section className="mabox-overview__summary" aria-label="配置摘要">
        <article className="mabox-overview__metric">
          <span className="mabox-overview__metric-label">已启用配置</span>
          <strong>{stats.enabled}</strong>
          <span>共 {stats.total} 个布尔开关</span>
        </article>
        <article className="mabox-overview__metric">
          <span className="mabox-overview__metric-label">登录安全配置</span>
          <strong>{loginSecurityEnabledCount} / 2</strong>
          <span>已启用 {loginSecurityEnabledCount}/2 项</span>
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
              <div><strong>正在读取站点诊断</strong><span>请稍候，正在核对运行环境。</span></div>
            </div>
          )}
          {diagnosticState.status === "error" && (
            <div className="mabox-overview__state mabox-overview__state--error" role="alert">
              <StateIcon name="error" />
              <div><strong>站点诊断暂时不可用</strong><span>请求失败，当前没有可展示的检查结果。</span></div>
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
          {diagnosticState.status === "success" && diagnosticCounts && (
            <div className="mabox-overview__diagnostic-result">
              <div
                className="mabox-overview__diagnostic-count"
                aria-label={`站点诊断 ${diagnosticCounts.good} / ${diagnosticCounts.total} 项通过${diagnosticCounts.moduleRisks > 0 ? `，${diagnosticCounts.moduleRisks} 个模块风险` : ""}`}
              >
                <strong>{diagnosticCounts.good}</strong><span>/ {diagnosticCounts.total} 项通过</span>
              </div>
              <div className="mabox-overview__diagnostic-meta">
                <p>
                  {diagnosticCounts.warning} 项待关注，{diagnosticCounts.critical} 项需要处理；
                  {diagnosticCounts.moduleRisks > 0
                    ? `${diagnosticCounts.moduleRisks} 个高风险或实验性模块已启用。`
                    : "未启用高风险或实验性模块。"}
                </p>
                {diagnosticAttention && <span>{diagnosticAttention}</span>}
                <small>生成于 {diagnosticState.data.generated_at}</small>
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

      <section
        className="mabox-overview__panel mabox-overview__editor-tools"
        aria-labelledby="editor-tools-heading"
      >
        <div className="mabox-overview__panel-heading">
          <div>
            <p className="mabox-overview__eyebrow">内容编辑</p>
            <h3 id="editor-tools-heading">编辑器工具</h3>
            <p className="mabox-overview__editor-tools-summary">
              3 个可编辑样板和 2 个实时数据区块，只在文章或页面编辑器中使用。
            </p>
          </div>
          <span className="mabox-overview__badge">5 项可用</span>
        </div>

        <div className="mabox-overview__editor-tools-body">
          <dl className="mabox-overview__editor-tools-list">
            <div>
              <dt>样板</dt>
              <dd>资源下载、文章结论、来源与版权说明</dd>
            </div>
            <div>
              <dt>动态区块</dt>
              <dd>站点数据；GitHub 项目（描述、语言、Stars 与 Forks）</dd>
            </div>
          </dl>

          <div className="mabox-overview__editor-tools-actions">
            <p>在区块插入器中选择 Npcink Site Toolbox，或搜索“站点数据”“GitHub 项目”。</p>
            <div>
              <a
                className="button button-primary mabox-overview__editor-link"
                href="post-new.php"
                target="_blank"
                rel="noopener noreferrer"
              >
                新建文章使用
                <span className="dashicons dashicons-external" aria-hidden="true" />
              </a>
              <a
                className="button mabox-overview__editor-link"
                href="post-new.php?post_type=page"
                target="_blank"
                rel="noopener noreferrer"
              >
                新建页面
                <span className="dashicons dashicons-external" aria-hidden="true" />
              </a>
            </div>
          </div>
        </div>
      </section>

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
                <button type="button" onClick={() => navigate(step.view, step.itemId)}>
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
          <button
            className="mabox-overview__full-button"
            type="button"
            onClick={() => navigate("china", "domestic-login_security-attempt_limit_enabled")}
          >
            管理国内安全设置
          </button>
        </section>
      </div>
    </div>
  );
};

export default Dashboard;
