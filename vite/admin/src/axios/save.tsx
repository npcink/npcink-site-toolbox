import { ApiResponse, restInstance } from "@/axios/public";
import {
  Option,
  SECRET_PATHS,
  SecretChanges,
  SettingsSavePayload,
} from "@/tool/interface";
import { assertValidOption } from "@/tool/option";

const DEFAULT_SAVE_ERROR = "保存失败，请重试";
const MAX_NOTICE_MESSAGE_LENGTH = 500;

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

function extractResponseMessage(value: unknown): string | undefined {
  if (!isRecord(value)) return undefined;

  for (const key of ["message", "error"] as const) {
    const candidate = value[key];
    if (typeof candidate === "string" && candidate.trim() !== "") {
      return candidate;
    }
  }

  return extractResponseMessage(value.data);
}

function sanitizeResponseMessage(message: string, secretChanges: SecretChanges): string {
  let sanitized = message;
  for (const change of Object.values(secretChanges)) {
    if (change?.operation === "replace" && change.value !== "") {
      sanitized = sanitized.split(change.value).join("[已隐藏]");
    }
  }

  const withoutControlCharacters = Array.from(sanitized, (character) => {
    const codePoint = character.codePointAt(0) ?? 0;
    return codePoint <= 31 || codePoint === 127 ? " " : character;
  }).join("");

  return withoutControlCharacters.trim().slice(0, MAX_NOTICE_MESSAGE_LENGTH);
}

function normalizeSaveError(error: unknown, secretChanges: SecretChanges): Error {
  let message: string | undefined;

  if (isRecord(error) && isRecord(error.response)) {
    message = extractResponseMessage(error.response.data);
  }
  if (!message) {
    message = extractResponseMessage(error);
  }
  if (!message && error instanceof Error && error.message.trim() !== "") {
    message = error.message;
  }

  const sanitized = sanitizeResponseMessage(message || DEFAULT_SAVE_ERROR, secretChanges);
  return new Error(sanitized || DEFAULT_SAVE_ERROR);
}

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
  revision: string,
): SettingsSavePayload => {
  assertValidOption(settings);
  assertSecretChangesAreValid(secretChanges);
  if (!/^[a-f0-9]{64}$/.test(revision)) {
    throw new Error("配置版本无效，请重新读取设置");
  }
  return { settings, secretChanges, revision };
};

export const saveOption = async (settings: Option, secretChanges: SecretChanges, revision: string) => {
  const payload = buildSettingsSavePayload(settings, secretChanges, revision);

  let response: ApiResponse;
  try {
    response = await restInstance.post<ApiResponse, ApiResponse>(
      "/settings",
      payload,
      { maboxNotify: false },
    );
  } catch (error) {
    throw normalizeSaveError(error, secretChanges);
  }

  if (!response || response.success !== true) {
    throw normalizeSaveError(response, secretChanges);
  }

  const message = extractResponseMessage(response);
  return message
    ? { ...response, message: sanitizeResponseMessage(message, secretChanges) }
    : response;
};
