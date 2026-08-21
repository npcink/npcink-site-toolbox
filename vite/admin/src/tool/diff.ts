import { ConfigDiffItem, SecretChanges, SecretPath, SecretStatus } from "@/tool/interface";
import { getFeatureLabelForPath, getFeatureRiskLevelForPath } from "@/tool/featureIndex";
import { __ } from "@/tool/i18n";

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
      label: __(SECRET_LABELS[path]),
      module: path.split(".")[0],
      before: status[path].configured ? __("已配置") : __("未配置"),
      after: change.operation === "replace" ? __("将替换") : __("将清除"),
      riskLevel: "none" as const,
    }];
  });
}

const PATH_LABELS: Record<string, string> = {
  "domestic.login_security.attempt_limit_enabled": "登录尝试保护",
  "domestic.login_security.attempt_limit_count": "失败尝试上限",
  "domestic.login_security.attempt_window_minutes": "统计窗口（分钟）",
  "domestic.login_security.lock_duration_minutes": "锁定时长（分钟）",
  "domestic.login_security.trusted_proxies": "可信代理 IP",
  "domestic.login_security.anonymous_author_guard_enabled": "限制匿名作者枚举",
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
 * 获取路径的人类可读标签
 */
function getPathLabel(path: string): string {
  const label = PATH_LABELS[path]
    || getFeatureLabelForPath(path)
    || "设置项";
  return __(label);
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
        const wasEnabled = isEnabled(beforeVal);
        const nowEnabled = isEnabled(afterVal);

        let riskLevel: ConfigDiffItem["riskLevel"] = "none";
        if (!wasEnabled && nowEnabled) {
          riskLevel = getFeatureRiskLevelForPath(path);
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
