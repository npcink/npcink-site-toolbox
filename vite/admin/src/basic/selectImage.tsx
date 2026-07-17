import {
  forwardRef,
  useCallback,
  useEffect,
  useRef,
  useState,
} from "react";
import {
  Button,
  Input,
  List,
  Modal,
  Radio,
  Space,
} from "antd";
import { FileImageOutlined } from "@ant-design/icons";
import type { InputProps, InputRef, RadioChangeEvent } from "antd";
import axios from "axios";

import { ApiBase, RestNonce } from "@/tool/dataContext";

interface MediaImage {
  id: number;
  source_url: string;
  slug?: string;
  alt_text?: string;
  title?: { rendered?: string };
  media_details?: {
    sizes?: Record<string, { source_url?: string }>;
  };
}

type SelectImageProps = Omit<
  InputProps,
  "defaultValue" | "onChange" | "value"
> & {
  value?: string;
  onChange?: (value: string) => void;
};

type LoadState = "idle" | "loading" | "ready" | "error";

const buildMediaEndpoint = (apiBase: string): string => {
  const [baseWithoutHash] = apiBase.split("#", 1);
  const queryIndex = baseWithoutHash.indexOf("?");

  if (queryIndex >= 0) {
    const basePath = baseWithoutHash.slice(0, queryIndex);
    const params = new URLSearchParams(baseWithoutHash.slice(queryIndex + 1));

    if (params.has("rest_route")) {
      params.set("rest_route", "/wp/v2/media");
      params.set("per_page", "12");
      const query = params
        .toString()
        .replace(
          /(^|&)rest_route=%2Fwp%2Fv2%2Fmedia(?=&|$)/i,
          "$1rest_route=/wp/v2/media",
        );

      return `${basePath}?${query}`;
    }
  }

  const normalizedBase = baseWithoutHash.split("?", 1)[0].replace(/\/+$/, "");

  if (normalizedBase === "/api") {
    return "/api/wp-json/wp/v2/media?per_page=12";
  }

  const restRoot = normalizedBase.replace(/\/mabox\/v1$/, "");

  return `${restRoot}/wp/v2/media?per_page=12`;
};

const isMediaImage = (value: unknown): value is MediaImage => {
  if (typeof value !== "object" || value === null) return false;

  const item = value as Partial<MediaImage>;
  return typeof item.id === "number" && typeof item.source_url === "string";
};

const mediaAlt = (item: MediaImage): string => {
  const altText = item.alt_text?.trim();
  if (altText) return altText;

  const title = item.title?.rendered?.replace(/<[^>]*>/g, "").trim();
  if (title) return title;

  return item.slug?.trim() || "媒体库图片";
};

const mediaThumbnail = (item: MediaImage): string =>
  item.media_details?.sizes?.medium?.source_url || item.source_url;

const SelectImage = forwardRef<InputRef, SelectImageProps>(
  (
    {
      value = "",
      onChange,
      placeholder = "图片地址",
      "aria-label": ariaLabel,
      "aria-describedby": ariaDescribedBy,
      id,
      disabled,
      readOnly,
      ...inputProps
    },
    ref,
  ) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [mediaImages, setMediaImages] = useState<MediaImage[]>([]);
    const [draftValue, setDraftValue] = useState(value);
    const [loadState, setLoadState] = useState<LoadState>("idle");
    const [fieldLabel, setFieldLabel] = useState(
      typeof ariaLabel === "string" ? ariaLabel : "当前字段",
    );
    const requestId = useRef(0);

    useEffect(() => {
      setDraftValue(value);
    }, [value]);

    useEffect(() => {
      if (typeof ariaLabel === "string" && ariaLabel.trim()) {
        setFieldLabel(ariaLabel.trim());
        return;
      }

      if (!id) return;

      const formLabel = Array.from(document.getElementsByTagName("label")).find(
        (label) => label.htmlFor === id,
      );
      const labelText = formLabel?.textContent?.trim();
      if (labelText) setFieldLabel(labelText);
    }, [ariaLabel, id]);

    useEffect(
      () => () => {
        requestId.current += 1;
      },
      [],
    );

    const getMediaData = useCallback(async () => {
      const currentRequest = requestId.current + 1;
      requestId.current = currentRequest;
      setLoadState("loading");

      try {
        const response = await axios.get(buildMediaEndpoint(ApiBase), {
          headers: { "X-WP-Nonce": RestNonce },
        });
        if (requestId.current !== currentRequest) return;

        if (!Array.isArray(response.data)) {
          throw new Error("Invalid media response");
        }

        setMediaImages(response.data.filter(isMediaImage));
        setLoadState("ready");
      } catch {
        if (requestId.current !== currentRequest) return;
        setMediaImages([]);
        setLoadState("error");
      }
    }, []);

    const showModal = () => {
      setDraftValue(value);
      setIsModalOpen(true);
      void getMediaData();
    };

    const handleOk = () => {
      onChange?.(draftValue);
      setIsModalOpen(false);
    };

    const handleCancel = () => {
      setDraftValue(value);
      setIsModalOpen(false);
    };

    const handleRadioChange = (event: RadioChangeEvent) => {
      setDraftValue(event.target.value as string);
    };

    return (
      <>
        <Space.Compact style={{ width: "100%" }}>
          <Input
            {...inputProps}
            ref={ref}
            id={id}
            aria-label={ariaLabel}
            aria-describedby={ariaDescribedBy}
            disabled={disabled}
            readOnly={readOnly}
            placeholder={placeholder}
            prefix={<FileImageOutlined aria-hidden="true" />}
            value={value}
            onChange={(event) => onChange?.(event.target.value)}
          />
          <Button
            htmlType="button"
            aria-label={`为${fieldLabel}选择图片`}
            aria-describedby={ariaDescribedBy}
            disabled={disabled || readOnly}
            onClick={showModal}
          >
            选择
          </Button>
        </Space.Compact>

        <Modal
          rootClassName="mabox-admin-modal"
          title={`选择${fieldLabel}`}
          open={isModalOpen}
          okText="使用所选图片"
          cancelText="取消"
          okButtonProps={{ "aria-label": "使用所选图片" }}
          cancelButtonProps={{ "aria-label": "取消" }}
          onOk={handleOk}
          onCancel={handleCancel}
        >
          <div aria-busy={loadState === "loading"}>
            {loadState === "loading" && (
              <div role="status" aria-live="polite">
                正在加载媒体库…
              </div>
            )}

            {loadState === "error" && (
              <div role="alert">
                <p>媒体库加载失败，请检查 REST API 权限或网络后重试。</p>
                <Button htmlType="button" onClick={() => void getMediaData()}>
                  重试加载媒体库
                </Button>
              </div>
            )}

            {loadState === "ready" && mediaImages.length === 0 && (
              <div role="status">媒体库中暂无可选图片。</div>
            )}

            {loadState === "ready" && mediaImages.length > 0 && (
              <div role="radiogroup" aria-label="媒体库图片">
                <Radio.Group
                  name={`${id || "select-image"}-media`}
                  value={draftValue}
                  onChange={handleRadioChange}
                  style={{ width: "100%" }}
                >
                  <List
                    grid={{
                      gutter: 16,
                      xs: 1,
                      sm: 2,
                      md: 4,
                      lg: 4,
                      xl: 6,
                      xxl: 3,
                    }}
                    dataSource={mediaImages}
                    renderItem={(item) => (
                      <List.Item key={item.id}>
                        <Radio value={item.source_url}>
                          <img
                            alt={mediaAlt(item)}
                            src={mediaThumbnail(item)}
                            width={200}
                            height={200}
                          />
                        </Radio>
                      </List.Item>
                    )}
                  />
                </Radio.Group>
              </div>
            )}
          </div>
        </Modal>
      </>
    );
  },
);

SelectImage.displayName = "SelectImage";

export default SelectImage;
