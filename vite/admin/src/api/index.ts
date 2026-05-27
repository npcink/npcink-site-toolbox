/**
 * 统一 API 服务层
 *
 * 封装所有 REST API 调用，替代分散在各组件中的 fetch/axios。
 * 统一处理：nonce、错误提示、响应格式化。
 */

import { restInstance, ApiResponse } from "@/axios/public";
import { DiagnosticSummary, SearchHealthSummary } from "@/tool/interface";

// ========== AI 审核 ==========
export const aiReviewApi = {
  getLogs: (page = 1, perPage = 20): Promise<ApiResponse<{ items: any[]; total: number; page: number; per_page: number }>> =>
    restInstance.get(`/ai-review/logs?page=${page}&per_page=${perPage}`) as Promise<any>,
  reviewItem: (index: number, action: string): Promise<ApiResponse> =>
    restInstance.post(`/ai-review/review/${index}`, { action }) as Promise<any>,
  clearLogs: (): Promise<ApiResponse> =>
    restInstance.post("/ai-review/clear-logs") as Promise<any>,
  testProvider: (provider: string, config: any): Promise<ApiResponse<{ provider: string; result: any; test_text: string }>> =>
    restInstance.post("/ai-review/test", { provider, config }) as Promise<any>,
};

// ========== 反馈与洞察 ==========
export const feedbackApi = {
  submit: (data: any): Promise<ApiResponse> =>
    restInstance.post("/feedback/submit", data) as Promise<any>,
  getInsights: (): Promise<ApiResponse<any>> =>
    restInstance.get("/feedback/insights") as Promise<any>,
};

// ========== 性能优化 ==========
export const performanceApi = {
  getDbStats: () => restInstance.get("/performance/db/stats"),
  cleanDb: (type: string, dryRun = true) =>
    restInstance.post("/performance/db/clean", { type, dry_run: dryRun }),
  checkSeo: (postId?: number) =>
    restInstance.post("/performance/seo/check", { post_id: postId }),
  fixSeoAlt: (postId?: number) =>
    restInstance.post("/performance/seo/fix-alt", { post_id: postId }),
  checkMedia: (postId?: number) =>
    restInstance.post("/performance/media/check", { post_id: postId }),
  fixMediaAlt: (postId?: number) =>
    restInstance.post("/performance/media/fix-alt", { post_id: postId }),
};

// ========== 国内生态 ==========
export const domesticApi = {
  baiduPush: (urls?: string[], offset?: number) =>
    restInstance.post("/domestic/baidu/push", { urls, offset }),
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
  get: () => restInstance.get("/settings"),
  save: (data: any) => restInstance.post("/settings", data),
  getSchema: () => restInstance.get("/settings/schema"),
  export: () => restInstance.get("/settings/export"),
  import: (file: File) => {
    const formData = new FormData();
    formData.append("file", file);
    return restInstance.post("/settings/import", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
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
