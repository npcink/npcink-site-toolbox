import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

const readSource = (relativePath: string) => readFileSync(fileURLToPath(new URL(relativePath, import.meta.url)), "utf8");
const source = readSource("../../../../../admin/partials/optimize/admin/open_all_edit_tabs.js");

const runScript = (edit: boolean, view: boolean) => {
  Object.assign(window, {
    npcinkSiteToolboxOpenAllEditTabs: { actions: { edit, view } },
  });
  new Function("window", "document", source)(window, document);
};

const popup = () => {
  const tab = { opener: window as Window | null, location: { href: "" } };
  const navigate = vi.fn((url: string) => {
    expect(tab.opener).toBeNull();
    return url;
  });
  Object.defineProperty(tab.location, "href", { set: navigate });
  return { tab, navigate };
};

describe("post-list bulk opening", () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div class="tablenav top"><div class="actions"></div></div>
      <table><tbody id="the-list">
        <tr id="post-12"><td><div class="row-actions">
          <span class="edit"><a href="/wp-admin/post.php?post=12&action=edit">Edit</a></span>
          <span class="view"><a href="/portfolio/work-12/">View</a></span>
        </div></td></tr>
        <tr id="post-13"><td><div class="row-actions">
          <span class="edit"><a href="/wp-admin/post.php?post=13&action=edit">Edit</a></span>
          <span class="preview"><a href="/?p=13&preview=true">Preview</a></span>
        </div></td></tr>
      </tbody></table>
      <div class="row-actions"><span class="view"><a href="/outside-list/">Unrelated</a></span></div>`;
    vi.spyOn(window, "alert").mockImplementation(() => {});
    vi.spyOn(window, "open");
  });

  afterEach(() => {
    vi.restoreAllMocks();
    Reflect.deleteProperty(window, "npcinkSiteToolboxOpenAllEditTabs");
    document.body.innerHTML = "";
  });

  it.each([[true, false, 1], [false, true, 1], [true, true, 2], [false, false, 0]])(
    "shows only enabled actions (%s, %s)", (edit, view, count) => {
      runScript(Boolean(edit), Boolean(view));
      expect(document.querySelectorAll("button")).toHaveLength(Number(count));
      expect(document.body.textContent?.includes("在新标签页打开全部编辑")).toBe(Boolean(edit));
      expect(document.body.textContent?.includes("在新标签页打开全部查看")).toBe(Boolean(view));
    },
  );

  it("opens each editable row and clears opener before navigation without a blocked alert", () => {
    const first = popup();
    const second = popup();
    vi.mocked(window.open).mockReturnValueOnce(first.tab as unknown as Window).mockReturnValueOnce(second.tab as unknown as Window);
    runScript(true, false);
    document.querySelector<HTMLButtonElement>("button")!.click();
    expect(window.open).toHaveBeenNthCalledWith(1, "", "_blank");
    expect(window.open).toHaveBeenNthCalledWith(2, "", "_blank");
    expect(first.navigate).toHaveBeenCalledWith(new URL("/wp-admin/post.php?post=12&action=edit", window.location.href).href);
    expect(second.navigate).toHaveBeenCalledWith(new URL("/wp-admin/post.php?post=13&action=edit", window.location.href).href);
    expect(window.alert).not.toHaveBeenCalled();
  });

  it("opens only native view links on the current page, excluding previews and other lists", () => {
    const opened = popup();
    vi.mocked(window.open).mockReturnValue(opened.tab as unknown as Window);
    runScript(false, true);
    document.querySelector<HTMLButtonElement>("button")!.click();
    expect(window.open).toHaveBeenCalledTimes(1);
    expect(opened.navigate).toHaveBeenCalledWith(new URL("/portfolio/work-12/", window.location.href).href);
    expect(window.alert).not.toHaveBeenCalled();
  });

  it("counts actual blocked windows and continues opening subsequent rows", () => {
    const opened = popup();
    vi.mocked(window.open).mockReturnValueOnce(null).mockReturnValueOnce(opened.tab as unknown as Window);
    runScript(true, false);
    document.querySelector<HTMLButtonElement>("button")!.click();
    expect(opened.navigate).toHaveBeenCalledTimes(1);
    expect(window.alert).toHaveBeenCalledWith("有 1 个标签页被浏览器拦截，请在允许本站弹出式窗口后重试。");
  });

  it("reports an empty view set without opening windows", () => {
    document.querySelector("#the-list .view")!.remove();
    runScript(false, true);
    document.querySelector<HTMLButtonElement>("button")!.click();
    expect(window.open).not.toHaveBeenCalled();
    expect(window.alert).toHaveBeenCalledWith("当前列表没有可查看的内容。");
  });

  it("does nothing outside a list toolbar", () => {
    document.querySelector(".tablenav")!.remove();
    runScript(true, true);
    expect(document.querySelectorAll("button")).toHaveLength(0);
    expect(window.open).not.toHaveBeenCalled();
  });
});
