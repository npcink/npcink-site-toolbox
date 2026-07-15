//页面 - 外观优化
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, InputNumber } from "antd";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFeature } from "@/tool/interface";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";

type FieldType = PageFeature;

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.page?.feature || defaultVarOption.page.feature;
  const [formData, setFormData] = useState(publicData || {});

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
    updateOption("page", "feature", formData);
  }, [formData]);

  return (
    <SettingsSection title="外观">
      <Form
        name="aspect"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <h3 className="mabox-menu-header">特效</h3>

        <ModuleRow
          title="阅读进度条"
          description="文章页面顶部显示阅读进度指示器，仅文章页展示"
          featureId="page-feature-reading_progress"
          enabled={formData.reading_progress as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ reading_progress: checked } as Partial<FieldType>, formData);
          }}
        >
          <Form.Item<FieldType> label="进度条颜色" name="reading_progress_color">
            <Input style={{ width: "30%" }} placeholder="#1677ff" />
          </Form.Item>
          <Form.Item<FieldType>
            label="进度条高度"
            name="reading_progress_height"
            extra={"单位: 像素"}
          >
            <InputNumber addonAfter={"px"} style={{ width: "120px" }} min={1} max={10} />
          </Form.Item>
        </ModuleRow>
      </Form>
    </SettingsSection>
  );
};

export default App;
