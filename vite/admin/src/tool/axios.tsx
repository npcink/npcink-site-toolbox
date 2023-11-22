//各种请求
import axios from "axios";

//开发环境状态
const state: boolean = import.meta.env.VITE_STATE;
//输出ajaxurl
function getAjaxurl(): string {
  if (state) {
    //开发
    return import.meta.env.VITE_AJAXURL;
  } else {
    //打包
    return (window as any).ajaxurl;
  }
}
//传值
const ajaxurl = getAjaxurl();

//获取所有数据库表名字
export const get_all_table_name = async () => {
  const params = new URLSearchParams();
  params.append("action", "get_all_table_names");
  try {
    const response = await axios.post(ajaxurl, params);

    if (response.status === 200) {
      //保存成功
      console.log(response);
      return response.data.data;
    } else {
      console.error("出错：" + response.data);
    }
  } catch (error: any) {
    console.error("出错：" + error.message);
  }
};

//获取数据库表下载
function downloadCSV(csvString: string, filename: string) {
  const blob = new Blob([csvString], { type: "text/csv;charset=utf-8;" });

  if ((navigator as any).msSaveBlob) {
    // 用类型断言告诉编译器 msSaveBlob 存在
    (navigator as any).msSaveBlob(blob, filename);
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

export const get_table_data = async (type: string) => {
  const params = new URLSearchParams();
  params.append("action", "get_table_data");
  params.append("databaseName", type);
  try {
    const response = await axios.post(ajaxurl, params);

    if (response.status === 200) {
      //保存成功
      downloadCSV(response.data, type + ".csv");
    } else {
      console.error("出错：" + response.data);
    }
  } catch (error: any) {
    console.error("出错：" + error.message);
  }
};
