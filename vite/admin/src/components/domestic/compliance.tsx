import React, { useContext, useState, useEffect } from "react";
import { Form, Input, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;
const { TextArea } = Input;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.compliance || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("domestic", "compliance", formData);
  }, [formData]);

  return (
    <Form
      name="compliance"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"面向中国站长的备案与合规工具"}>
        <h2>备案与合规信息</h2>
      </Form.Item>

      <Form.Item label="ICP 备案号" name="icp_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-compliance-icp_enabled" />
      </Form.Item>
      {formData.icp_enabled && (
        <>
          <Form.Item label="备案号" name="icp_number">
            <Input placeholder="如：京ICP备12345678号" />
          </Form.Item>
          <Form.Item label="查询链接" name="icp_link">
            <Input />
          </Form.Item>
        </>
      )}

      <Form.Item label="公安网备号" name="police_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-compliance-police_enabled" />
      </Form.Item>
      {formData.police_enabled && (
        <>
          <Form.Item label="网备号" name="police_number">
            <Input placeholder="如：京公网安备11010102001234号" />
          </Form.Item>
          <Form.Item label="查询链接" name="police_link">
            <Input />
          </Form.Item>
        </>
      )}

      <Form.Item label="Cookie 同意弹窗" name="cookie_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-compliance-cookie_enabled" />
      </Form.Item>
      {formData.cookie_enabled && (
        <>
          <Form.Item label="弹窗样式" name="cookie_style">
            <Select options={[{ label: "底部", value: "bottom" }, { label: "顶部", value: "top" }]} />
          </Form.Item>
          <Form.Item label="标题" name="cookie_title">
            <Input />
          </Form.Item>
          <Form.Item label="内容" name="cookie_content">
            <TextArea rows={3} />
          </Form.Item>
          <Form.Item label="按钮文字" name="cookie_button">
            <Input />
          </Form.Item>
        </>
      )}

      <Form.Item label="版权信息" name="copyright_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-compliance-copyright_enabled" />
      </Form.Item>
      {formData.copyright_enabled && (
        <Form.Item label="自定义 HTML" name="copyright_html" extra="留空则使用默认版权格式">
          <TextArea rows={3} placeholder="&copy; 2024 网站名称 版权所有" />
        </Form.Item>
      )}
    </Form>
  );
};

export default App;