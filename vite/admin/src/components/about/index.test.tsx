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
  it("places AI diagnostics in its own addressable tab", () => {
    window.history.replaceState({}, "", "/wp-admin/admin.php?page=npcink-site-toolbox&view=about");
    render(<About />);

    expect(screen.getByRole("tab", { name: "运行状态" })).toHaveAttribute("aria-selected", "true");
    fireEvent.click(screen.getByRole("tab", { name: "AI 诊断" }));

    expect(screen.getByText("AI 诊断面板")).toBeInTheDocument();
    expect(screen.queryByText("运行状态面板")).not.toBeInTheDocument();
    expect(window.location.search).toContain("tab=about-help.ai-diagnostics");
  });
});
