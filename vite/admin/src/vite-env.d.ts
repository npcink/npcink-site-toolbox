/// <reference types="vite/client" />

interface DataLocal {
  url_site: string;
  ajaxurl?: string;
  nonce?: string;
  apiBase?: string;
  restNonce?: string;
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
