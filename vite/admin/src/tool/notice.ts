import "./notice.css";
import { __ } from "@/tool/i18n";

type NoticeKind = "info" | "success" | "warning" | "error";

const MAX_NOTICES = 3;
const NOTICE_DURATION_MS = 2000;
const activeNotices: HTMLElement[] = [];
const removalTimers = new Map<HTMLElement, number>();
const announcementTimers = new Map<HTMLElement, number>();

let noticeStack: HTMLElement | null = null;
let politeAnnouncer: HTMLElement | null = null;
let assertiveAnnouncer: HTMLElement | null = null;

const createAnnouncer = (
  className: string,
  role: "status" | "alert",
  ariaLive: "polite" | "assertive",
): HTMLElement => {
  const announcer = document.createElement("div");
  announcer.className = `mabox-notice-announcer ${className}`;
  announcer.setAttribute("role", role);
  announcer.setAttribute("aria-live", ariaLive);
  announcer.setAttribute("aria-atomic", "true");
  return announcer;
};

const getNoticeAnnouncers = (): {
  polite: HTMLElement;
  assertive: HTMLElement;
} | null => {
  if (typeof document === "undefined") return null;

  if (politeAnnouncer?.isConnected && assertiveAnnouncer?.isConnected) {
    return { polite: politeAnnouncer, assertive: assertiveAnnouncer };
  }

  politeAnnouncer = createAnnouncer(
    "mabox-notice-announcer--polite",
    "status",
    "polite",
  );
  assertiveAnnouncer = createAnnouncer(
    "mabox-notice-announcer--assertive",
    "alert",
    "assertive",
  );
  document.body.append(politeAnnouncer, assertiveAnnouncer);

  return { polite: politeAnnouncer, assertive: assertiveAnnouncer };
};

const announceNotice = (
  announcers: { polite: HTMLElement; assertive: HTMLElement },
  kind: NoticeKind,
  message: string,
): void => {
  const announcer = kind === "error" ? announcers.assertive : announcers.polite;
  const previousTimer = announcementTimers.get(announcer);
  if (previousTimer !== undefined) window.clearTimeout(previousTimer);

  announcer.textContent = "";
  announcementTimers.set(
    announcer,
    window.setTimeout(() => {
      announcer.textContent = message;
      announcementTimers.delete(announcer);
    }, 0),
  );
};

const getNoticeStack = (): HTMLElement | null => {
  if (typeof document === "undefined") return null;

  if (noticeStack?.isConnected) return noticeStack;

  noticeStack = document.createElement("div");
  noticeStack.className = "mabox-notice-stack";
  noticeStack.setAttribute("aria-label", __("系统通知"));
  document.body.appendChild(noticeStack);
  return noticeStack;
};

const removeNotice = (element: HTMLElement): void => {
  const timer = removalTimers.get(element);
  if (timer !== undefined) window.clearTimeout(timer);
  removalTimers.delete(element);

  const index = activeNotices.indexOf(element);
  if (index !== -1) activeNotices.splice(index, 1);
  element.remove();

  if (activeNotices.length === 0 && noticeStack) {
    noticeStack.remove();
    noticeStack = null;
  }
};

const showNotice = (kind: NoticeKind, message: string): void => {
  const announcers = getNoticeAnnouncers();
  const stack = getNoticeStack();
  if (!announcers || !stack) return;

  while (activeNotices.length >= MAX_NOTICES) {
    removeNotice(activeNotices[0]);
  }

  const element = document.createElement("div");
  element.className = `mabox-notice mabox-notice--${kind}`;

  const content = document.createElement("span");
  content.className = "mabox-notice__content";
  content.textContent = message;
  element.appendChild(content);

  stack.appendChild(element);
  activeNotices.push(element);
  announceNotice(announcers, kind, message);
  removalTimers.set(
    element,
    window.setTimeout(() => removeNotice(element), NOTICE_DURATION_MS),
  );
};

export const notice = Object.freeze({
  info: (message: string): void => showNotice("info", message),
  success: (message: string): void => showNotice("success", message),
  warning: (message: string): void => showNotice("warning", message),
  error: (message: string): void => showNotice("error", message),
});
