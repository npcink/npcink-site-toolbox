import { Modal } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { getUiSchemaSync, fetchUiSchema } from "@/tool/uiSchema";
import { RiskInfo } from "@/tool/interface";

const RISKY_FEATURES: Record<string, { title: string; warning: string; suggestion: string; noDismiss?: boolean }> = {
  "optimize-medium-no_auto_size": {
    title: "禁止缩略图",
    warning: "此功能可能与部分主题不兼容，导致图片显示异常。",
    suggestion: "开启前请确认主题支持。",
  },

  "performance-db_clean-enabled": {
    title: "数据库清理",
    warning: "数据库清理操作不可逆，删除的数据无法恢复。",
    suggestion: "执行前务必先预览影响数量，并做好备份。",
    noDismiss: true,
  },

  "optimize-medium-medium_add_svg": {
    title: "SVG 上传支持",
    warning: "SVG 文件可能包含恶意脚本，已做安全过滤但仍需注意。",
    suggestion: "仅允许可信用户上传 SVG 文件。",
  },
  "domestic-login_security-custom_login_enabled": {
    title: "自定义登录地址",
    warning: "修改登录地址后，原 wp-login.php 将被重定向，配置错误可能导致无法登录。",
    suggestion: "记住新的登录地址，避免锁定自己。",
    noDismiss: true,
  },
  "domestic-login_security-ip_lock_enabled": {
    title: "IP 锁定",
    warning: "IP 锁定可能在反向代理环境下误判，导致正常用户被锁定。",
    suggestion: "如使用 CDN 或反向代理，请配置可信代理 IP。",
  },
};

const STORAGE_KEY = "mabox_risky_dismissed";

function resolveRiskInfoSync(featureId: string): { title: string; warning: string; suggestion: string; noDismiss?: boolean } | null {
  const schema = getUiSchemaSync();
  if (schema) {
    const entry = schema[featureId];
    if (entry?.risk) {
      const r = entry.risk as RiskInfo;
      return {
        title: r.title,
        warning: r.warning,
        suggestion: r.suggestion,
        noDismiss: r.noDismiss,
      };
    }
    for (const [, val] of Object.entries(schema)) {
      if (val.feature_id === featureId && val.risk) {
        const r = val.risk as RiskInfo;
        return {
          title: r.title,
          warning: r.warning,
          suggestion: r.suggestion,
          noDismiss: r.noDismiss,
        };
      }
    }
  }
  return RISKY_FEATURES[featureId] || null;
}

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
  const riskInfo = resolveRiskInfoSync(featureId);

  if (!riskInfo) {
    if (!getUiSchemaSync()) {
      fetchUiSchema().then(() => {
        const afterFetch = resolveRiskInfoSync(featureId);
        if (afterFetch) {
          showRiskConfirm(featureId, afterFetch, newValue, onConfirm);
        } else {
          onConfirm();
        }
      });
      return false;
    }
    return true;
  }

  return showRiskConfirm(featureId, riskInfo, newValue, onConfirm);
}

function showRiskConfirm(
  featureId: string,
  riskInfo: { title: string; warning: string; suggestion: string; noDismiss?: boolean },
  newValue: unknown,
  onConfirm: () => void
): boolean {
  const isEnabled = typeof newValue === "boolean" ? newValue : newValue !== "false" && newValue !== "";
  if (!isEnabled) {
    return true;
  }

  const dismissed = getDismissedFeatures();
  if (!riskInfo.noDismiss && dismissed.includes(featureId)) {
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
    footer: riskInfo.noDismiss
      ? (_, { OkBtn, CancelBtn }) => (
          <>
            <CancelBtn />
            <OkBtn />
          </>
        )
      : (_, { OkBtn, CancelBtn }) => (
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
