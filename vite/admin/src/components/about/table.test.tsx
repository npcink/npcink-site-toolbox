import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

import { renderSource } from "./table";

describe("来源链接", () => {
  it("只将真实 HTTP URL 渲染为链接", () => {
    const { rerender } = render(renderSource("待完善"));

    expect(screen.getByText("待完善").closest("a")).toBeNull();

    rerender(renderSource("https://www.npc.ink/5783.html"));
    expect(screen.getByRole("link", { name: "https://www.npc.ink/5783.html" })).toHaveAttribute(
      "rel",
      "noreferrer",
    );
  });
});
