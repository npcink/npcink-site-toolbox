import React, { useContext } from "react";
import { Button, Form, Input, Space, Tag, Typography } from "antd";

import { DataContext } from "@/tool/dataContext";
import { SecretPath } from "@/tool/interface";

interface SecretFieldProps {
  label: string;
  path: SecretPath;
}

const SecretField: React.FC<SecretFieldProps> = ({ label, path }) => {
  const { secretStatus, secretChanges, setSecretChange } = useContext(DataContext);
  const configured = secretStatus[path].configured;
  const draft = secretChanges[path];
  const replacement = draft?.operation === "replace" ? draft.value : "";

  const statusLabel = draft?.operation === "replace"
    ? "将替换"
    : draft?.operation === "clear"
      ? "将清除"
      : configured
        ? "已配置"
        : "未配置";

  const statusColor = draft?.operation === "clear"
    ? "error"
    : draft?.operation === "replace"
      ? "processing"
      : configured
        ? "success"
        : "default";

  return (
    <Form.Item label={label}>
      <Space direction="vertical" size={6} style={{ width: "100%" }}>
        <Space wrap>
          <Tag color={statusColor}>{statusLabel}</Tag>
          <Typography.Text type="secondary">
            {configured ? "已保存的值不会显示；留空表示保留。" : "尚未保存凭据。"}
          </Typography.Text>
        </Space>
        <Input.Password
          aria-label={`${label}新值`}
          autoComplete="new-password"
          value={replacement}
          placeholder={configured ? "输入新值以替换" : "输入新值"}
          onChange={(event) => {
            const value = event.target.value;
            setSecretChange(path, value ? { operation: "replace", value } : undefined);
          }}
        />
        <Space>
          <Button
            danger
            size="small"
            disabled={!configured}
            onClick={() => setSecretChange(path, { operation: "clear" })}
          >
            清除已保存凭据
          </Button>
          {draft && (
            <Button size="small" onClick={() => setSecretChange(path)}>
              撤销凭据更改
            </Button>
          )}
        </Space>
      </Space>
    </Form.Item>
  );
};

export default SecretField;
