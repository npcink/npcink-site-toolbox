import type { DataLocal } from "./tool/interface";

declare global {
  interface Window {
    dataLocal?: DataLocal;
  }
}

export {};
