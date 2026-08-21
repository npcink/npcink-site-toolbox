import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionSeo } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";

type FieldType = FunctionSeo;

const fromConfig = AntConfig.from;

const { TextArea } = Input;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);

  const publicData = optionData.function?.seo || defaultVarOption.function.seo;

  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: Partial<FieldType>, _allValues?: FieldType) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  useEffect(() => {
    updateOption("function", "seo", formData);
  }, [formData]);

  return (
    <SettingsSection title={__("简单SEO")} description={__("仅解决有无问题，推荐使用专业 SEO 插件")}>
      <Form
        name="seo"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item<FieldType> label={__("标题")} name="title" extra={__("站点标题")}>
          <Input />
        </Form.Item>
        <Form.Item<FieldType>
          label={__("关键词")}
          name="keywords"
          extra={__("网站相关关键词，用英文逗号分隔，建议不超过6个词")}
        >
          <Input />
        </Form.Item>
        <Form.Item<FieldType>
          label={__("描述")}
          name="description"
          extra={__("关于网站的描述，建议240字以内")}
        >
          <TextArea rows={4} />
        </Form.Item>
        <ModuleRow
          title={__("文章SEO")}
          description={__("title是文章标题，keywords是文章标签，description是文章描述或文章首段前40字")}
          featureId="function-seo-seo_single"
          enabled={formData.seo_single as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ seo_single: checked });
          }}
        />
        <ModuleRow
          title={__("分类和标签SEO")}
          description={__("T 是分类名称，K 是分类关键词，D 是分类描述，标签只做了D，是标签描述")}
          featureId="function-seo-seo_category"
          enabled={formData.seo_category as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ seo_category: checked });
          }}
        />
      </Form>
    </SettingsSection>
  );
};

export default App;
