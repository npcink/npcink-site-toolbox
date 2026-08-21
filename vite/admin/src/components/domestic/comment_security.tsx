import React, { useContext, useState, useEffect } from "react";
import { Form, Input, InputNumber, Select } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow } from "@/components/settings-ui";
import { __ } from "@/tool/i18n";

const fromConfig = AntConfig.from;
const { TextArea } = Input;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.domestic?.comment_security || {};
  const [formData, setFormData] = useState(publicData || {});

  const onValuesChange = (changedValues: any, _allValues?: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("domestic", "comment_security", formData);
  }, [formData]);

  return (
    <SettingsSection title={__("评论安全")} description={__("评论安全中心，过滤垃圾评论")}>
      <Form
        name="comment_security"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title={__("敏感词过滤")}
          featureId="domestic-comment_security-blacklist_enabled"
          enabled={formData.blacklist_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ blacklist_enabled: checked });
          }}
        >
          <Form.Item label={__("敏感词列表")} name="blacklist_words" extra={__("每行一个")}>
            <TextArea rows={4} />
          </Form.Item>
          <Form.Item label={__("处理方式")} name="blacklist_action">
            <Select options={[{ label: __("拦截"), value: "block" }, { label: __("标记待审核"), value: "mark" }]} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("链接数量限制")}
          featureId="domestic-comment_security-link_limit_enabled"
          enabled={formData.link_limit_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ link_limit_enabled: checked });
          }}
        >
          <Form.Item label={__("最大链接数")} name="link_limit_count">
            <InputNumber min={0} max={10} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("昵称过滤")}
          featureId="domestic-comment_security-nickname_filter_enabled"
          enabled={formData.nickname_filter_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ nickname_filter_enabled: checked });
          }}
        >
          <Form.Item label={__("禁用昵称")} name="nickname_filter_words" extra={__("每行一个")}>
            <TextArea rows={3} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("邮箱域名黑名单")}
          featureId="domestic-comment_security-email_domain_enabled"
          enabled={formData.email_domain_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ email_domain_enabled: checked });
          }}
        >
          <Form.Item label={__("黑名单域名")} name="email_domain_blacklist" extra={__("每行一个")}>
            <TextArea rows={3} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("重复评论拦截")}
          featureId="domestic-comment_security-duplicate_enabled"
          enabled={formData.duplicate_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ duplicate_enabled: checked });
          }}
        />

        <ModuleRow
          title={__("IP 频率限制")}
          featureId="domestic-comment_security-ip_rate_enabled"
          enabled={formData.ip_rate_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ ip_rate_enabled: checked });
          }}
        >
          <Form.Item label={__("限制次数")} name="ip_rate_limit">
            <InputNumber min={1} max={100} />
          </Form.Item>
          <Form.Item label={__("时间窗口(秒)")} name="ip_rate_window">
            <InputNumber min={10} max={3600} />
          </Form.Item>
        </ModuleRow>

        <ModuleRow
          title={__("记录拦截日志")}
          featureId="domestic-comment_security-log_enabled"
          enabled={formData.log_enabled as boolean}
          onChange={(checked: boolean) => {
            onValuesChange({ log_enabled: checked });
          }}
        />
      </Form>
    </SettingsSection>
  );
};

export default App;
