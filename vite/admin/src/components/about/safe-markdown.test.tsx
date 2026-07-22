import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";

import SafeMarkdown from "./safe-markdown";

describe("SafeMarkdown", () => {
  it("renders the diagnostic Markdown structure", () => {
    render(
      <SafeMarkdown
        markdown={[
          "### 已确认问题",
          "1. **版本异常** (`wp-core.version`)",
          "2. 需要核对",
          "",
          "- 安全步骤",
          "",
          "```",
          "wp core version",
          "```",
        ].join("\n")}
      />,
    );

    const result = screen.getByLabelText("DeepSeek 分析结果内容");
    expect(screen.getByRole("heading", { name: "已确认问题", level: 5 })).toBeInTheDocument();
    expect(screen.getByText("版本异常").tagName).toBe("STRONG");
    expect(screen.getByText("wp-core.version").tagName).toBe("CODE");
    expect(result.querySelectorAll("ol li")).toHaveLength(2);
    expect(result.querySelectorAll("ul li")).toHaveLength(1);
    expect(result.querySelector("pre code")).toHaveTextContent("wp core version");
  });

  it("renders GFM tables, task lists, and strikethrough", () => {
    render(
      <SafeMarkdown
        markdown={[
          "| 检查项 | 状态 |",
          "| --- | --- |",
          "| 对象缓存 | 未启用 |",
          "",
          "- [x] 已检查 PHP 内存",
          "- [ ] 复查慢查询",
          "",
          "~~旧结论~~",
          "",
          "参考 https://example.com/diagnostics",
        ].join("\n")}
      />,
    );

    const result = screen.getByLabelText("DeepSeek 分析结果内容");
    expect(screen.getByRole("table")).toBeInTheDocument();
    expect(screen.getAllByRole("row")).toHaveLength(2);
    expect(screen.getByText("旧结论").tagName).toBe("DEL");
    const checkboxes = screen.getAllByRole("checkbox");
    expect(checkboxes).toHaveLength(2);
    expect(checkboxes[0]).toBeChecked();
    expect(checkboxes[0]).toBeDisabled();
    expect(result.querySelector("a")).toBeNull();
    expect(result).toHaveTextContent("https://example.com/diagnostics");
  });

  it("drops model HTML and disables Markdown links", () => {
    render(
      <SafeMarkdown
        markdown={'### 检查\n<img src="x" onerror="alert(1)"> **保留正文** [危险链接](javascript:alert(1))'}
      />,
    );

    const result = screen.getByLabelText("DeepSeek 分析结果内容");
    expect(result.querySelector("img")).toBeNull();
    expect(result.querySelector("a")).toBeNull();
    expect(result).not.toHaveTextContent("onerror");
    expect(screen.getByText("保留正文").tagName).toBe("STRONG");
    expect(screen.getByText("危险链接")).toBeInTheDocument();
  });
});
