import { createContext } from "react";
import axios from "axios";

import { restInstance } from "@/axios/public";
import { defaultVarData, defaultVarOption } from "@/tool/defaultVar";
import { assertValidOption } from "@/tool/option";
import {
  DataLocal,
  Option,
  SECRET_PATHS,
  SecretChange,
  SecretChanges,
  SecretPath,
  SecretStatus,
  SettingsResponse,
} from "@/tool/interface";

const state: boolean = import.meta.env.VITE_STATE;

function getDataLocal(): DataLocal {
  if (state) {
    axios.defaults.baseURL = "/api";
    return defaultVarData;
  }

  return window.dataLocal || defaultVarData;
}

function getAjaxurl(): string {
  if (state) return "/wp-admin/admin-ajax.php";
  return window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php";
}

function getApiBase(): string {
  if (state) return "/api";
  return window.dataLocal?.apiBase || "/wp-json/mabox/v1";
}

function getRestNonce(): string {
  if (state) return "";
  return window.dataLocal?.restNonce || "";
}

const dataObject = getDataLocal();

export const url_site = dataObject.url_site;
export const Ajaxurl = getAjaxurl();
export const ApiBase = getApiBase();
export const RestNonce = getRestNonce();

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
    throw new Error("设置接口返回格式无效");
  }

  if (!isRecord(value.secretStatus)) {
    throw new Error("设置接口缺少凭据状态");
  }

  assertValidOption(value.data);

  const secretStatus = emptySecretStatus();
  for (const path of SECRET_PATHS) {
    const entry = value.secretStatus[path];
    if (!isRecord(entry) || typeof entry.configured !== "boolean") {
      throw new Error(`设置接口的凭据状态无效：${path}`);
    }
    secretStatus[path] = { configured: entry.configured };
  }

  return {
    success: true,
    data: value.data,
    secretStatus,
  };
}

export const fetchSettings = async (): Promise<SettingsResponse> => {
  const response: unknown = await restInstance.get("/settings");
  return parseSettingsResponse(response);
};

export type SettingsLoadState = "loading" | "ready" | "error";

export interface OptionContextType {
  optionData: Option;
  updateOption: (father: string, son: string, newValue: unknown) => void;
  refreshOption: () => Promise<void>;
  lastSavedOption: Option;
  setLastSavedOption: (data: Option) => void;
  secretStatus: SecretStatus;
  secretChanges: SecretChanges;
  setSecretChange: (path: SecretPath, change?: SecretChange) => void;
  clearSecretChanges: () => void;
  settingsState: SettingsLoadState;
  settingsError: string | null;
}

export const DataContext = createContext<OptionContextType>({
  optionData: defaultVarOption,
  updateOption: () => {},
  refreshOption: async () => {},
  lastSavedOption: defaultVarOption,
  setLastSavedOption: () => {},
  secretStatus: emptySecretStatus(),
  secretChanges: {},
  setSecretChange: () => {},
  clearSecretChanges: () => {},
  settingsState: "loading",
  settingsError: null,
});
