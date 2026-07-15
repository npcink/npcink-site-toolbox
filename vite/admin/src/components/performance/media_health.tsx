import React, { useContext, useState, useEffect } from "react";
import { Form, Button, message } from "antd";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow, CheckTable } from "@/components/settings-ui";
import StatusTag from "@/components/settings-ui/StatusTag";
import { MediaHealthIssue, performanceApi } from "@/api";

const fromConfig = AntConfig.from;

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.media_health || {};
  const [formData, setFormData] = useState(publicData || {});
  const [issues, setIssues] = useState<MediaHealthIssue[]>([]);
  const [checking, setChecking] = useState(false);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "media_health", formData);
  }, [formData]);

  const handleCheck = async () => {
    setChecking(true);
    try {
      const res = await performanceApi.checkMedia();
      if (res.success) setIssues(res.data?.issues || []);
    } catch {
      message.error("检查失败");
    } finally {
      setChecking(false);
    }
  };

  const handleFixAlt = async () => {
    try {
      const res = await performanceApi.fixMediaAlt();
      if (res.success) message.success("已补全 " + (res.data?.fixed || 0) + " 张图片的 Alt");
    } catch {
      message.error("修复失败");
    }
  };

  const columns = [
    {
      title: "检测项",
      dataIndex: "type",
      key: "type",
      width: 120,
    },
    {
      title: "状态",
      dataIndex: "severity",
      key: "severity",
      width: 80,
      render: (severity: string) => {
        if (severity === "error") return <StatusTag status="异常" />;
        return <StatusTag status="待处理" />;
      },
    },
    {
      title: "数量",
      dataIndex: "count",
      key: "count",
      width: 80,
      render: (count: number) => `${count} 个`,
    },
  ];

  const dataSource = issues.map((item, i) => ({
    key: String(i),
    type: item.type,
    severity: item.severity || "warning",
    count: item.count || 0,
  }));

  return (
    <SettingsSection title="媒体库体检" description="媒体库健康体检">
      <Form
        name="media_health"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title="启用媒体库体检"
          description="检查媒体库中的异常文件"
          featureId="performance-media_health-enabled"
          enabled={!!formData.enabled}
          onChange={(checked) => {
            setFormData((prev: any) => ({ ...prev, enabled: checked }));
          }}
        />

        <Form.Item wrapperCol={fromConfig.wrapperCol}>
          <Button type="primary" onClick={handleCheck} loading={checking}>
            开始体检
          </Button>
          <Button style={{ marginLeft: 8 }} onClick={handleFixAlt}>
            批量补全 Alt
          </Button>
        </Form.Item>

        {issues.length > 0 && (
          <CheckTable columns={columns} dataSource={dataSource} />
        )}
      </Form>
    </SettingsSection>
  );
};

export default App;
