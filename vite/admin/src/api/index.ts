/**
 * 统一 API 服务层
 *
 * 封装所有 REST API 调用，替代分散在各组件中的 fetch/axios。
 * 统一处理：nonce、错误提示、响应格式化。
 */

import { restInstance, ApiResponse } from "@/axios/public";
import {
  AiFollowUp,
  AiFollowUpRequest,
  AiReview,
  AiReviewPack,
  AiReviewRequest,
  AiReviewScope,
  DiagnosticAnalysis,
  DiagnosticPack,
  DiagnosticSummary,
  RuntimeFeatureStatus,
  SearchHealthSummary,
  SettingsSavePayload,
} from "@/tool/interface";

export type DbCleanType =
  | "revisions"
  | "drafts"
  | "spam"
  | "transients"
  | "optimize"
  | "pending"
  | "trash";

export interface DbStats {
  revisions: number;
  drafts: number;
  spam: number;
  transients: number;
  db_size: number | string;
}

export interface DbPreview {
  revisions?: number;
  drafts?: number;
  spam?: number;
  transients?: number;
  pending?: number;
  trash?: number;
  affected?: number;
  message?: string;
  dry_run?: boolean;
}

export interface DbCleanResult {
  deleted?: number;
  message?: string;
  dry_run: boolean;
}

export interface MediaHealthIssue {
  type: string;
  count: number;
  severity?: string;
}

export interface MediaFormatSummary {
  count: number;
  bytes: number;
}

export type MediaWebpRecommendation =
  | "unsupported"
  | "no_candidates"
  | "cleanup_failed"
  | "insufficient_sample"
  | "sample_failed"
  | "low_savings"
  | "consider_batch"
  | "below_scale";

export interface MediaWebpAssessment {
  supported: boolean;
  checked: number;
  sampled: boolean;
  missing_files: number;
  formats: Record<"jpeg" | "png" | "webp" | "other", MediaFormatSummary>;
  sample: {
    attempted: number;
    successful: number;
    errors: number;
    input_bytes: number;
    output_bytes: number;
    savings_bytes: number;
    savings_percent: number | null;
    temporary_files_cleaned: boolean;
    recommendation: MediaWebpRecommendation;
  };
  thresholds: {
    candidate_count: number;
    candidate_bytes: number;
    savings_percent: number;
  };
  batch: {
    candidate_ids: number[];
    restorable_ids: number[];
    batch_size: number;
    original_retained: boolean;
    restorable: boolean;
  };
}

export type MediaWebpBatchStatus = "converted" | "restored" | "skipped" | "failed";

export interface MediaWebpBatchResult {
  processed: number;
  converted?: number;
  restored?: number;
  skipped: number;
  failed: number;
  results: Array<{
    attachment_id: number;
    status: MediaWebpBatchStatus;
    message: string;
  }>;
  original_retained: boolean;
}

export interface MediaHealthResult {
  issues: MediaHealthIssue[];
  attachment_scan: {
    checked: number;
    total: number;
    sampled: boolean;
  };
  webp_assessment: MediaWebpAssessment;
}

export interface SeoIssue {
  type: string;
  message: string;
  severity?: string;
}

export interface OssConnectionResult {
  provider: "aliyun" | "tencent" | "qiniu";
  objectKey: string;
  latencyMs: number;
}

// ========== 性能优化 ==========
export const performanceApi = {
  testOssConnection: (payload: SettingsSavePayload): Promise<ApiResponse<OssConnectionResult>> =>
    restInstance.post<ApiResponse<OssConnectionResult>, ApiResponse<OssConnectionResult>>(
      "/performance/oss/test",
      payload,
      { maboxNotify: false },
    ),
  getDbStats: (): Promise<ApiResponse<DbStats>> =>
    restInstance.get<ApiResponse<DbStats>, ApiResponse<DbStats>>(
      "/performance/db/stats",
      { maboxNotify: false },
    ),
  previewDb: (type: DbCleanType): Promise<ApiResponse<DbPreview>> =>
    restInstance.post<ApiResponse<DbPreview>, ApiResponse<DbPreview>>("/performance/db/preview", {
      type,
      dry_run: true,
    }, { maboxNotify: false }),
  cleanDb: (type: DbCleanType, dryRun = true): Promise<ApiResponse<DbCleanResult>> =>
    restInstance.post<ApiResponse<DbCleanResult>, ApiResponse<DbCleanResult>>("/performance/db/clean", {
      type,
      dry_run: dryRun,
    }, { maboxNotify: false }),
  checkSeo: (postId?: number): Promise<ApiResponse<{ issues: SeoIssue[]; total: number }>> =>
    restInstance.post<ApiResponse<{ issues: SeoIssue[]; total: number }>, ApiResponse<{ issues: SeoIssue[]; total: number }>>(
      "/performance/seo/check",
      { post_id: postId },
      { maboxNotify: false },
    ),
  fixSeoAlt: (postId?: number): Promise<ApiResponse<{ fixed: number }>> =>
    restInstance.post<ApiResponse<{ fixed: number }>, ApiResponse<{ fixed: number }>>(
      "/performance/seo/fix-alt",
      { post_id: postId },
      { maboxNotify: false },
    ),
  checkMedia: (postId?: number): Promise<ApiResponse<MediaHealthResult>> =>
    restInstance.post<ApiResponse<MediaHealthResult>, ApiResponse<MediaHealthResult>>(
      "/performance/media/check",
      { post_id: postId },
      { maboxNotify: false },
    ),
  fixMediaAlt: (postId?: number): Promise<ApiResponse<{ fixed: number }>> =>
    restInstance.post<ApiResponse<{ fixed: number }>, ApiResponse<{ fixed: number }>>(
      "/performance/media/fix-alt",
      { post_id: postId },
      { maboxNotify: false },
    ),
  convertMediaWebp: (attachmentIds: number[]): Promise<ApiResponse<MediaWebpBatchResult>> =>
    restInstance.post<ApiResponse<MediaWebpBatchResult>, ApiResponse<MediaWebpBatchResult>>(
      "/performance/media/webp/convert",
      { attachment_ids: attachmentIds },
      { maboxNotify: false },
    ),
  restoreMediaWebp: (attachmentIds: number[]): Promise<ApiResponse<MediaWebpBatchResult>> =>
    restInstance.post<ApiResponse<MediaWebpBatchResult>, ApiResponse<MediaWebpBatchResult>>(
      "/performance/media/webp/restore",
      { attachment_ids: attachmentIds },
      { maboxNotify: false },
    ),
};

// ========== 国内生态 ==========
export const domesticApi = {
  checkEnvironment: (): Promise<ApiResponse<Record<string, { service: string; reachable: boolean; latency: number; suggestion: string }>>> =>
    restInstance.get("/domestic/environment/check", { maboxNotify: false }) as Promise<any>,
  applyEnvironmentFix: (fixes: string[]): Promise<ApiResponse<{ applied: string[]; new_config: any }>> =>
    restInstance.post(
      "/domestic/environment/apply",
      { fixes },
      { maboxNotify: false },
    ) as Promise<any>,
};

// ========== 设置 ==========
export const settingsApi = {
  getSchema: () => restInstance.get("/settings/schema", { maboxNotify: false }),
};

// ========== 站点诊断 ==========
export const diagnosticsApi = {
  getSummary: (): Promise<ApiResponse<DiagnosticSummary>> =>
    restInstance.get<ApiResponse<DiagnosticSummary>, ApiResponse<DiagnosticSummary>>(
      "/diagnostics/summary",
      { maboxNotify: false },
    ),
  getFeatureStatus: (): Promise<ApiResponse<RuntimeFeatureStatus>> =>
    restInstance.get<ApiResponse<RuntimeFeatureStatus>, ApiResponse<RuntimeFeatureStatus>>(
      "/diagnostics/features",
      { maboxNotify: false },
    ),
  getSupportReport: (): Promise<ApiResponse<DiagnosticPack>> =>
    restInstance.get<ApiResponse<DiagnosticPack>, ApiResponse<DiagnosticPack>>(
      "/diagnostics/support-report",
      { maboxNotify: false },
    ),
  analyzeSupportReport: (problem: string): Promise<ApiResponse<DiagnosticAnalysis>> =>
    restInstance.post<ApiResponse<DiagnosticAnalysis>, ApiResponse<DiagnosticAnalysis>>(
      "/diagnostics/analyses",
      { problem },
      { maboxNotify: false },
    ),
  getReviewPack: (scope: Exclude<AiReviewScope, "settings_risk">): Promise<ApiResponse<AiReviewPack>> =>
    restInstance.get<ApiResponse<AiReviewPack>, ApiResponse<AiReviewPack>>(
      `/diagnostics/review-packs?scope=${scope}`,
      { maboxNotify: false },
    ),
  createReview: (request: AiReviewRequest): Promise<ApiResponse<AiReview>> =>
    restInstance.post<ApiResponse<AiReview>, ApiResponse<AiReview>>(
      "/diagnostics/reviews",
      request,
      { maboxNotify: false },
    ),
  createFollowUp: (request: AiFollowUpRequest): Promise<ApiResponse<AiFollowUp>> =>
    restInstance.post<ApiResponse<AiFollowUp>, ApiResponse<AiFollowUp>>(
      "/diagnostics/follow-ups",
      request,
      { maboxNotify: false },
    ),
};

// ========== 搜索健康 ==========
export const searchHealthApi = {
  getSummary: (days = 30): Promise<ApiResponse<SearchHealthSummary>> =>
    restInstance.get(
      `/search-health/summary?days=${days}`,
      { maboxNotify: false },
    ) as Promise<any>,
};
