import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Page from "@/components/page";

vi.mock("@/components/page/comment", () => ({ default: () => <div>评论内容</div> }));
vi.mock("@/components/page/feature", () => ({ default: () => <div>外观内容</div> }));
vi.mock("@/components/page/function", () => ({ default: () => <div>功能内容</div> }));
vi.mock("@/components/page/jurisdiction", () => ({ default: () => <div>权限内容</div> }));

afterEach(cleanup);

describe("内容与页面标签页", () => {
  it("暴露标签语义并支持方向键、Home 和 End 导航", () => {
    render(<Page />);

    expect(screen.getByRole("tablist", { name: "内容与页面分组" })).toBeInTheDocument();
    const appearance = screen.getByRole("tab", { name: "外观" });
    const permission = screen.getByRole("tab", { name: "权限" });
    expect(appearance).toHaveAttribute("aria-selected", "true");
    expect(appearance).toHaveAttribute("tabindex", "0");
    expect(permission).toHaveAttribute("tabindex", "-1");
    expect(screen.getByRole("tabpanel")).toHaveAccessibleName("外观");

    fireEvent.keyDown(appearance, { key: "ArrowRight" });
    expect(permission).toHaveAttribute("aria-selected", "true");
    expect(screen.getByText("权限内容")).toBeInTheDocument();
    expect(screen.getByRole("tabpanel")).toHaveAccessibleName("权限");

    fireEvent.keyDown(permission, { key: "End" });
    const comment = screen.getByRole("tab", { name: "评论" });
    expect(comment).toHaveAttribute("aria-selected", "true");
    expect(screen.getByText("评论内容")).toBeInTheDocument();

    fireEvent.keyDown(comment, { key: "Home" });
    expect(appearance).toHaveAttribute("aria-selected", "true");
  });

  it("根据搜索目标自动打开所属分组", () => {
    render(<Page targetItemId="page-comment-sensitive_words" />);

    expect(screen.getByRole("tab", { name: "评论" })).toHaveAttribute("aria-selected", "true");
    expect(screen.getByText("评论内容")).toBeInTheDocument();
  });
});
