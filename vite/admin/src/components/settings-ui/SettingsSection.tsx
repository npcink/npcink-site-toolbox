import React from "react";
import { __ } from "@/tool/i18n";

interface SettingsSectionProps {
  title: string;
  description?: string;
  children: React.ReactNode;
  className?: string;
  id?: string;
}

const SettingsSection: React.FC<SettingsSectionProps> = ({
  title,
  description,
  children,
  className,
  id,
}) => {
  return (
    <div className={`mabox-section ${className || ""}`} id={id}>
      <div className="mabox-section-header">
        <h2 className="mabox-section-title">{__(title)}</h2>
        {description && (
          <p className="mabox-section-desc">{__(description)}</p>
        )}
      </div>
      <div className="mabox-section-body">{children}</div>
    </div>
  );
};

export default SettingsSection;
