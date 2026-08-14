import React, { useContext, useEffect, useRef, useState } from "react";
import { InfoCircleOutlined } from "@ant-design/icons";
import { Alert, Button, Form, Input, Popover, Select, Space, Typography } from "antd";
import { performanceApi } from "@/api";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type {
  PerformanceOss,
  SecretChange,
  SecretChanges,
} from "@/tool/interface";
import { AntConfig } from "@/tool/tool";
import { __, sprintf } from "@/tool/i18n";
import {
  DetailDrawer,
  ModuleCard,
  SecretField,
  SettingsSection,
} from "@/components/settings-ui";

const fromConfig = AntConfig.from;
const OSS_SECRET_PATHS = [
  "performance.oss.access_key",
  "performance.oss.secret_key",
] as const;
const SAMPLE_MEDIA_PATH = "YYYY/MM/example.jpg";
const CONNECTION_TEST_OBJECT = "npcink-site-toolbox/connection-test.txt";

const providerGuidance: Record<string, {
  bucketPlaceholder: string;
  bucketHelp: string;
}> = {
  aliyun: {
    bucketPlaceholder: __("示例：npcink-media"),
    bucketHelp: __("只填写 Bucket 名称；上传目录请填写在下一项。"),
  },
  tencent: {
    bucketPlaceholder: __("示例：npcink-media-1250000000"),
    bucketHelp: __("填写带 APPID 后缀的完整 Bucket 名称。"),
  },
  qiniu: {
    bucketPlaceholder: __("示例：npcink-media"),
    bucketHelp: __("填写七牛云空间名称，不要填写访问域名。"),
  },
};

interface InfoButtonProps {
  label: string;
  title: string;
  children: React.ReactNode;
}

const InfoButton: React.FC<InfoButtonProps> = ({ label, title, children }) => (
  <Popover
    trigger="click"
    placement="bottomRight"
    title={title}
    content={<div className="mabox-oss-popover">{children}</div>}
  >
    <Button
      type="text"
      size="small"
      className="mabox-oss-info-button"
      aria-label={label}
      icon={<InfoCircleOutlined />}
    />
  </Popover>
);

interface ConnectionNotice {
  type: "success" | "error";
  message: string;
  summary: string;
  details?: string;
}

const isSecretConfigured = (configured: boolean, change?: SecretChange): boolean => {
  if (change?.operation === "replace") return change.value.trim() !== "";
  if (change?.operation === "clear") return false;
  return configured;
};

const trimSlashes = (value: string): string => value.trim().replace(/^\/+|\/+$/g, "");

const prefixObjectKey = (path: string, objectKey: string): string => {
  const normalizedPath = trimSlashes(path);
  return normalizedPath ? `${normalizedPath}/${objectKey}` : objectKey;
};

const normalizeAliyunEndpointPreview = (value: string): string => {
  let endpoint = value.trim().toLowerCase().replace(/^https?:\/\//, "").replace(/\/$/, "");
  if (endpoint && !endpoint.includes(".")) {
    endpoint = endpoint.startsWith("oss-") ? endpoint : `oss-${endpoint}`;
    endpoint = `${endpoint}.aliyuncs.com`;
  }
  return endpoint;
};

const App: React.FC = () => {
  const {
    optionData,
    updateOption,
    secretStatus,
    secretChanges,
  } = useContext(DataContext);
  const publicData = optionData.performance?.oss || defaultVarOption.performance.oss;
  const [formData, setFormData] = useState<PerformanceOss>(publicData);
  const formDataRef = useRef<PerformanceOss>(publicData);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [testing, setTesting] = useState(false);
  const [connectionNotice, setConnectionNotice] = useState<ConnectionNotice | null>(null);
  const [connectionDetailsOpen, setConnectionDetailsOpen] = useState(false);

  const onValuesChange = (changedValues: Partial<PerformanceOss>) => {
    const nextFormData = { ...formDataRef.current, ...changedValues };
    formDataRef.current = nextFormData;
    setFormData(nextFormData);
    setConnectionNotice(null);
    setConnectionDetailsOpen(false);
    updateOption("performance", "oss", nextFormData);
  };

  const accessKeyChange = secretChanges["performance.oss.access_key"];
  const secretKeyChange = secretChanges["performance.oss.secret_key"];
  useEffect(() => {
    setConnectionNotice(null);
    setConnectionDetailsOpen(false);
  }, [accessKeyChange, secretKeyChange]);

  const accessKeyConfigured = isSecretConfigured(
    secretStatus["performance.oss.access_key"].configured,
    secretChanges["performance.oss.access_key"],
  );
  const secretKeyConfigured = isSecretConfigured(
    secretStatus["performance.oss.secret_key"].configured,
    secretChanges["performance.oss.secret_key"],
  );
  const providerTargetConfigured = formData.provider === "aliyun"
    ? Boolean(formData.endpoint.trim())
    : formData.provider === "tencent"
      ? Boolean(formData.region.trim())
      : formData.provider === "qiniu";
  const writeTargetConfigured = Boolean(
    formData.provider.trim()
    && formData.bucket.trim()
    && providerTargetConfigured,
  );
  const credentialsConfigured = accessKeyConfigured && secretKeyConfigured;
  const storageTargetConfigured = writeTargetConfigured && Boolean(formData.domain.trim());
  const configurationStatus = storageTargetConfigured && accessKeyConfigured && secretKeyConfigured
    ? "已配置" as const
    : "未配置" as const;
  const connectionTestReady = writeTargetConfigured && credentialsConfigured;
  const guidance = providerGuidance[formData.provider] || providerGuidance.aliyun;
  const objectPreview = prefixObjectKey(formData.path, SAMPLE_MEDIA_PATH);
  const testObjectPreview = prefixObjectKey(formData.path, CONNECTION_TEST_OBJECT);
  const bucketPreview = formData.bucket.trim() || "{Bucket}";
  const storageScheme = formData.provider === "tencent"
    ? "cos"
    : formData.provider === "qiniu"
      ? "kodo"
      : "oss";
  const storagePreview = `${storageScheme}://${bucketPreview}/${objectPreview}`;
  const publicBase = formData.domain.trim().replace(/\/+$/, "");
  const publicPreview = publicBase ? `${publicBase}/${SAMPLE_MEDIA_PATH}` : __("尚未填写公开访问地址");
  const requestHostPreview = formData.provider === "aliyun"
    ? `${bucketPreview}.${normalizeAliyunEndpointPreview(formData.endpoint) || "{Endpoint}"}`
    : formData.provider === "tencent"
      ? `${bucketPreview}.cos.${formData.region.trim() || "{Region}"}.myqcloud.com`
      : "up.qiniup.com";

  const testConnection = async () => {
    const ossSecretChanges: SecretChanges = {};
    OSS_SECRET_PATHS.forEach((path) => {
      if (secretChanges[path]) {
        ossSecretChanges[path] = secretChanges[path];
      }
    });

    setTesting(true);
    setConnectionNotice(null);
    setConnectionDetailsOpen(false);
    try {
      const response = await performanceApi.testOssConnection({
        settings: {
          ...optionData,
          performance: {
            ...optionData.performance,
            oss: formDataRef.current,
          },
        },
        secretChanges: ossSecretChanges,
      });
      if (!response.success || !response.data) {
        throw new Error(response.message || __("连接测试失败"));
      }
      setConnectionNotice({
        type: "success",
        message: __("连接成功"),
        summary: sprintf(__("已写入测试对象 · %d ms"), response.data.latencyMs),
        details: sprintf(__("对象 %s。固定测试对象会被覆盖；本次测试未保存设置，也未改变启用状态。"), response.data.objectKey),
      });
    } catch (error) {
      const requestError = error as {
        message?: string;
        response?: { data?: { message?: string } };
      };
      setConnectionNotice({
        type: "error",
        message: __("连接测试失败"),
        summary: requestError.response?.data?.message
          || requestError.message
          || __("请检查凭据、Bucket、地域节点和写入权限。"),
      });
    } finally {
      setTesting(false);
    }
  };

  return (
    <SettingsSection title={__("对象存储")} description={__("图片自动上传至云存储")}>
      <>
        <ModuleCard
          title={__("启用对象存储")}
          description={__("启用后图片将自动上传至云存储")}
          featureId="performance-oss-enabled"
          tags={[configurationStatus]}
          enabled={!!formData.enabled}
          onChange={(checked) => onValuesChange({ enabled: checked })}
          actionLabel={__("配置")}
          onAction={() => setDrawerOpen(true)}
        />

        <DetailDrawer
          title={__("对象存储配置")}
          visible={drawerOpen}
          onClose={() => {
            setDrawerOpen(false);
            setConnectionNotice(null);
            setConnectionDetailsOpen(false);
          }}
          description={__("先配置并测试；更改随页面顶部的“保存”生效。")}
          width={520}
        >
          <Alert
            type="info"
            showIcon
            className="mabox-oss-safety-strip"
            message={(
              <Space size={4} wrap>
                <span>{__("本地副本始终保留，上传失败时自动回退。")}</span>
                <InfoButton
                  label={__("查看本地文件回退说明")}
                  title={__("本地文件回退")}
                >
                  {__("停用对象存储、上传失败或更换目标时，媒体文件仍可从本站读取。")}
                </InfoButton>
              </Space>
            )}
          />
          <Form
            name="oss"
            disabled={testing}
            labelCol={fromConfig.labelCol}
            wrapperCol={fromConfig.wrapperCol}
            style={{ maxWidth: fromConfig.maxWidth }}
            initialValues={publicData}
            autoComplete="off"
            onValuesChange={onValuesChange}
          >
            <section className="mabox-oss-section" aria-labelledby="mabox-oss-credentials-title">
              <Typography.Title level={5} id="mabox-oss-credentials-title">
                {__("服务商与凭据")}
              </Typography.Title>
              <Form.Item label={__("服务商")} name="provider">
                <Select options={[
                  { label: __("阿里云 OSS"), value: "aliyun" },
                  { label: __("腾讯云 COS"), value: "tencent" },
                  { label: __("七牛云"), value: "qiniu" },
                ]} />
              </Form.Item>
              <SecretField
                compact
                label="Access Key"
                path="performance.oss.access_key"
              />
              <SecretField
                compact
                label="Secret Key"
                path="performance.oss.secret_key"
              />
            </section>

            <section className="mabox-oss-section" aria-labelledby="mabox-oss-target-title">
              <Typography.Title level={5} id="mabox-oss-target-title">
                {__("存储目标")}
              </Typography.Title>
              <Form.Item label="Bucket" name="bucket">
                <Input
                  placeholder={guidance.bucketPlaceholder}
                  suffix={(
                    <InfoButton label={__("查看 Bucket 填写说明")} title="Bucket">
                      <Space direction="vertical" size={4}>
                        <span>{guidance.bucketHelp}</span>
                        <Typography.Text type="secondary">
                          {__("不要填写 oss://、访问域名或上传目录。")}
                        </Typography.Text>
                      </Space>
                    </InfoButton>
                  )}
                />
              </Form.Item>
              <Form.Item label={__("上传目录（可选）")} name="path">
                <Input
                  placeholder={__("示例：www 或 uploads/site-a")}
                  suffix={(
                    <InfoButton label={__("查看上传目录填写说明")} title={__("上传目录")}>
                      {__("作为对象键前缀；不要填写 Bucket、oss://，也不要添加开头或结尾斜杠。")}
                    </InfoButton>
                  )}
                />
              </Form.Item>
              {formData.provider === "aliyun" && (
                <Form.Item label="Endpoint" name="endpoint">
                  <Input
                    placeholder={__("示例：oss-cn-shanghai.aliyuncs.com")}
                    suffix={(
                      <InfoButton label={__("查看 Endpoint 填写说明")} title="Endpoint">
                        <Space direction="vertical" size={6}>
                          <span>
                            {__("可直接粘贴阿里云控制台中的外网 Endpoint，也接受 cn-shanghai 快捷写法。")}
                          </span>
                          <span>
                            {__("仅当 WordPress 服务器位于同地域阿里云内网时使用 -internal 节点。")}
                          </span>
                          <Typography.Link
                            href="https://help.aliyun.com/en/oss/user-guide/regions-and-endpoints"
                            target="_blank"
                            rel="noreferrer"
                          >
                            {__("查看节点列表")}
                          </Typography.Link>
                        </Space>
                      </InfoButton>
                    )}
                  />
                </Form.Item>
              )}
              {formData.provider === "tencent" && (
                <Form.Item label="Region" name="region">
                  <Input
                    placeholder={__("示例：ap-beijing")}
                    suffix={(
                      <InfoButton label={__("查看 Region 填写说明")} title="Region">
                        {__("填写地域 ID，例如 ap-beijing；不要填写完整 COS 域名。")}
                      </InfoButton>
                    )}
                  />
                </Form.Item>
              )}
              {formData.provider === "qiniu" && (
                <Typography.Paragraph type="secondary" className="mabox-oss-inline-note">
                  {__("七牛云使用全局上传节点，无需填写地域。")}
                </Typography.Paragraph>
              )}
            </section>

            <section className="mabox-oss-section" aria-labelledby="mabox-oss-public-title">
              <Typography.Title level={5} id="mabox-oss-public-title">
                {__("公开访问")}
              </Typography.Title>
                <Form.Item label={__("公开访问地址")} name="domain">
                <Input
                  placeholder={__("示例：https://img.example.com/www")}
                  suffix={(
                    <InfoButton label={__("查看公开访问地址填写说明")} title={__("公开访问地址")}>
                      <Space direction="vertical" size={4}>
                        <span>{__("填写源站或 CDN 地址，需包含 http:// 或 https://。")}</span>
                        <span>
                          {__("若上传目录为 www，地址通常也以 /www 结尾；插件不会重复追加目录。")}
                        </span>
                      </Space>
                    </InfoButton>
                  )}
                />
              </Form.Item>
            </section>
          </Form>

          <section className="mabox-oss-summary" aria-labelledby="mabox-oss-summary-title">
            <Typography.Title level={5} id="mabox-oss-summary-title">
              {__("目标预览")}
            </Typography.Title>
            <dl>
              <div>
                <dt>{__("请求主机")}</dt>
                <dd><code title={requestHostPreview}>{requestHostPreview}</code></dd>
              </div>
              <div>
                <dt>{__("远端对象")}</dt>
                <dd><code title={storagePreview}>{storagePreview}</code></dd>
              </div>
              <div>
                <dt>{__("公开 URL")}</dt>
                <dd><code title={publicPreview}>{publicPreview}</code></dd>
              </div>
            </dl>
          </section>

          <section className="mabox-oss-test" aria-labelledby="mabox-oss-test-title">
            <Typography.Title level={5} id="mabox-oss-test-title">
              {__("连接测试")}
            </Typography.Title>
            <Space direction="vertical" size={10} style={{ width: "100%" }}>
              <Space size={4} wrap>
                <Typography.Text type="secondary">
                  {__("测试不会保存设置或改变启用状态。")}
                </Typography.Text>
                <InfoButton label={__("查看连接测试说明")} title={__("连接测试")}>
                  {sprintf(__("测试会写入并覆盖 %s；公开访问地址可稍后填写，不影响写入测试。"), testObjectPreview)}
                </InfoButton>
              </Space>
            <Button
              type="primary"
              loading={testing}
              disabled={!connectionTestReady}
              onClick={testConnection}
            >
              {__("测试连接")}
            </Button>
            {!connectionTestReady && (
              <Typography.Text type="secondary">
                {__("请先完整填写 Bucket、服务商地域节点和两项凭据。")}
              </Typography.Text>
            )}
            {connectionNotice && (
              <Alert
                showIcon
                type={connectionNotice.type}
                role={connectionNotice.type === "success" ? "status" : "alert"}
                className="mabox-oss-connection-notice"
                message={connectionNotice.message}
                description={(
                  <div className="mabox-oss-connection-result">
                    <div className="mabox-oss-connection-summary">
                      <span>{connectionNotice.summary}</span>
                      {connectionNotice.details && (
                        <Button
                          type="link"
                          size="small"
                          className="mabox-oss-connection-details-button"
                          aria-expanded={connectionDetailsOpen}
                          onClick={() => setConnectionDetailsOpen((open) => !open)}
                        >
                          {connectionDetailsOpen ? __("收起详情") : __("查看详情")}
                        </Button>
                      )}
                    </div>
                    {connectionDetailsOpen && connectionNotice.details && (
                      <div className="mabox-oss-connection-details">
                        {connectionNotice.details}
                      </div>
                    )}
                  </div>
                )}
              />
            )}
            </Space>
          </section>
        </DetailDrawer>
      </>
    </SettingsSection>
  );
};

export default App;
