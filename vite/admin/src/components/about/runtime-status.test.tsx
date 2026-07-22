import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";

import type { RuntimeFeatureStatus } from "@/tool/interface";
import RuntimeStatus from "./runtime-status";

const apiMocks = vi.hoisted(() => ({
  getFeatureStatus: vi.fn(),
}));

vi.mock("@/api", () => ({
  diagnosticsApi: apiMocks,
}));

const featureStatus: RuntimeFeatureStatus = {
  plugin: { name: "Npcink Site Toolbox", version: "3.2.0" },
  environment: { wordpress_version: "7.0.2", php_version: "8.4.21" },
  counts: { registered: 55, active: 2, always_loaded: 1, editor_tools: 2 },
  modules: [
    {
      id: "optimize.widgets",
      label: "站点小工具",
      category: "optimize",
      category_label: "站点与媒体",
      view: "site",
      target_id: "",
      scope: "both",
      tier: "core",
      always_loaded: true,
    },
    {
      id: "performance.oss",
      label: "对象存储 / OSS",
      category: "performance",
      category_label: "存储与维护",
      view: "maintenance",
      target_id: "performance-oss-enabled",
      scope: "both",
      tier: "core",
      always_loaded: false,
    },
  ],
  editor_tools: [
    {
      id: "npcink-site-toolbox/resource-download-card",
      type: "pattern",
      title: "资源下载卡片",
      description: "展示下载入口。",
    },
    {
      id: "npcink/github-project",
      type: "block",
      title: "GitHub 项目",
      description: "展示公开仓库资料。",
    },
  ],
  diagnostics: {
    status: "good",
    items: [
      {
        id: "php_version",
        title: "PHP 版本",
        status: "good",
        message: "当前 PHP 版本满足最低要求。",
      },
    ],
    module_risks: [],
    generated_at: "2026-07-21 12:00:00",
  },
  generated_at: "2026-07-21 12:00:00",
};

describe("RuntimeStatus", () => {
  beforeEach(() => {
    apiMocks.getFeatureStatus.mockReset();
    apiMocks.getFeatureStatus.mockResolvedValue({ success: true, data: featureStatus });
    window.history.replaceState({}, "", "/wp-admin/admin.php?page=npcink-site-toolbox&view=about");
  });

  it("shows factual runtime modules without the AI workflow", async () => {
    const onNavigate = vi.fn();
    render(<RuntimeStatus onNavigate={onNavigate} />);

    expect(await screen.findByRole("heading", { name: "功能与运行状态" })).toBeInTheDocument();
    expect(screen.getByText("2 / 55")).toBeInTheDocument();
    expect(screen.getByText("站点小工具")).toBeInTheDocument();
    expect(screen.getByText("对象存储 / OSS")).toBeInTheDocument();
    expect(screen.getByText("资源下载卡片")).toBeInTheDocument();
    expect(screen.getByText("GitHub 项目")).toBeInTheDocument();
    expect(screen.getByText(/本页面只读，不会发送外部请求/)).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: "生成诊断报告" })).not.toBeInTheDocument();

    const settingsLink = screen.getByRole("link", { name: "前往对象存储 / OSS设置" });
    fireEvent.click(settingsLink);
    expect(onNavigate).toHaveBeenCalledWith("maintenance", "performance-oss-enabled");
  });

  it("keeps invalid responses out of the working surface and allows retry", async () => {
    apiMocks.getFeatureStatus
      .mockResolvedValueOnce({ success: true, data: { plugin: null } })
      .mockResolvedValueOnce({ success: true, data: featureStatus });
    render(<RuntimeStatus />);

    expect(await screen.findByRole("alert")).toHaveTextContent("运行状态暂时不可用");
    fireEvent.click(screen.getByRole("button", { name: "重新获取" }));

    expect(await screen.findByText("站点小工具")).toBeInTheDocument();
    expect(apiMocks.getFeatureStatus).toHaveBeenCalledTimes(2);
  });
});
