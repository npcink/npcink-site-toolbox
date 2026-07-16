import React from "react";
import FeatureSwitch from "@/basic/feature-switch";
import Preview from "@/basic/preview";
import StatusTag, { type StatusType } from "./StatusTag";

interface ModuleRowProps {
  title: string;
  description?: string;
  featureId: string;
  enabled: boolean;
  onChange: (checked: boolean) => void;
  tags?: StatusType[];
  preview?: { title: string; img: string };
  onDetails?: () => void;
  children?: React.ReactNode;
  className?: string;
  aliases?: string[];
}

const ModuleRow: React.FC<ModuleRowProps> = ({
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
  aliases,
}) => {
  const aliasAttr = aliases?.length
    ? { "data-search-aliases": aliases.join(" ") }
    : {};

  return (
    <div className={`mabox-module-row ${className || ""}`} id={featureId} {...aliasAttr}>
      <div className="mabox-module-row-header">
        <div className="mabox-module-row-info">
          <div className="mabox-module-row-title-row">
            <span className="mabox-module-row-title">{title}</span>
            {tags?.map((tag) => (
              <StatusTag key={tag} status={tag} />
            ))}
          </div>
          {description && (
            <div className="mabox-module-row-desc">{description}</div>
          )}
        </div>
        <div className="mabox-module-row-actions">
          <FeatureSwitch featureId={featureId} label={title} checked={enabled} onChange={onChange} />
          {preview && <Preview title={preview.title} img={preview.img} />}
          {onDetails && (
            <button className="mabox-module-row-details-btn" onClick={onDetails}>
              详情
            </button>
          )}
        </div>
      </div>
      {enabled && children && (
        <div className="mabox-module-row-body">{children}</div>
      )}
    </div>
  );
};

export default ModuleRow;
