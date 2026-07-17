import { Form } from "antd";
import axios from "axios";
import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
  within,
} from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

const dataContextMock = vi.hoisted(() => ({
  apiBase: "https://example.com/subdirectory/wp-json/mabox/v1",
  restNonce: "rest-nonce",
}));

vi.mock("@/tool/dataContext", () => ({
  get ApiBase() {
    return dataContextMock.apiBase;
  },
  get RestNonce() {
    return dataContextMock.restNonce;
  },
}));

import SelectImage from "@/basic/selectImage";

const media = [
  {
    id: 11,
    source_url: "https://example.com/uploads/first.jpg",
    slug: "first",
    alt_text: "精选封面",
    title: { rendered: "第一张图片" },
    media_details: {
      sizes: {
        medium: { source_url: "https://example.com/uploads/first-medium.jpg" },
      },
    },
  },
  {
    id: 12,
    source_url: "https://example.com/uploads/second.jpg",
    slug: "second",
    alt_text: "",
    title: { rendered: "第二张图片" },
  },
];

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

beforeEach(() => {
  dataContextMock.apiBase =
    "https://example.com/subdirectory/wp-json/mabox/v1";
  dataContextMock.restNonce = "rest-nonce";
  const getComputedStyle = window.getComputedStyle.bind(window);
  vi.spyOn(window, "getComputedStyle").mockImplementation((element) =>
    getComputedStyle(element),
  );
  vi.spyOn(axios, "get").mockResolvedValue({ data: media });
});

describe("SelectImage", () => {
  it.each([
    [
      "https://example.com/subdirectory/wp-json/mabox/v1",
      "https://example.com/subdirectory/wp-json/wp/v2/media?per_page=12",
    ],
    [
      "https://example.com/subdirectory/?rest_route=/mabox/v1&context=edit",
      "https://example.com/subdirectory/?rest_route=/wp/v2/media&context=edit&per_page=12",
    ],
    [
      "/api",
      "/api/wp-json/wp/v2/media?per_page=12",
    ],
  ])("从现有 API 契约推导媒体端点：%s", async (apiBase, expected) => {
    dataContextMock.apiBase = apiBase;
    vi.mocked(axios.get).mockResolvedValueOnce({ data: [] });
    render(<SelectImage aria-label="媒体图片" value="" onChange={vi.fn()} />);

    fireEvent.click(screen.getByRole("button", { name: "为媒体图片选择图片" }));

    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith(expected, {
        headers: { "X-WP-Nonce": "rest-nonce" },
      });
    });
  });

  it("保留 Form 标签和说明关联，并以字符串报告手动输入", () => {
    const onChange = vi.fn();
    render(
      <Form initialValues={{ image: "https://example.com/old.jpg" }}>
        <Form.Item label="倒计时图片" name="image" extra="建议使用横向图片">
          <SelectImage onChange={onChange} />
        </Form.Item>
      </Form>,
    );

    const input = screen.getByRole("textbox", { name: "倒计时图片" });
    const description = screen.getByText("建议使用横向图片");

    expect(input).toHaveValue("https://example.com/old.jpg");
    expect(input).toHaveAttribute("aria-describedby", description.id);
    expect(
      screen.getByRole("button", { name: "为倒计时图片选择图片" }),
    ).toHaveAttribute("aria-describedby", description.id);

    fireEvent.change(input, { target: { value: "https://example.com/new.jpg" } });
    expect(onChange).toHaveBeenCalledWith("https://example.com/new.jpg");
  });

  it("从 ApiBase 推导媒体端点、携带 nonce，并在单个有名称的选项组中确认 URL", async () => {
    let resolveRequest: ((value: { data: typeof media }) => void) | undefined;
    vi.mocked(axios.get).mockReturnValueOnce(
      new Promise((resolve) => {
        resolveRequest = resolve;
      }),
    );
    const onChange = vi.fn();

    render(
      <SelectImage
        aria-label="专题头图"
        value="https://example.com/uploads/second.jpg"
        onChange={onChange}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "为专题头图选择图片" }));

    expect(axios.get).toHaveBeenCalledWith(
      "https://example.com/subdirectory/wp-json/wp/v2/media?per_page=12",
      { headers: { "X-WP-Nonce": "rest-nonce" } },
    );
    expect(screen.getByRole("status")).toHaveTextContent("正在加载媒体库");

    resolveRequest?.({ data: media });

    const group = await screen.findByRole("radiogroup", { name: "媒体库图片" });
    expect(within(group).getAllByRole("radio")).toHaveLength(2);
    expect(screen.getByRole("img", { name: "精选封面" })).toHaveAttribute(
      "src",
      "https://example.com/uploads/first-medium.jpg",
    );
    expect(screen.getByRole("img", { name: "第二张图片" })).toBeInTheDocument();

    fireEvent.click(screen.getByRole("radio", { name: "精选封面" }));
    fireEvent.click(screen.getByRole("button", { name: "使用所选图片" }));

    expect(onChange).toHaveBeenCalledWith("https://example.com/uploads/first.jpg");
    await waitFor(() => {
      expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });
  });

  it("取消时丢弃草稿，重新打开和外部值变化时同步当前值", async () => {
    const { rerender } = render(
      <SelectImage
        aria-label="文章头图"
        value="https://example.com/uploads/first.jpg"
        onChange={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "为文章头图选择图片" }));
    await screen.findByRole("radiogroup", { name: "媒体库图片" });
    fireEvent.click(screen.getByRole("radio", { name: "第二张图片" }));
    fireEvent.click(screen.getByRole("button", { name: "取消" }));

    fireEvent.click(screen.getByRole("button", { name: "为文章头图选择图片" }));
    expect(await screen.findByRole("radio", { name: "精选封面" })).toBeChecked();

    rerender(
      <SelectImage
        aria-label="文章头图"
        value="https://example.com/uploads/second.jpg"
        onChange={vi.fn()}
      />,
    );
    expect(screen.getByRole("radio", { name: "第二张图片" })).toBeChecked();
  });

  it("加载失败时提供可访问错误和重试操作", async () => {
    vi.mocked(axios.get)
      .mockRejectedValueOnce(new Error("network unavailable"))
      .mockResolvedValueOnce({ data: media });

    render(<SelectImage aria-label="封面" value="" onChange={vi.fn()} />);
    fireEvent.click(screen.getByRole("button", { name: "为封面选择图片" }));

    expect(await screen.findByRole("alert")).toHaveTextContent("媒体库加载失败");
    fireEvent.click(screen.getByRole("button", { name: "重试加载媒体库" }));

    expect(await screen.findByRole("radiogroup", { name: "媒体库图片" })).toBeInTheDocument();
    expect(axios.get).toHaveBeenCalledTimes(2);
  });
});
