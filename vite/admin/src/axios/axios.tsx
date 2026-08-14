import { restInstance } from "@/axios/public";
import { __ } from "@/tool/i18n";

export interface CategoryOption {
  label: string;
  value: number;
}

export interface CategoryData {
  categorys: CategoryOption[];
  tags: CategoryOption[];
  pages: CategoryOption[];
}

interface CategoryDataResponse {
  success: boolean;
  data?: unknown;
}

function isCategoryOption(value: unknown): value is CategoryOption {
  if (typeof value !== "object" || value === null) return false;

  const option = value as Record<string, unknown>;
  return typeof option.label === "string" && typeof option.value === "number";
}

function isCategoryData(value: unknown): value is CategoryData {
  if (typeof value !== "object" || value === null) return false;

  const data = value as Record<string, unknown>;
  return [data.categorys, data.tags, data.pages].every(
    (options) => Array.isArray(options) && options.every(isCategoryOption),
  );
}

export const getCategoryData = async (): Promise<CategoryData> => {
  const response = await restInstance.get<CategoryDataResponse, CategoryDataResponse>(
    "/tools/categories",
  );

  if (response.success !== true || !isCategoryData(response.data)) {
    throw new Error(__("分类数据格式无效"));
  }

  return response.data;
};
