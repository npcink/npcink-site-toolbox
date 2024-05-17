//权限 - 辅助功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, Switch } from "antd";
import DataContext from "@/tool/dataContext";
import { FunctionSeo } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = FunctionSeo;

//Ant 组件配置
const fromConfig = AntConfig.from;

//多行输入
const { TextArea } = Input;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { authority: {} };

  //简化并提供默认值
  let publicData = optionObj.authority?.seo || defaultVar.authority.seo;

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

  useEffect(() => {
    //由于选项site可能不存在，这里需要使用复制来新建
    optionObj.authority = {
      ...optionObj.authority,
      seo: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="seo"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
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
          label="文章SEO"
          name="single_seo"
          valuePropName="checked"
          extra={<p>title是文章标题，keywords是文章标签，description是文章描述</p>}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="分类和标签SEO"
          name="category_seo"
          valuePropName="checked"
          extra={<p>title 是分类名称，keywords 是分类名称，description 是分类描述</p>}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
