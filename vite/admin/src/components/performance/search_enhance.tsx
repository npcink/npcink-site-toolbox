import React, { useContext, useState, useEffect } from "react";
import { Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.search_enhance || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "search_enhance", formData);
  }, [formData]);

  return (
    <Form
      name="search_enhance"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"站内搜索体验增强"}>
        <h2>站内搜索增强</h2>
      </Form.Item>

      <Form.Item label="关键词高亮" name="highlight_enabled" valuePropName="checked">
        <FeatureSwitch featureId="performance-search_enhance-highlight_enabled" />
      </Form.Item>

      <Form.Item label="无结果推荐" name="recommend_enabled" valuePropName="checked"
        extra="搜索无结果时显示热门标签">
        <FeatureSwitch featureId="performance-search_enhance-recommend_enabled" />
      </Form.Item>

      <Form.Item label="热词统计" name="hotwords_enabled" valuePropName="checked"
        extra="记录搜索热词（后台可查看）">
        <FeatureSwitch featureId="performance-search_enhance-hotwords_enabled" />
      </Form.Item>
    </Form>
  );
};

export default App;