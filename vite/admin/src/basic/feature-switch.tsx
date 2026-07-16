import React, { useCallback, useEffect, useState } from "react";
import { Switch } from "antd";
import type { SwitchProps } from "antd";
import { StarOutlined, StarFilled } from "@ant-design/icons";
import { isFavorite, toggleFavorite } from "@/tool/favorites";
import { checkRiskyFeature } from "@/tool/riskyFeature";

interface FeatureSwitchProps extends Omit<SwitchProps, "onChange"> {
  featureId: string;
  label: string;
  onChange?: SwitchProps["onChange"];
}

const FeatureSwitch: React.FC<FeatureSwitchProps> = ({ featureId, label, onChange, checked, ...restProps }) => {
  const [favorited, setFavorited] = useState(() => isFavorite(featureId));

  useEffect(() => {
    setFavorited(isFavorite(featureId));
  }, [featureId]);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    e.preventDefault();
    setFavorited(toggleFavorite(featureId));
  };

  const handleChange = useCallback<NonNullable<SwitchProps["onChange"]>>((nextChecked, event) => {
    const shouldProceed = checkRiskyFeature(featureId, nextChecked, () => {
      onChange?.(nextChecked, event);
    });
    if (shouldProceed) {
      onChange?.(nextChecked, event);
    }
  }, [featureId, onChange]);

  const handleHitboxClick = (event: React.MouseEvent<HTMLSpanElement>) => {
    if (event.target !== event.currentTarget) return;
    event.currentTarget.querySelector<HTMLButtonElement>(".ant-switch")?.click();
  };

  return (
    <div className="mabox-feature-switch">
      <span className="mabox-feature-switch-control" onClick={handleHitboxClick}>
        <Switch
          {...restProps}
          aria-label={label}
          checked={checked}
          onChange={handleChange}
        />
      </span>
      <button
        type="button"
        aria-label={`${favorited ? "取消收藏" : "加入常用功能"}：${label}`}
        aria-pressed={favorited}
        className="mabox-favorite-action"
        onClick={handleFavoriteClick}
        title={favorited ? "从常用功能中移除" : "加入常用功能"}
      >
        {favorited
          ? <StarFilled aria-hidden="true" />
          : <StarOutlined aria-hidden="true" />}
      </button>
    </div>
  );
};

export default FeatureSwitch;
