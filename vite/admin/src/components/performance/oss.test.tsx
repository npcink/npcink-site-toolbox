import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { ConfigProvider } from "antd";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import Oss from "@/components/performance/oss";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { Option, PerformanceOss, SecretChanges } from "@/tool/interface";

const apiMocks = vi.hoisted(() => ({
  testOssConnection: vi.fn(),
}));

vi.mock("@/api", () => ({
  performanceApi: apiMocks,
}));

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

interface RenderOssOptions {
  oss?: Partial<PerformanceOss>;
  credentialsConfigured?: boolean;
  secretChanges?: SecretChanges;
}

function renderOss({
  oss = {},
  credentialsConfigured = false,
  secretChanges = {},
}: RenderOssOptions = {}) {
  const optionData: Option = {
    ...defaultVarOption,
    performance: {
      ...defaultVarOption.performance,
      oss: {
        ...defaultVarOption.performance.oss,
        ...oss,
      },
    },
  };
  const secretStatus = emptySecretStatus();
  secretStatus["performance.oss.access_key"] = { configured: credentialsConfigured };
  secretStatus["performance.oss.secret_key"] = { configured: credentialsConfigured };
  const updateOption = vi.fn();

  render(
    <ConfigProvider theme={{ token: { motion: false } }}>
      <DataContext.Provider
        value={{
          optionData,
          updateOption,
          refreshOption: vi.fn(),
          lastSavedOption: optionData,
          setLastSavedOption: vi.fn(),
          secretStatus,
          secretChanges,
          setSecretChange: vi.fn(),
          clearSecretChanges: vi.fn(),
          settingsState: "ready",
          settingsError: null,
        }}
      >
        <Oss />
      </DataContext.Provider>
    </ConfigProvider>,
  );

  return updateOption;
}

const getComputedStyle = window.getComputedStyle.bind(window);

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
  apiMocks.testOssConnection.mockReset();
  apiMocks.testOssConnection.mockResolvedValue({
    success: true,
    message: "连接成功，已写入并覆盖测试对象。",
    data: {
      provider: "aliyun",
      objectKey: "www/npcink-site-toolbox/connection-test.txt",
      latencyMs: 42,
    },
  });
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("对象存储设置", () => {
  it("停用时仍可从主卡片打开右侧配置，并保持启用开关独立", async () => {
    const updateOption = renderOss();

    expect(screen.getByRole("switch", { name: "启用对象存储" })).not.toBeChecked();
    expect(screen.queryByLabelText("Bucket")).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));

    expect(await screen.findByText("对象存储配置")).toBeInTheDocument();
    expect(screen.getByText("先配置并测试；更改随页面顶部的“保存”生效。"))
      .toBeInTheDocument();
    expect(screen.getByLabelText("服务商")).toBeInTheDocument();
    expect(screen.getByLabelText("Access Key新值")).toBeInTheDocument();
    expect(screen.getByLabelText("Secret Key新值")).toBeInTheDocument();
    expect(screen.getByLabelText("Bucket")).toBeInTheDocument();
    expect(screen.getByLabelText("上传目录（可选）")).toBeInTheDocument();
    expect(screen.getByLabelText("Endpoint")).toBeInTheDocument();
    expect(screen.queryByLabelText("Region")).not.toBeInTheDocument();
    expect(screen.getByLabelText("公开访问地址")).toBeInTheDocument();
    expect(screen.getByPlaceholderText("示例：npcink-media")).toBeInTheDocument();
    expect(screen.getByPlaceholderText("示例：oss-cn-shanghai.aliyuncs.com")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Bucket"), { target: { value: "bucket-a" } });
    await waitFor(() => {
      expect(updateOption).toHaveBeenLastCalledWith(
        "performance",
        "oss",
        expect.objectContaining({ enabled: false, bucket: "bucket-a" }),
      );
    });

    fireEvent.click(screen.getByRole("switch", { name: "启用对象存储" }));
    await waitFor(() => {
      expect(updateOption).toHaveBeenLastCalledWith(
        "performance",
        "oss",
        expect.objectContaining({ enabled: true, bucket: "bucket-a" }),
      );
    });
  });

  it("按任务分组配置并把详细规则收进可访问的信息入口", async () => {
    renderOss();

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));

    expect(await screen.findByRole("heading", { name: "服务商与凭据" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "存储目标" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "公开访问" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "目标预览" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "连接测试" })).toBeInTheDocument();
    expect(screen.getByText("本地副本始终保留，上传失败时自动回退。")).toBeInTheDocument();
    expect(screen.queryByText(/停用对象存储、上传失败或更换目标时/)).not.toBeInTheDocument();
    expect(screen.getByRole("button", { name: "查看本地文件回退说明" })).toBeInTheDocument();
    expect(screen.queryByText("只填写 Bucket 名称；上传目录请填写在下一项。"))
      .not.toBeInTheDocument();
    expect(screen.queryByText(/可直接粘贴阿里云控制台中的外网 Endpoint/))
      .not.toBeInTheDocument();
    expect(screen.queryByText("配置示例")).not.toBeInTheDocument();
    expect(screen.queryByText(/npcink-site-toolbox\/connection-test\.txt/)).not.toBeInTheDocument();
    expect(screen.getByRole("button", { name: "查看 Endpoint 填写说明" })).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "查看连接测试说明" })).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "查看 Bucket 填写说明" }));
    expect(await screen.findByText("只填写 Bucket 名称；上传目录请填写在下一项。"))
      .toBeInTheDocument();
  }, 30_000);

  it("仅在存储目标和两项凭据都齐备时标记为已配置", () => {
    renderOss({
      credentialsConfigured: true,
      oss: {
        provider: "aliyun",
        bucket: "bucket-a",
        path: "www",
        endpoint: "oss-cn-hangzhou.aliyuncs.com",
        region: "",
        domain: "https://cdn.example.com",
      },
    });

    expect(screen.getByText("已配置")).toBeInTheDocument();
  });

  it("把两项待替换凭据计入配置状态", () => {
    renderOss({
      oss: {
        provider: "aliyun",
        bucket: "bucket-a",
        path: "www",
        endpoint: "oss-cn-hangzhou.aliyuncs.com",
        region: "",
        domain: "https://cdn.example.com",
      },
      secretChanges: {
        "performance.oss.access_key": { operation: "replace", value: "access-key" },
        "performance.oss.secret_key": { operation: "replace", value: "secret-key" },
      },
    });

    expect(screen.getByText("已配置")).toBeInTheDocument();
  });

  it("任一凭据待清除时立即标记为未配置", () => {
    renderOss({
      credentialsConfigured: true,
      oss: {
        provider: "aliyun",
        bucket: "bucket-a",
        path: "www",
        endpoint: "oss-cn-hangzhou.aliyuncs.com",
        region: "",
        domain: "https://cdn.example.com",
      },
      secretChanges: {
        "performance.oss.secret_key": { operation: "clear" },
      },
    });

    expect(screen.getByText("未配置")).toBeInTheDocument();
  });

  it("配置完整时可用未保存草稿执行连接测试且不保存或改变既有启用状态", async () => {
    const updateOption = renderOss({
      oss: {
        enabled: true,
        provider: "aliyun",
        bucket: "bucket-a",
        path: "www",
        endpoint: "oss-cn-hangzhou.aliyuncs.com",
        region: "",
        domain: "",
      },
      secretChanges: {
        "domestic.wechat.appsecret": { operation: "replace", value: "unrelated-secret" },
        "performance.oss.access_key": { operation: "replace", value: "access-key" },
        "performance.oss.secret_key": { operation: "replace", value: "secret-key" },
      },
    });

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));
    const testButton = await screen.findByRole("button", { name: "测试连接" });
    expect(testButton).toBeEnabled();

    fireEvent.click(testButton);

    await waitFor(() => {
      expect(apiMocks.testOssConnection).toHaveBeenCalledWith({
        settings: expect.objectContaining({
          performance: expect.objectContaining({
            oss: expect.objectContaining({
              enabled: true,
              bucket: "bucket-a",
              path: "www",
              endpoint: "oss-cn-hangzhou.aliyuncs.com",
            }),
          }),
        }),
        secretChanges: {
          "performance.oss.access_key": { operation: "replace", value: "access-key" },
          "performance.oss.secret_key": { operation: "replace", value: "secret-key" },
        },
      });
    });
    expect(await screen.findByText("连接成功")).toBeInTheDocument();
    expect(screen.getByText("已写入测试对象 · 42 ms")).toBeInTheDocument();
    expect(screen.queryByText(/对象 www\/npcink-site-toolbox\/connection-test\.txt/))
      .not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "查看详情" }));
    expect(screen.getByText(
      "对象 www/npcink-site-toolbox/connection-test.txt。固定测试对象会被覆盖；本次测试未保存设置，也未改变启用状态。",
    )).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "收起详情" })).toHaveAttribute(
      "aria-expanded",
      "true",
    );
    expect(updateOption).not.toHaveBeenCalled();

    fireEvent.change(screen.getByLabelText("Bucket"), { target: { value: "bucket-b" } });
    expect(screen.queryByText("连接成功")).not.toBeInTheDocument();
  });

  it("在测试失败时原地保留明确错误且不显示成功详情入口", async () => {
    apiMocks.testOssConnection.mockRejectedValueOnce(new Error("AccessDenied：缺少写入权限"));
    renderOss({
      credentialsConfigured: true,
      oss: {
        provider: "aliyun",
        bucket: "bucket-a",
        endpoint: "oss-cn-hangzhou.aliyuncs.com",
      },
    });

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));
    fireEvent.click(await screen.findByRole("button", { name: "测试连接" }));

    expect(await screen.findByText("连接测试失败")).toBeInTheDocument();
    expect(screen.getByText("AccessDenied：缺少写入权限")).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "查看详情" })).not.toBeInTheDocument();
  });

  it("按服务商显示 Endpoint 或 Region，七牛云不显示无效地域字段", async () => {
    renderOss();

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));
    const provider = await screen.findByLabelText("服务商");
    expect(screen.getByLabelText("Endpoint")).toBeInTheDocument();

    fireEvent.mouseDown(provider);
    fireEvent.click(await screen.findByText("腾讯云 COS"));
    expect(screen.getByLabelText("Region")).toBeInTheDocument();
    expect(screen.queryByLabelText("Endpoint")).not.toBeInTheDocument();

    fireEvent.mouseDown(provider);
    fireEvent.click(await screen.findByText("七牛云"));
    expect(screen.queryByLabelText("Region")).not.toBeInTheDocument();
    expect(screen.queryByLabelText("Endpoint")).not.toBeInTheDocument();
  });

  it("上传目录和公开地址共同生成可核对的对象与 URL 预览", async () => {
    renderOss({
      oss: {
        provider: "aliyun",
        bucket: "file-npc-ink",
        path: "www",
        endpoint: "oss-cn-shanghai.aliyuncs.com",
        domain: "https://n.getimg.net/www",
      },
    });

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));

    expect(await screen.findByText(
      "oss://file-npc-ink/www/YYYY/MM/example.jpg",
    )).toBeInTheDocument();
    expect(screen.getByText(
      "https://n.getimg.net/www/YYYY/MM/example.jpg",
    )).toBeInTheDocument();
    expect(screen.getByText(
      "file-npc-ink.oss-cn-shanghai.aliyuncs.com",
    )).toBeInTheDocument();
  });

  it("配置不完整时不允许发起连接测试", async () => {
    renderOss();

    fireEvent.click(screen.getByRole("button", { name: "配置：启用对象存储" }));

    expect(await screen.findByRole("button", { name: "测试连接" })).toBeDisabled();
    expect(apiMocks.testOssConnection).not.toHaveBeenCalled();
  });
});
