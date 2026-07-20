import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import Oss from "@/components/performance/oss";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { Option, PerformanceOss, SecretChanges } from "@/tool/interface";

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
    expect(screen.getByLabelText("Region")).toBeInTheDocument();
    expect(screen.getByLabelText("CDN 域名")).toBeInTheDocument();

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
        region: "cn-hangzhou",
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
        region: "cn-hangzhou",
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
        region: "cn-hangzhou",
        domain: "https://cdn.example.com",
      },
      secretChanges: {
        "performance.oss.secret_key": { operation: "clear" },
      },
    });

    expect(screen.getByText("未配置")).toBeInTheDocument();
  });
});
