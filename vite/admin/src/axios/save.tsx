//保存选项接口
import { Ajaxurl } from "@/tool/dataContext";
import { getNonce } from "@/tool/dataContext";
import { instance, addParamIfDefined } from "@/axios/public";
import { Option } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
//接收选项
export const saceOption = async (data: Option) => {
  //console.log("待保存数据：" + JSON.stringify(data, null, 2));
  const params = new URLSearchParams();
  params.append("action", "save_option_wmt");
  params.append("nonce", getNonce());

  if (data) {
    addParamIfDefined(params, "object_data", JSON.stringify(data));
  } else {
    //若刚开始没有选项值,第一次保存使用默认的值
    addParamIfDefined(params, "object_data", JSON.stringify(defaultVarOption));
  }

  try {
    await instance.post(Ajaxurl, params);
  } catch (error) {
    console.log(`保存设置选项时出错：${error}`);
    throw error;
  }
};
