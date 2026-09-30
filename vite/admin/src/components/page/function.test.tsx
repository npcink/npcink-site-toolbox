import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import PageFunctionSettings from "@/components/page/function";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { Option } from "@/tool/interface";

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

function renderSettings(maintenanceTips = "false") {
  const optionData: Option = JSON.parse(JSON.stringify(defaultVarOption)) as Option;
  optionData.page.function.maintenance_tips = maintenanceTips;
  const updateOption = vi.fn();

  render(
    <DataContext.Provider
      value={{
        optionData,
        updateOption,
        refreshOption: vi.fn(),
      discardChanges: vi.fn(),
        lastSavedOption: optionData,
        setLastSavedOption: vi.fn(),
        secretStatus: emptySecretStatus(),
        secretChanges: {},
        setSecretChange: vi.fn(),
        clearSecretChanges: vi.fn(),
        settingsState: "ready",
        settingsError: null,
      configEpoch: 0,
      }}
    >
      <PageFunctionSettings />
    </DataContext.Provider>,
  );

  return { updateOption };
}

afterEach(() => {
  cleanup();
});

describe("维护提示页设置", () => {
  it("作为独立功能卡片展示，并由开关控制详细配置", () => {
    const { updateOption } = renderSettings();
    const maintenanceSwitch = screen.getByRole("switch", { name: "维护提示页" });

    expect(maintenanceSwitch).not.toBeChecked();
    expect(screen.getByText("临时关闭前台访问，管理员仍可正常访问")).toBeInTheDocument();
    expect(screen.getByText("谨慎")).toBeInTheDocument();
    expect(screen.queryByText(/当前样式：/)).not.toBeInTheDocument();

    fireEvent.click(maintenanceSwitch);

    expect(maintenanceSwitch).toBeChecked();
    expect(screen.getByText("当前样式：默认简洁")).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "页面样式" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "显示计划" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "维护内容" })).toBeInTheDocument();
    expect(screen.getByRole("textbox", { name: "维护提示显示时间：开始时间" })).toBeInTheDocument();
    expect(screen.getByRole("textbox", { name: "维护提示显示时间：结束时间" })).toBeInTheDocument();
    expect(screen.getByRole("textbox", { name: "维护标题" })).toHaveAttribute(
      "placeholder",
      "例如：网站维护中",
    );
    expect(screen.getByRole("button", { name: "为维护背景图片选择图片" })).toHaveTextContent(
      "从媒体库选择",
    );
    expect(screen.queryByText(/<p>抱歉，我们的网站正在维护中/)).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "查看维护说明 HTML 示例" }));
    expect(screen.getByText(/<p>抱歉，我们的网站正在维护中/)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "更换维护提示样式，当前：默认简洁" })).toHaveTextContent(
      "更换样式",
    );
    expect(updateOption).toHaveBeenLastCalledWith(
      "page",
      "function",
      expect.objectContaining({ maintenance_tips: "default" }),
    );

    fireEvent.click(maintenanceSwitch);

    expect(maintenanceSwitch).not.toBeChecked();
    expect(screen.queryByText(/当前样式：/)).not.toBeInTheDocument();
    expect(updateOption).toHaveBeenLastCalledWith(
      "page",
      "function",
      expect.objectContaining({ maintenance_tips: "false" }),
    );
  });

  it("保留已选择的维护页样式", () => {
    renderSettings("red");

    expect(screen.getByRole("switch", { name: "维护提示页" })).toBeChecked();
    expect(screen.getByText("当前样式：红色纯粹")).toBeInTheDocument();
  });
});
