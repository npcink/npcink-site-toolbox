//准备初始数据
import { createContext } from "react";
import { DataLocal } from "@/tool/interface";
import option from "@/tool/defaultVar";
import axios from "axios";

//开发环境状态
const state: boolean = import.meta.env.VITE_STATE;

//组建开发环境下的对象

//输出选项值
function getDataLocal(): DataLocal {
  if (state) {
    axios.defaults.baseURL = "/api"; //开发模式下使用代理
    //开发
    return option;
  } else {
    //打包
    //return (window as any).dataLocal.option;
    return (window as any).dataLocal?.option !== ""
      ? (window as any).dataLocal?.option
      : {};
  }
}

//输出ajaxurl
function getAjaxurl(): string {
  if (state) {
    //开发
    return "/wp-admin/admin-ajax.php";
  } else {
    //打包
    return (window as any).ajaxurl;
  }
}

//传值
const dataObject: DataLocal = getDataLocal();
console.log(dataObject);
const DataContext = createContext(dataObject);

export const Ajaxurl = getAjaxurl();

export default DataContext;
