import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import CommentSettings from "@/components/page/comment";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { Option } from "@/tool/interface";

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

function renderSettings() {
  const optionData: Option = JSON.parse(JSON.stringify(defaultVarOption)) as Option;
  const updateOption = vi.fn();

  render(
    <DataContext.Provider
      value={{
        optionData,
        updateOption,
        refreshOption: vi.fn(),
        lastSavedOption: optionData,
        setLastSavedOption: vi.fn(),
        secretStatus: emptySecretStatus(),
        secretChanges: {},
        setSecretChange: vi.fn(),
        clearSecretChanges: vi.fn(),
        settingsState: "ready",
        settingsError: null,
      }}
    >
      <CommentSettings />
    </DataContext.Provider>,
  );

  return { updateOption };
}

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("用户评论 REST 设置", () => {
  it("默认关闭总开关，并允许单独关闭兜底后台页面", () => {
    const { updateOption } = renderSettings();
    const restSwitch = screen.getByRole("switch", { name: "用户评论 REST 接口" });

    expect(restSwitch).not.toBeChecked();
    expect(screen.queryByRole("switch", { name: "显示“我的评论”后台页面" })).not.toBeInTheDocument();

    fireEvent.click(restSwitch);

    expect(restSwitch).toBeChecked();
    const adminPageSwitch = screen.getByRole("switch", { name: "显示“我的评论”后台页面" });
    expect(adminPageSwitch).toBeChecked();
    expect(updateOption).toHaveBeenLastCalledWith(
      "page",
      "comment",
      expect.objectContaining({ self_service_enabled: true }),
    );

    fireEvent.click(adminPageSwitch);

    expect(adminPageSwitch).not.toBeChecked();
    expect(updateOption).toHaveBeenLastCalledWith(
      "page",
      "comment",
      expect.objectContaining({
        self_service_enabled: true,
        self_service_admin_page_enabled: false,
      }),
    );
  });

  it("提供用户教程入口", () => {
    const openSpy = vi.spyOn(window, "open").mockImplementation(() => null);
    window.dataLocal = {
      url_site: "https://example.com",
      commentRestHelpUrl:
        "https://example.com/wp-admin/admin.php?page=npcink-site-toolbox-comment-rest-help",
    };
    renderSettings();
    const detailButtons = screen.getAllByRole("button", { name: "详情" });

    fireEvent.click(detailButtons[detailButtons.length - 1]);

    expect(openSpy).toHaveBeenCalledWith(
      "https://example.com/wp-admin/admin.php?page=npcink-site-toolbox-comment-rest-help",
      "_blank",
      "noopener,noreferrer",
    );

    delete window.dataLocal;
  });
});
