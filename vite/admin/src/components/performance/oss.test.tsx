import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
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
    </DataContext.Provider>,
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
    expect(screen.getByText("可先完成配置，再决定是否启用；更改随页面顶部的“保存”统一保存。"))
      .toBeInTheDocument();
    expect(screen.getByLabelText("服务商")).toBeInTheDocument();
    expect(screen.getByLabelText("Access Key新值")).toBeInTheDocument();
    expect(screen.getByLabelText("Secret Key新值")).toBeInTheDocument();
    expect(screen.getByLabelText("Bucket")).toBeInTheDocument();
    expect(screen.getByLabelText("上传目录（可选）")).toBeInTheDocument();
    expect(screen.getByLabelText("Endpoint")).toBeInTheDocument();
    expect(screen.queryByLabelText("Region")).not.toBeInTheDocument();
    expect(screen.getByLabelText("公开访问地址")).toBeInTheDocument();
    expect(screen.getByText("本地副本始终保留。停用对象存储、上传失败或更换目标时，媒体文件仍可从本站读取。"))
      .toBeInTheDocument();
    expect(screen.getByText("只填写 Bucket 名称；上传目录请填写在下一项。"))
      .toBeInTheDocument();
    expect(screen.getByPlaceholderText("示例：npcink-media")).toBeInTheDocument();
    expect(screen.getByText(/可直接粘贴阿里云控制台中的外网 Endpoint/))
      .toBeInTheDocument();
    expect(screen.getByPlaceholderText("示例：oss-cn-shanghai.aliyuncs.com")).toBeInTheDocument();
    expect(screen.getByText("配置示例")).toBeInTheDocument();
    expect(screen.getByText(/oss:\/\/npcink-media\/www\/YYYY\/MM\/example.jpg/))
      .toBeInTheDocument();
    expect(screen.getByText(/npcink-site-toolbox\/connection-test\.txt/)).toBeInTheDocument();

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
    expect(await screen.findByText("连接成功，已写入并覆盖测试对象。")).toBeInTheDocument();
    expect(screen.getByText(
      "对象 www/npcink-site-toolbox/connection-test.txt；耗时 42 ms；本次测试未保存设置，也未改变启用状态。",
    )).toBeInTheDocument();
    expect(updateOption).not.toHaveBeenCalled();
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
