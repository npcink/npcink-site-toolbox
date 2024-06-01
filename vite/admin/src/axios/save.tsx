//保存选项接口
import { Ajaxurl } from "@/tool/dataContext";
import { instance, addParamIfDefined } from "@/axios/public";
import { Option } from "@/tool/interface";
//接收选项
export const saceOption = async (data: Option) => {
  const params = new URLSearchParams();
  params.append("action", "save_object_option");
  addParamIfDefined(params, "object_data", JSON.stringify(data));
  try {
    await instance.post(Ajaxurl, params);
  } catch (error) {
    console.log(`保存设置选项时出错：${error}`);
    throw error;
  }
};
