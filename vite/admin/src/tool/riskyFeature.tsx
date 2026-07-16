import { Modal } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { getUiSchemaSync, fetchUiSchema, hasFetchedUiSchemaSync } from "@/tool/uiSchema";
import { RiskInfo } from "@/tool/interface";

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
      return normalizeRiskInfo(entry.risk);
    }
    for (const [, val] of Object.entries(schema)) {
      if (val.feature_id === featureId && val.risk) {
        return normalizeRiskInfo(val.risk);
      }
    }
  }
  return null;
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
    if (!hasFetchedUiSchemaSync()) {
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
