import { ConfigDiffItem, SecretChanges, SecretPath, SecretStatus } from "@/tool/interface";

const SECRET_LABELS: Record<SecretPath, string> = {
  "domestic.wechat.appsecret": "微信 AppSecret",
  "performance.oss.access_key": "对象存储 Access Key",
  "performance.oss.secret_key": "对象存储 Secret Key",
};

export function diffSecretChanges(
  status: SecretStatus,
  changes: SecretChanges,
): ConfigDiffItem[] {
  return (Object.keys(changes) as SecretPath[]).flatMap((path) => {
    const change = changes[path];
    if (!change) return [];

    return [{
      path,
      label: SECRET_LABELS[path],
      module: path.split(".")[0],
      before: status[path].configured ? "已配置" : "未配置",
      after: change.operation === "replace" ? "将替换" : "将清除",
      riskLevel: "none" as const,
    }];
  });
}

/**
 * 高风险功能路径映射
 * key: path 格式（点分隔），与 riskyFeature.tsx 中的 featureId 对应
 */
const RISKY_PATHS: Record<string, { label: string; title: string }> = {
  "optimize.medium.no_auto_size": { label: "禁止缩略图", title: "禁止缩略图" },
};

/**
 * 判断值是否为"开启"状态
 */
function isEnabled(value: unknown): boolean {
  if (typeof value === "boolean") return value;
  if (typeof value === "string") return value !== "false" && value !== "";
  if (typeof value === "number") return value > 0;
  return !!value;
}

/**
 * 判断路径是否对应高风险功能
 */
function isRiskyPath(path: string): boolean {
  return !!RISKY_PATHS[path];
}

/**
 * 获取路径的人类可读标签
 */
function getPathLabel(path: string): string {
  return RISKY_PATHS[path]?.label || path;
}

/**
 * 递归比较两个配置对象，生成差异列表
 *
 * @param before 基准配置（通常是最近一次服务端配置）
 * @param after 当前配置（用户修改后的 optionData）
 * @returns ConfigDiffItem[]
 */
export function diffConfig(before: any, after: any): ConfigDiffItem[] {
  const diffs: ConfigDiffItem[] = [];

  function traverse(
    currentBefore: any,
    currentAfter: any,
    pathParts: string[],
    moduleRoot: string
  ) {
    // 字段被删除：after 为 undefined/null，但 before 有值
    if (currentAfter === undefined && currentBefore !== undefined) {
      const path = pathParts.join(".");
      diffs.push({
        path,
        label: getPathLabel(path),
        module: moduleRoot,
        before: currentBefore,
        after: currentAfter,
        riskLevel: "none",
      });
      return;
    }

    if (currentAfter === null || currentAfter === undefined) {
      return;
    }

    // 如果 after 是基本类型（非对象），直接比较
    if (typeof currentAfter !== "object" || Array.isArray(currentAfter)) {
      const beforeVal = currentBefore;
      const afterVal = currentAfter;

      if (!valuesEqual(beforeVal, afterVal)) {
        const path = pathParts.join(".");
        const isRisky = isRiskyPath(path);
        const wasEnabled = isEnabled(beforeVal);
        const nowEnabled = isEnabled(afterVal);

        let riskLevel: ConfigDiffItem["riskLevel"] = "none";
        if (isRisky && !wasEnabled && nowEnabled) {
          riskLevel = "high";
        }

        diffs.push({
          path,
          label: getPathLabel(path),
          module: moduleRoot,
          before: beforeVal,
          after: afterVal,
          riskLevel,
        });
      }
      return;
    }

    // after 是对象，遍历其键
    const keys = new Set([
      ...Object.keys(currentAfter || {}),
      ...(typeof currentBefore === "object" && currentBefore !== null && !Array.isArray(currentBefore)
        ? Object.keys(currentBefore)
        : []),
    ]);

    keys.forEach((key) => {
      const nextBefore =
        typeof currentBefore === "object" && currentBefore !== null
          ? currentBefore[key]
          : undefined;
      const nextAfter = currentAfter[key];

      // 确定模块根（第一层）
      const nextModuleRoot = pathParts.length === 0 ? key : moduleRoot;

      traverse(nextBefore, nextAfter, [...pathParts, key], nextModuleRoot);
    });
  }

  traverse(before, after, [], "");

  // 排序：高风险在前，然后按模块分组
  diffs.sort((a, b) => {
    if (a.riskLevel === "high" && b.riskLevel !== "high") return -1;
    if (a.riskLevel !== "high" && b.riskLevel === "high") return 1;
    if (a.module !== b.module) return a.module.localeCompare(b.module);
    return a.path.localeCompare(b.path);
  });

  return diffs;
}

/**
 * 判断两个值是否相等（支持基本类型和简单数组）
 */
function valuesEqual(a: unknown, b: unknown): boolean {
  if (a === b) return true;
  if (typeof a !== typeof b) return false;

  if (typeof a === "object" && a !== null && b !== null) {
    if (Array.isArray(a) && Array.isArray(b)) {
      if (a.length !== b.length) return false;
      return a.every((val, idx) => valuesEqual(val, b[idx]));
    }
    // 对于深层对象，不在这里递归，由 traverse 处理
    return JSON.stringify(a) === JSON.stringify(b);
  }

  return false;
}

/**
 * 获取差异统计摘要
 */
export function getDiffSummary(diffs: ConfigDiffItem[]) {
  const highRiskCount = diffs.filter((d) => d.riskLevel === "high").length;
  const totalCount = diffs.length;
  const modulesChanged = Array.from(new Set(diffs.map((d) => d.module)));

  return {
    totalCount,
    highRiskCount,
    modulesChanged,
    hasChanges: totalCount > 0,
    requiresConfirmation: highRiskCount > 0,
  };
}

/**
 * 判断两个配置是否有差异
 */
export function hasConfigChanged(before: any, after: any): boolean {
  return diffConfig(before, after).length > 0;
}
