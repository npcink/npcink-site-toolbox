import React from "react";
import { Button } from "antd";
import FeatureSwitch from "@/basic/feature-switch";
import Preview from "@/basic/preview";
import StatusTag, { type StatusType } from "./StatusTag";

interface ModuleCardProps {
  title: string;
  description: string;
  featureId: string;
  enabled?: boolean;
  onChange?: (checked: boolean) => void;
  tags?: StatusType[];
  preview?: { title: string; img: string };
  onDetails?: () => void;
  children?: React.ReactNode;
  className?: string;
  switchable?: boolean;
  actionLabel?: string;
  onAction?: () => void;
  actionLoading?: boolean;
  aliases?: string[];
}

const ModuleCard: React.FC<ModuleCardProps> = ({
  title,
  description,
  featureId,
  enabled,
  onChange,
  tags,
  preview,
  onDetails,
  children,
  className,
  switchable = true,
  actionLabel,
  onAction,
  actionLoading,
  aliases,
}) => {
  const aliasAttr = aliases?.length
    ? { "data-search-aliases": aliases.join(" ") }
    : {};

  return (
    <div className={`mabox-module-card ${className || ""}`} id={featureId} {...aliasAttr}>
      <div className="mabox-module-card-header">
        <div className="mabox-module-card-info">
          <div className="mabox-module-card-title-row">
            <span className="mabox-module-card-title">{title}</span>
            {tags?.map((tag) => (
              <StatusTag key={tag} status={tag} />
            ))}
          </div>
          <div className="mabox-module-card-desc">{description}</div>
        </div>
        <div className="mabox-module-card-actions">
          {switchable && (
            <FeatureSwitch featureId={featureId} checked={enabled} onChange={onChange} />
          )}
          {actionLabel && onAction && (
            <Button size="small" type="primary" onClick={onAction} loading={actionLoading}>
              {actionLabel}
            </Button>
          )}
          {preview && <Preview title={preview.title} img={preview.img} />}
          {onDetails && (
            <button className="mabox-module-card-details-btn" onClick={onDetails}>
              详情
            </button>
          )}
        </div>
      </div>
      {switchable && enabled && children && (
        <div className="mabox-module-card-body">{children}</div>
      )}
    </div>
  );
};

export default ModuleCard;
