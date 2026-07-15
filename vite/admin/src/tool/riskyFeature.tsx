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
  "domestic-login_security-attempt_limit_enabled": {
    title: "登录尝试保护",
    warning: "可信代理配置错误可能让多个访客共享同一来源 IP，造成账号误锁。",
    suggestion: "确认开启后请在保存前核对可信代理；如发生误锁，可在 wp-config.php 中将 MABOX_DISABLE_LOGIN_PROTECTION 定义为 true 后恢复。",
  },
};

const STORAGE_KEY = "mabox_risky_dismissed";

function normalizeRiskInfo(risk: RiskInfo | undefined): { title: string; warning: string; suggestion: string; noDismiss?: boolean } | null {
  if (
    !risk
    || risk.level === "none"
    || typeof risk.title !== "string"
    || typeof risk.warning !== "string"
    || typeof risk.suggestion !== "string"
  ) {
    return null;
  }

  return {
    title: risk.title,
    warning: risk.warning,
    suggestion: risk.suggestion,
    noDismiss: risk.noDismiss,
  };
}

function resolveRiskInfoSync(featureId: string): { title: string; warning: string; suggestion: string; noDismiss?: boolean } | null {
  const schema = getUiSchemaSync();
  if (schema) {
    const entry = schema[featureId];
    if (entry?.risk) {
      return normalizeRiskInfo(entry.risk as RiskInfo);
    }
    for (const [, val] of Object.entries(schema)) {
      if (val.feature_id === featureId && val.risk) {
        return normalizeRiskInfo(val.risk as RiskInfo);
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
    rootClassName: "mabox-admin-modal",
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
