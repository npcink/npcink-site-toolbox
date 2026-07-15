import React, { useContext, useState, useEffect } from "react";
import { Form, Input, Button, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";
import { domesticApi, runBaiduBatchPush } from "@/api";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.baidu_push || {};
  const [formData, setFormData] = useState(publicData || {});
  const [pushing, setPushing] = useState(false);

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("domestic", "baidu_push", formData);
  }, [formData]);

  const handleBatchPush = async () => {
    setPushing(true);
    try {
      const response = await runBaiduBatchPush((offset) => domesticApi.baiduPush(undefined, offset));
      if (response.success) message.success(response.data?.message || "批量推送完成");
    } catch (error) {
      message.error(error instanceof Error ? error.message : "推送失败");
    } finally {
      setPushing(false);
    }
  };

  return (
    <SettingsSection title="百度推送" description="文章发布自动推送到百度搜索资源平台">
      <Form
        name="baidu_push"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="主动推送"
          featureId="domestic-baidu_push-active_push_enabled"
          enabled={formData.active_push_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ active_push_enabled: checked });
          }}
        >
          <Form.Item label="Site" name="site">
            <Input placeholder="如：https://www.example.com" />
          </Form.Item>
          <Form.Item label="Token" name="token">
            <Input placeholder="百度搜索资源平台提供的 Token" />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title="自动推送 JS"
          description="在页面底部插入百度自动推送代码"
          featureId="domestic-baidu_push-auto_push_enabled"
          enabled={formData.auto_push_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ auto_push_enabled: checked });
          }}
        />

        <ModuleRow
          title="批量推送"
          description="推送所有历史文章到百度"
          featureId="domestic-baidu_push-batch_push_enabled"
          enabled={formData.batch_push_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ batch_push_enabled: checked });
          }}
        >
          <Form.Item wrapperCol={fromConfig.wrapperCol}>
            <Button type="primary" onClick={handleBatchPush} loading={pushing}>
              开始批量推送
            </Button>
          </Form.Item>
        </ModuleRow>
      </Form>
    </SettingsSection>
  );
};

export default App;
