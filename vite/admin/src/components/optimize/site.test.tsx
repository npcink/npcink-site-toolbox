import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Site from "@/components/optimize/site";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import type { Option } from "@/tool/interface";

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

function renderSite(siteOverrides: Partial<Option["optimize"]["site"]> = {}) {
  const optionData: Option = {
    ...defaultVarOption,
    optimize: {
      ...defaultVarOption.optimize,
      site: {
        ...defaultVarOption.optimize.site,
        ...siteOverrides,
      },
    },
  };

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
      <Site />
    </DataContext.Provider>,
  );
}

afterEach(cleanup);

describe("站点优化设置", () => {
  it("使用准确说明，并且不再提供远程 CDN URL 改写", () => {
    renderSite();

    expect(screen.getByText("对无法编辑文章的登录用户隐藏前台顶部工具条")).toBeInTheDocument();
    expect(screen.getByText("在用户列表中以“昵称”列替代“姓名”列")).toBeInTheDocument();
    expect(screen.queryByRole("switch", { name: "禁用自动更新" })).not.toBeInTheDocument();
    expect(screen.queryByText("禁用自动更新可能导致安全风险")).not.toBeInTheDocument();
    expect(screen.queryByRole("switch", { name: "国内 CDN 替换" })).not.toBeInTheDocument();
    expect(screen.queryByRole("switch", { name: "Gravatar 头像替换" })).not.toBeInTheDocument();
    expect(screen.queryByRole("switch", { name: "Google Fonts 替换" })).not.toBeInTheDocument();
    expect(screen.queryByRole("switch", { name: "Google Ajax 替换" })).not.toBeInTheDocument();
    expect(screen.queryByLabelText("自定义 CDN 替换")).not.toBeInTheDocument();
  });
});
