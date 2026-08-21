import React from "react";
import { Modal, List, Tag, Space, Typography } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { ConfigDiffItem } from "@/tool/interface";
import { __, sprintf } from "@/tool/i18n";

interface DiffModalProps {
  visible: boolean;
  diffs: ConfigDiffItem[];
  onConfirm: () => void;
  onCancel: () => void;
  title?: string;
  confirmText?: string;
}

const { Text } = Typography;

function formatValue(value: unknown): string {
  if (value === undefined || value === null) return __("未设置");
  if (typeof value === "boolean") return value ? __("开启") : __("关闭");
  if (typeof value === "string") {
    if (value === "") return __("空");
    if (value === "false") return __("关闭");
    return value;
  }
  if (typeof value === "number") return String(value);
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
}

const DiffModal: React.FC<DiffModalProps> = ({
  visible,
  diffs,
  onConfirm,
  onCancel,
  title = __("确认保存以下更改？"),
  confirmText = __("确认保存"),
}) => {
  const highRiskCount = diffs.filter((d) => d.riskLevel === "high").length;

  return (
    <Modal
      rootClassName="mabox-admin-modal"
      title={
        <span>
          {highRiskCount > 0 && (
            <ExclamationCircleOutlined style={{ color: "#f5222d", marginRight: 8 }} />
          )}
          {__(title)}
        </span>
      }
      open={visible}
      onOk={onConfirm}
      onCancel={onCancel}
      okText={confirmText}
      cancelText={__("取消")}
      okButtonProps={{ danger: highRiskCount > 0 }}
      width={600}
    >
      <Space direction="vertical" className="mabox-full-width" style={{ marginTop: 8 }}>
        {highRiskCount > 0 && (
          <Text type="danger">
            {sprintf(__("检测到 %d 项高风险功能将被开启，请谨慎确认。"), highRiskCount)}
          </Text>
        )}
        <List
          className="mabox-diff-list"
          size="small"
          bordered
          dataSource={diffs}
          renderItem={(item) => (
            <List.Item className="mabox-diff-list-item">
              <div className="mabox-diff-item">
                <div className="mabox-diff-item-heading">
                  <Text strong>{item.label}</Text>
                  {item.riskLevel === "high" && (
                    <Tag color="red" className="mabox-diff-risk-tag">
                      {__("高风险")}
                    </Tag>
                  )}
                </div>
                <div className="mabox-diff-values">
                  <span className="mabox-diff-value mabox-diff-value--before">
                    <span className="mabox-visually-hidden">{__("原值：")}</span>
                    {formatValue(item.before)}
                  </span>
                  <span className="mabox-diff-arrow" aria-hidden="true">→</span>
                  <span
                    className={`mabox-diff-value mabox-diff-value--after ${
                      item.riskLevel === "high" ? "mabox-diff-value--high-risk" : ""
                    }`}
                  >
                    <span className="mabox-visually-hidden">{__("新值：")}</span>
                    {formatValue(item.after)}
                  </span>
                </div>
              </div>
            </List.Item>
          )}
          locale={{ emptyText: __("暂无变更") }}
        />
      </Space>
    </Modal>
  );
};

export default DiffModal;
