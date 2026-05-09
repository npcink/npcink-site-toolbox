/**
 *
 * 介绍：美化
 */
//站点 - 模版
import { useState, useContext, useEffect } from "react";
import { Form, ColorPicker, InputNumber } from "antd";

import { DataContext } from "@/tool/dataContext";
import { LoginBeautify } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";

import type { Color } from "antd/es/color-picker";
import { AntConfig } from "@/tool/tool";
import SelectImage from "@/basic/selectImage";
import FeatureSwitch from "@/basic/feature-switch";

type FieldType = LoginBeautify;

//Ant 组件配置
const fromConfig = AntConfig.from;

//处理颜色格式
const getHexString = (color: Color | string): string => {
  return typeof color === "string" ? color : color.toHexString();
};

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  const publicData =
    optionData.login?.beautify || defaultVarOption.login.beautify;

  //存储表单值
  const [formData, setFormData] = useState(publicData || {});

  //修改表单值
  const onValuesChange = (
    changedValues: Partial<FieldType>,
    allValues: FieldType
  ) => {
    const updatedValues = {
      ...changedValues,
      background_left: getHexString(allValues.background_left || ""),
      background_right: getHexString(allValues.background_right || ""),
    };

    setFormData((prevState) => ({
      ...prevState,
      ...updatedValues,
    }));
  };

  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("login", "beautify", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="login_beautify"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
        style={{ maxWidth: fromConfig.maxWidth }}
        initialValues={publicData}
        autoComplete="off"
        onFinish={() => {}}
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>美化</h2>
        </Form.Item>
        <Form.Item<FieldType>
          id="login-beautify-modify_login_link"
          label="LOGO链接"
          name="modify_login_link"
          valuePropName="checked"
          extra={"改为首页链接"}
        >
          <FeatureSwitch featureId="login-beautify-modify_login_link" />
        </Form.Item>
        <Form.Item<FieldType>
          id="login-beautify-remove_langue"
          label="移除语言选择框"
          name="remove_langue"
          valuePropName="checked"
          extra={"移除登录页面语言选择框"}
        >
          <FeatureSwitch featureId="login-beautify-remove_langue" />
        </Form.Item>

        <Form.Item<FieldType>
          id="login-beautify-custom_login_page"
          label="自定义登录页"
          name="custom_login_page"
          valuePropName="checked"
          extra={""}
        >
          <FeatureSwitch featureId="login-beautify-custom_login_page" />
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
              extra={"默认84，最大180（推荐宽高比为1:1的正方形LOGO）"}
            >
              <InputNumber
                min={0}
                max={180}
                formatter={(value) => `${value}px`}
              />
            </Form.Item>

            <Form.Item<FieldType> label="顶部LOGO" name="top_logo" extra={""}>
              <SelectImage />
            </Form.Item>

            <Form.Item<FieldType>
              label="文字背景图"
              name="background_img"
              extra={""}
            >
              <SelectImage />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
