import { useEffect } from "react";

import { __ } from "@/tool/i18n";

// 应用内切换视图时修改仍会保留（可返回统一保存），文案必须如实描述
export const UNSAVED_CHANGES_MESSAGE = __(
  "还有设置尚未保存。切换视图不会丢失这些修改，可稍后返回统一保存；关闭或刷新页面则不会保存。仍要切换吗？",
);

export function confirmUnsavedNavigation(
  hasUnsavedChanges: boolean,
  confirm: (message: string) => boolean = window.confirm,
): boolean {
  return !hasUnsavedChanges || confirm(UNSAVED_CHANGES_MESSAGE);
}

export function createBeforeUnloadHandler(hasUnsavedChanges: boolean) {
  return (event: BeforeUnloadEvent) => {
    if (!hasUnsavedChanges) return;

    event.preventDefault();
    event.returnValue = "";
  };
}

export function useUnsavedChangesGuard(hasUnsavedChanges: boolean): void {
  useEffect(() => {
    const handleBeforeUnload = createBeforeUnloadHandler(hasUnsavedChanges);
    window.addEventListener("beforeunload", handleBeforeUnload);
    return () => window.removeEventListener("beforeunload", handleBeforeUnload);
  }, [hasUnsavedChanges]);
}
