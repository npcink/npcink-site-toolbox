//基础组件 - 图片选项
import { useState } from "react";
import {  Space, Button, Modal, List, Radio, Image } from "antd";
import type { RadioChangeEvent } from "antd";

import Disabled from "@/assets/basic/禁用.svg";

interface FixedImageProps {
  alists: { value: string; label: string }[];
}

const FixedImage: React.FC<FixedImageProps> = (props: any) => {
  //弹窗
  const [isModalOpen, setIsModalOpen] = useState(false);

  //默认媒体图片
  const defaultList = [{ value: "false", label: Disabled }];
  const mediaImage = [...defaultList, ...props.alists];

  //默认图片
  const result = mediaImage.find(item => item.value === props.value);


  //打开弹窗
  const showModal = () => {
    setIsModalOpen(true);
  };

  //确定按钮
  const handleOk = () => {
    setIsModalOpen(false);
    //传递选中的图片
    props.onChange(imageValue);
  };

  //取消按钮
  const handleCancel = () => {
    setIsModalOpen(false);
    // console.log("取消");
  };
  //接收传来的值

  //选中
  const [imageValue, setImageValue] = useState(props.value);

  //选中方法
  const onChange = (e: RadioChangeEvent) => {
    console.log("radio checked", e.target.value);
    setImageValue(e.target.value);
  };


  return (
    <>
      <Space style={{ width: "100%" }} size={"middle"}>
       
          <Image src={result.label} width={300} height={200}/>
          
        <Button onClick={showModal}>更换</Button>
      </Space>

      <Modal
        title="选择您需要的图片"
        width={750}
        open={isModalOpen}
        onOk={handleOk}
        onCancel={handleCancel}
      >
        <List
        
          dataSource={mediaImage}
          renderItem={(item: any) => (
            <Radio.Group onChange={onChange} value={imageValue}>
              <Radio value={item.value}>
                <Image src={item.label} width={300} height={200} />
              </Radio>
            </Radio.Group>
          )}
        />
      </Modal>
    </>
  );
};

export default FixedImage;
