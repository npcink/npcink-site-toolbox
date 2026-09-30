import { restInstance } from "@/axios/public";
import { __, sprintf } from "@/tool/i18n";
import {
  SECRET_PATHS,
  SecretStatus,
  SettingsResponse,
} from "@/tool/interface";
import { assertValidOption } from "@/tool/option";

export const emptySecretStatus = (): SecretStatus => ({
  "domestic.wechat.appsecret": { configured: false },
  "performance.oss.access_key": { configured: false },
  "performance.oss.secret_key": { configured: false },
});

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

export function parseSettingsResponse(value: unknown): SettingsResponse {
  if (!isRecord(value) || value.success !== true || !isRecord(value.data)) {
    throw new Error(__("设置接口返回格式无效"));
  }

  if (!isRecord(value.secretStatus)) {
    throw new Error(__("设置接口缺少凭据状态"));
  }
  if (typeof value.revision !== "string" || !/^[a-f0-9]{64}$/.test(value.revision)) {
    throw new Error(__("设置接口缺少有效配置版本"));
  }

  assertValidOption(value.data);

  const secretStatus = emptySecretStatus();
  for (const path of SECRET_PATHS) {
    const entry = value.secretStatus[path];
    if (!isRecord(entry) || typeof entry.configured !== "boolean") {
      throw new Error(sprintf(__("设置接口的凭据状态无效：%s"), path));
    }
    secretStatus[path] = { configured: entry.configured };
  }

  return {
    success: true,
    data: value.data,
    secretStatus,
    revision: value.revision,
  };
}

export const fetchSettings = async (): Promise<SettingsResponse> => {
  const response: unknown = await restInstance.get("/settings", { maboxNotify: false });
  return parseSettingsResponse(response);
};
