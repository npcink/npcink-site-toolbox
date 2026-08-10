import React, { useEffect, useId, useState } from "react";
import { Button, Form, Image, List, Modal, Popover, Radio, Space } from "antd";
import type { RadioChangeEvent } from "antd";

import Disabled from "@/assets/basic/disabled.svg";

interface FixedImageOption {
  value: string;
  label: string;
  title: string;
}

type FixedImageProps = Omit<
  React.ComponentProps<typeof Button>,
  "children" | "onChange" | "onClick" | "value"
> & {
  alists: FixedImageOption[];
  includeDisabled?: boolean;
  value?: string;
  onChange?: (value: string) => void;
};

const disabledOption: FixedImageOption = {
  value: "false",
  label: Disabled,
  title: "禁用",
};

const FixedImage = React.forwardRef<
  React.ElementRef<typeof Button>,
  FixedImageProps
>(
  (
    {
      alists,
      includeDisabled = true,
      value = "false",
      onChange,
      "aria-label": ariaLabel,
      "aria-invalid": ariaInvalid,
      ...buttonProps
    },
    ref,
  ) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [draftValue, setDraftValue] = useState(value);
    const { status } = Form.Item.useStatus();
    const generatedName = useId();
    const mediaImages = includeDisabled ? [disabledOption, ...alists] : alists;
    const currentImage =
      mediaImages.find((item) => item.value === value) ?? disabledOption;
    const radioName = buttonProps.id
      ? `${buttonProps.id}-options`
      : `fixed-image-${generatedName}`;

    useEffect(() => {
      setDraftValue(value);
    }, [value]);

    const showModal = () => {
      setDraftValue(value);
      setIsModalOpen(true);
    };

    const handleOk = () => {
      setIsModalOpen(false);
      onChange?.(draftValue);
    };

    const handleCancel = () => {
      setDraftValue(value);
      setIsModalOpen(false);
    };

    const handleSelectionChange = (event: RadioChangeEvent) => {
      setDraftValue(event.target.value as string);
    };

    return (
      <>
        <Space size="middle" wrap>
          <span>当前样式：{currentImage.title}</span>
          <Button
            {...buttonProps}
            ref={ref}
            size={buttonProps.size ?? "small"}
            htmlType="button"
            aria-label={
              ariaLabel ?? `更换维护提示样式，当前：${currentImage.title}`
            }
            aria-invalid={
              ariaInvalid ?? (status === "error" ? true : undefined)
            }
            onClick={showModal}
          >
            更换样式
          </Button>
        </Space>

        <Modal
          rootClassName="mabox-admin-modal"
          title="选择您需要的样式"
          open={isModalOpen}
          onOk={handleOk}
          onCancel={handleCancel}
        >
          <div role="radiogroup" aria-label="维护提示样式">
            <Radio.Group
              name={radioName}
              value={draftValue}
              onChange={handleSelectionChange}
            >
              <List
                dataSource={mediaImages}
                renderItem={(item) => (
                  <List.Item>
                    <Radio value={item.value}>
                      <Popover
                        rootClassName="mabox-admin-modal"
                        placement="rightTop"
                        content={
                          <Image
                            src={item.label}
                            width={200}
                            alt={`维护提示样式预览：${item.title}`}
                            preview={{ rootClassName: "mabox-admin-modal" }}
                          />
                        }
                        title={`预览样式：${item.title}`}
                      >
                        <span>{item.title}</span>
                      </Popover>
                    </Radio>
                  </List.Item>
                )}
              />
            </Radio.Group>
          </div>
        </Modal>
      </>
    );
  },
);

FixedImage.displayName = "FixedImage";

export default FixedImage;
