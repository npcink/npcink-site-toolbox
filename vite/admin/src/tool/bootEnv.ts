import axios from "axios";

import { defaultVarData } from "@/tool/defaultVar";
import { DataLocal } from "@/tool/interface";

const state: boolean = import.meta.env.VITE_STATE;

function getDataLocal(): DataLocal {
  if (state) {
    axios.defaults.baseURL = "/api";
    return defaultVarData;
  }

  return window.npcinkSiteToolboxData || defaultVarData;
}

function getAjaxurl(): string {
  if (state) return "/wp-admin/admin-ajax.php";
  return window.npcinkSiteToolboxData?.ajaxurl || "/wp-admin/admin-ajax.php";
}

function getApiBase(): string {
  if (state) return "/api";
  return window.npcinkSiteToolboxData?.apiBase || "/wp-json/npcink-site-toolbox/v1";
}

function getRestNonce(): string {
  if (state) return "";
  return window.npcinkSiteToolboxData?.restNonce || "";
}

const dataObject = getDataLocal();

export const url_site = dataObject.url_site;
export const Ajaxurl = getAjaxurl();
export const ApiBase = getApiBase();
export const RestNonce = getRestNonce();
