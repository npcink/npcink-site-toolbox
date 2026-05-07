//准备初始数据
import { createContext } from "react";
import { DataLocal, Option } from "@/tool/interface";
import { defaultVarData } from "@/tool/defaultVar";
import axios from "axios";

//开发环境状态
const state: boolean = import.meta.env.VITE_STATE;

//组建开发环境下的对象

//输出选项值
function getDataLocal(): DataLocal {
  if (state) {
    axios.defaults.baseURL = "/api"; //开发模式下使用代理
    //开发
    return defaultVarData;
  } else {
    //打包
    //return (window as any).dataLocal.option;
    return (window as any).dataLocal !== "" ? (window as any).dataLocal : defaultVarData;
  }
}

//输出ajaxurl
function getAjaxurl(): string {
  if (state) {
    //开发
    return "/wp-admin/admin-ajax.php";
  } else {
    //打包
    return (window as any).dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php";
  }
}

//拿到传来的值
const dataObject: DataLocal = getDataLocal();
//console.log("拿到的选项");
//console.log(dataObject);

//选项
export const defaultOption = dataObject?.option;
//站点地址
export const url_site = dataObject?.url_site;

//ajaxurl
export const Ajaxurl = getAjaxurl();

export const getNonce = (): string => {
  if (state) {
    return "";
  }
  return (window as any).dataLocal?.nonce || "";
};

//准备选项默认值
interface OptionContextType {
  optionData: Option; //选项默认值
  updateOption: (father: string, son: string, newValue: any) => void; // 修改选项方法
}

//组件间传递选项数据
export const DataContext = createContext<OptionContextType>({
  optionData: defaultOption, //选项值
  updateOption: () => {}, // 空函数作为默认值
});
