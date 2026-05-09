import { Modal } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";

const RISKY_FEATURES: Record<string, { title: string; warning: string; suggestion: string }> = {
  "page-jurisdiction-ban_copy": {
    title: "禁止复制",
    warning: "此功能可能影响正常用户复制内容，导致用户无法复制文章中的代码或引用。",
    suggestion: "内容站、教程站谨慎开启。",
  },
  "page-feature-background_effect": {
    title: "背景特效",
    warning: "此功能可能消耗较多系统资源，影响页面加载速度和用户体验。",
    suggestion: "性能敏感站点建议关闭。",
  },
  "page-feature-particle": {
    title: "点击特效",
    warning: "此功能会加载额外的 JS/CSS 资源，可能影响页面性能。",
    suggestion: "移动端体验可能下降。",
  },
  "page-feature-site_grey": {
    title: "全站变灰",
    warning: "此功能会将整个网站变为灰色，仅适合特殊纪念日使用。",
    suggestion: "非特殊时间建议关闭。",
  },
  "page-function-top_ad": {
    title: "顶部广告位",
    warning: "此功能允许插入自定义广告代码，请注意代码安全性。",
    suggestion: "确保广告代码来源可信，避免 XSS 风险。",
  },
  "optimize-medium-no_auto_size": {
    title: "禁止缩略图",
    warning: "此功能可能与部分主题不兼容，导致图片显示异常。",
    suggestion: "开启前请确认主题支持。",
  },
  "page-feature-lantern": {
    title: "灯笼效果",
    warning: "此功能会加载额外资源，影响页面性能。",
    suggestion: "仅在特殊节日短期开启。",
  },
  "page-feature-screen_hair": {
    title: "屏幕上的毛",
    warning: "此功能会在页面上添加一根毛发装饰，可能分散用户注意力。",
    suggestion: "正式商业站点建议关闭。",
  },
  "page-feature-pixel_chicken": {
    title: "像素小鸡",
    warning: "此功能会在页脚添加动画元素，可能影响页面性能。",
    suggestion: "移动端不显示，但性能敏感站点仍需谨慎。",
  },
};

const STORAGE_KEY = "mabox_risky_dismissed";

function getDismissedFeatures(): string[] {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch (e) {
    return [];
  }
  return [];
}

function addDismissedFeature(featureId: string) {
  try {
    const dismissed = getDismissedFeatures();
    if (!dismissed.includes(featureId)) {
      dismissed.push(featureId);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed));
    }
  } catch (e) {
    console.error("记录不再提示失败", e);
  }
}

export function checkRiskyFeature(
  featureId: string,
  newValue: unknown,
  onConfirm: () => void
): boolean {
  const riskInfo = RISKY_FEATURES[featureId];

  if (!riskInfo) {
    return true;
  }

  const isEnabled = typeof newValue === "boolean" ? newValue : newValue !== "false" && newValue !== "";
  if (!isEnabled) {
    return true;
  }

  const dismissed = getDismissedFeatures();
  if (dismissed.includes(featureId)) {
    return true;
  }

  Modal.confirm({
    title: `您正在开启「${riskInfo.title}」`,
    icon: <ExclamationCircleOutlined style={{ color: "#faad14" }} />,
    content: (
      <div style={{ marginTop: 8 }}>
        <p style={{ color: "#f5222d", marginBottom: 8 }}>⚠️ {riskInfo.warning}</p>
        <p style={{ color: "#666" }}>建议：{riskInfo.suggestion}</p>
      </div>
    ),
    okText: "确认开启",
    cancelText: "取消",
    onOk: () => {
      onConfirm();
    },
    footer: (_, { OkBtn, CancelBtn }) => (
      <>
        <CancelBtn />
        <OkBtn />
        <a
          style={{ marginLeft: 8, fontSize: 12, color: "#999" }}
          onClick={() => {
            addDismissedFeature(featureId);
            onConfirm();
            Modal.destroyAll();
          }}
        >
          不再提示
        </a>
      </>
    ),
  });

  return false;
}
