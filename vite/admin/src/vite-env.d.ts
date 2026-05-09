/// <reference types="vite/client" />

interface DataLocal {
  option?: Record<string, unknown>;
  ajaxurl?: string;
  nonce?: string;
  countData?: Record<string, unknown>;
  single_arr?: unknown[];
  cat_arr?: unknown[];
  [key: string]: unknown;
}

interface Navigator {
  msSaveBlob?: (blob: Blob, filename: string) => void;
}

interface Window {
  dataLocal?: DataLocal;
  __wxjs_environment?: string;
}
