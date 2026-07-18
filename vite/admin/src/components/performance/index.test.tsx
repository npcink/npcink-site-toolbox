import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Performance from "@/components/performance";

vi.mock("@/components/performance/oss", () => ({ default: () => <div>对象存储内容</div> }));
vi.mock("@/components/performance/seo_checker", () => ({ default: () => <div>SEO 检查内容</div> }));
vi.mock("@/components/performance/media_health", () => ({ default: () => <div>媒体体检内容</div> }));
vi.mock("@/components/performance/search_enhance", () => ({ default: () => <div>搜索增强内容</div> }));
vi.mock("@/components/performance/db_clean", () => ({ default: () => <div>数据库内容</div> }));

afterEach(cleanup);

describe("维护工具标签页", () => {
  it("呈现五个工具分组并根据搜索目标自动切换", () => {
    render(<Performance targetItemId="performance-db_clean-enabled" />);

    expect(screen.getByRole("tablist", { name: "维护工具分组" })).toBeInTheDocument();
    expect(screen.getAllByRole("tab")).toHaveLength(5);
    expect(screen.getByRole("tab", { name: "数据库" })).toHaveAttribute("aria-selected", "true");
    expect(screen.getByText("数据库内容")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("tab", { name: "SEO 检查" }));
    expect(screen.getByText("SEO 检查内容")).toBeInTheDocument();
    expect(screen.getByRole("tabpanel")).toHaveAccessibleName("SEO 检查");
  });
});
