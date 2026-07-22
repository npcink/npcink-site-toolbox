import { SECRET_PATHS } from "@/generated/settings-types";
import type {
  DomesticCommentSecurity,
  DomesticCompliance,
  DomesticLoginSecurity,
  DomesticWechat,
  FunctionAuxiliary,
  FunctionSeo,
  OptimizeAdmin,
  OptimizeMedium,
  OptimizeSite,
  Option,
  PageComment,
  PageFeature,
  PageFunction,
  PageJurisdiction,
  PerformanceDbClean,
  PerformanceMediaHealth,
  PerformanceOss,
  PerformanceSearchEnhance,
  PerformanceSeoChecker,
  SecretPath,
} from "@/generated/settings-types";

export { SECRET_PATHS };
export type {
  DomesticCommentSecurity,
  DomesticCompliance,
  DomesticLoginSecurity,
  DomesticWechat,
  FunctionAuxiliary,
  FunctionSeo,
  OptimizeAdmin,
  OptimizeMedium,
  OptimizeSite,
  Option,
  PageComment,
  PageFeature,
  PageFunction,
  PageJurisdiction,
  PerformanceDbClean,
  PerformanceMediaHealth,
  PerformanceOss,
  PerformanceSearchEnhance,
  PerformanceSeoChecker,
  SecretPath,
};

//准备对象类型

//准备类型
export type DataLocal = {
  url_site: string;
  ajaxurl?: string;
  connectorsUrl?: string;
  nonce?: string;
  apiBase?: string;
  restNonce?: string;
  webpSupported?: boolean;
};

export interface SecretStatusEntry {
  configured: boolean;
}

export type SecretStatus = Record<SecretPath, SecretStatusEntry>;

export type SecretChange =
  | { operation: "replace"; value: string }
  | { operation: "clear" };

export type SecretChanges = Partial<Record<SecretPath, SecretChange>>;

export interface SettingsResponse {
  success: boolean;
  data: Option;
  secretStatus: SecretStatus;
}

export interface SettingsSavePayload {
  settings: Option;
  secretChanges: SecretChanges;
}

/**
 * Axios 返回类型
 */
export interface axiosType {
  success: boolean; //状态
  data: {
    data?: any; //返回值
    message?: string; //成功信息
    error?: string; //失败信息
  };
}

/**
 * 诊断相关类型
 * @since 2.5.0
 */
export interface DiagnosticItem {
  id: string;
  title: string;
  status: "good" | "warning" | "critical";
  message: string;
}

export interface DiagnosticModuleRisk {
  module_id: string;
  tier: "high_risk" | "experimental";
  title: string;
  message: string;
}

export interface ConfigDiffItem {
  path: string;
  label: string;
  module: string;
  before: any;
  after: any;
  riskLevel: "none" | "low" | "high";
}

export interface DiagnosticSummary {
  status: "good" | "warning" | "critical";
  items: DiagnosticItem[];
  module_risks: DiagnosticModuleRisk[];
  generated_at: string;
}

export interface RuntimeFeatureModule {
  id: string;
  label: string;
  category: string;
  category_label: string;
  view: "site" | "content" | "seo" | "china" | "maintenance" | "";
  target_id: string;
  scope: "frontend" | "admin" | "both";
  tier: "core" | "advanced" | "high_risk" | "experimental";
  always_loaded: boolean;
}

export interface RuntimeEditorTool {
  id: string;
  type: "pattern" | "block";
  title: string;
  description: string;
}

export interface RuntimeFeatureStatus {
  plugin: {
    name: string;
    version: string;
  };
  environment: {
    wordpress_version: string;
    php_version: string;
  };
  counts: {
    registered: number;
    active: number;
    always_loaded: number;
    editor_tools: number;
  };
  modules: RuntimeFeatureModule[];
  editor_tools: RuntimeEditorTool[];
  diagnostics: DiagnosticSummary;
  generated_at: string;
}

export interface DiagnosticPackFact {
  id: string;
  label: string;
  value: string;
}

export interface DiagnosticPackSection {
  id: string;
  title: string;
  facts: DiagnosticPackFact[];
}

export interface DiagnosticPack {
  contract_version: "diagnostic_pack.v1";
  scope: "manual_support";
  generated_at: string;
  sections: DiagnosticPackSection[];
  limitations: string[];
  privacy: {
    external_requests_performed: false;
    persisted: false;
    review_before_sharing: true;
  };
}

export interface DiagnosticAnalysis {
  contract_version: "diagnostic_analysis.v1";
  generated_at: string;
  source: {
    contract_version: "diagnostic_pack.v1";
    generated_at: string;
  };
  provider: {
    id: "deepseek";
    model: string;
  };
  analysis: string;
  follow_up_context?: AiFollowUpContext;
  privacy: {
    external_request_performed: true;
    persisted: false;
    automated_changes: false;
  };
}

export type AiReviewScope = "performance" | "maintenance" | "settings_risk";
export type AiReviewScenario = AiReviewScope | "verification";
export type AiFollowUpScenario = "troubleshooting" | AiReviewScenario;

export interface AiFollowUpSourcePack {
  contract_version: "diagnostic_pack.v1" | "site_review_pack.v1" | "site_review_comparison.v1";
  scope: string;
  generated_at: string;
  sections: DiagnosticPackSection[];
  limitations: string[];
  privacy: {
    external_requests_performed: false;
    persisted: false;
    review_before_sharing: true;
  };
}

export interface AiFollowUpContext {
  contract_version: "ai_follow_up_context.v1";
  scenario: AiFollowUpScenario;
  source_pack: AiFollowUpSourcePack;
}

export interface AiReviewPack {
  contract_version: "site_review_pack.v1";
  scope: AiReviewScope;
  generated_at: string;
  sections: DiagnosticPackSection[];
  limitations: string[];
  privacy: {
    external_requests_performed: false;
    persisted: false;
    review_before_sharing: true;
  };
}

export interface AiReview {
  contract_version: "ai_review.v1";
  scenario: AiReviewScenario;
  generated_at: string;
  source: {
    contract_version: "site_review_pack.v1" | "site_review_comparison.v1";
    generated_at: string;
    scope: string;
  };
  provider: {
    id: "deepseek";
    model: string;
  };
  analysis: string;
  follow_up_context?: AiFollowUpContext;
  privacy: {
    external_request_performed: true;
    persisted: false;
    automated_changes: false;
  };
}

export interface AiSettingsChange {
  path: string;
  before: unknown;
  after: unknown;
}

export interface AiReviewRequest {
  scenario: AiReviewScenario;
  problem?: string;
  changes?: AiSettingsChange[];
  baseline?: AiReviewPack;
}

export interface AiFollowUpTurn {
  question: string;
  answer: string;
}

export interface AiFollowUpRequest {
  scenario: AiFollowUpScenario;
  question: string;
  context: AiFollowUpContext;
  initial_analysis: string;
  turns: AiFollowUpTurn[];
}

export interface AiFollowUp {
  contract_version: "ai_follow_up.v1";
  scenario: AiFollowUpScenario;
  turn: 1 | 2 | 3;
  generated_at: string;
  source: {
    context_version: "ai_follow_up_context.v1";
    contract_version: AiFollowUpSourcePack["contract_version"];
    generated_at: string;
    scope: string;
  };
  provider: {
    id: "deepseek";
    model: string;
  };
  answer: string;
  privacy: {
    external_request_performed: true;
    persisted: false;
    automated_changes: false;
  };
}

export interface SearchHealthTerm {
  term: string;
  count: number;
  no_result_count: number;
}

export interface SearchHealthSuspicious {
  term: string;
  count: number;
  reason: string;
}

export interface SearchHealthRecommendation {
  id: string;
  title: string;
  reason: string;
}

export interface SearchHealthSummary {
  range_days: number;
  total_searches: number;
  unique_terms: number;
  top_terms: SearchHealthTerm[];
  no_result_terms: SearchHealthTerm[];
  suspicious_terms: SearchHealthSuspicious[];
  recommendations: SearchHealthRecommendation[];
}

export interface RiskInfo {
  level: "none" | "low" | "high";
  title?: string;
  warning?: string;
  suggestion?: string;
  noDismiss?: boolean;
}

export interface UiSchemaEntry {
  path: string;
  type: string;
  label?: string;
  group?: string;
  feature_id?: string;
  risk?: RiskInfo;
  depends_on?: string | string[];
  preset_tags?: string[];
  risk_tags?: string[];
}

export type UiSchemaMap = Record<string, UiSchemaEntry>;
