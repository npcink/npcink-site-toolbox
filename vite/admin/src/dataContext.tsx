//存值
import { createContext } from "react";

//开发环境状态
const state: boolean = import.meta.env.VITE_STATE;

//组建开发环境下的对象
const option = {
  option: {
    name: import.meta.env.VITE_OPTION_NAME,
    age: parseInt(import.meta.env.VITE_OPTION_AGE),
    handle: import.meta.env.VITE_OPTION_HANDLE === "true",
  },
};
//准备的默认值
const dataObject = {
  option: {
    name: "Npcink",
    age: 18,
    handle: true,
  },
};
const DataContext = createContext(dataObject);

export default DataContext;
