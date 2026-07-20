import React, { useContext, useRef, useState } from "react";
import { Form, Input, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { PerformanceOss, SecretChange } from "@/tool/interface";
import { AntConfig } from "@/tool/tool";
import {
  DetailDrawer,
  ModuleCard,
  SecretField,
  SettingsSection,
} from "@/components/settings-ui";

const fromConfig = AntConfig.from;

const isSecretConfigured = (configured: boolean, change?: SecretChange): boolean => {
  if (change?.operation === "replace") return change.value.trim() !== "";
  if (change?.operation === "clear") return false;
  return configured;
};

const App: React.FC = () => {
  const {
    optionData,
    updateOption,
    secretStatus,
    secretChanges,
  } = useContext(DataContext);
  const publicData = optionData.performance?.oss || defaultVarOption.performance.oss;
  const [formData, setFormData] = useState<PerformanceOss>(publicData);
  const formDataRef = useRef<PerformanceOss>(publicData);
  const [drawerOpen, setDrawerOpen] = useState(false);

  const onValuesChange = (changedValues: Partial<PerformanceOss>) => {
    const nextFormData = { ...formDataRef.current, ...changedValues };
    formDataRef.current = nextFormData;
    setFormData(nextFormData);
    updateOption("performance", "oss", nextFormData);
  };

  const accessKeyConfigured = isSecretConfigured(
    secretStatus["performance.oss.access_key"].configured,
    secretChanges["performance.oss.access_key"],
  );
  const secretKeyConfigured = isSecretConfigured(
    secretStatus["performance.oss.secret_key"].configured,
    secretChanges["performance.oss.secret_key"],
  );
  const storageTargetConfigured = Boolean(
    formData.provider.trim()
    && formData.bucket.trim()
    && formData.domain.trim()
    && (formData.provider === "qiniu" || formData.region.trim()),
  );
  const configurationStatus = storageTargetConfigured && accessKeyConfigured && secretKeyConfigured
    ? "已配置" as const
    : "未配置" as const;

  return (
    <SettingsSection title="对象存储" description="图片自动上传至云存储">
      <>
        <ModuleCard
          title="启用对象存储"
          description="启用后图片将自动上传至云存储"
          featureId="performance-oss-enabled"
          tags={[configurationStatus]}
          enabled={!!formData.enabled}
          onChange={(checked) => onValuesChange({ enabled: checked })}
          actionLabel="配置"
          onAction={() => setDrawerOpen(true)}
        />

        <DetailDrawer
          title="对象存储配置"
          visible={drawerOpen}
          onClose={() => setDrawerOpen(false)}
          description="可先完成配置，再决定是否启用；更改随页面顶部的“保存”统一保存。"
          width={520}
        >
          <Form
            name="oss"
            labelCol={fromConfig.labelCol}
            wrapperCol={fromConfig.wrapperCol}
            style={{ maxWidth: fromConfig.maxWidth }}
            initialValues={publicData}
            autoComplete="off"
            onValuesChange={onValuesChange}
          >
            <Form.Item label="服务商" name="provider">
              <Select options={[
                { label: "阿里云 OSS", value: "aliyun" },
                { label: "腾讯云 COS", value: "tencent" },
                { label: "七牛云", value: "qiniu" },
              ]} />
            </Form.Item>
            <SecretField label="Access Key" path="performance.oss.access_key" />
            <SecretField label="Secret Key" path="performance.oss.secret_key" />
            <Form.Item label="Bucket" name="bucket">
              <Input />
            </Form.Item>
            <Form.Item label="Region" name="region">
              <Input placeholder="阿里云 cn-beijing；腾讯云 ap-beijing" />
            </Form.Item>
            <Form.Item label="CDN 域名" name="domain">
              <Input placeholder="如：https://cdn.example.com" />
            </Form.Item>
          </Form>
        </DetailDrawer>
      </>
    </SettingsSection>
  );
};

export default App;
