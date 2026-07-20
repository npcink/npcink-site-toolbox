import React, { useContext, useEffect, useRef, useState } from "react";
import { Alert, Button, Form, Input, Select, Space, Typography } from "antd";
import { performanceApi } from "@/api";
import { DataContext } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type {
  PerformanceOss,
  SecretChange,
  SecretChanges,
} from "@/tool/interface";
import { AntConfig } from "@/tool/tool";
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
  exampleBucket: string;
  examplePath: string;
  exampleTarget: string;
  examplePublicUrl: string;
}> = {
  aliyun: {
    bucketPlaceholder: "示例：npcink-media",
    bucketHelp: "只填写 Bucket 名称；上传目录请填写在下一项。",
    exampleBucket: "npcink-media",
    examplePath: "www",
    exampleTarget: "oss-cn-shanghai.aliyuncs.com",
    examplePublicUrl: "https://img.example.com/www",
  },
  tencent: {
    bucketPlaceholder: "示例：npcink-media-1250000000",
    bucketHelp: "填写带 APPID 后缀的完整 Bucket 名称。",
    exampleBucket: "npcink-media-1250000000",
    examplePath: "www",
    exampleTarget: "ap-beijing",
    examplePublicUrl: "https://img.example.com/www",
  },
  qiniu: {
    bucketPlaceholder: "示例：npcink-media",
    bucketHelp: "填写七牛云空间名称，不要填写访问域名。",
    exampleBucket: "npcink-media",
    examplePath: "www",
    exampleTarget: "当前使用全局上传节点",
    examplePublicUrl: "https://img.example.com/www",
  },
};

interface ConnectionNotice {
  type: "success" | "error";
  message: string;
  description: string;
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

  const onValuesChange = (changedValues: Partial<PerformanceOss>) => {
    const nextFormData = { ...formDataRef.current, ...changedValues };
    formDataRef.current = nextFormData;
    setFormData(nextFormData);
    setConnectionNotice(null);
    updateOption("performance", "oss", nextFormData);
  };

  const accessKeyChange = secretChanges["performance.oss.access_key"];
  const secretKeyChange = secretChanges["performance.oss.secret_key"];
  useEffect(() => {
    setConnectionNotice(null);
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
  const publicPreview = publicBase ? `${publicBase}/${SAMPLE_MEDIA_PATH}` : "尚未填写公开访问地址";
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
        throw new Error(response.message || "连接测试失败");
      }
      setConnectionNotice({
        type: "success",
        message: response.message || "连接成功，已写入并覆盖测试对象。",
        description: `对象 ${response.data.objectKey}；耗时 ${response.data.latencyMs} ms；本次测试未保存设置，也未改变启用状态。`,
      });
    } catch (error) {
      const requestError = error as {
        message?: string;
        response?: { data?: { message?: string } };
      };
      setConnectionNotice({
        type: "error",
        message: "连接测试失败",
        description: requestError.response?.data?.message
          || requestError.message
          || "请检查凭据、Bucket、地域节点和写入权限。",
      });
    } finally {
      setTesting(false);
    }
  };

  return (
    <SettingsSection title="对象存储" description="图片自动上传至云存储">
      <>
        <ModuleCard
          title="启用对象存储"
          description="启用后图片将自动上传至云存储"
          featureId="performance-oss-enabled"
          tags={[configurationStatus]}
          enabled={!!formData.enabled}
          onChange={(checked) => onValuesChange({ enabled: checked })}
          actionLabel="配置"
          onAction={() => setDrawerOpen(true)}
        />

        <DetailDrawer
          title="对象存储配置"
          visible={drawerOpen}
          onClose={() => setDrawerOpen(false)}
          description="可先完成配置，再决定是否启用；更改随页面顶部的“保存”统一保存。"
          width={520}
        >
          <Alert
            type="info"
            showIcon
            message="本地文件安全回退"
            description="本地副本始终保留。停用对象存储、上传失败或更换目标时，媒体文件仍可从本站读取。"
            style={{ marginBottom: 16 }}
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
            <Form.Item label="服务商" name="provider">
              <Select options={[
                { label: "阿里云 OSS", value: "aliyun" },
                { label: "腾讯云 COS", value: "tencent" },
                { label: "七牛云", value: "qiniu" },
              ]} />
            </Form.Item>
            <SecretField label="Access Key" path="performance.oss.access_key" />
            <SecretField label="Secret Key" path="performance.oss.secret_key" />
            <Form.Item label="Bucket" name="bucket" extra={guidance.bucketHelp}>
              <Input placeholder={guidance.bucketPlaceholder} />
            </Form.Item>
            <Form.Item
              label="上传目录（可选）"
              name="path"
              extra="对象键前缀，例如 www 或 uploads/site-a；不要填写 Bucket、oss://、开头或结尾斜杠。"
            >
              <Input placeholder="示例：www" />
            </Form.Item>
            {formData.provider === "aliyun" && (
              <Form.Item
                label="Endpoint"
                name="endpoint"
                extra={(
                  <span>
                    可直接粘贴阿里云控制台中的外网 Endpoint，例如 oss-cn-shanghai.aliyuncs.com；
                    也接受 cn-shanghai 快捷写法。仅当 WordPress 服务器位于同地域阿里云内网时使用
                    -internal 节点。{" "}
                    <Typography.Link
                      href="https://help.aliyun.com/en/oss/user-guide/regions-and-endpoints"
                      target="_blank"
                      rel="noreferrer"
                    >
                      查看节点列表
                    </Typography.Link>
                  </span>
                )}
              >
                <Input placeholder="示例：oss-cn-shanghai.aliyuncs.com" />
              </Form.Item>
            )}
            {formData.provider === "tencent" && (
              <Form.Item
                label="Region"
                name="region"
                extra="填写地域 ID，例如 ap-beijing；不要填写完整 COS 域名。"
              >
                <Input placeholder="示例：ap-beijing" />
              </Form.Item>
            )}
            {formData.provider === "qiniu" && (
              <Alert
                type="info"
                showIcon
                message="当前七牛云使用全局上传节点，无需填写 Region。"
                style={{ marginBottom: 16 }}
              />
            )}
            <Form.Item
              label="公开访问地址"
              name="domain"
              extra="填写源站或 CDN 的公开地址前缀，需包含 http:// 或 https://。若上传目录为 www，通常也应以 /www 结尾；插件不会自动重复追加目录。"
            >
              <Input placeholder="示例：https://img.example.com/www" />
            </Form.Item>
          </Form>
          <Alert
            type="info"
            showIcon
            message="配置示例"
            description={(
              <Space direction="vertical" size={2}>
                <Typography.Text>
                  Bucket：<Typography.Text code>{guidance.exampleBucket}</Typography.Text>
                </Typography.Text>
                <Typography.Text>
                  上传目录：<Typography.Text code>{guidance.examplePath}</Typography.Text>
                </Typography.Text>
                <Typography.Text>
                  地域节点：<Typography.Text code>{guidance.exampleTarget}</Typography.Text>
                </Typography.Text>
                <Typography.Text>
                  公开地址：<Typography.Text code>{guidance.examplePublicUrl}</Typography.Text>
                </Typography.Text>
                <Typography.Text>
                  对象示例：<Typography.Text code>
                    {`${storageScheme}://${guidance.exampleBucket}/${guidance.examplePath}/${SAMPLE_MEDIA_PATH}`}
                  </Typography.Text>
                </Typography.Text>
              </Space>
            )}
            style={{ marginBottom: 16 }}
          />
          <Alert
            type="info"
            showIcon
            message="当前目标预览"
            description={(
              <Space direction="vertical" size={2}>
                <Typography.Text>
                  请求主机：<Typography.Text code>{requestHostPreview}</Typography.Text>
                </Typography.Text>
                <Typography.Text>
                  远端对象：<Typography.Text code>{storagePreview}</Typography.Text>
                </Typography.Text>
                <Typography.Text>
                  公开 URL：<Typography.Text code>{publicPreview}</Typography.Text>
                </Typography.Text>
              </Space>
            )}
            style={{ marginBottom: 16 }}
          />
          <Space direction="vertical" size={10} style={{ width: "100%" }}>
            <Typography.Text type="secondary">
              测试会写入并覆盖 {testObjectPreview}；不会保存设置，也不会改变启用状态。
              公开访问地址可稍后填写，不影响写入测试。
            </Typography.Text>
            <Button
              type="primary"
              loading={testing}
              disabled={!connectionTestReady}
              onClick={testConnection}
            >
              测试连接
            </Button>
            {!connectionTestReady && (
              <Typography.Text type="secondary">
                请先完整填写 Bucket、服务商地域节点和两项凭据。
              </Typography.Text>
            )}
            {connectionNotice && (
              <Alert
                showIcon
                type={connectionNotice.type}
                message={connectionNotice.message}
                description={connectionNotice.description}
              />
            )}
          </Space>
        </DetailDrawer>
      </>
    </SettingsSection>
  );
};

export default App;
