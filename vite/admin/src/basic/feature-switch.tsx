import React, { useCallback } from "react";
import { Switch } from "antd";
import { StarOutlined, StarFilled } from "@ant-design/icons";
import { isFavorite, toggleFavorite } from "@/tool/favorites";
import { checkRiskyFeature } from "@/tool/riskyFeature";

interface FeatureSwitchProps {
  featureId: string;
  [key: string]: any;
}

const FeatureSwitch: React.FC<FeatureSwitchProps> = ({ featureId, onChange, ...restProps }) => {
  const favorited = isFavorite(featureId);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    e.preventDefault();
    toggleFavorite(featureId);
  };

  const handleChange = useCallback((checked: boolean, event: any) => {
    const shouldProceed = checkRiskyFeature(featureId, checked, () => {
      onChange?.(checked, event);
    });
    if (shouldProceed) {
      onChange?.(checked, event);
    }
  }, [featureId, onChange]);

  return (
    <div style={{ display: "inline-flex", alignItems: "center", gap: 8 }}>
      <Switch {...restProps} onChange={handleChange} />
      <span
        onClick={handleFavoriteClick}
        style={{
          cursor: "pointer",
          fontSize: 16,
          color: favorited ? "#faad14" : "#d9d9d9",
          transition: "color 0.2s",
          lineHeight: 1,
          display: "inline-flex",
          alignItems: "center",
        }}
        title={favorited ? "从常用功能中移除" : "加入常用功能"}
      >
        {favorited ? <StarFilled /> : <StarOutlined />}
      </span>
    </div>
  );
};

export default FeatureSwitch;