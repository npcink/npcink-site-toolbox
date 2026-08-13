import React, { useContext, useState, useEffect } from "react";
import { Alert, Form, Button } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow, CheckTable } from "@/components/settings-ui";
import StatusTag from "@/components/settings-ui/StatusTag";
import { performanceApi, SeoIssue } from "@/api";
import { t } from "@/tool/i18n";

const fromConfig = AntConfig.from;

interface OperationFeedback {
  type: "success" | "error" | "info";
  message: string;
}

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.seo_checker || {};
  const [formData, setFormData] = useState(publicData || {});
  const [issues, setIssues] = useState<SeoIssue[]>([]);
  const [checking, setChecking] = useState(false);
  const [operationFeedback, setOperationFeedback] = useState<OperationFeedback | null>(null);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "seo_checker", formData);
  }, [formData]);

  const handleCheck = async () => {
    setOperationFeedback(null);
    setChecking(true);
    try {
      const res = await performanceApi.checkSeo();
      if (res.success) {
        const nextIssues = res.data?.issues || [];
        setIssues(nextIssues);
        setOperationFeedback({
          type: "info",
          message: nextIssues.length > 0
            ? `${t("检查完成：发现")} ${nextIssues.length} ${t("项需要关注")}。`
            : t("检查完成：未发现需要处理的问题。"),
        });
      } else {
        setOperationFeedback({ type: "error", message: t("检查失败，请重试。") });
      }
    } catch {
      setOperationFeedback({ type: "error", message: t("检查失败，请重试。") });
    } finally {
      setChecking(false);
    }
  };

  const columns = [
    {
      title: t("检测项"),
      dataIndex: "type",
      key: "type",
      width: 120,
    },
    {
      title: t("状态"),
      dataIndex: "severity",
      key: "severity",
      width: 80,
      render: (severity: string) => {
        if (severity === "error") return <StatusTag status="异常" label={t("异常")} />;
        if (severity === "warning") return <StatusTag status="待处理" label={t("待处理")} />;
        return <StatusTag status="推荐" label={t("推荐")} />;
      },
    },
    {
      title: t("说明"),
      dataIndex: "message",
      key: "message",
    },
  ];

  const dataSource = issues.map((item, i) => ({
    key: String(i),
    type: item.type,
    severity: item.severity || "warning",
    message: item.message,
  }));

  return (
    <SettingsSection title={t("SEO 检查助手")} description={t("SEO 健康度检查")}>
      <Form
        name="seo_checker"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title={t("启用 SEO 检查")}
          description={t("定期检查网站 SEO 健康度")}
          featureId="performance-seo_checker-enabled"
          enabled={!!formData.enabled}
          onChange={(checked) => {
            setFormData((prev: any) => ({ ...prev, enabled: checked }));
          }}
          tags={["SEO"]}
        />

        <Form.Item wrapperCol={fromConfig.wrapperCol}>
          <Button type="primary" onClick={handleCheck} loading={checking}>
            {t("开始检查")}
          </Button>
        </Form.Item>

        {operationFeedback && (
          <Alert
            showIcon
            type={operationFeedback.type}
            role={operationFeedback.type === "error" ? "alert" : "status"}
            message={operationFeedback.message}
            style={{ marginBottom: 12 }}
          />
        )}

        {issues.length > 0 && (
          <CheckTable columns={columns} dataSource={dataSource} />
        )}
      </Form>
    </SettingsSection>
  );
};

export default App;
