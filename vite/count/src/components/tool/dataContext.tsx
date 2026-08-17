import { createContext } from "react";
import { Receive, DataLocal } from "./interface";
import option from "./defaultVar";

const state: boolean = import.meta.env.VITE_STATE;

function getDataLocal(): Receive {
  if (state) {
    return option;
  } else {
    const wl = window.npcinkSiteToolboxData;
    if (wl !== "" && typeof wl === "object") {
      return wl;
    }
    return { countData: { single: { count: [], today: { width: 0, height: 0, title: "", dataset: [] }, month: { width: 0, height: 0, title: "", dataset: [] } } } };
  }
}

const dataObject: DataLocal = getDataLocal()?.countData;

const DataContext = createContext(dataObject);

export default DataContext;
