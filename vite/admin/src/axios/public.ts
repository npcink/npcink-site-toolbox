import axios from "axios";
import { message } from "antd";
import { ApiBase, RestNonce } from "@/tool/dataContext";

/**
 * 统一 API 响应结构（restInstance 拦截器已解包为 response body）
 */
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
        message.success(responseData.data.message);
      }
    } else {
      const errMsg = responseData.data?.error || responseData.data?.message || '未知错误';
      message.error(errMsg);
    }
    return responseData;
  },
  (error) => {
    const errorMessage =
      error.response && error.response.status
        ? `出错： ${error.response.data?.data?.error || error.response.data?.data?.message || error.message}`
        : `出错：${error.message}`;
    message.error(errorMessage);
    console.error(errorMessage);
    return Promise.reject(error);
  }
);

restInstance.interceptors.response.use(
  (response) => {
    const responseData = response.data;
    if (responseData.success) {
      if (responseData.message) {
        message.success(responseData.message);
      }
    } else {
      // 适配标准化错误格式：{ code: 'xxx', message: '...' }
      const errData = responseData.data || responseData;
      const errMsg = errData?.message || errData?.error || responseData.message || '未知错误';
      message.error(errMsg);
    }
    return responseData;
  },
  (error) => {
    const errorData = error.response?.data;
    // 适配标准化错误格式
    const errBody = errorData?.data || errorData;
    const errMsg = errBody?.message || errBody?.error || errorData?.message || error.message;
    message.error(`出错：${errMsg}`);
    console.error(errMsg);
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
