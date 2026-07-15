import { ApiResponse, restInstance } from "@/axios/public";
import {
  Option,
  SECRET_PATHS,
  SecretChanges,
  SettingsSavePayload,
} from "@/tool/interface";
import { assertValidOption } from "@/tool/option";

function assertSecretChangesAreValid(secretChanges: SecretChanges): void {
  const allowedPaths = new Set<string>(SECRET_PATHS);

  for (const [path, change] of Object.entries(secretChanges)) {
    if (!allowedPaths.has(path) || !change) {
      throw new Error(`未知凭据路径：${path}`);
    }
    if (change.operation !== "replace" && change.operation !== "clear") {
      throw new Error(`未知凭据操作：${path}`);
    }
    if (change.operation === "replace") {
      if (typeof change.value !== "string" || change.value.trim() === "") {
        throw new Error(`凭据替换值不能为空：${path}`);
      }
      if (new TextEncoder().encode(change.value).length > 4096) {
        throw new Error(`凭据长度超出限制：${path}`);
      }
      const containsControlCharacter = Array.from(change.value).some((character) => {
        const codePoint = character.codePointAt(0) ?? 0;
        return codePoint <= 31 || codePoint === 127;
      });
      if (containsControlCharacter) {
        throw new Error(`凭据不得包含控制字符：${path}`);
      }
    }
  }
}

export const buildSettingsSavePayload = (
  settings: Option,
  secretChanges: SecretChanges,
): SettingsSavePayload => {
  assertValidOption(settings);
  assertSecretChangesAreValid(secretChanges);
  return { settings, secretChanges };
};

export const saveOption = async (settings: Option, secretChanges: SecretChanges) => {
  const payload = buildSettingsSavePayload(settings, secretChanges);

  const response = await restInstance.post<ApiResponse, ApiResponse>("/settings", payload);
  if (!response || response.success !== true) {
    throw new Error(response?.message || "设置接口拒绝保存");
  }
  return response;
};
