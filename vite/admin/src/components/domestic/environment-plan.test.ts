import { describe, expect, it } from "vitest";

import { mergeEnvironmentProposal } from "@/components/domestic/environment-plan";
import { defaultVarOption } from "@/tool/defaultVar";

describe("mergeEnvironmentProposal", () => {
  it("只生成新的站点编辑态，不修改当前设置", () => {
    const current = { ...defaultVarOption.optimize.site };
    const before = { ...current };

    const next = mergeEnvironmentProposal(current, {
      cdn_replace: true,
      cdn_gravatar: true,
      cdn_gravatar_mirror: "cravatar.cn/avatar/",
    });

    expect(current).toEqual(before);
    expect(next).not.toBe(current);
    expect(next).toEqual(expect.objectContaining({
      cdn_replace: true,
      cdn_gravatar: true,
      cdn_gravatar_mirror: "cravatar.cn/avatar/",
    }));
  });

  it("拒绝未知字段和错误类型", () => {
    expect(() => mergeEnvironmentProposal(defaultVarOption.optimize.site, {
      unknown: true,
    })).toThrow("无效字段");
    expect(() => mergeEnvironmentProposal(defaultVarOption.optimize.site, {
      cdn_replace: "true",
    })).toThrow("无效字段");
  });
});
