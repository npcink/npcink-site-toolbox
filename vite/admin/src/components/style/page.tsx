import { useState, useContext, useEffect } from "react";
import { Switch, Form, ColorPicker, Input, InputNumber } from "antd";
import { FileImageOutlined } from "@ant-design/icons";
import DataContext from "@/tool/dataContext";
import { StylePage } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";

import type { Color } from "antd/es/color-picker";

type FieldType = StylePage;

//处理颜色格式
const getHexString = (color: Color | string): string => {
  return typeof color === "string" ? color : color.toHexString();
};

const App: React.FC = () => {
  //准备默认值
  const optionObj = useContext(DataContext) ?? { style: {} };
  const publicData = optionObj.style?.page || defaultVar.style.page;

  //存储表单值
  const [formData, setFormData] = useState(publicData || {});

  //修改表单值
  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    const updatedValues = {
      ...changedValues,
      background_left: getHexString(changedValues.background_left || ""),
      background_right: getHexString(changedValues.background_right || ""),
    };

    setFormData((prevState) => ({
      ...prevState,
      ...updatedValues,
    }));
  };

  //修改公共值
  useEffect(() => {
    optionObj.style = {
      ...optionObj.style,
      page: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="page"
        labelCol={{ span: 8 }}
        wrapperCol={{ span: 16 }}
        style={{ maxWidth: 800 }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>特效</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="添加粒子特效"
          name="particle"
          valuePropName="checked"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="添加圆角彩色背景标签云"
          name="color_tag"
          valuePropName="checked"
          extra={"可在小工具中添加标签云，前台即可看到效果"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="评论区添加OWO表情包"
          name="comment_emote"
          valuePropName="checked"
          extra={""}
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="自定义登录页"
          name="custom_login_page"
          valuePropName="checked"
          extra={""}
        >
          <Switch />
        </Form.Item>

        {formData.custom_login_page && (
          <>
            <Form.Item<FieldType>
              label="左下角颜色"
              name="background_left"
              extra={""}
            >
              <ColorPicker showText />
            </Form.Item>
            <Form.Item<FieldType>
              label="右上角颜色"
              name="background_right"
              extra={""}
            >
              <ColorPicker showText />
            </Form.Item>

            <Form.Item<FieldType>
              label="LOGO尺寸(px)"
              name="logo_size"
              extra={"默认120，最大180"}
            >
              <InputNumber min={0} max={180}  formatter={(value) => `${value}px`}/>
            </Form.Item>

            <Form.Item<FieldType> label="顶部LOGO" name="top_logo" extra={""}>
              <Input
                addonBefore={<FileImageOutlined />}
                placeholder="图片网址"
              />
            </Form.Item>

            <Form.Item<FieldType>
              label="文字背景图"
              name="background_img"
              extra={""}
            >
              <Input
                addonBefore={<FileImageOutlined />}
                placeholder="图片网址"
              />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
