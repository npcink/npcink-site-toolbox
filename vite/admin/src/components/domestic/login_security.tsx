import React, { useContext, useState, useEffect } from "react";
import { Form, Input, InputNumber } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;
const { TextArea } = Input;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.login_security || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("domestic", "login_security", formData);
  }, [formData]);

  return (
    <Form
      name="login_security"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"登录安全中心，防止暴力破解"}>
        <h2>登录安全中心</h2>
      </Form.Item>

      <Form.Item label="限制登录失败次数" name="fail_limit_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-fail_limit_enabled" />
      </Form.Item>
      {formData.fail_limit_enabled && (
        <>
          <Form.Item label="最大失败次数" name="fail_limit_count">
            <InputNumber min={3} max={20} />
          </Form.Item>
          <Form.Item label="锁定时间(分钟)" name="fail_lock_duration">
            <InputNumber min={5} max={1440} />
          </Form.Item>
        </>
      )}

      <Form.Item label="IP 登录失败锁定" name="ip_lock_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-ip_lock_enabled" />
      </Form.Item>
      {formData.ip_lock_enabled && (
        <>
          <Form.Item label="最大失败次数" name="ip_lock_count">
            <InputNumber min={5} max={50} />
          </Form.Item>
          <Form.Item label="锁定时间(分钟)" name="ip_lock_duration">
            <InputNumber min={5} max={1440} />
          </Form.Item>
        </>
      )}

      <Form.Item label="自定义登录地址" name="custom_login_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-custom_login_enabled" />
      </Form.Item>
      {formData.custom_login_enabled && (
        <Form.Item label="登录 slug" name="custom_login_slug" extra="如：my-login">
          <Input />
        </Form.Item>
      )}

      <Form.Item label="禁止用户名枚举" name="ban_enumeration_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-ban_enumeration_enabled" />
      </Form.Item>

      <Form.Item label="登录通知邮件" name="login_notify_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-login_notify_enabled" />
      </Form.Item>

      <Form.Item label="登录日志" name="login_log_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-login_log_enabled" />
      </Form.Item>

      <Form.Item label="IP 白名单" name="ip_whitelist_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-login_security-ip_whitelist_enabled" />
      </Form.Item>
      {formData.ip_whitelist_enabled && (
        <Form.Item label="白名单 IP" name="ip_whitelist" extra="每行一个">
          <TextArea rows={4} />
        </Form.Item>
      )}
    </Form>
  );
};

export default App;