import React, { useContext, useState, useEffect, useRef } from "react";
import { Alert, Form, Button, Modal, Progress, Select } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { DataContext } from "@/tool/dataContext";
import { AntConfig } from "@/tool/tool";
import { SettingsSection, ModuleRow, CheckTable } from "@/components/settings-ui";
import StatusTag from "@/components/settings-ui/StatusTag";
import { MediaHealthIssue, MediaWebpAssessment, performanceApi } from "@/api";
import { __, sprintf } from "@/tool/i18n";

import "./media-health.css";

const fromConfig = AntConfig.from;

interface OperationFeedback {
  type: "success" | "error" | "info" | "warning";
  message: string;
}

interface BatchProgressState {
  mode: "convert" | "restore";
  target: number;
  processed: number;
  succeeded: number;
  skipped: number;
  failed: number;
}

const continuousTargetOptions = [10, 20, 50].map((value) => ({
  value,
  label: sprintf(__("最多 %d 张"), value),
}));

const formatBytes = (bytes: number) => {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const describeSampleChange = (percent: number | null) => {
  if (percent === null) return __("未取得有效体积数据");
  if (percent < 0) return sprintf(__("体积增加 %s%%"), Math.abs(percent));
  return sprintf(__("预计节省 %s%%"), percent);
};

const recommendationCopy: Record<MediaWebpAssessment["sample"]["recommendation"], string> = {
  unsupported: __("当前服务器不支持生成 WebP，暂不应启用转换。"),
  no_candidates: __("未发现 JPEG 候选，无需安排转换。"),
  cleanup_failed: __("临时样本未能完整清理，请先排查文件权限。"),
  insufficient_sample: __("可用样本不足，暂不据此决定批量转换。"),
  sample_failed: __("样本转换失败，请先排查图像处理环境。"),
  low_savings: __("样本体积没有明显下降，不建议批量转换。"),
  consider_batch: __("候选规模和样本收益均达到阈值，可连续分批转换。"),
  below_scale: __("样本有收益但候选规模较小；如有明确需要，可连续分批转换。"),
};

const recommendationStatus = (assessment: MediaWebpAssessment) => {
  switch (assessment.sample.recommendation) {
    case "cleanup_failed":
    case "sample_failed":
    case "unsupported":
      return <StatusTag status="异常" label={__("异常")} />;
    case "insufficient_sample":
    case "low_savings":
      return <StatusTag status="待复核" label={__("待复核")} />;
    case "consider_batch":
      return <StatusTag status="待处理" label={__("待处理")} />;
    default:
      return <StatusTag status="正常" label={__("正常")} />;
  }
};

const App: React.FC = () => {
  const { optionData, updateOption } = useContext(DataContext);
  const publicData = optionData.performance?.media_health || {};
  const [formData, setFormData] = useState(publicData || {});
  const [issues, setIssues] = useState<MediaHealthIssue[]>([]);
  const [webpAssessment, setWebpAssessment] = useState<MediaWebpAssessment | null>(null);
  const [checking, setChecking] = useState(false);
  const [converting, setConverting] = useState(false);
  const [restoring, setRestoring] = useState(false);
  const [continuousTarget, setContinuousTarget] = useState(20);
  const [batchProgress, setBatchProgress] = useState<BatchProgressState | null>(null);
  const [stopRequested, setStopRequested] = useState(false);
  const [lastBatchIds, setLastBatchIds] = useState<number[]>([]);
  const [operationFeedback, setOperationFeedback] = useState<OperationFeedback | null>(null);
  const stopRequestedRef = useRef(false);
  const persistentRestoreIds = webpAssessment?.batch.restorable_ids || [];
  const restoreIds = lastBatchIds.length > 0 ? lastBatchIds : persistentRestoreIds;
  const canConvert = ["consider_batch", "below_scale"].includes(
    webpAssessment?.sample.recommendation || "",
  )
    && (webpAssessment?.batch.candidate_ids.length || 0) > 0;

  const onValuesChange = (changedValues: any, _allValues: any) => {
    setFormData((prev: any) => ({ ...prev, ...changedValues }));
  };

  useEffect(() => {
    updateOption("performance", "media_health", formData);
  }, [formData]);

  const refreshHealth = async (announce = true) => {
    if (announce) setOperationFeedback(null);
    setChecking(true);
    try {
      const res = await performanceApi.checkMedia();
      if (res.success) {
        const nextIssues = res.data?.issues || [];
        setIssues(nextIssues);
        setWebpAssessment(res.data?.webp_assessment || null);
        if (announce) {
          setOperationFeedback({
            type: "info",
            message: nextIssues.length > 0
              ? sprintf(__("体检完成：发现 %d 类问题。"), nextIssues.length)
              : __("体检完成：未发现需要处理的问题。"),
          });
        }
        return true;
      } else {
        setWebpAssessment(null);
        if (announce) setOperationFeedback({ type: "error", message: __("体检失败，请重试。") });
      }
    } catch {
      setWebpAssessment(null);
      if (announce) setOperationFeedback({ type: "error", message: __("体检失败，请重试。") });
    } finally {
      setChecking(false);
    }
    return false;
  };

  const handleCheck = () => refreshHealth(true);

  const convertContinuous = async () => {
    const queue = (webpAssessment?.batch.candidate_ids || []).slice(0, continuousTarget);
    const serverBatchSize = webpAssessment?.batch.batch_size || 5;
    if (queue.length < 1) return;

    let processed = 0;
    let converted = 0;
    let skipped = 0;
    let failed = 0;
    let requestFailed = false;
    let convertedIds: number[] = [];
    setOperationFeedback(null);
    setConverting(true);
    setStopRequested(false);
    stopRequestedRef.current = false;
    setBatchProgress({ mode: "convert", target: queue.length, processed: 0, succeeded: 0, skipped: 0, failed: 0 });
    try {
      for (let offset = 0; offset < queue.length; offset += serverBatchSize) {
        if (stopRequestedRef.current) break;
        const ids = queue.slice(offset, offset + serverBatchSize);
        let res;
        try {
          res = await performanceApi.convertMediaWebp(ids);
        } catch {
          requestFailed = true;
          break;
        }
        if (!res.success || !res.data) {
          requestFailed = true;
          break;
        }

        const currentConvertedIds = res.data.results
          .filter((item) => item.status === "converted")
          .map((item) => item.attachment_id);
        convertedIds = [...convertedIds, ...currentConvertedIds];
        processed += res.data.processed || ids.length;
        converted += res.data.converted || 0;
        skipped += res.data.skipped || 0;
        failed += res.data.failed || 0;
        setLastBatchIds([...convertedIds]);
        setBatchProgress({
          mode: "convert",
          target: queue.length,
          processed,
          succeeded: converted,
          skipped,
          failed,
        });
        if (failed > 0) break;
      }

      await refreshHealth(false);
      const stopped = stopRequestedRef.current;
      const type = failed > 0 || requestFailed ? "warning" : stopped ? "info" : "success";
      const prefix = failed > 0 || requestFailed
        ? __("连续转换已停止")
        : stopped
          ? __("已按要求停止")
          : __("本次连续转换已完成");
      setOperationFeedback({
        type,
        message: sprintf(
          __("%1$s%2$s：已转换 %3$d/%4$d 张，跳过 %5$d 张，失败 %6$d 张；原 JPEG 已保留。"),
          prefix,
          requestFailed ? __("（请求失败）") : "",
          converted,
          queue.length,
          skipped,
          failed,
        ),
      });
    } finally {
      setConverting(false);
      setStopRequested(false);
      stopRequestedRef.current = false;
    }
  };

  const handleConvertWebp = () => {
    const count = Math.min(
      continuousTarget,
      webpAssessment?.batch.candidate_ids.length || 0,
    );
    if (count < 1) return;
    Modal.confirm({
      rootClassName: "mabox-admin-modal",
      title: sprintf(__("连续转换最多 %d 张 JPEG？"), count),
      icon: <ExclamationCircleOutlined />,
      content: __("浏览器会依次提交每批最多 5 张，页面需要保持打开；可在当前小批完成后停止。原 JPEG 与恢复记录会保留，若已启用对象存储，WebP 会按现有配置同步。"),
      okText: __("开始连续转换"),
      cancelText: __("取消"),
      onOk: convertContinuous,
    });
  };

  const restoreConversionRun = async () => {
    const queue = [...restoreIds];
    const serverBatchSize = webpAssessment?.batch.batch_size || 5;
    if (queue.length < 1) return;

    let remainingIds = [...queue];
    let processed = 0;
    let restored = 0;
    let skipped = 0;
    let failed = 0;
    let requestFailed = false;
    setOperationFeedback(null);
    setRestoring(true);
    setStopRequested(false);
    stopRequestedRef.current = false;
    setBatchProgress({ mode: "restore", target: queue.length, processed: 0, succeeded: 0, skipped: 0, failed: 0 });
    try {
      for (let offset = 0; offset < queue.length; offset += serverBatchSize) {
        if (stopRequestedRef.current) break;
        const ids = queue.slice(offset, offset + serverBatchSize);
        let res;
        try {
          res = await performanceApi.restoreMediaWebp(ids);
        } catch {
          requestFailed = true;
          break;
        }
        if (!res.success || !res.data) {
          requestFailed = true;
          break;
        }

        const completedIds = res.data.results
          .filter((item) => item.status === "restored" || item.status === "skipped")
          .map((item) => item.attachment_id);
        remainingIds = remainingIds.filter((id) => !completedIds.includes(id));
        processed += res.data.processed || ids.length;
        restored += res.data.restored || 0;
        skipped += res.data.skipped || 0;
        failed += res.data.failed || 0;
        setLastBatchIds([...remainingIds]);
        setBatchProgress({
          mode: "restore",
          target: queue.length,
          processed,
          succeeded: restored,
          skipped,
          failed,
        });
        if (failed > 0) break;
      }

      await refreshHealth(false);
      const stopped = stopRequestedRef.current;
      const type = failed > 0 || requestFailed ? "warning" : stopped ? "info" : "success";
      const prefix = failed > 0 || requestFailed
        ? __("连续恢复已停止")
        : stopped
          ? __("已按要求停止恢复")
          : __("本次转换记录已恢复");
      setOperationFeedback({
        type,
        message: sprintf(
          __("%1$s%2$s：已恢复 %3$d/%4$d 张，跳过 %5$d 张，失败 %6$d 张。"),
          prefix,
          requestFailed ? __("（请求失败）") : "",
          restored,
          queue.length,
          skipped,
          failed,
        ),
      });
    } finally {
      setRestoring(false);
      setStopRequested(false);
      stopRequestedRef.current = false;
    }
  };

  const handleRestoreWebp = () => {
    if (restoreIds.length < 1) return;
    Modal.confirm({
      rootClassName: "mabox-admin-modal",
      title: sprintf(__("恢复本次转换的 %d 张图片？"), restoreIds.length),
      icon: <ExclamationCircleOutlined />,
      content: __("附件会重新指向原 JPEG，并清理本次生成的本地 WebP 文件；对象存储中的无引用副本不会自动删除。"),
      okText: __("开始连续恢复"),
      cancelText: __("取消"),
      onOk: restoreConversionRun,
    });
  };

  const requestStop = () => {
    stopRequestedRef.current = true;
    setStopRequested(true);
  };

  const columns = [
    {
      title: __("检测项"),
      dataIndex: "type",
      key: "type",
      width: 120,
    },
    {
      title: __("状态"),
      dataIndex: "severity",
      key: "severity",
      width: 80,
      render: (severity: string) => {
        if (severity === "error") return <StatusTag status="异常" label={__("异常")} />;
        return <StatusTag status="待处理" label={__("待处理")} />;
      },
    },
    {
      title: __("数量"),
      dataIndex: "count",
      key: "count",
      width: 80,
      render: (count: number) => sprintf(__("%d 个"), count),
    },
  ];

  const dataSource = issues.map((item, i) => ({
    key: String(i),
    type: item.type,
    severity: item.severity || "warning",
    count: item.count || 0,
  }));

  return (
    <SettingsSection title={__("媒体库体检")} description={__("媒体库健康体检")}>
      <Form
        name="media_health"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onValuesChange={onValuesChange}
      >
        <ModuleRow
          title={__("启用媒体库体检")}
          description={__("检查媒体库中的异常文件")}
          featureId="performance-media_health-enabled"
          enabled={!!formData.enabled}
          onChange={(checked) => {
            setFormData((prev: any) => ({ ...prev, enabled: checked }));
          }}
        />

        <Form.Item wrapperCol={fromConfig.wrapperCol}>
          <Button type="primary" onClick={handleCheck} loading={checking} disabled={converting || restoring}>
            {__("开始体检")}
          </Button>
        </Form.Item>

        {operationFeedback && (
          <Alert
            showIcon
            type={operationFeedback.type}
            role={operationFeedback.type === "error" ? "alert" : "status"}
            message={operationFeedback.message}
            style={{ marginBottom: 12 }}
          />
        )}

        {issues.length > 0 && (
          <CheckTable columns={columns} dataSource={dataSource} />
        )}

        {webpAssessment && (
          <section
            className="mabox-webp-assessment"
            aria-labelledby="mabox-webp-assessment-title"
          >
            <div className="mabox-webp-assessment__heading">
              <h3 id="mabox-webp-assessment-title">{__("WebP 转换预检")}</h3>
              {recommendationStatus(webpAssessment)}
            </div>
            <div className="mabox-webp-assessment__metrics">
              <div>
                <span>{__("JPEG 候选")}</span>
                <strong>{sprintf(__("%d 张"), webpAssessment.formats.jpeg.count)}</strong>
                <small>{formatBytes(webpAssessment.formats.jpeg.bytes)}</small>
              </div>
              <div>
                <span>{__("PNG 观察")}</span>
                <strong>{sprintf(__("%d 张"), webpAssessment.formats.png.count)}</strong>
                <small>{formatBytes(webpAssessment.formats.png.bytes)}</small>
              </div>
              <div>
                <span>{__("已有 WebP")}</span>
                <strong>{sprintf(__("%d 张"), webpAssessment.formats.webp.count)}</strong>
                <small>{formatBytes(webpAssessment.formats.webp.bytes)}</small>
              </div>
            </div>
            <p className="mabox-webp-assessment__result">
              {recommendationCopy[webpAssessment.sample.recommendation]}
            </p>
            <p className="mabox-webp-assessment__meta">
              {webpAssessment.sample.successful > 0
                ? sprintf(
                    __("临时转换 %1$d/%2$d 张；%3$s。"),
                    webpAssessment.sample.successful,
                    webpAssessment.sample.attempted,
                    describeSampleChange(webpAssessment.sample.savings_percent),
                  )
                : sprintf(__("本次检查了 %d 张图片，未形成可用转换样本。"), webpAssessment.checked)}
              {webpAssessment.sampled ? ` ${__("结果来自最近附件抽样。")}` : ""}
              {webpAssessment.missing_files > 0 ? ` ${sprintf(__("%d 个文件不可读。"), webpAssessment.missing_files)}` : ""}
            </p>
            {(canConvert || restoreIds.length > 0) && (
              <div className="mabox-webp-assessment__batch-area">
                {canConvert && (
                  <div className="mabox-webp-assessment__target">
                    <span>{__("本次计划")}</span>
                    <Select
                      aria-label={__("本次连续转换数量")}
                      value={continuousTarget}
                      options={continuousTargetOptions}
                      onChange={setContinuousTarget}
                      disabled={checking || converting || restoring}
                    />
                  </div>
                )}
                <div className="mabox-webp-assessment__actions">
                  {canConvert && (
                    <Button
                      type="primary"
                      onClick={handleConvertWebp}
                      loading={converting}
                      disabled={checking || restoring}
                    >
                      {sprintf(__("连续转换（最多 %d 张）"), Math.min(continuousTarget, webpAssessment.batch.candidate_ids.length))}
                    </Button>
                  )}
                  {restoreIds.length > 0 && (
                    <Button
                      onClick={handleRestoreWebp}
                      loading={restoring}
                      disabled={checking || converting}
                    >
                      {lastBatchIds.length > 0
                        ? sprintf(__("恢复本次转换（%d 张）"), lastBatchIds.length)
                        : sprintf(__("恢复一批（%d 张）"), persistentRestoreIds.length)}
                    </Button>
                  )}
                  {(converting || restoring) && (
                    <Button onClick={requestStop} disabled={stopRequested}>
                      {stopRequested ? __("将在当前小批完成后停止") : __("完成当前小批后停止")}
                    </Button>
                  )}
                </div>
              </div>
            )}
            {batchProgress && (
              <div className="mabox-webp-assessment__progress">
                <div>
                  <strong>{batchProgress.mode === "convert" ? __("转换进度") : __("恢复进度")}</strong>
                  <span>{sprintf(__("%1$d/%2$d 张"), batchProgress.processed, batchProgress.target)}</span>
                </div>
                <Progress
                  percent={Math.min(100, Math.round((batchProgress.processed / batchProgress.target) * 100))}
                  status={batchProgress.failed > 0 ? "exception" : undefined}
                  size="small"
                  aria-label={batchProgress.mode === "convert" ? __("连续转换进度") : __("连续恢复进度")}
                />
              </div>
            )}
            <p className="mabox-webp-assessment__notice">
              {sprintf(
                __("预检本身只读；连续操作仍按每批最多 %d 张依次提交，页面关闭后不会继续。仅转换 JPEG，原图不会删除或覆盖。"),
                webpAssessment.batch.batch_size,
              )}
            </p>
          </section>
        )}
      </Form>
    </SettingsSection>
  );
};

export default App;
