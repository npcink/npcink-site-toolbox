import React, { useContext, useState, useEffect } from "react";
import { aiReviewApi } from "@/api";
import { Form, Input, Select, Button, message, Tag, Space, Card } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const fromConfig = AntConfig.from;
const { TextArea } = Input;

const providerOptions = [
  { label: "本地规则引擎（默认）", value: "local" },
  { label: "DeepSeek", value: "deepseek" },
  { label: "阿里云内容安全", value: "aliyun" },
  { label: "自定义 API", value: "custom" },
];

const modeOptions = [
  { label: "标记为待审核", value: "mark" },
  { label: "直接拦截", value: "block" },
];

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const data = optionData.ai_review || {};
  const [formData, setFormData] = useState(data);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState<any>(null);

  const onValuesChange = (changedValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("ai_review", "", formData);
  }, [formData]);

  const handleTest = async () => {
    setTesting(true);
    setTestResult(null);
    try {
      const result = await aiReviewApi.testProvider(formData.provider || "local", formData);
      setTestResult(result);
      if (result.success) {
        message.success("测试成功");
      } else {
        message.error(result.message || result.data?.error || "测试失败");
      }
    } catch (e) {
      message.error("请求失败");
    } finally {
      setTesting(false);
    }
  };

  const renderProviderConfig = () => {
    const provider = formData?.provider || "local";

    if (provider === "deepseek") {
      return (
        <>
          <Form.Item label="API Key" name="deepseek_api_key">
            <Input.Password placeholder="sk-..." />
          </Form.Item>
          <Form.Item label="API 地址" name="deepseek_api_url" extra="默认使用官方地址">
            <Input placeholder="https://api.deepseek.com/v1/chat/completions" />
          </Form.Item>
          <Form.Item label="模型" name="deepseek_model">
            <Input placeholder="deepseek-chat" />
          </Form.Item>
        </>
      );
    }

    if (provider === "aliyun") {
      return (
        <>
          <Form.Item label="AccessKey ID" name="aliyun_access_key">
            <Input placeholder="LTAI..." />
          </Form.Item>
          <Form.Item label="AccessKey Secret" name="aliyun_secret_key">
            <Input.Password placeholder="..." />
          </Form.Item>
          <Form.Item label="区域" name="aliyun_region">
            <Select
              options={[
                { label: "上海", value: "cn-shanghai" },
                { label: "北京", value: "cn-beijing" },
                { label: "深圳", value: "cn-shenzhen" },
              ]}
            />
          </Form.Item>
        </>
      );
    }

    if (provider === "custom") {
      return (
        <>
          <Form.Item label="API 地址" name="custom_api_url">
            <Input placeholder="https://your-api.com/review" />
          </Form.Item>
          <Form.Item label="请求方法" name="custom_api_method">
            <Select
              options={[
                { label: "POST", value: "POST" },
                { label: "PUT", value: "PUT" },
              ]}
            />
          </Form.Item>
          <Form.Item label="请求头 (JSON)" name="custom_api_headers" extra='如 {"Authorization": "Bearer xxx"}'>
            <TextArea rows={2} placeholder='{"Authorization": "Bearer xxx"}' />
          </Form.Item>
          <Form.Item label="请求体模板" name="custom_api_body_template" extra="使用 {{text}} 占位评论内容">
            <TextArea rows={3} placeholder='{"text": "{{text}}"}' />
          </Form.Item>
        </>
      );
    }

    return (
      <>
        <Form.Item label="关键词列表" name="local_keywords" extra="每行一个关键词">
          <TextArea rows={4} placeholder="广告&#10;灌水&#10;敏感词" />
        </Form.Item>
        <Form.Item label="正则规则" name="local_regex" extra="每行一个正则表达式">
          <TextArea rows={3} placeholder="/广告[\\u4e00-\\u9fa5]{2,}/" />
        </Form.Item>
      </>
    );
  };

  return (
    <Form
      name="ai_review_config"
      labelCol={fromConfig.labelCol}
      wrapperCol={fromConfig.wrapperCol}
      style={{ maxWidth: fromConfig.maxWidth }}
      initialValues={data}
      autoComplete="off"
      onValuesChange={onValuesChange}
    >
      <Form.Item extra="使用 AI 或规则自动审核评论，识别广告、灌水、敏感内容">
        <h2>AI 审核助手</h2>
      </Form.Item>

      <Form.Item label="启用审核" name="enabled" valuePropName="checked">
        <FeatureSwitch featureId="ai-review-enabled" />
      </Form.Item>

      {formData?.enabled && (
        <>
          <Form.Item label="审核引擎" name="provider">
            <Select options={providerOptions} />
          </Form.Item>

          <Form.Item label="处理方式" name="mode">
            <Select options={modeOptions} />
          </Form.Item>

          <Form.Item label="严格模式" name="strict_mode" valuePropName="checked" extra="开启后更严格地判定为不安全">
            <FeatureSwitch featureId="ai-review-strict" />
          </Form.Item>

          <Card title="引擎配置" size="small" style={{ marginBottom: 16 }}>
            {renderProviderConfig()}
          </Card>

          <Form.Item label="启用审核日志" name="log_enabled" valuePropName="checked">
            <FeatureSwitch featureId="ai-review-log" />
          </Form.Item>

          {formData?.log_enabled && (
            <Form.Item label="日志保留条数" name="log_max_entries">
              <Input type="number" min={100} max={5000} />
            </Form.Item>
          )}

          <Form.Item label=" " colon={false}>
            <Button type="primary" onClick={handleTest} loading={testing}>
              测试审核引擎
            </Button>
          </Form.Item>

          {testResult && (
            <Card title="测试结果" size="small">
              <Space direction="vertical">
                <div>当前引擎：{testResult.provider}</div>
                <div>
                  审核结果：
                  <Tag color={testResult.result?.is_safe ? "green" : "red"}>
                    {testResult.result?.is_safe ? "安全" : "不安全"}
                  </Tag>
                </div>
                <div>置信度：{(testResult.result?.confidence * 100).toFixed(0)}%</div>
                <div>原因：{testResult.result?.reason}</div>
              </Space>
            </Card>
          )}
        </>
      )}
    </Form>
  );
};

export default App;
