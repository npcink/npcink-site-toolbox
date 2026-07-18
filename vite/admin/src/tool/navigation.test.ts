import { describe, expect, it } from "vitest";

import {
  ADMIN_VIEWS,
  TARGETABLE_ADMIN_VIEWS,
  adminViewSupportsTargetItem,
  createAdminViewUrl,
  getAdminViewFromSearch,
  isAdminView,
  normalizeAdminView,
} from "@/tool/navigation";

describe("admin navigation", () => {
  it("recognizes only the semantic admin views", () => {
    expect(ADMIN_VIEWS).toEqual([
      "overview",
      "site",
      "content",
      "seo",
      "china",
      "maintenance",
      "about",
    ]);
    expect(isAdminView("security")).toBe(false);
    expect(isAdminView("13")).toBe(false);
  });

  it("restores a known view from the query string", () => {
    expect(getAdminViewFromSearch("?page=npcink-site-toolbox&view=china")).toBe("china");
  });

  it("falls back to overview for missing or unknown views", () => {
    expect(getAdminViewFromSearch("?page=npcink-site-toolbox")).toBe("overview");
    expect(getAdminViewFromSearch("?page=npcink-site-toolbox&view=security")).toBe("overview");
    expect(normalizeAdminView("security")).toBe("overview");
    expect(normalizeAdminView("13")).toBe("overview");
    expect(normalizeAdminView("unknown")).toBe("overview");
  });

  it("writes the semantic view without losing other URL state", () => {
    expect(
      createAdminViewUrl(
        "https://example.test/wp-admin/plugins.php?page=npcink-site-toolbox&view=site#module",
        "maintenance",
      ),
    ).toBe("/wp-admin/plugins.php?page=npcink-site-toolbox&view=maintenance#module");
  });

  it("passes search targets only to views that can reveal a matching item", () => {
    expect(TARGETABLE_ADMIN_VIEWS).toEqual([
      "site",
      "content",
      "seo",
      "china",
      "maintenance",
    ]);
    expect(adminViewSupportsTargetItem("site")).toBe(true);
    expect(adminViewSupportsTargetItem("maintenance")).toBe(true);
    expect(adminViewSupportsTargetItem("overview")).toBe(false);
    expect(adminViewSupportsTargetItem("about")).toBe(false);
  });
});
