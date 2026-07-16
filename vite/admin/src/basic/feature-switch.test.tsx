import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import FeatureSwitch from "@/basic/feature-switch";

const { isFavorite, toggleFavorite } = vi.hoisted(() => ({
  isFavorite: vi.fn(() => false),
  toggleFavorite: vi.fn(() => true),
}));

vi.mock("@/tool/favorites", () => ({
  isFavorite,
  toggleFavorite,
}));

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

beforeEach(() => {
  isFavorite.mockReturnValue(false);
  toggleFavorite.mockReturnValue(true);
});

afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

describe("FeatureSwitch", () => {
  it("为开关和收藏动作提供唯一名称与键盘可操作状态", () => {
    const onChange = vi.fn();
    render(
      <FeatureSwitch
        featureId="content-keyword-highlight"
        label="关键词高亮"
        checked={false}
        onChange={onChange}
      />,
    );

    fireEvent.click(screen.getByRole("switch", { name: "关键词高亮" }));
    expect(onChange).toHaveBeenCalledWith(true, expect.anything());

    const favorite = screen.getByRole("button", { name: "加入常用功能：关键词高亮" });
    expect(favorite).toHaveAttribute("aria-pressed", "false");

    fireEvent.click(favorite);
    expect(toggleFavorite).toHaveBeenCalledWith("content-keyword-highlight");
    expect(screen.getByRole("button", { name: "取消收藏：关键词高亮" }))
      .toHaveAttribute("aria-pressed", "true");
  });

  it("允许点击扩大的触控区域切换状态", () => {
    const onChange = vi.fn();
    const { container } = render(
      <FeatureSwitch
        featureId="content-keyword-highlight"
        label="关键词高亮"
        checked={false}
        onChange={onChange}
      />,
    );

    fireEvent.click(container.querySelector(".mabox-feature-switch-control") as HTMLElement);
    expect(onChange).toHaveBeenCalledWith(true, expect.anything());
  });
});
