//基础组件 - 选中媒体库图片
import { useState } from "react";
import { Input, Space, Button, Modal, List, Radio, Popover, Image } from "antd";
import { FileImageOutlined } from "@ant-design/icons";
import type { RadioChangeEvent } from "antd";

//获取媒体库数据
import axios from "axios";
import { url_site } from "@/tool/dataContext";

const SelectImage: React.FC = (props: any) => {
  //弹窗
  const [isModalOpen, setIsModalOpen] = useState(false);

  //媒体图片
  const [mediaImage, setMediaImage] = useState<any>([]);

  const getMediaData = async () => {
    //准备网址
    const site = url_site + "/wp-json/wp/v2/media?per_page=12";

    try {
      const response = await axios.get(site);
      //console.log(response.data);
      const data = response.data;
      setMediaImage(data);
    } catch (error: any) {
      console.error("出错：" + error.message);
      //TODO:完善报错
      alert("请开启 REST API 功能");
    }
  };

  //打开弹窗
  const showModal = () => {
    setIsModalOpen(true);
    //获取数据并存储
    getMediaData();
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

  //准备图片预览内容
  const content = (
    <>{props.value ? <Image src={imageValue} width={200} /> : "暂无选中图片"}</>
  );
  return (
    <>
      <Space.Compact style={{ width: "100%" }}>
        <Popover content={content} title="预览">
          <Input
            {...props}
            placeholder="图片地址"
            addonBefore={<FileImageOutlined />}
          />
        </Popover>

        <Button onClick={showModal}>选择</Button>
      </Space.Compact>

      <Modal
        title="选择您需要的图片"
        open={isModalOpen}
        onOk={handleOk}
        onCancel={handleCancel}
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
          dataSource={mediaImage}
          renderItem={(item: any) => (
            <Radio.Group onChange={onChange} value={imageValue}>
              <Radio value={item.source_url}>
                <img
                  alt={item.slug}
                  src={item.source_url}
                  width={200}
                  height={200}
                />
              </Radio>
            </Radio.Group>
          )}
        />
      </Modal>
    </>
  );
};

export default SelectImage;
