import { useEffect } from "react";

export const UNSAVED_CHANGES_MESSAGE = "还有设置尚未保存，离开后这些修改将丢失。确定离开吗？";

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
