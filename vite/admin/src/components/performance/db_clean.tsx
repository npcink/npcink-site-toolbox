import React, { useContext, useState, useEffect } from "react";
import { Form, Button, Select, message, Modal, Typography, Alert } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow, RiskNotice, CheckTable, StatusTag } from "@/components/settings-ui";
import FeatureSwitch from "@/basic/feature-switch";
import { DbCleanType, DbPreview, DbStats, performanceApi } from "@/api";

const { Text } = Typography;
const fromConfig = AntConfig.from;

interface StatsRow {
  key: string;
  name: string;
  statusLabel: "正常" | "待处理";
  valueLabel: string;
  type: DbCleanType;
  noAction?: boolean;
}

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.db_clean || {};
  const [formData, setFormData] = useState(publicData || {});
  const [stats, setStats] = useState<DbStats | null>(null);
  const [previewData, setPreviewData] = useState<Partial<Record<DbCleanType, DbPreview>>>({});
  const [previewLoading, setPreviewLoading] = useState(false);
  const [cleanLoadingType, setCleanLoadingType] = useState<DbCleanType | null>(null);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "db_clean", formData);
  }, [formData]);

  const fetchStats = async () => {
    try {
      const res = await performanceApi.getDbStats();
      if (res.success && res.data) setStats(res.data);
    } catch {
      message.error("统计请求失败");
    }
  };

  const handlePreview = async (type: DbCleanType) => {
    setPreviewLoading(true);
    try {
      const res = await performanceApi.previewDb(type);
      if (res.success && res.data) {
        setPreviewData((prev) => ({ ...prev, [type]: res.data }));
      } else {
        message.error("预览失败");
      }
    } catch {
      message.error("预览请求失败");
    } finally {
      setPreviewLoading(false);
    }
  };

  const getAffectedCount = (type: DbCleanType, data?: DbPreview): number => {
    if (!data) return 0;
    if (type === "all") return data.total || 0;
    const typeCount = data[type as keyof DbPreview];
    return data.affected ?? (typeof typeCount === "number" ? typeCount : 0);
  };

  const handleClean = (type: DbCleanType) => {
    const preview = previewData[type];
    const affectedCount = getAffectedCount(type, preview);
    Modal.confirm({
      rootClassName: "mabox-admin-modal",
      title: "确认执行数据库清理？",
      icon: <ExclamationCircleOutlined />,
      content: (
        <div>
          <Alert
            message="此操作不可逆"
            description="删除的数据无法恢复，请确保已备份数据库。"
            type="error"
            showIcon
            style={{ marginBottom: 8 }}
          />
          <Text type="secondary">
            清理类型：{type}。将删除 {affectedCount} 条数据。
          </Text>
        </div>
      ),
      okText: "确认清理",
      okButtonProps: { danger: true },
      cancelText: "取消",
      onOk: () => {
        return new Promise<void>((resolve) => {
          setCleanLoadingType(type);
          performanceApi.cleanDb(type, false)
            .then((res) => {
              setCleanLoadingType(null);
              if (res.success) {
                message.success("清理完成" + (res?.data?.deleted ? "，删除 " + res.data.deleted + " 条" : ""));
                setPreviewData((prev) => {
                  const next = { ...prev };
                  delete next[type];
                  return next;
                });
                fetchStats();
              }
              resolve();
            })
            .catch(() => {
              setCleanLoadingType(null);
              message.error("清理失败");
              resolve();
            });
        });
      },
    });
  };

  const statsColumns = [
    { title: "检测项", dataIndex: "name", key: "name", width: 120 },
    {
      title: "状态",
      key: "status",
      width: 80,
      render: (_: unknown, record: StatsRow) => <StatusTag status={record.statusLabel} />,
    },
    {
      title: "值",
      key: "value",
      width: 80,
      render: (_: unknown, record: StatsRow) => record.valueLabel,
    },
    {
      title: "操作",
      key: "action",
      width: 160,
      render: (_: unknown, record: StatsRow) => {
        if (record.noAction) return null;
        return (
          <span>
            <Button size="small" onClick={() => handlePreview(record.type)} loading={previewLoading}>预览</Button>
            <Button size="small" style={{ marginLeft: 4 }} onClick={() => handleClean(record.type)} loading={cleanLoadingType === record.type} disabled={!previewData[record.type]}>清理</Button>
          </span>
        );
      },
    },
  ];

  const formatSize = (bytes: number | string): string => {
    if (typeof bytes === "string") return bytes;
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  const statsDataSource: StatsRow[] = stats?.db_size
    ? [
        { key: "db", name: "数据库大小", statusLabel: "正常" as const, valueLabel: formatSize(stats.db_size), type: "all", noAction: true },
        { key: "revisions", name: "修订版本", statusLabel: stats.revisions > 0 ? "待处理" as const : "正常" as const, valueLabel: `${stats.revisions} 条`, type: "revisions" },
        { key: "drafts", name: "自动草稿", statusLabel: stats.drafts > 0 ? "待处理" as const : "正常" as const, valueLabel: `${stats.drafts} 条`, type: "drafts" },
        { key: "spam", name: "垃圾评论", statusLabel: stats.spam > 0 ? "待处理" as const : "正常" as const, valueLabel: `${stats.spam} 条`, type: "spam" },
        { key: "transients", name: "Transient", statusLabel: stats.transients > 0 ? "待处理" as const : "正常" as const, valueLabel: `${stats.transients} 条`, type: "transients" },
      ]
    : [];

  return (
    <SettingsSection title="数据库清理" description="数据库清理与优化">
      <Form
        name="db_clean"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <RiskNotice warning="数据库清理操作不可逆，删除的数据无法恢复" suggestion="执行前务必先预览影响数量，并做好备份" />

        <ModuleRow
          title="启用数据库清理"
          featureId="performance-db_clean-enabled"
          enabled={!!formData.enabled}
          onChange={(checked) => {
            setFormData((prev: any) => ({ ...prev, enabled: checked }));
          }}
          tags={["高风险", "不可逆"]}
        />

        <Form.Item label="清理修订版本" name="clean_revisions" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_revisions" />
        </Form.Item>
        <Form.Item label="清理自动草稿" name="clean_drafts" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_drafts" />
        </Form.Item>
        <Form.Item label="清理垃圾评论" name="clean_spam_comments" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_spam_comments" />
        </Form.Item>
        <Form.Item label="清理过期 Transient" name="clean_transients" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_transients" />
        </Form.Item>

        <Form.Item label="定时自动清理" name="auto_clean" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-auto_clean" />
        </Form.Item>
        {formData.auto_clean && (
          <Form.Item label="清理周期" name="auto_clean_schedule">
            <Select options={[
              { label: "每天", value: "daily" },
              { label: "每周", value: "weekly" },
              { label: "每月", value: "monthly" },
            ]} />
          </Form.Item>
        )}

        <Form.Item wrapperCol={fromConfig.wrapperCol}>
          <Button onClick={fetchStats}>查看统计</Button>
          <Button
            type="primary"
            danger
            style={{ marginLeft: 8 }}
            onClick={() => handlePreview("all")}
            loading={previewLoading}
          >
            预览清理
          </Button>
          <Button
            danger
            style={{ marginLeft: 8 }}
            onClick={() => handleClean("all")}
            loading={cleanLoadingType === "all"}
            disabled={!previewData["all"]}
          >
            执行清理
          </Button>
        </Form.Item>

        {previewData["all"] && (
          <Form.Item wrapperCol={fromConfig.wrapperCol}>
            <Alert
              message="预览结果"
              description={`将清理 ${getAffectedCount("all", previewData["all"])} 条数据`}
              type="warning"
              showIcon
              closable
              onClose={() => setPreviewData((prev) => { const next = { ...prev }; delete next.all; return next; })}
            />
          </Form.Item>
        )}

        {statsDataSource.length > 0 && (
          <CheckTable columns={statsColumns} dataSource={statsDataSource} />
        )}
      </Form>
    </SettingsSection>
  );
};

export default App;
