import { useContext, useMemo, useRef, useState } from "react";
import { DataContext } from "@/tool/dataContext";
import { saveOption } from "@/axios/save";
import { diffConfig, diffSecretChanges } from "@/tool/diff";
import { loadDiffModal } from "@/tool/diffModalLoader";
import { ConfigDiffItem } from "@/tool/interface";
import { notice } from "@/tool/notice";
import { t } from "@/tool/i18n";

type DiffModalComponent = Awaited<ReturnType<typeof loadDiffModal>>["default"];

interface SaveFeedback {
  kind: "warning" | "error";
  message: string;
}

const App: React.FC = () => {
  const {
    optionData,
    refreshOption,
    lastSavedOption,
    secretStatus,
    secretChanges,
    clearSecretChanges,
    settingsState,
    settingsRevision,
  } = useContext(DataContext);
  const [saving, setSaving] = useState(false);
  const [preparingConfirmation, setPreparingConfirmation] = useState(false);
  const [diffVisible, setDiffVisible] = useState(false);
  const [diffs, setDiffs] = useState<ConfigDiffItem[]>([]);
  const [LoadedDiffModal, setLoadedDiffModal] = useState<DiffModalComponent | null>(null);
  const [saveFeedback, setSaveFeedback] = useState<SaveFeedback | null>(null);
  const statusRef = useRef<HTMLSpanElement>(null);
  const changes = useMemo(
    () => [
      ...diffConfig(lastSavedOption, optionData),
      ...diffSecretChanges(secretStatus, secretChanges),
    ],
    [lastSavedOption, optionData, secretChanges, secretStatus],
  );
  const changeCount = changes.length;

  const doSave = async () => {
    setSaveFeedback(null);
    setSaving(true);
    let saved = false;
    try {
      const response = await saveOption(optionData, secretChanges, settingsRevision || "");
      saved = true;
      clearSecretChanges();
      await refreshOption();
      setSaveFeedback(null);
      notice.success(response.message || t("保存成功"));
    } catch (error) {
      if (saved) {
        setSaveFeedback({
          kind: "warning",
          message: t("设置已保存，但重新读取失败；保存功能已禁用，请重新读取后继续"),
        });
      } else {
        setSaveFeedback({
          kind: "error",
          message: error instanceof Error && error.message ? error.message : t("保存失败，请重试"),
        });
      }
    } finally {
      setSaving(false);
    }
  };

  const handleSaveClick = async () => {
    if (changes.length === 0) return;

    setSaveFeedback(null);
    setDiffs(changes);
    if (LoadedDiffModal) {
      setDiffVisible(true);
      return;
    }

    setPreparingConfirmation(true);
    try {
      const module = await loadDiffModal();
      setLoadedDiffModal(() => module.default);
      setDiffVisible(true);
    } catch {
      setSaveFeedback({
        kind: "error",
        message: t("保存确认界面加载失败，请重试"),
      });
    } finally {
      setPreparingConfirmation(false);
    }
  };

  const handleConfirmSave = () => {
    setDiffVisible(false);
    void doSave();
    window.requestAnimationFrame(() => {
      statusRef.current?.focus({ preventScroll: true });
    });
  };

  let statusText = t("已保存");
  let buttonText = t("保存");
  let statusKind = "saved";

  if (saving) {
    statusText = t("正在保存…");
    buttonText = t("正在保存…");
    statusKind = "saving";
  } else if (preparingConfirmation) {
    statusText = t("正在准备确认…");
    buttonText = t("正在准备…");
    statusKind = "loading";
  } else if (settingsState === "loading") {
    statusText = t("正在读取设置…");
    statusKind = "loading";
  } else if (settingsState === "error") {
    statusText = t("设置不可用");
    statusKind = "error";
  } else if (changeCount > 0) {
    statusText = `${changeCount} ${t("项待保存")}`;
    buttonText = t("查看并保存");
    statusKind = "pending";
  }

  const saveDisabled = saving || preparingConfirmation || settingsState !== "ready" || changeCount === 0;

  return (
    <div className={`mabox-save-trust mabox-save-trust--${statusKind}`}>
      {saveFeedback && (
        <div
          className={`mabox-save-feedback mabox-save-feedback--${saveFeedback.kind}`}
          role={saveFeedback.kind === "error" ? "alert" : "status"}
          aria-live={saveFeedback.kind === "error" ? "assertive" : "polite"}
          aria-atomic="true"
        >
          {saveFeedback.message}
        </div>
      )}
      <span
        ref={statusRef}
        className="mabox-save-status"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        tabIndex={-1}
      >
        {statusText}
      </span>
      <button
        type="button"
        className="mabox-save-action"
        onClick={handleSaveClick}
        disabled={saveDisabled}
        aria-busy={saving || preparingConfirmation || undefined}
      >
        {(saving || preparingConfirmation) && (
          <span className="mabox-save-action-spinner" aria-hidden="true" />
        )}
        <span>{buttonText}</span>
      </button>
      {LoadedDiffModal && (
        <LoadedDiffModal
          visible={diffVisible}
          diffs={diffs}
          onConfirm={handleConfirmSave}
          onCancel={() => setDiffVisible(false)}
        />
      )}
    </div>
  );
};

export default App;
