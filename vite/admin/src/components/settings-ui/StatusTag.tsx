import React from "react";
import { Tag } from "antd";

type StatusType =
  | "推荐"
  | "安全"
  | "SEO"
  | "性能"
  | "谨慎"
  | "高风险"
  | "异常"
  | "待处理"
  | "已配置"
  | "未配置"
  | "仅前台"
  | "仅后台"
  | "需主题兼容"
  | "数据敏感"
  | "不可逆"
  | "经典编辑器"
  | "正常"
  | "已通过"
  | "已拒绝"
  | "待复核";

export type { StatusType };

interface StatusTagProps {
  status: StatusType;
  className?: string;
}

const statusColorMap: Record<StatusType, string> = {
  "推荐": "blue",
  "安全": "green",
  "SEO": "purple",
  "性能": "cyan",
  "谨慎": "orange",
  "高风险": "volcano",
  "异常": "red",
  "待处理": "orange",
  "已配置": "green",
  "未配置": "default",
  "仅前台": "geekblue",
  "仅后台": "lime",
  "需主题兼容": "magenta",
  "数据敏感": "gold",
  "不可逆": "red",
  "经典编辑器": "default",
  "正常": "green",
  "已通过": "green",
  "已拒绝": "red",
  "待复核": "orange",
};

const StatusTag: React.FC<StatusTagProps> = ({ status, className }) => {
  return (
    <Tag
      color={statusColorMap[status] || "default"}
      className={className}
      style={{ margin: 0, fontSize: 11 }}
    >
      {status}
    </Tag>
  );
};

export default StatusTag;
