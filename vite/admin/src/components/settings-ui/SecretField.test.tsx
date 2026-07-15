import React, { useState } from "react";
import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";

import SecretField from "@/components/settings-ui/SecretField";
import { DataContext, emptySecretStatus } from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { SecretChange, SecretChanges, SecretPath } from "@/tool/interface";

const PATH: SecretPath = "domestic.wechat.appsecret";

const Harness: React.FC<{ configured: boolean }> = ({ configured }) => {
  const [changes, setChanges] = useState<SecretChanges>({});
  const status = emptySecretStatus();
  status[PATH] = { configured };

  const setSecretChange = (path: SecretPath, change?: SecretChange) => {
    setChanges((previous) => {
      const next = { ...previous };
      if (change) next[path] = change;
      else delete next[path];
      return next;
    });
  };

  return (
    <DataContext.Provider
      value={{
        optionData: defaultVarOption,
        updateOption: vi.fn(),
        refreshOption: async () => {},
        lastSavedOption: defaultVarOption,
        setLastSavedOption: vi.fn(),
        secretStatus: status,
        secretChanges: changes,
        setSecretChange,
        clearSecretChanges: () => setChanges({}),
        settingsState: "ready",
        settingsError: null,
      }}
    >
      <SecretField label="AppSecret" path={PATH} />
    </DataContext.Provider>
  );
};

afterEach(cleanup);

describe("SecretField", () => {
  it("已配置凭据初始值永远为空，并支持替换、清除和撤销", () => {
    render(<Harness configured />);
    const input = screen.getByLabelText("AppSecret新值") as HTMLInputElement;

    expect(screen.getByText("已配置")).toBeInTheDocument();
    expect(input.value).toBe("");

    fireEvent.change(input, { target: { value: "replacement-secret" } });
    expect(screen.getByText("将替换")).toBeInTheDocument();
    expect(input.value).toBe("replacement-secret");

    fireEvent.click(screen.getByRole("button", { name: "清除已保存凭据" }));
    expect(screen.getByText("将清除")).toBeInTheDocument();
    expect(input.value).toBe("");

    fireEvent.click(screen.getByRole("button", { name: "撤销凭据更改" }));
    expect(screen.getByText("已配置")).toBeInTheDocument();
    expect(input.value).toBe("");
  });

  it("未配置凭据不允许提交清除操作", () => {
    render(<Harness configured={false} />);

    expect(screen.getByText("未配置")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "清除已保存凭据" })).toBeDisabled();
  });
});
