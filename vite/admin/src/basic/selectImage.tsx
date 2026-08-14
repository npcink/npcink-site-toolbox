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
  Modal,
  Radio,
} from "antd";
import { FileImageOutlined } from "@ant-design/icons";
import type { InputProps, InputRef, RadioChangeEvent } from "antd";
import axios from "axios";

import { ApiBase, RestNonce } from "@/tool/dataContext";
import { __, sprintf } from "@/tool/i18n";
import "./selectImage.css";

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

const MEDIA_PAGE_SIZE = 12;

const buildMediaEndpoint = (apiBase: string, page: number): string => {
  const [baseWithoutHash] = apiBase.split("#", 1);
  const queryIndex = baseWithoutHash.indexOf("?");

  if (queryIndex >= 0) {
    const basePath = baseWithoutHash.slice(0, queryIndex);
    const params = new URLSearchParams(baseWithoutHash.slice(queryIndex + 1));

    if (params.has("rest_route")) {
      params.set("rest_route", "/wp/v2/media");
      params.set("per_page", String(MEDIA_PAGE_SIZE));
      params.set("page", String(page));
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
    return `/api/wp-json/wp/v2/media?per_page=${MEDIA_PAGE_SIZE}&page=${page}`;
  }

  // dataLocal.apiBase points at this plugin's `{namespace}/vN` route. Remove
  // that contract generically so product renames do not leak into core media
  // endpoint construction.
  const restRoot = normalizedBase.replace(/\/[^/]+\/v\d+$/, "");

  return `${restRoot}/wp/v2/media?per_page=${MEDIA_PAGE_SIZE}&page=${page}`;
};

const parseTotalPages = (value: unknown): number | null => {
  const totalPages = Number.parseInt(String(value), 10);
  return Number.isFinite(totalPages) && totalPages > 0 ? totalPages : null;
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

  return item.slug?.trim() || __("媒体库图片");
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
    const [currentPage, setCurrentPage] = useState(0);
    const [totalPages, setTotalPages] = useState(1);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [loadMoreFailed, setLoadMoreFailed] = useState(false);
    const [fieldLabel, setFieldLabel] = useState(
      typeof ariaLabel === "string" ? ariaLabel : __("当前字段"),
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

    const getMediaData = useCallback(async (page = 1) => {
      const isFirstPage = page === 1;
      const currentRequest = requestId.current + 1;
      requestId.current = currentRequest;
      setLoadMoreFailed(false);

      if (isFirstPage) {
        setLoadState("loading");
      } else {
        setIsLoadingMore(true);
      }

      try {
        const response = await axios.get(buildMediaEndpoint(ApiBase, page), {
          headers: { "X-WP-Nonce": RestNonce },
        });
        if (requestId.current !== currentRequest) return;

        if (!Array.isArray(response.data)) {
          throw new Error("Invalid media response");
        }

        const nextImages = response.data.filter(isMediaImage);
        const responseTotalPages = parseTotalPages(
          response.headers?.["x-wp-totalpages"],
        );

        if (isFirstPage) {
          setMediaImages(nextImages);
        } else {
          setMediaImages((currentImages) => {
            const loadedIds = new Set(currentImages.map((item) => item.id));
            return [
              ...currentImages,
              ...nextImages.filter((item) => !loadedIds.has(item.id)),
            ];
          });
        }

        setCurrentPage(page);
        setTotalPages(
          responseTotalPages ??
            (nextImages.length < MEDIA_PAGE_SIZE ? page : page + 1),
        );
        setLoadState("ready");
      } catch {
        if (requestId.current !== currentRequest) return;

        if (isFirstPage) {
          setMediaImages([]);
          setLoadState("error");
        } else {
          setLoadMoreFailed(true);
        }
      } finally {
        if (requestId.current === currentRequest && !isFirstPage) {
          setIsLoadingMore(false);
        }
      }
    }, []);

    const showModal = () => {
      setDraftValue(value);
      setMediaImages([]);
      setCurrentPage(0);
      setTotalPages(1);
      setLoadMoreFailed(false);
      setIsModalOpen(true);
      void getMediaData();
    };

    const handleOk = () => {
      if (!draftValue) return;
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
        <div className="mabox-image-field">
          <div className="mabox-image-field-summary">
            <span className="mabox-image-field-icon" aria-hidden="true">
              <FileImageOutlined />
            </span>
            <span className="mabox-image-field-meta">
              <strong>{value ? __("已选择图片") : __("尚未选择图片")}</strong>
              <span className="mabox-image-field-value" title={value || undefined}>
                {value || __("可从媒体库选择，也可以在下方粘贴图片 URL。")}
              </span>
            </span>
            <span className="mabox-image-field-actions">
              <Button
                htmlType="button"
                aria-label={sprintf(__("为%s选择图片"), fieldLabel)}
                aria-describedby={ariaDescribedBy}
                disabled={disabled || readOnly}
                onClick={showModal}
              >
                {__("从媒体库选择")}
              </Button>
              {value && (
                <Button
                  type="text"
                  htmlType="button"
                  aria-label={sprintf(__("清除%s"), fieldLabel)}
                  aria-describedby={ariaDescribedBy}
                  disabled={disabled || readOnly}
                  onClick={() => onChange?.("")}
                >
                  {__("清除")}
                </Button>
              )}
            </span>
          </div>
          <Input
            {...inputProps}
            ref={ref}
            id={id}
            aria-label={ariaLabel}
            aria-describedby={ariaDescribedBy}
            disabled={disabled}
            readOnly={readOnly}
            placeholder={placeholder === "图片地址" ? __("或粘贴图片 URL") : placeholder}
            prefix={<FileImageOutlined aria-hidden="true" />}
            value={value}
            onChange={(event) => onChange?.(event.target.value)}
          />
        </div>

        <Modal
          rootClassName="mabox-admin-modal"
          className="mabox-media-picker-modal"
          title={sprintf(__("选择%s"), fieldLabel)}
          open={isModalOpen}
          width={760}
          okText={__("使用所选图片")}
          cancelText={__("取消")}
          okButtonProps={{
            "aria-label": __("使用所选图片"),
            disabled: !draftValue,
          }}
          cancelButtonProps={{ "aria-label": __("取消") }}
          onOk={handleOk}
          onCancel={handleCancel}
        >
          <div aria-busy={loadState === "loading" || isLoadingMore}>
            {loadState === "loading" && (
              <div role="status" aria-live="polite">
                {__("正在加载媒体库…")}
              </div>
            )}

            {loadState === "error" && (
              <div role="alert">
                <p>{__("媒体库加载失败，请检查 REST API 权限或网络后重试。")}</p>
                <Button htmlType="button" onClick={() => void getMediaData()}>
                  {__("重试加载媒体库")}
                </Button>
              </div>
            )}

            {loadState === "ready" && mediaImages.length === 0 && (
              <div role="status">{__("媒体库中暂无可选图片。")}</div>
            )}

            {loadState === "ready" && mediaImages.length > 0 && (
              <>
                <div
                  role="radiogroup"
                  aria-label={__("媒体库图片")}
                  className="mabox-media-picker-grid"
                >
                  <Radio.Group
                    name={`${id || "select-image"}-media`}
                    value={draftValue}
                    onChange={handleRadioChange}
                    className="mabox-media-picker-options"
                  >
                    {mediaImages.map((item) => {
                      const label = mediaAlt(item);
                      const selected = draftValue === item.source_url;

                      return (
                        <Radio
                          key={item.id}
                          value={item.source_url}
                          aria-label={label}
                          className={`mabox-media-picker-option${selected ? " mabox-media-picker-option--selected" : ""}`}
                        >
                          <span className="mabox-media-picker-thumbnail">
                            <img
                              alt={label}
                              src={mediaThumbnail(item)}
                              loading="lazy"
                            />
                          </span>
                          <span className="mabox-media-picker-label" title={label}>
                            {label}
                          </span>
                        </Radio>
                      );
                    })}
                  </Radio.Group>
                </div>

                <div className="mabox-media-picker-pagination" aria-live="polite">
                  {loadMoreFailed ? (
                    <div className="mabox-media-picker-more-error" role="alert">
                      <span>{__("更多图片加载失败，已加载的图片仍可继续选择。")}</span>
                      <Button
                        htmlType="button"
                        onClick={() => void getMediaData(currentPage + 1)}
                      >
                        {__("重试加载更多图片")}
                      </Button>
                    </div>
                  ) : currentPage < totalPages ? (
                    <Button
                      htmlType="button"
                      loading={isLoadingMore}
                      disabled={isLoadingMore}
                      onClick={() => void getMediaData(currentPage + 1)}
                    >
                      {__("加载更多图片")}
                    </Button>
                  ) : (
                    <span role="status">{__("已加载全部图片")}</span>
                  )}
                </div>
              </>
            )}
          </div>
        </Modal>
      </>
    );
  },
);

SelectImage.displayName = "SelectImage";

export default SelectImage;
