import axios from "axios";
import { notice } from "@/tool/notice";
import { __, sprintf } from "@/tool/i18n";

declare module "axios" {
  interface AxiosRequestConfig {
    maboxNotify?: boolean;
  }
}

function getApiBase(): string {
  const dl = (window as any).dataLocal;
  return dl?.apiBase || "/wp-json/npcink-site-toolbox/v1";
}

function getRestNonce(): string {
  const dl = (window as any).dataLocal;
  return dl?.restNonce || "";
}

const ApiBase = getApiBase();
const RestNonce = getRestNonce();

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T & Record<string, any>;
  message?: string;
  error?: string;
  [key: string]: any;
}

export const instance = axios.create({});

export const restInstance = axios.create({
  baseURL: ApiBase,
  headers: {
    "Content-Type": "application/json",
    "X-WP-Nonce": RestNonce,
  },
});

instance.interceptors.response.use(
  (response) => {
    const responseData = response.data;
    if (responseData.success) {
      if (responseData.data?.message) {
        notice.success(responseData.data.message);
      }
    } else {
      const errMsg = responseData.data?.error || responseData.data?.message || __('未知错误');
      notice.error(errMsg);
    }
    return responseData;
  },
  (error) => {
    const errorMessage =
      error.response && error.response.status
        ? sprintf(__("出错：%s"), error.response.data?.data?.error || error.response.data?.data?.message || error.message)
        : sprintf(__("出错：%s"), error.message);
    notice.error(errorMessage);
    console.error(errorMessage);
    return Promise.reject(error);
  }
);

restInstance.interceptors.response.use(
  (response) => {
    const responseData = response.data;
    if (responseData.success) {
      if (responseData.message && response.config.maboxNotify !== false) {
        notice.success(responseData.message);
      }
    } else if (response.config.maboxNotify !== false) {
      // 适配标准化错误格式：{ code: 'xxx', message: '...' }
      const errData = responseData.data || responseData;
      const errMsg = errData?.message || errData?.error || responseData.message || __('未知错误');
      notice.error(errMsg);
    }
    return responseData;
  },
  (error) => {
    const errorData = error.response?.data;
    // 适配标准化错误格式
    const errBody = errorData?.data || errorData;
    const errMsg = errBody?.message || errBody?.error || errorData?.message || error.message;
    if (error.config?.maboxNotify !== false) {
      notice.error(sprintf(__("出错：%s"), errMsg));
      console.error(errMsg);
    }
    return Promise.reject(error);
  }
);

export const addParamIfDefined = (
  params: URLSearchParams,
  key: string,
  value: any
) => {
  if (value !== undefined) {
    params.append(key, value);
  }
};
