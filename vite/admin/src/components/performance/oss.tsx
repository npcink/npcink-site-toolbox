import React, { useContext, useState, useEffect } from "react";
import { Form, Input, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.oss || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "oss", formData);
  }, [formData]);

  return (
    <Form
      name="oss"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"图片自动上传至云存储"}>
        <h2>对象存储 / OSS</h2>
      </Form.Item>

      <Form.Item label="启用" name="enabled" valuePropName="checked">
        <FeatureSwitch featureId="performance-oss-enabled" />
      </Form.Item>
      {formData.enabled && (
        <>
          <Form.Item label="服务商" name="provider">
            <Select options={[
              { label: "阿里云 OSS", value: "aliyun" },
              { label: "腾讯云 COS", value: "tencent" },
              { label: "七牛云", value: "qiniu" },
            ]} />
          </Form.Item>
          <Form.Item label="Access Key" name="access_key">
            <Input />
          </Form.Item>
          <Form.Item label="Secret Key" name="secret_key">
            <Input.Password />
          </Form.Item>
          <Form.Item label="Bucket" name="bucket">
            <Input />
          </Form.Item>
          <Form.Item label="Region" name="region">
            <Input placeholder="如：oss-cn-beijing" />
          </Form.Item>
          <Form.Item label="CDN 域名" name="domain">
            <Input placeholder="如：https://cdn.example.com" />
          </Form.Item>
          <Form.Item label="上传后删除本地文件" name="delete_local" valuePropName="checked">
            <FeatureSwitch featureId="performance-oss-delete_local" />
          </Form.Item>
        </>
      )}
    </Form>
  );
};

export default App;