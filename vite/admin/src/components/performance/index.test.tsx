import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Performance from "@/components/performance";

const moduleLoads = vi.hoisted(() => ({
  oss: vi.fn(),
  seoChecker: vi.fn(),
  mediaHealth: vi.fn(),
  searchEnhance: vi.fn(),
  dbClean: vi.fn(),
}));

vi.mock("@/components/performance/oss", () => {
  moduleLoads.oss();
  return { default: () => <div>对象存储内容</div> };
});
vi.mock("@/components/performance/seo_checker", () => {
  moduleLoads.seoChecker();
  return { default: () => <div>SEO 检查内容</div> };
});
vi.mock("@/components/performance/media_health", () => {
  moduleLoads.mediaHealth();
  return { default: () => <div>媒体体检内容</div> };
});
vi.mock("@/components/performance/search_enhance", () => {
  moduleLoads.searchEnhance();
  return { default: () => <div>搜索增强内容</div> };
});
vi.mock("@/components/performance/db_clean", () => {
  moduleLoads.dbClean();
  return { default: () => <div>数据库内容</div> };
});

afterEach(cleanup);

describe("存储与维护标签页", () => {
  it("只加载搜索目标分组，并在用户切换后加载下一分组", async () => {
    render(<Performance targetItemId="performance-db_clean-enabled" />);

    expect(screen.getByRole("tablist", { name: "存储与维护分组" })).toBeInTheDocument();
    expect(screen.getAllByRole("tab")).toHaveLength(5);
    expect(screen.getByRole("tab", { name: "数据库" })).toHaveAttribute("aria-selected", "true");
    expect(await screen.findByText("数据库内容")).toBeInTheDocument();
    await waitFor(() => expect(moduleLoads.dbClean).toHaveBeenCalledTimes(1));
    expect(moduleLoads.oss).not.toHaveBeenCalled();
    expect(moduleLoads.seoChecker).not.toHaveBeenCalled();
    expect(moduleLoads.mediaHealth).not.toHaveBeenCalled();
    expect(moduleLoads.searchEnhance).not.toHaveBeenCalled();

    fireEvent.click(screen.getByRole("tab", { name: "SEO 检查" }));
    expect(await screen.findByText("SEO 检查内容")).toBeInTheDocument();
    await waitFor(() => expect(moduleLoads.seoChecker).toHaveBeenCalledTimes(1));
    expect(screen.getByRole("tabpanel")).toHaveAccessibleName("SEO 检查");
  });
});
