import React from "react";
import { Modal, List, Tag, Space, Typography } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { ConfigDiffItem } from "@/tool/interface";

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
  if (value === undefined || value === null) return "未设置";
  if (typeof value === "boolean") return value ? "开启" : "关闭";
  if (typeof value === "string") {
    if (value === "") return "空";
    if (value === "false") return "关闭";
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
  title = "确认保存以下更改？",
  confirmText = "确认保存",
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
          {title}
        </span>
      }
      open={visible}
      onOk={onConfirm}
      onCancel={onCancel}
      okText={confirmText}
      cancelText="取消"
      okButtonProps={{ danger: highRiskCount > 0 }}
      width={600}
    >
      <Space direction="vertical" className="mabox-full-width" style={{ marginTop: 8 }}>
        {highRiskCount > 0 && (
          <Text type="danger">
            检测到 {highRiskCount} 项高风险功能将被开启，请谨慎确认。
          </Text>
        )}
        <List
          size="small"
          bordered
          dataSource={diffs}
          renderItem={(item) => (
            <List.Item>
              <div className="mabox-full-width">
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                  <Text strong>
                    {item.label}
                    {item.riskLevel === "high" && (
                      <Tag color="red" style={{ marginLeft: 8, fontSize: 11 }}>
                        高风险
                      </Tag>
                    )}
                  </Text>
                  <Text type="secondary" style={{ fontSize: 12 }}>
                    {formatValue(item.before)} <span style={{ color: "#999" }}>→</span>{" "}
                    <Text style={{ color: item.riskLevel === "high" ? "#f5222d" : "#52c41a" }}>
                      {formatValue(item.after)}
                    </Text>
                  </Text>
                </div>
                <Text type="secondary" style={{ fontSize: 11 }}>
                  {item.path}
                </Text>
              </div>
            </List.Item>
          )}
          locale={{ emptyText: "暂无变更" }}
        />
      </Space>
    </Modal>
  );
};

export default DiffModal;
