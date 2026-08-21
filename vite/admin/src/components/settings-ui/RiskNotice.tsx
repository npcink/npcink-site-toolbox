import React from "react";
import { Alert } from "antd";
import { __ } from "@/tool/i18n";

interface RiskNoticeProps {
  title?: string;
  warning: string;
  suggestion?: string;
  className?: string;
}

const RiskNotice: React.FC<RiskNoticeProps> = ({
  title,
  warning,
  suggestion,
  className,
}) => {
  return (
    <Alert
      type="warning"
      showIcon
      className={className}
      message={title || __("风险提示")}
      description={
        <div>
          <p style={{ marginBottom: suggestion ? 4 : 0 }}>{warning}</p>
          {suggestion && (
            <p style={{ color: "#666" }}>{__("建议：")} {suggestion}</p>
          )}
        </div>
      }
      style={{ marginBottom: 12 }}
    />
  );
};

export default RiskNotice;
