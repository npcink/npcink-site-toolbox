//权限 - 辅助功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionSeo } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

//选项类型
type FieldType = FunctionSeo;

//Ant 组件配置
const fromConfig = AntConfig.from;

//多行输入
const { TextArea } = Input;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData = optionData.function?.seo || defaultVarOption.function.seo;

  //创建变量并设默认值
  const [formData, setFormData] = useState(publicData || {});

  //表单同步修改值
  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  // 表单值发生变化时更新dataContext的值
  useEffect(() => {
    updateOption("function", "seo", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="seo"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={publicData}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={() => {}}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item extra={"仅解决有无问题，推荐使用专业 SEO 插件"}>
          <h2>简单SEO</h2>
        </Form.Item>
        <Form.Item<FieldType> label="标题" name="title" extra={"站点标题"}>
          <Input />
        </Form.Item>
        <Form.Item<FieldType>
          label="关键词"
          name="keywords"
          extra={"网站相关关键词，用英文逗号分隔，建议不超过6个词"}
        >
          <Input />
        </Form.Item>
        <Form.Item<FieldType>
          label="描述"
          name="description"
          extra={"关于网站的描述，建议240字以内"}
        >
          <TextArea rows={4} />
        </Form.Item>
        <Form.Item<FieldType>
          id="function-seo-seo_single"
          label="文章SEO"
          name="seo_single"
          valuePropName="checked"
          extra={
            <p>
              title是文章标题，keywords是文章标签，description是文章描述或文章首段前40字
            </p>
          }
        >
          <FeatureSwitch featureId="function-seo-seo_single" />
        </Form.Item>
        <Form.Item<FieldType>
          id="function-seo-seo_category"
          label="分类和标签SEO"
          name="seo_category"
          valuePropName="checked"
          extra={
            <p>
              T 是分类名称，K 是分类关键词，D 是分类描述，标签只做了D
              ，是标签描述
            </p>
          }
        >
          <FeatureSwitch featureId="function-seo-seo_category" />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
