import { beforeEach, describe, expect, it, vi } from "vitest";

import { buildSettingsSavePayload, saveOption } from "@/axios/save";
import {
  emptySecretStatus,
  fetchSettings,
} from "@/tool/dataContext";
import { defaultVarOption } from "@/tool/defaultVar";
import { diffConfig } from "@/tool/diff";
import { Option } from "@/tool/interface";
import { updateOptionValue } from "@/tool/option";

const restMocks = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
}));
const SETTINGS_REVISION = "a".repeat(64);

vi.mock("@/axios/public", () => ({
  restInstance: restMocks,
}));

function freshDefaults(): Option {
  return JSON.parse(JSON.stringify(defaultVarOption)) as Option;
}

describe("设置 REST 契约", () => {
  beforeEach(() => {
    restMocks.get.mockReset();
    restMocks.post.mockReset();
  });

  it("解析非敏感设置与独立凭据状态", async () => {
    const secretStatus = {
      ...emptySecretStatus(),
      "domestic.wechat.appsecret": { configured: true },
    };
    restMocks.get.mockResolvedValue({
      success: true,
      data: defaultVarOption,
      secretStatus,
      revision: SETTINGS_REVISION,
    });

    const response = await fetchSettings();

    expect(restMocks.get).toHaveBeenCalledWith(
      "/settings",
      { maboxNotify: false },
    );
    expect(response.data).toBe(defaultVarOption);
    expect(response.secretStatus).toEqual(secretStatus);
    expect(response.revision).toBe(SETTINGS_REVISION);
  });

  it("接受一份完整、独立的 fresh defaults", async () => {
    const settings = freshDefaults();
    restMocks.get.mockResolvedValue({
      success: true,
      data: settings,
      secretStatus: emptySecretStatus(),
      revision: SETTINGS_REVISION,
    });

    await expect(fetchSettings()).resolves.toMatchObject({ data: settings });
    expect(() => buildSettingsSavePayload(settings, {}, SETTINGS_REVISION)).not.toThrow();
  });

  it("GET 请求失败时向调用方抛错，不回退页面注入或默认配置", async () => {
    restMocks.get.mockRejectedValue(new Error("network unavailable"));

    await expect(fetchSettings()).rejects.toThrow("network unavailable");
  });

  it("拒绝缺少凭据状态的成功响应", async () => {
    restMocks.get.mockResolvedValue({ success: true, data: defaultVarOption });

    await expect(fetchSettings()).rejects.toThrow("缺少凭据状态");
  });

  it("拒绝服务端意外回显的原始凭据", async () => {
    const canary = "canary-saved-secret";
    restMocks.get.mockResolvedValue({
      success: true,
      data: {
        ...defaultVarOption,
        domestic: {
          ...defaultVarOption.domestic,
          wechat: {
            ...defaultVarOption.domestic.wechat,
            appsecret: canary,
          },
        },
      },
      secretStatus: emptySecretStatus(),
      revision: SETTINGS_REVISION,
    });

    await expect(fetchSettings()).rejects.toThrow("包含敏感字段");
  });

  it.each([
    ["空对象", () => ({})],
    ["缺少字段", () => {
      const settings = freshDefaults();
      delete (settings.optimize.site as Partial<typeof settings.optimize.site>).hide_top_toolbar;
      return settings;
    }],
    ["未知字段", () => {
      const settings = freshDefaults();
      (settings.optimize.site as Record<string, unknown>).unexpected = true;
      return settings;
    }],
    ["已清退的登录安全字段", () => {
      const settings = freshDefaults();
      (settings.domestic.login_security as unknown as Record<string, unknown>).fail_limit_enabled = false;
      return settings;
    }],
    ["字段类型错误", () => {
      const settings = freshDefaults();
      settings.page.jurisdiction.category_id = ["1" as unknown as number];
      return settings;
    }],
  ])("GET 拒绝不完整或不精确的 Option：%s", async (_label, makeSettings) => {
    restMocks.get.mockResolvedValue({
      success: true,
      data: makeSettings(),
      secretStatus: emptySecretStatus(),
      revision: SETTINGS_REVISION,
    });

    await expect(fetchSettings()).rejects.toThrow(/设置(缺少|包含|字段)/);
  });

  it("保存 payload 同样拒绝空、部分、未知和错误类型 Option", () => {
    const missing = freshDefaults();
    delete (missing.domestic.wechat as Partial<typeof missing.domestic.wechat>).appid;
    const unknown = freshDefaults();
    (unknown.performance.oss as Record<string, unknown>).unknown = "value";
    const wrongType = freshDefaults();
    wrongType.performance.oss.enabled = "true" as unknown as boolean;

    [{}, missing, unknown, wrongType].forEach((settings) => {
      expect(() => buildSettingsSavePayload(settings as Option, {}, SETTINGS_REVISION)).toThrow();
    });
  });

  it("保存只发送 settings 与 secretChanges 包装对象且不创建 localStorage 快照", async () => {
    const storageSpy = vi.spyOn(Storage.prototype, "setItem");
    restMocks.post.mockResolvedValue({ success: true });
    const secretChanges = {
      "domestic.wechat.appsecret": {
        operation: "replace" as const,
        value: "canary-new-secret",
      },
    };

    await saveOption(defaultVarOption, secretChanges, SETTINGS_REVISION);

    expect(restMocks.post).toHaveBeenCalledWith(
      "/settings",
      {
        settings: defaultVarOption,
        secretChanges,
        revision: SETTINGS_REVISION,
      },
      { maboxNotify: false },
    );
    expect(storageSpy).not.toHaveBeenCalled();
    storageSpy.mockRestore();
  });

  it("将 WP REST 保存错误规范化为不回显凭据的具体消息", async () => {
    const canary = "canary-secret-must-not-leak";
    restMocks.post.mockRejectedValue({
      response: {
        data: {
          code: "rest_save_failed",
          message: `保存 ${canary} 失败，已恢复为之前的设置`,
          data: { status: 500 },
        },
      },
    });

    await expect(saveOption(defaultVarOption, {
      "domestic.wechat.appsecret": {
        operation: "replace",
        value: canary,
      },
    }, SETTINGS_REVISION)).rejects.toThrow("保存 [已隐藏] 失败，已恢复为之前的设置");
  });

  it("客户端拒绝未知凭据路径、未知操作和空替换", () => {
    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "unknown.secret": { operation: "clear" },
    } as never, SETTINGS_REVISION)).toThrow("未知凭据路径");
    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "domestic.wechat.appsecret": { operation: "rotate" },
    } as never, SETTINGS_REVISION)).toThrow("未知凭据操作");
    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "domestic.wechat.appsecret": { operation: "replace", value: "" },
    }, SETTINGS_REVISION)).toThrow("替换值不能为空");
  });

  it("客户端拒绝纯空白、控制字符和超过 4096 字节的替换值", () => {
    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "domestic.wechat.appsecret": { operation: "replace", value: "   " },
    }, SETTINGS_REVISION)).toThrow("替换值不能为空");
    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "domestic.wechat.appsecret": { operation: "replace", value: "bad\nsecret" },
    }, SETTINGS_REVISION)).toThrow("不得包含控制字符");
    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "domestic.wechat.appsecret": { operation: "replace", value: "x".repeat(4097) },
    }, SETTINGS_REVISION)).toThrow("长度超出限制");

    expect(() => buildSettingsSavePayload(defaultVarOption, {
      "domestic.wechat.appsecret": { operation: "replace", value: "x".repeat(4096) },
    }, SETTINGS_REVISION)).not.toThrow();
  });

  it("拒绝缺少或格式无效的配置版本", () => {
    expect(() => buildSettingsSavePayload(defaultVarOption, {}, "")).toThrow("配置版本无效");
    expect(() => buildSettingsSavePayload(defaultVarOption, {}, "not-a-revision")).toThrow("配置版本无效");
  });

  it("普通设置更新不污染服务端基线，diff 仍能看到变更", () => {
    const lastSavedOption = defaultVarOption;
    const previousValue = lastSavedOption.optimize.site.hide_top_toolbar;
    const nextSite = {
      ...lastSavedOption.optimize.site,
      hide_top_toolbar: !previousValue,
    };

    const optionData = updateOptionValue(
      lastSavedOption,
      "optimize",
      "site",
      nextSite,
    );

    expect(lastSavedOption.optimize.site.hide_top_toolbar).toBe(previousValue);
    expect(optionData.optimize).not.toBe(lastSavedOption.optimize);
    expect(optionData.optimize.site).toBe(nextSite);
    expect(diffConfig(lastSavedOption, optionData)).toEqual([
      expect.objectContaining({
        path: "optimize.site.hide_top_toolbar",
        before: previousValue,
        after: !previousValue,
      }),
    ]);
  });
});
