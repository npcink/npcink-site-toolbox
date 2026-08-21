import React, { useContext, useState, useEffect } from "react";
import { Form, Button, Select, Modal, Typography, Alert } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow, RiskNotice, CheckTable, StatusTag } from "@/components/settings-ui";
import FeatureSwitch from "@/basic/feature-switch";
import { DbCleanType, DbPreview, DbStats, performanceApi } from "@/api";
import { __, sprintf } from "@/tool/i18n";

const { Text } = Typography;
const fromConfig = AntConfig.from;
const cleanupLabels: Record<DbCleanType, string> = {
  revisions: __("文章修订版本"),
  drafts: __("自动草稿"),
  spam: __("垃圾评论"),
  transients: __("过期临时选项"),
  optimize: __("数据库表优化"),
  pending: __("待审核文章"),
  trash: __("回收站文章"),
};

interface StatsRow {
  key: string;
  name: string;
  statusLabel: "正常" | "待处理";
  valueLabel: string;
  type?: DbCleanType;
  noAction?: boolean;
}

interface OperationFeedback {
  type: "info" | "success" | "warning" | "error";
  message: string;
}

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.db_clean || {};
  const [formData, setFormData] = useState(publicData || {});
  const [stats, setStats] = useState<DbStats | null>(null);
  const [previewData, setPreviewData] = useState<Partial<Record<DbCleanType, DbPreview>>>({});
  const [previewLoadingType, setPreviewLoadingType] = useState<DbCleanType | null>(null);
  const [cleanLoadingType, setCleanLoadingType] = useState<DbCleanType | null>(null);
  const [operationFeedback, setOperationFeedback] = useState<OperationFeedback | null>(null);

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "db_clean", formData);
  }, [formData]);

  const fetchStats = async (clearFeedback = true): Promise<boolean> => {
    if (clearFeedback) setOperationFeedback(null);
    try {
      const res = await performanceApi.getDbStats();
      if (res.success && res.data) {
        setStats(res.data);
        return true;
      }
      setOperationFeedback({ type: "error", message: __("统计获取失败，请重试。") });
    } catch {
      setOperationFeedback({ type: "error", message: __("统计请求失败，请重试。") });
    }
    return false;
  };

  const handlePreview = async (type: DbCleanType) => {
    setOperationFeedback(null);
    setPreviewLoadingType(type);
    try {
      const res = await performanceApi.previewDb(type);
      if (res.success && res.data) {
        setPreviewData((prev) => ({ ...prev, [type]: res.data }));
        setOperationFeedback({
          type: "info",
          message: sprintf(__("预览完成：预计影响 %d 条数据。"), getAffectedCount(type, res.data)),
        });
      } else {
        setOperationFeedback({ type: "error", message: __("预览失败，请重试。") });
      }
    } catch {
      setOperationFeedback({ type: "error", message: __("预览请求失败，请重试。") });
    } finally {
      setPreviewLoadingType(null);
    }
  };

  const getAffectedCount = (type: DbCleanType, data?: DbPreview): number => {
    if (!data) return 0;
    const typeCount = data[type as keyof DbPreview];
    return data.affected ?? data.table_count ?? (typeof typeCount === "number" ? typeCount : 0);
  };

  const handleClean = (type: DbCleanType) => {
    const preview = previewData[type];
    if (!preview) {
      setOperationFeedback({ type: "warning", message: __("请先预览该清理项目，再确认执行。") });
      return;
    }
    const affectedCount = getAffectedCount(type, preview);
    Modal.confirm({
      rootClassName: "mabox-admin-modal",
      title: __("确认执行数据库清理？"),
      icon: <ExclamationCircleOutlined />,
      content: (
        <div>
          <Alert
            message={__("此操作不可逆")}
            description={__("删除的数据无法恢复，请确保已备份数据库。")}
            type="error"
            showIcon
            style={{ marginBottom: 8 }}
          />
          <Text type="secondary">
            {sprintf(__("清理项目：%1$s。预计影响 %2$d 条数据。"), cleanupLabels[type], affectedCount)}
          </Text>
        </div>
      ),
      okText: __("确认清理"),
      okButtonProps: { danger: true },
      cancelText: __("取消"),
      onOk: async () => {
        setOperationFeedback(null);
        setCleanLoadingType(type);
        try {
          const res = await performanceApi.cleanDb(type, preview.preview_token);
          if (!res.success) {
            setOperationFeedback({ type: "error", message: __("清理失败，请重试。") });
            return;
          }

          const deleted = res.data?.deleted || 0;
          setPreviewData((prev) => {
            const next = { ...prev };
            delete next[type];
            return next;
          });
          const refreshed = await fetchStats(false);
          setOperationFeedback(refreshed
            ? { type: "success", message: sprintf(__("清理完成，删除 %d 条数据。"), deleted) }
            : {
                type: "warning",
                message: sprintf(__("清理完成，删除 %d 条数据；统计刷新失败，请重新查看统计。"), deleted),
              });
        } catch (error) {
          const requestError = error as {
            response?: { data?: { message?: string } };
          };
          const message = requestError.response?.data?.message;
          if (message?.includes("重新预览") || message?.includes("预览已失效")) {
            setPreviewData((prev) => {
              const next = { ...prev };
              delete next[type];
              return next;
            });
            setOperationFeedback({ type: "warning", message });
          } else {
            setOperationFeedback({ type: "error", message: __("清理失败，请重试。") });
          }
        } finally {
          setCleanLoadingType(null);
        }
      },
    });
  };

  const statsColumns = [
    { title: __("检测项"), dataIndex: "name", key: "name", width: 120 },
    {
      title: __("状态"),
      key: "status",
      width: 80,
      render: (_: unknown, record: StatsRow) => <StatusTag status={record.statusLabel} />,
    },
    {
      title: __("值"),
      key: "value",
      width: 80,
      render: (_: unknown, record: StatsRow) => record.valueLabel,
    },
    {
      title: __("操作"),
      key: "action",
      width: 160,
      render: (_: unknown, record: StatsRow) => {
        if (record.noAction || !record.type) return null;
        const type = record.type;
        return (
          <span>
            <Button
              size="small"
              onClick={() => handlePreview(type)}
              loading={previewLoadingType === type}
              disabled={previewLoadingType !== null && previewLoadingType !== type}
            >
              {__("预览")}
            </Button>
            <Button size="small" style={{ marginLeft: 4 }} onClick={() => handleClean(type)} loading={cleanLoadingType === type} disabled={!previewData[type]}>{__("清理")}</Button>
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
        { key: "db", name: __("数据库大小"), statusLabel: "正常" as const, valueLabel: formatSize(stats.db_size), noAction: true },
        { key: "revisions", name: __("修订版本"), statusLabel: stats.revisions > 0 ? "待处理" as const : "正常" as const, valueLabel: sprintf(__("%d 条"), stats.revisions), type: "revisions" },
        { key: "drafts", name: __("自动草稿"), statusLabel: stats.drafts > 0 ? "待处理" as const : "正常" as const, valueLabel: sprintf(__("%d 条"), stats.drafts), type: "drafts" },
        { key: "spam", name: __("垃圾评论"), statusLabel: stats.spam > 0 ? "待处理" as const : "正常" as const, valueLabel: sprintf(__("%d 条"), stats.spam), type: "spam" },
        { key: "transients", name: "Transient", statusLabel: stats.transients > 0 ? "待处理" as const : "正常" as const, valueLabel: sprintf(__("%d 条"), stats.transients), type: "transients" },
      ]
    : [];

  return (
    <SettingsSection title={__("数据库清理")} description={__("数据库清理与优化")}>
      <Form
        name="db_clean"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <RiskNotice warning={__("数据库清理操作不可逆，删除的数据无法恢复")} suggestion={__("执行前务必先预览影响数量，并做好备份")} />

        <ModuleRow
          title={__("启用数据库清理")}
          featureId="performance-db_clean-enabled"
          enabled={!!formData.enabled}
          onChange={(checked) => {
            setFormData((prev: any) => ({ ...prev, enabled: checked }));
          }}
          tags={["高风险", "不可逆"]}
        />

        <Form.Item label={__("清理修订版本")} name="clean_revisions" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_revisions" label={__("清理修订版本")} />
        </Form.Item>
        <Form.Item label={__("清理自动草稿")} name="clean_drafts" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_drafts" label={__("清理自动草稿")} />
        </Form.Item>
        <Form.Item label={__("清理垃圾评论")} name="clean_spam_comments" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_spam_comments" label={__("清理垃圾评论")} />
        </Form.Item>
        <Form.Item label={__("清理过期 Transient")} name="clean_transients" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-clean_transients" label={__("清理过期 Transient")} />
        </Form.Item>

        <Form.Item label={__("定时自动清理")} name="auto_clean" valuePropName="checked">
          <FeatureSwitch featureId="performance-db_clean-auto_clean" label={__("定时自动清理")} />
        </Form.Item>
        {formData.auto_clean && (
          <Form.Item label={__("清理周期")} name="auto_clean_schedule">
            <Select options={[
              { label: __("每天"), value: "daily" },
              { label: __("每周"), value: "weekly" },
              { label: __("每月"), value: "monthly" },
            ]} />
          </Form.Item>
        )}

        <Form.Item wrapperCol={fromConfig.wrapperCol}>
          <Button onClick={() => void fetchStats()}>{__("查看统计")}</Button>
        </Form.Item>

        {statsDataSource.length > 0 && (
          <CheckTable columns={statsColumns} dataSource={statsDataSource} />
        )}
        {operationFeedback && (
          <Alert
            showIcon
            type={operationFeedback.type}
            role={operationFeedback.type === "error" ? "alert" : "status"}
            message={operationFeedback.message}
            style={{ marginTop: 12 }}
          />
        )}
      </Form>
    </SettingsSection>
  );
};

export default App;
