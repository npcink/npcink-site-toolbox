import { describe, expect, it, vi } from "vitest";

import {
  createBeforeUnloadHandler,
  confirmUnsavedNavigation,
  UNSAVED_CHANGES_MESSAGE,
} from "@/tool/unsavedChanges";

describe("unsaved changes guard", () => {
  it("没有待保存修改时直接允许导航", () => {
    const confirm = vi.fn();

    expect(confirmUnsavedNavigation(false, confirm)).toBe(true);
    expect(confirm).not.toHaveBeenCalled();
  });

  it("有待保存修改时允许用户取消或确认导航", () => {
    const cancel = vi.fn().mockReturnValue(false);
    const proceed = vi.fn().mockReturnValue(true);

    expect(confirmUnsavedNavigation(true, cancel)).toBe(false);
    expect(cancel).toHaveBeenCalledWith(UNSAVED_CHANGES_MESSAGE);
    expect(confirmUnsavedNavigation(true, proceed)).toBe(true);
    expect(proceed).toHaveBeenCalledWith(UNSAVED_CHANGES_MESSAGE);
  });

  it("只有存在待保存修改时才阻止页面卸载", () => {
    const cleanEvent = {
      preventDefault: vi.fn(),
      returnValue: "unchanged",
    } as unknown as BeforeUnloadEvent;
    const dirtyEvent = {
      preventDefault: vi.fn(),
      returnValue: "unchanged",
    } as unknown as BeforeUnloadEvent;

    createBeforeUnloadHandler(false)(cleanEvent);
    expect(cleanEvent.preventDefault).not.toHaveBeenCalled();
    expect(cleanEvent.returnValue).toBe("unchanged");

    createBeforeUnloadHandler(true)(dirtyEvent);
    expect(dirtyEvent.preventDefault).toHaveBeenCalledOnce();
    expect(dirtyEvent.returnValue).toBe("");
  });
});
