export const ADMIN_VIEWS = [
  "overview",
  "site",
  "content",
  "seo",
  "security",
  "china",
  "maintenance",
  "about",
] as const;

export type AdminView = (typeof ADMIN_VIEWS)[number];

export const DEFAULT_ADMIN_VIEW: AdminView = "overview";

export function isAdminView(value: string | null | undefined): value is AdminView {
  return ADMIN_VIEWS.includes(value as AdminView);
}

export function normalizeAdminView(value: string | null | undefined): AdminView {
  return isAdminView(value) ? value : DEFAULT_ADMIN_VIEW;
}

export function getAdminViewFromSearch(search: string): AdminView {
  return normalizeAdminView(new URLSearchParams(search).get("view"));
}

export function createAdminViewUrl(currentUrl: string, view: AdminView): string {
  const url = new URL(currentUrl);
  url.searchParams.set("view", view);
  return `${url.pathname}${url.search}${url.hash}`;
}

export function writeAdminViewToHistory(
  view: AdminView,
  mode: "push" | "replace" = "push",
): void {
  const nextUrl = createAdminViewUrl(window.location.href, view);
  if (mode === "replace") {
    window.history.replaceState({ view }, "", nextUrl);
    return;
  }
  window.history.pushState({ view }, "", nextUrl);
}
