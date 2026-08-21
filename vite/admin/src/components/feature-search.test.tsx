import { act, cleanup, fireEvent, render, screen, within } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import FeatureSearch from "@/components/feature-search";
import { isFavorite, toggleFavorite } from "@/tool/favorites";

vi.mock("@/tool/favorites", () => ({
  isFavorite: vi.fn().mockReturnValue(false),
  toggleFavorite: vi.fn(),
}));

describe("FeatureSearch", () => {
  afterEach(cleanup);

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(isFavorite).mockReturnValue(false);
  });

  it("直接使用生成索引跳转到语义化视图", () => {
    const onNavigate = vi.fn();
    render(<FeatureSearch onNavigate={onNavigate} />);

    fireEvent.change(screen.getByRole("searchbox", { name: "搜索功能或设置" }), {
      target: { value: "数据库清理" },
    });
    fireEvent.click(screen.getByRole("button", { name: "打开数据库清理优化" }));

    expect(onNavigate).toHaveBeenCalledWith("maintenance", "performance-db_clean-enabled");
  });

  it("登录安全搜索结果使用 canonical 设置行 id", () => {
    const onNavigate = vi.fn();
    render(<FeatureSearch onNavigate={onNavigate} />);

    fireEvent.change(screen.getByRole("searchbox", { name: "搜索功能或设置" }), {
      target: { value: "作者枚举" },
    });
    fireEvent.click(screen.getByRole("button", { name: "打开限制匿名作者枚举" }));

    expect(onNavigate).toHaveBeenCalledWith(
      "china",
      "domestic-login_security-anonymous_author_guard_enabled",
    );
  });

  it("Escape 关闭结果并保留搜索词和输入焦点", () => {
    render(<FeatureSearch onNavigate={vi.fn()} />);
    const input = screen.getByRole("searchbox", { name: "搜索功能或设置" }) as HTMLInputElement;

    expect(input).toHaveAttribute("aria-expanded", "false");
    expect(input).not.toHaveAttribute("aria-controls");
    act(() => input.focus());
    fireEvent.change(input, { target: { value: "数据库清理" } });
    expect(screen.getByRole("list", { name: "功能搜索结果" })).not.toBeNull();
    expect(input).toHaveAttribute("aria-expanded", "true");
    expect(input).toHaveAttribute("aria-controls", "mabox-feature-search-results");

    fireEvent.keyDown(input, { key: "Escape" });

    expect(screen.queryByRole("list", { name: "功能搜索结果" })).toBeNull();
    expect(input.value).toBe("数据库清理");
    expect(document.activeElement).toBe(input);
    expect(input).toHaveAttribute("aria-expanded", "false");
    expect(input).not.toHaveAttribute("aria-controls");
  });

  it("Escape 关闭后第一次方向键即可重新打开并聚焦结果", () => {
    render(<FeatureSearch onNavigate={vi.fn()} />);
    const input = screen.getByRole("searchbox", { name: "搜索功能或设置" }) as HTMLInputElement;

    fireEvent.change(input, { target: { value: "数据库清理" } });
    fireEvent.keyDown(input, { key: "Escape" });
    fireEvent.keyDown(input, { key: "ArrowDown" });

    expect(document.activeElement).toBe(
      screen.getByRole("button", { name: "打开数据库清理优化" }),
    );
    expect(input).toHaveAttribute("aria-expanded", "true");
    expect(input).toHaveAttribute("aria-controls", "mabox-feature-search-results");
  });

  it("方向键在搜索结果间移动，Escape 返回搜索框", () => {
    render(<FeatureSearch onNavigate={vi.fn()} />);
    const input = screen.getByRole("searchbox", { name: "搜索功能或设置" }) as HTMLInputElement;

    fireEvent.change(input, { target: { value: "评论" } });
    fireEvent.keyDown(input, { key: "ArrowDown" });
    const firstResult = screen.getByRole("button", { name: "打开用户评论 REST 接口" });
    expect(document.activeElement).toBe(firstResult);

    fireEvent.keyDown(firstResult, { key: "ArrowDown" });
    const secondResult = screen.getByRole("button", { name: "打开评论敏感词过滤" });
    expect(document.activeElement).toBe(secondResult);

    fireEvent.keyDown(secondResult, { key: "Escape" });
    expect(document.activeElement).toBe(input);
    expect(screen.queryByRole("list", { name: "功能搜索结果" })).toBeNull();
  });

  it("无结果时提供状态反馈，并可清空后回到搜索框", () => {
    render(<FeatureSearch onNavigate={vi.fn()} />);
    const input = screen.getByRole("searchbox", { name: "搜索功能或设置" }) as HTMLInputElement;

    fireEvent.change(input, { target: { value: "不存在的功能" } });
    expect(screen.getByRole("status").textContent).toContain("未找到匹配的功能");
    expect(input).toHaveAttribute("aria-expanded", "true");
    expect(input).toHaveAttribute("aria-controls", "mabox-feature-search-results");

    fireEvent.click(screen.getByRole("button", { name: "清空搜索" }));
    expect(input.value).toBe("");
    expect(document.activeElement).toBe(input);
    expect(screen.queryByText("未找到匹配的功能")).toBeNull();
    expect(input).toHaveAttribute("aria-expanded", "false");
    expect(input).not.toHaveAttribute("aria-controls");
  });

  it("收藏按钮保留独立操作和按下状态", () => {
    let favorite = false;
    vi.mocked(isFavorite).mockImplementation(() => favorite);
    vi.mocked(toggleFavorite).mockImplementation(() => {
      favorite = !favorite;
      return favorite;
    });
    render(<FeatureSearch onNavigate={vi.fn()} />);

    fireEvent.change(screen.getByRole("searchbox", { name: "搜索功能或设置" }), {
      target: { value: "数据库清理" },
    });
    fireEvent.click(screen.getByRole("button", { name: "收藏数据库清理优化" }));

    expect(toggleFavorite).toHaveBeenCalledWith("performance-db_clean-enabled");
    expect(
      screen
        .getByRole("button", { name: "取消收藏数据库清理优化" })
        .getAttribute("aria-pressed"),
    ).toBe("true");
  });

  it("匹配项超过上限时只展示前 20 项", () => {
    render(<FeatureSearch onNavigate={vi.fn()} />);

    fireEvent.change(screen.getByRole("searchbox", { name: "搜索功能或设置" }), {
      target: { value: "e" },
    });

    const resultList = screen.getByRole("list", { name: "功能搜索结果" });
    expect(within(resultList).getAllByRole("button", { name: /^打开/ })).toHaveLength(20);
    expect(screen.getByText(/显示前 20 项/)).not.toBeNull();
  });
});
