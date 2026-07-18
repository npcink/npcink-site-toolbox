import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import Optimize from "@/components/optimize";

vi.mock("@/components/optimize/site", () => ({ default: () => <div>站点内容</div> }));
vi.mock("@/components/optimize/medium", () => ({ default: () => <div>媒体内容</div> }));
vi.mock("@/components/optimize/admin", () => ({ default: () => <div>后台内容</div> }));

afterEach(cleanup);

describe("站点与媒体标签页", () => {
  it("呈现三个分组并根据搜索目标自动切换", () => {
    render(<Optimize targetItemId="optimize-medium-img_add_tag" />);

    expect(screen.getByRole("tablist", { name: "站点与媒体分组" })).toBeInTheDocument();
    expect(screen.getAllByRole("tab")).toHaveLength(3);
    expect(screen.getByRole("tab", { name: "媒体" })).toHaveAttribute("aria-selected", "true");
    expect(screen.getByText("媒体内容")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("tab", { name: "后台" }));
    expect(screen.getByText("后台内容")).toBeInTheDocument();
    expect(screen.getByRole("tabpanel")).toHaveAccessibleName("后台");
  });
});
