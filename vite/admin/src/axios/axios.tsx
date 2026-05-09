//各种请求
import { Ajaxurl, getNonce } from "@/tool/dataContext";
import { instance, addParamIfDefined } from "@/axios/public";
import { message } from "antd";

//获取所有数据库表名字
export const get_all_table_name = async () => {
  const params = new URLSearchParams();
  params.append("action", "get_all_table_names");
  params.append("nonce", getNonce());
  try {
    const response = await instance.post(Ajaxurl, params);
    //console.log(response.data.data);
    return response.data.data;
  } catch (error: any) {
    console.error("出错：" + error);
    message.error("获取数据库表名失败");
  }
};

//获取数据库表下载
function downloadCSV(csvString: string, filename: string) {
  const blob = new Blob([csvString], { type: "text/csv;charset=utf-8;" });

  if (navigator.msSaveBlob) {
    navigator.msSaveBlob(blob, filename);
  } else {
    const link = document.createElement("a");
    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob);
      link.setAttribute("href", url);
      link.setAttribute("download", filename);
      link.style.visibility = "hidden";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }
}

//获取表格数据
export const get_table_data = async (type: string) => {
  const params = new URLSearchParams();
  params.append("action", "get_table_data");
  params.append("nonce", getNonce());
  addParamIfDefined(params, "databaseName", type);
  try {
    const response = await instance.post(Ajaxurl, params);
    //保存成功
    downloadCSV(response.data.data, type + ".csv");
  } catch (error: any) {
    console.error("出错：" + error.message);
    message.error("获取表格数据失败");
  }
};

//获取分类数据（分类、标签、页面）
export const getCategoryData = async () => {
  const params = new URLSearchParams();
  params.append("action", "get_all_category_names");
  params.append("nonce", getNonce());
  try {
    const response = await instance.post(Ajaxurl, params);
    return response.data.data;
  } catch (error: any) {
    console.error("出错：" + error.message);
    message.error("获取分类数据失败");
  }
};



//替换列表
//const b: { [key: string]: string } = {
//  users: "用户",
//  usermeta: "用户元数据",
//  posts: "文章",
//  comments: "评论",
//  links: "友情链接",
//  options: "选项",
//  postmeta: "文章元数据",
//  terms: "目录、分类和标签",
//  term_taxonomy: "目录或标签对应的分类关系",
//  term_relationships: "文章或链接的分类关系",
//  termmeta: "分类的元数据",
//  commentmeta: "评论元数据12",
//  zrz_order: "B2订单数据",
//};
