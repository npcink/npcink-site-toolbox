import type { DataLocal } from "./tool/interface";

declare global {
  interface Window {
    npcinkSiteToolboxData?: DataLocal;
  }
}

export {};
