import { createContext } from "react";
import { DataLocal, Option } from "@/tool/interface";
import { defaultVarData } from "@/tool/defaultVar";
import { restInstance } from "@/axios/public";
import axios from "axios";

const state: boolean = import.meta.env.VITE_STATE;

function getDataLocal(): DataLocal {
  if (state) {
    axios.defaults.baseURL = "/api";
    return defaultVarData as DataLocal;
  } else {
    return window.dataLocal ? (window.dataLocal as unknown as DataLocal) : (defaultVarData as DataLocal);
  }
}

function getAjaxurl(): string {
  if (state) {
    return "/wp-admin/admin-ajax.php";
  } else {
    return window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php";
  }
}

function getApiBase(): string {
  if (state) {
    return "/api";
  } else {
    const dl = window.dataLocal as any;
    return dl?.apiBase || "/wp-json/mabox/v1";
  }
}

function getRestNonce(): string {
  if (state) {
    return "";
  }
  const dl = window.dataLocal as any;
  return dl?.restNonce || "";
}

const dataObject: DataLocal = getDataLocal();

export const defaultOption = dataObject?.option;
export const url_site = dataObject?.url_site;
export const serverDefaults = dataObject?.defaults || null;

export const Ajaxurl = getAjaxurl();
export const ApiBase = getApiBase();
export const RestNonce = getRestNonce();

export const fetchSettings = async (): Promise<Option> => {
  try {
    const response: any = await restInstance.get("/settings");
    if (response?.success && response?.data) {
      return response.data as Option;
    }
  } catch (error) {
    console.error("从 REST API 拉取配置失败，使用本地注入数据:", error);
  }
  return defaultOption;
};

interface OptionContextType {
  optionData: Option;
  updateOption: (father: string, son: string, newValue: unknown) => void;
  refreshOption: () => Promise<void>;
  lastSavedOption: Option;
  setLastSavedOption: (data: Option) => void;
}

export const DataContext = createContext<OptionContextType>({
  optionData: defaultOption,
  updateOption: () => {},
  refreshOption: async () => {},
  lastSavedOption: defaultOption,
  setLastSavedOption: () => {},
});
