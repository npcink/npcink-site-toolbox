/**
 * 页面优化 - 权限
 */
import { useState, useContext, useEffect } from "react";
import { Form, Select, Input, Radio } from "antd";
import { DataContext } from "@/tool/dataContext";
import { PageJurisdiction, ListData } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { getCategoryData } from "@/axios/axios";
import TextAreaHtml from "@/basic/htmlInput";
import { checkRiskyFeature } from "@/tool/riskyFeature.tsx";
import FeatureSwitch from "@/basic/feature-switch";

type FieldType = PageJurisdiction;

const fromConfig = AntConfig.from;

const RISKY_FIELDS: Record<string, string> = {
  ban_copy: "page-jurisdiction-ban_copy",
};

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData =
    optionData.page?.jurisdiction || defaultVarOption.page.jurisdiction;
  const [formData, setFormData] = useState(publicData || {});

  const applyChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    const fieldKey = Object.keys(changedValues)[0];
    const featureId = RISKY_FIELDS[fieldKey];
    if (featureId) {
      const newValue = changedValues[fieldKey as keyof FieldType];
      const shouldProceed = checkRiskyFeature(featureId, newValue, () => {
        applyChange(changedValues);
      });
      if (!shouldProceed) {
        return;
      }
    }
    applyChange(changedValues);
  };

  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("page", "jurisdiction", formData);
  }, [formData]);

  //存储表单值
  interface TagData {
    categorys: ListData[];//分类数组
    tags: ListData[];//标签数组
    pages:ListData[];//页面数组
  }
  const [tagArray, setTagArray] = useState<TagData>();
  //获取分类数组
  const getData = async () => {
    try {
      // 获取原始数据
      const list = await getCategoryData();
      //console.log(list);
      setTagArray(list);
    } catch (error) {
      console.error("Error fetching table data:", error);
    }
  };
  //加载页面自动获取数据
  useEffect(() => {
    // 在页面加载完成后执行 函数，获取数据并更新状态
    getData();
  }, []);

  return (
    <>
      <Form
        name="jurisdiction"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>权限</h2>
        </Form.Item>
        <Form.Item>
          <h3 className="menu-header">隐私权限</h3>
        </Form.Item>
        <Form.Item<FieldType>
          id="page-jurisdiction-ban_open_weixing"
          label="禁止在微信中打开"
          name="ban_open_weixing"
          valuePropName="checked"
          extra={<>（可能有防红功能）</>}
        >
          <FeatureSwitch featureId="page-jurisdiction-ban_open_weixing" />
        </Form.Item>
        {formData.ban_open_weixing && (
          <>
            <Form.Item<FieldType>
              label="处理方式"
              name="ban_open_weixing_mode"
            >
              <Radio.Group>
                <Radio value="alert">弹窗提示</Radio>
                <Radio value="optimize">优化体验+引导</Radio>
              </Radio.Group>
            </Form.Item>
            {formData.ban_open_weixing_mode === 'optimize' && (
              <>
                <Form.Item<FieldType> label="引导文字" name="wechat_guide_text">
                  <Input style={{ width: "70%" }} placeholder="点击右上角 ··· 在浏览器中打开" />
                </Form.Item>
                <Form.Item<FieldType>
                  label="小程序引导"
                  name="wechat_xcx_guide"
                  valuePropName="checked"
                >
                  <FeatureSwitch featureId="page-jurisdiction-wechat_xcx_guide" />
                </Form.Item>
                {formData.wechat_xcx_guide && (
                  <>
                    <Form.Item<FieldType> label="小程序引导文字" name="wechat_xcx_guide_text">
                      <Input style={{ width: "50%" }} placeholder="在小程序中打开" />
                    </Form.Item>
                    <Form.Item<FieldType> label="小程序链接" name="wechat_xcx_link">
                      <Input style={{ width: "70%" }} placeholder="weixin://dl/business/..." />
                    </Form.Item>
                  </>
                )}
              </>
            )}
          </>
        )}
        <Form.Item<FieldType>
          id="page-jurisdiction-ban_open_qq"
          label="禁止在 QQ 中打开"
          name="ban_open_qq"
          valuePropName="checked"
          extra={<>（可能有防红功能）</>}
        >
          <FeatureSwitch featureId="page-jurisdiction-ban_open_qq" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-jurisdiction-ban_copy"
          label="禁止复制"
          name="ban_copy"
          valuePropName="checked"
        >
          <FeatureSwitch featureId="page-jurisdiction-ban_copy" />
        </Form.Item>
        <Form.Item<FieldType>
          id="page-jurisdiction-front_debug"
          label="禁用F12前端调试"
          name="front_debug"
          valuePropName="checked"
          extra={<>打开浏览器控制台显示空白内容</>}
        >
          <FeatureSwitch featureId="page-jurisdiction-front_debug" />
        </Form.Item>
        <Form.Item>
          <h3 className="menu-header">未登录权限</h3>
        </Form.Item>

        <Form.Item<FieldType>
          label="隐藏指定分类下的内容"
          name="category_id"
          extra={"该分类下的内容未登录时，不可见，仅展示提示内容"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的分类"
            options={tagArray?.categorys}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label="隐藏指定标签下的内容"
          name="tag_id"
          extra={"该标签下的内容未登录时，不可见，仅展示提示内容"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的标签"
            options={tagArray?.tags}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label="隐藏指定页面"
          name="page_id"
          extra={"该页面下的内容未登录时，不可见，仅展示提示内容"}
        >
          <Select
            mode="multiple"
            allowClear
            style={{ width: "100%" }}
            placeholder="请选择要隐藏的页面"
            options={tagArray?.pages}
          />
        </Form.Item>
        {/**TODO:要不要预先提供几个模版 */}
        <Form.Item<FieldType>
          label="隐藏时的提示内容"
          name="tip_content"
          extra={"内容被隐藏时的提示内容，支持HTML"}
        >
          <TextAreaHtml />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
