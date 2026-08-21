import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

import About from "./index";

vi.mock("@/components/about/runtime-status", () => ({
  default: () => <div>运行状态面板</div>,
}));

vi.mock("@/components/about/ai-diagnostics", () => ({
  default: () => <div>AI 诊断面板</div>,
}));

describe("About tabs", () => {
  it("opens practical help first and keeps AI diagnostics addressable", () => {
    window.history.replaceState({}, "", "/wp-admin/admin.php?page=npcink-site-toolbox&view=about");
    render(<About />);

    expect(screen.getByRole("tab", { name: "使用帮助" })).toHaveAttribute("aria-selected", "true");
    expect(screen.getByText("保存与离开保护")).toBeInTheDocument();
    expect(screen.getByText("图片 Alt 检查")).toBeInTheDocument();
    expect(screen.getByText(/不提供 Alt 自动写入/)).toBeInTheDocument();
    expect(screen.queryByText("图片 Alt 补全")).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole("tab", { name: "AI 诊断" }));

    expect(screen.getByText("AI 诊断面板")).toBeInTheDocument();
    expect(screen.queryByText("运行状态面板")).not.toBeInTheDocument();
    expect(window.location.search).toContain("tab=about-help.ai-diagnostics");
  });
});
