import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Medium from "@/components/optimize/medium";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { Option } from "@/tool/interface";

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

function renderMedium(webpSupported: boolean) {
  window.npcinkSiteToolboxData = {
    url_site: "https://example.com",
    webpSupported,
  };
  const optionData: Option = JSON.parse(JSON.stringify(defaultVarOption)) as Option;

  render(
    <DataContext.Provider
      value={{
        optionData,
        updateOption: vi.fn(),
        refreshOption: vi.fn(),
        lastSavedOption: defaultVarOption,
        setLastSavedOption: vi.fn(),
        secretStatus: emptySecretStatus(),
        secretChanges: {},
        setSecretChange: vi.fn(),
        clearSecretChanges: vi.fn(),
        settingsState: "ready",
        settingsError: null,
      }}
    >
      <Medium />
    </DataContext.Provider>,
  );
}

afterEach(() => {
  cleanup();
  delete window.npcinkSiteToolboxData;
});

describe("媒体设置", () => {
  it("服务器支持时准确说明 WebP 主图、缩略图与原图备份", () => {
    renderMedium(true);

    expect(screen.getByRole("switch", { name: "新生成图片使用 WebP" })).not.toBeChecked();
    expect(screen.getByText(/原始上传的 JPEG 留作恢复备份/)).toBeInTheDocument();
    expect(screen.getByText(/WebP 主图和缩略图/)).toBeInTheDocument();
    expect(screen.getByText(/不转换 PNG，不删除或覆盖原图/)).toBeInTheDocument();
    expect(screen.getByText("性能")).toBeInTheDocument();
    expect(screen.getByText("安全")).toBeInTheDocument();
  });

  it("服务器不支持时不承诺转换成功", () => {
    renderMedium(false);

    expect(screen.getByText(/当前服务器的 WordPress 图片编辑器不支持 WebP/)).toBeInTheDocument();
    expect(screen.getByText("异常")).toBeInTheDocument();
    expect(screen.queryByText("正常")).not.toBeInTheDocument();
  });
});
