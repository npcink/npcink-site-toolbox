/**
 * 统一 API 服务层
 *
 * 封装所有 REST API 调用，替代分散在各组件中的 fetch/axios。
 * 统一处理：nonce、错误提示、响应格式化。
 */

import { restInstance, ApiResponse } from "@/axios/public";
import { DiagnosticSummary, SearchHealthSummary } from "@/tool/interface";

export type DbCleanType =
  | "revisions"
  | "drafts"
  | "spam"
  | "transients"
  | "optimize"
  | "all"
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
  total?: number;
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

export interface SeoIssue {
  type: string;
  message: string;
  severity?: string;
}

// ========== 性能优化 ==========
export const performanceApi = {
  getDbStats: (): Promise<ApiResponse<DbStats>> =>
    restInstance.get<ApiResponse<DbStats>, ApiResponse<DbStats>>("/performance/db/stats"),
  previewDb: (type: DbCleanType): Promise<ApiResponse<DbPreview>> =>
    restInstance.post<ApiResponse<DbPreview>, ApiResponse<DbPreview>>("/performance/db/preview", {
      type,
      dry_run: true,
    }),
  cleanDb: (type: DbCleanType, dryRun = true): Promise<ApiResponse<DbCleanResult>> =>
    restInstance.post<ApiResponse<DbCleanResult>, ApiResponse<DbCleanResult>>("/performance/db/clean", {
      type,
      dry_run: dryRun,
    }),
  checkSeo: (postId?: number): Promise<ApiResponse<{ issues: SeoIssue[]; total: number }>> =>
    restInstance.post<ApiResponse<{ issues: SeoIssue[]; total: number }>, ApiResponse<{ issues: SeoIssue[]; total: number }>>("/performance/seo/check", { post_id: postId }),
  fixSeoAlt: (postId?: number): Promise<ApiResponse<{ fixed: number }>> =>
    restInstance.post<ApiResponse<{ fixed: number }>, ApiResponse<{ fixed: number }>>("/performance/seo/fix-alt", { post_id: postId }),
  checkMedia: (postId?: number): Promise<ApiResponse<{ issues: MediaHealthIssue[] }>> =>
    restInstance.post<ApiResponse<{ issues: MediaHealthIssue[] }>, ApiResponse<{ issues: MediaHealthIssue[] }>>("/performance/media/check", { post_id: postId }),
  fixMediaAlt: (postId?: number): Promise<ApiResponse<{ fixed: number }>> =>
    restInstance.post<ApiResponse<{ fixed: number }>, ApiResponse<{ fixed: number }>>("/performance/media/fix-alt", { post_id: postId }),
};

// ========== 国内生态 ==========
export const domesticApi = {
  checkEnvironment: (): Promise<ApiResponse<Record<string, { service: string; reachable: boolean; latency: number; suggestion: string }>>> =>
    restInstance.get("/domestic/environment/check") as Promise<any>,
  applyEnvironmentFix: (fixes: string[]): Promise<ApiResponse<{ applied: string[]; new_config: any }>> =>
    restInstance.post("/domestic/environment/apply", { fixes }) as Promise<any>,
};

// ========== 工具 ==========
export const toolsApi = {
  getTables: () => restInstance.get("/tools/tables"),
  getTableData: (databaseName: string, limit = 1000, offset = 0) =>
    restInstance.post("/tools/table-data", { databaseName, limit, offset }),
  getCategories: () => restInstance.get("/tools/categories"),
};

// ========== 设置 ==========
export const settingsApi = {
  getSchema: () => restInstance.get("/settings/schema"),
};

// ========== 站点诊断 ==========
export const diagnosticsApi = {
  getSummary: (): Promise<ApiResponse<DiagnosticSummary>> =>
    restInstance.get("/diagnostics/summary") as Promise<any>,
};

// ========== 搜索健康 ==========
export const searchHealthApi = {
  getSummary: (days = 30): Promise<ApiResponse<SearchHealthSummary>> =>
    restInstance.get(`/search-health/summary?days=${days}`) as Promise<any>,
};

// ========== 批量替换 ==========
export const batchReplaceApi = {
  execute: (pairs: any[], dryRun = true) =>
    restInstance.post("/page/batch-replace", { pairs, dry_run: dryRun }),
  rollbackAll: () =>
    restInstance.post("/page/batch-replace/rollback", { confirm: true }),
  rollbackPost: (postId: number) =>
    restInstance.post(`/page/batch-replace/rollback/${postId}`),
};
