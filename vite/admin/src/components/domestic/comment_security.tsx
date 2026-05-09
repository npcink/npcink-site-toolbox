import React, { useContext, useState, useEffect } from "react";
import { Form, Input, InputNumber, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;
const { TextArea } = Input;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.comment_security || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("domestic", "comment_security", formData);
  }, [formData]);

  return (
    <Form
      name="comment_security"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={publicData}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra={"评论安全中心，过滤垃圾评论"}>
        <h2>评论安全中心</h2>
      </Form.Item>

      <Form.Item label="敏感词过滤" name="blacklist_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-blacklist_enabled" />
      </Form.Item>
      {formData.blacklist_enabled && (
        <>
          <Form.Item label="敏感词列表" name="blacklist_words" extra="每行一个">
            <TextArea rows={4} />
          </Form.Item>
          <Form.Item label="处理方式" name="blacklist_action">
            <Select options={[{ label: "拦截", value: "block" }, { label: "标记待审核", value: "mark" }]} />
          </Form.Item>
        </>
      )}

      <Form.Item label="链接数量限制" name="link_limit_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-link_limit_enabled" />
      </Form.Item>
      {formData.link_limit_enabled && (
        <Form.Item label="最大链接数" name="link_limit_count">
          <InputNumber min={0} max={10} />
        </Form.Item>
      )}

      <Form.Item label="昵称过滤" name="nickname_filter_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-nickname_filter_enabled" />
      </Form.Item>
      {formData.nickname_filter_enabled && (
        <Form.Item label="禁用昵称" name="nickname_filter_words" extra="每行一个">
          <TextArea rows={3} />
        </Form.Item>
      )}

      <Form.Item label="邮箱域名黑名单" name="email_domain_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-email_domain_enabled" />
      </Form.Item>
      {formData.email_domain_enabled && (
        <Form.Item label="黑名单域名" name="email_domain_blacklist" extra="每行一个">
          <TextArea rows={3} />
        </Form.Item>
      )}

      <Form.Item label="重复评论拦截" name="duplicate_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-duplicate_enabled" />
      </Form.Item>

      <Form.Item label="IP 频率限制" name="ip_rate_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-ip_rate_enabled" />
      </Form.Item>
      {formData.ip_rate_enabled && (
        <>
          <Form.Item label="限制次数" name="ip_rate_limit">
            <InputNumber min={1} max={100} />
          </Form.Item>
          <Form.Item label="时间窗口(秒)" name="ip_rate_window">
            <InputNumber min={10} max={3600} />
          </Form.Item>
        </>
      )}

      <Form.Item label="记录拦截日志" name="log_enabled" valuePropName="checked">
        <FeatureSwitch featureId="domestic-comment_security-log_enabled" />
      </Form.Item>
    </Form>
  );
};

export default App;