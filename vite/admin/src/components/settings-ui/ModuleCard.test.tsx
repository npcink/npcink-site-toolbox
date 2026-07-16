import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import ModuleCard from "@/components/settings-ui/ModuleCard";

afterEach(cleanup);

describe("ModuleCard", () => {
  it("用模块标题区分重复的配置按钮", () => {
    const onAction = vi.fn();
    render(
      <ModuleCard
        title="登录安全"
        description="保护登录入口"
        featureId="domestic-login-security"
        switchable={false}
        actionLabel="配置"
        onAction={onAction}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "配置：登录安全" }));
    expect(onAction).toHaveBeenCalledOnce();
  });
});
