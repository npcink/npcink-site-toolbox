import React, { useContext, useState, useEffect } from "react";
import { feedbackApi } from "@/api";
import { Form, Input, Select, Button, message, Card, Statistic, Row, Col } from "antd";
import { SendOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

const { TextArea } = Input;
const fromConfig = AntConfig.from;

const feedbackTypes = [
  { label: "功能建议", value: "feature" },
  { label: "Bug 报告", value: "bug" },
  { label: "使用问题", value: "question" },
  { label: "其他", value: "other" },
];

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const data = optionData.feedback || {};
  const [formData, setFormData] = useState(data);
  const [activeTab, setActiveTab] = useState<"submit" | "settings" | "insights">("submit");
  const [submitting, setSubmitting] = useState(false);
  const [feedbackForm, setFeedbackForm] = useState({ subject: "", content: "", type: "feature" });
  const [insights, setInsights] = useState<any>(null);

  const onValuesChange = (changedValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("feedback", "", formData);
  }, [formData]);

  const handleSubmit = async () => {
    if (!feedbackForm.content.trim()) {
      message.error("反馈内容不能为空");
      return;
    }
    setSubmitting(true);
    try {
      const result = await feedbackApi.submit(feedbackForm);
      if (result.success) {
        message.success(result.message || "反馈已提交");
        setFeedbackForm({ subject: "", content: "", type: "feature" });
      } else {
        message.error(result.message || result.data?.error || "提交失败");
      }
    } catch {
      message.error("请求失败");
    } finally {
      setSubmitting(false);
    }
  };

  const loadInsights = async () => {
    try {
      const result = await feedbackApi.getInsights();
      if (result.success) {
        setInsights(result.data);
      } else {
        message.error(result.message || "获取数据失败");
      }
    } catch {
      message.error("请求失败");
    }
  };

  useEffect(() => {
    if (activeTab === "insights") loadInsights();
  }, [activeTab]);

  return (
    <>
      <div style={{ marginBottom: 16, display: "flex", gap: 8 }}>
        {[
          { key: "submit" as const, label: "提交反馈" },
          { key: "settings" as const, label: "反馈设置" },
          { key: "insights" as const, label: "数据洞察" },
        ].map((tab) => (
          <button
            key={tab.key}
            onClick={() => setActiveTab(tab.key)}
            style={{
              padding: "6px 16px",
              border: "none",
              borderRadius: 4,
              cursor: "pointer",
              background: activeTab === tab.key ? "#1677ff" : "#f0f0f0",
              color: activeTab === tab.key ? "#fff" : "#333",
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {activeTab === "submit" && (
        <Card title="提交反馈" extra="反馈将自动附带环境信息（WordPress 版本、PHP 版本、主题等）">
          <Form layout="vertical">
            <Form.Item label="反馈类型">
              <Select
                value={feedbackForm.type}
                onChange={(v) => setFeedbackForm({ ...feedbackForm, type: v })}
                options={feedbackTypes}
              />
            </Form.Item>
            <Form.Item label="主题（可选）">
              <Input
                value={feedbackForm.subject}
                onChange={(e) => setFeedbackForm({ ...feedbackForm, subject: e.target.value })}
                placeholder="简要描述反馈内容"
              />
            </Form.Item>
            <Form.Item label="详细内容" required>
              <TextArea
                rows={5}
                value={feedbackForm.content}
                onChange={(e) => setFeedbackForm({ ...feedbackForm, content: e.target.value })}
                placeholder="请详细描述您遇到的问题或建议..."
              />
            </Form.Item>
            <Form.Item>
              <Button type="primary" icon={<SendOutlined />} onClick={handleSubmit} loading={submitting}>
                提交反馈
              </Button>
            </Form.Item>
          </Form>
        </Card>
      )}

      {activeTab === "settings" && (
        <Form
          name="feedback_settings"
          labelCol={fromConfig.labelCol}
          wrapperCol={fromConfig.wrapperCol}
          style={{ maxWidth: fromConfig.maxWidth }}
          initialValues={data}
          autoComplete="off"
          onValuesChange={onValuesChange}
        >
          <Form.Item label="启用反馈功能" name="enabled" valuePropName="checked">
            <FeatureSwitch featureId="feedback-enabled" />
          </Form.Item>

          {formData?.enabled && (
            <>
              <Form.Item label="启用反馈表单" name="feedback_enabled" valuePropName="checked">
                <FeatureSwitch featureId="feedback-form" />
              </Form.Item>
              <Form.Item label="反馈接收邮箱" name="feedback_email">
                <Input placeholder="admin@example.com" />
              </Form.Item>
              <Form.Item label="自动回复" name="feedback_auto_reply">
                <TextArea rows={2} placeholder="感谢您的反馈..." />
              </Form.Item>

              <Card title="匿名数据统计" size="small" style={{ marginTop: 16 }}>
                <Form.Item label="启用数据收集" name="telemetry_enabled" valuePropName="checked" extra="收集功能开启率等匿名数据，帮助改进插件">
                  <FeatureSwitch featureId="feedback-telemetry" />
                </Form.Item>
                <Form.Item label="完全匿名" name="telemetry_anonymous" valuePropName="checked" extra="不收集任何个人信息">
                  <FeatureSwitch featureId="feedback-anonymous" />
                </Form.Item>
              </Card>
            </>
          )}
        </Form>
      )}

      {activeTab === "insights" && (
        <div>
          <Card title="数据洞察" extra="基于匿名统计数据">
            {insights ? (
              <Row gutter={[16, 16]}>
                <Col span={8}>
                  <Statistic title="反馈总数" value={Object.values(insights.feedback_stats || {}).reduce((a: number, b: any) => a + b, 0)} />
                </Col>
                <Col span={8}>
                  <Statistic title="功能开启率排行" value="Top 3" suffix="SEO / 评论 / 登录" />
                </Col>
                <Col span={8}>
                  <Statistic title="活跃用户" value={insights.estimated_users || 0} suffix="位站长" />
                </Col>
              </Row>
            ) : (
              <div style={{ textAlign: "center", padding: 24, color: "#999" }}>加载中...</div>
            )}
          </Card>
        </div>
      )}
    </>
  );
};

export default App;
