import React, { useContext, useState, useEffect } from "react";
import { Form, Input, Button, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.baidu_push || {};
  const [formData, setFormData] = useState(publicData || {});
  const [pushing, setPushing] = useState(false);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("domestic", "baidu_push", formData);
  }, [formData]);

  const handleBatchPush = () => {
    setPushing(true);
    const doPush = (offset: number) => {
      const formData2 = new FormData();
      formData2.append("action", "mabox_baidu_batch_push");
      formData2.append("offset", String(offset));
      fetch(window.dataLocal?.ajaxurl || "/wp-admin/admin-ajax.php", {
        method: "POST",
        body: formData2,
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.success && !res.data?.done) {
            doPush(res.data.offset);
          } else {
            setPushing(false);
            message.success(res.data?.message || "批量推送完成");
          }
        })
        .catch(() => {
          setPushing(false);
          message.error("推送失败");
        });
    };
    doPush(0);
  };

  return (
    <Form
      name="baidu_push"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"文章发布自动推送到百度搜索资源平台"}>
        <h2>百度收录推送</h2>
      </Form.Item>

      <Form.Item label="主动推送" name="active_push_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-baidu_push-active_push_enabled" />
      </Form.Item>
      {formData.active_push_enabled && (
        <>
          <Form.Item label="Site" name="site">
            <Input placeholder="如：https://www.example.com" />
          </Form.Item>
          <Form.Item label="Token" name="token">
            <Input placeholder="百度搜索资源平台提供的 Token" />
          </Form.Item>
        </>
      )}

      <Form.Item label="自动推送 JS" name="auto_push_enabled" valuePropName="checked"
        extra="在页面底部插入百度自动推送代码">
        <FeatureSwitch featureId="domestic-baidu_push-auto_push_enabled" />
      </Form.Item>

      <Form.Item label="批量推送" name="batch_push_enabled" valuePropName="checked"
        extra="推送所有历史文章到百度">
        <FeatureSwitch featureId="domestic-baidu_push-batch_push_enabled" />
      </Form.Item>
      {formData.batch_push_enabled && (
        <Form.Item wrapperCol={{ offset: fromConfig.labelCol, span: fromConfig.wrapperCol }}>
          <Button type="primary" onClick={handleBatchPush} loading={pushing}>
            开始批量推送
          </Button>
        </Form.Item>
      )}
    </Form>
  );
};

export default App;