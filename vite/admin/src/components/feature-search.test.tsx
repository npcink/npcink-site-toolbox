import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";

import FeatureSearch from "@/components/feature-search";

vi.mock("@/tool/featureIndex", () => ({
  fetchFeatureIndex: vi.fn().mockResolvedValue([
    {
      id: "performance-db_clean-enabled",
      label: "数据库清理优化",
      tabKey: "maintenance",
      tabLabel: "维护工具",
      keywords: ["数据库", "清理"],
      tags: ["性能"],
    },
  ]),
}));

vi.mock("@/tool/favorites", () => ({
  isFavorite: vi.fn().mockReturnValue(false),
  toggleFavorite: vi.fn(),
}));

describe("FeatureSearch", () => {
  it("通过可聚焦按钮跳转到语义化视图", async () => {
    const onNavigate = vi.fn();
    render(<FeatureSearch onNavigate={onNavigate} />);

    fireEvent.change(screen.getByRole("textbox", { name: "搜索功能或设置" }), {
      target: { value: "数据库清理" },
    });
    fireEvent.click(await screen.findByRole("button", { name: "打开数据库清理优化" }));

    expect(onNavigate).toHaveBeenCalledWith("maintenance", "performance-db_clean-enabled");
  });
});
