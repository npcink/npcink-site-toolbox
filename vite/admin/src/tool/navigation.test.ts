import { describe, expect, it } from "vitest";

import {
  ADMIN_VIEWS,
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
      "security",
      "china",
      "maintenance",
      "about",
    ]);
    expect(isAdminView("security")).toBe(true);
    expect(isAdminView("13")).toBe(false);
  });

  it("restores a known view from the query string", () => {
    expect(getAdminViewFromSearch("?page=MaBox_config&view=china")).toBe("china");
  });

  it("falls back to overview for missing or unknown views", () => {
    expect(getAdminViewFromSearch("?page=MaBox_config")).toBe("overview");
    expect(normalizeAdminView("13")).toBe("overview");
    expect(normalizeAdminView("unknown")).toBe("overview");
  });

  it("writes the semantic view without losing other URL state", () => {
    expect(
      createAdminViewUrl(
        "https://example.test/wp-admin/plugins.php?page=MaBox_config&view=site#module",
        "maintenance",
      ),
    ).toBe("/wp-admin/plugins.php?page=MaBox_config&view=maintenance#module");
  });
});
