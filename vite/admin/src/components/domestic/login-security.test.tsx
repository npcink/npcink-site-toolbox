import { cleanup, fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import Domestic from "@/components/domestic";
import { DataContext } from "@/tool/dataContext";
import { emptySecretStatus } from "@/tool/settingsSource";
import { defaultVarOption } from "@/tool/defaultVar";
import type { DomesticLoginSecurity, Option } from "@/tool/interface";

vi.mock("@/tool/riskyFeature", () => ({
  checkRiskyFeature: () => true,
}));

function renderDomestic(targetItemId?: string, optionData: Option = defaultVarOption) {
  const updateOption = vi.fn();

  render(
    <DataContext.Provider
      value={{
        optionData,
        updateOption,
        refreshOption: vi.fn(),
      discardChanges: vi.fn(),
        lastSavedOption: defaultVarOption,
        setLastSavedOption: vi.fn(),
        secretStatus: emptySecretStatus(),
        secretChanges: {},
        setSecretChange: vi.fn(),
        clearSecretChanges: vi.fn(),
        settingsState: "ready",
        settingsError: null,
      configEpoch: 0,
      }}
    >
      <Domestic targetItemId={targetItemId} />
    </DataContext.Provider>,
  );

  return updateOption;
}

function openLoginSecurityDrawer() {
  const cardTitle = screen.getByText("登录安全", { selector: ".mabox-module-card-title" });
  const card = cardTitle.closest(".mabox-module-card");
  expect(card).not.toBeNull();
  fireEvent.click(within(card as HTMLElement).getByRole("button", { name: /配\s*置/ }));
  return card as HTMLElement;
}

const getComputedStyle = window.getComputedStyle.bind(window);

beforeEach(() => {
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) => getComputedStyle(element));
});

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("登录安全设置", () => {
  it("只展示两项可验证能力，并为卡片和设置行使用唯一 canonical id", async () => {
    const updateOption = renderDomestic();
    const card = openLoginSecurityDrawer();

    expect(card).toHaveAttribute("id", "domestic-login_security");
    expect(await screen.findByText("登录安全设置")).toBeInTheDocument();
    expect(screen.getByText("在统计窗口内限制同一已存在账号与来源 IP 组合的连续失败尝试")).toBeInTheDocument();
    expect(screen.queryByText("在统计窗口内限制同一账号或来源 IP 的连续失败尝试")).not.toBeInTheDocument();

    const attemptRow = document.getElementById("domestic-login_security-attempt_limit_enabled");
    const authorGuardRow = document.getElementById("domestic-login_security-anonymous_author_guard_enabled");
    expect(attemptRow).not.toBeNull();
    expect(authorGuardRow).not.toBeNull();
    expect(document.querySelectorAll("#domestic-login_security")).toHaveLength(1);
    expect(document.querySelectorAll("#domestic-login_security-attempt_limit_enabled")).toHaveLength(1);
    expect(document.querySelectorAll("#domestic-login_security-anonymous_author_guard_enabled")).toHaveLength(1);

    expect(screen.queryByText("IP 失败锁定")).not.toBeInTheDocument();
    expect(screen.queryByText("自定义登录地址")).not.toBeInTheDocument();
    expect(screen.queryByText("登录通知")).not.toBeInTheDocument();
    expect(screen.queryByText("登录日志")).not.toBeInTheDocument();
    expect(screen.queryByText("IP 白名单")).not.toBeInTheDocument();

    expect(screen.queryByLabelText("失败尝试上限")).not.toBeInTheDocument();
    fireEvent.click(within(attemptRow as HTMLElement).getByRole("switch"));

    const attemptLimitInput = await screen.findByLabelText("失败尝试上限");
    expect(attemptLimitInput).toHaveAttribute("aria-valuemin", "2");
    expect(attemptLimitInput).toHaveAttribute("aria-valuemax", "20");
    expect(attemptLimitInput).toHaveAttribute("step", "1");
    expect(attemptLimitInput).toHaveValue("5");

    const attemptWindowInput = screen.getByLabelText("统计窗口（分钟）");
    expect(attemptWindowInput).toHaveAttribute("aria-valuemin", "1");
    expect(attemptWindowInput).toHaveAttribute("aria-valuemax", "1440");
    expect(attemptWindowInput).toHaveAttribute("step", "1");
    expect(attemptWindowInput).toHaveValue("15");

    const lockDurationInput = screen.getByLabelText("锁定时长（分钟）");
    expect(lockDurationInput).toHaveAttribute("aria-valuemin", "1");
    expect(lockDurationInput).toHaveAttribute("aria-valuemax", "1440");
    expect(lockDurationInput).toHaveAttribute("step", "1");
    expect(lockDurationInput).toHaveValue("30");

    fireEvent.change(attemptLimitInput, { target: { value: "6.8" } });
    fireEvent.blur(attemptLimitInput);
    await waitFor(() => {
      const latestCall = updateOption.mock.calls[updateOption.mock.calls.length - 1];
      const latestConfig = latestCall?.[2] as DomesticLoginSecurity | undefined;
      expect(latestConfig?.attempt_limit_count).toBe(7);
    });

    const advancedSummary = screen.getByText("高级：可信代理");
    fireEvent.click(advancedSummary);
    expect(advancedSummary.closest("details")).toHaveAttribute("open");
    expect(screen.getByLabelText("代理出口 IP")).toBeInTheDocument();

    await waitFor(() => {
      expect(updateOption).toHaveBeenLastCalledWith(
        "domestic",
        "login_security",
        expect.objectContaining({ attempt_limit_enabled: true }),
      );
    });
  });

  it("从搜索深链直接打开抽屉并定位匿名作者枚举行", async () => {
    renderDomestic("domestic-login_security-anonymous_author_guard_enabled");

    expect(await screen.findByText("登录安全设置")).toBeInTheDocument();
    expect(document.getElementById("domestic-login_security-anonymous_author_guard_enabled")).not.toBeNull();
  });
});
