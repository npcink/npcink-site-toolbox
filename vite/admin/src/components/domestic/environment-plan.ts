import { OptimizeSite } from "@/tool/interface";

const PROPOSAL_FIELDS: Record<string, "boolean" | "string"> = {
  cdn_replace: "boolean",
  cdn_gravatar: "boolean",
  cdn_gravatar_mirror: "string",
  cdn_google_fonts: "boolean",
  cdn_google_fonts_mirror: "string",
  cdn_google_ajax: "boolean",
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

export function mergeEnvironmentProposal(
  current: OptimizeSite,
  proposed: unknown,
): OptimizeSite {
  if (!isRecord(proposed)) {
    throw new Error("环境修复建议格式无效");
  }

  Object.entries(proposed).forEach(([key, value]) => {
    const expectedType = PROPOSAL_FIELDS[key];
    if (!expectedType || typeof value !== expectedType) {
      throw new Error(`环境修复建议包含无效字段：${key}`);
    }
  });

  return { ...current, ...proposed };
}
