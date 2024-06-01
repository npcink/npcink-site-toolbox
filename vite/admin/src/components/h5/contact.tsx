//h5 - 联系
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input } from "antd";
import { DataContext } from "@/tool/dataContext";
import { H5Contact } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { validateLink } from "@/tool/tool";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = H5Contact;

//Ant 组件配置
const fromConfig = AntConfig.from;
const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { h5: {} };

  //简化并提供默认值
  const publicData = optionObj.h5?.contact || defaultVarOption.h5.contact;

  //创建变量并设默认值
  const [formData, setFormData] = useState(publicData);

  //表单同步修改值
  const onValuesChange = (changedValues: Partial<FieldType>) => {
    setFormData((prevFormData) => ({
      ...prevFormData,
      ...changedValues,
    }));
  };

  // 表单值发生变化时更新dataContext的值
  useEffect(() => {
    optionObj.h5 = {
      ...optionObj.h5,
      contact: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="contact"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
        style={{ maxWidth: fromConfig.maxWidth }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={publicData}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={() => {}}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>联系</h2>
        </Form.Item>
        <Form.Item<FieldType> label="标题" name="title">
          <Input />
        </Form.Item>
        <Form.Item<FieldType> label="标题-1" name="title_one">
          <Input />
        </Form.Item>
        <Form.Item<FieldType> label="内容-1" name="content_one">
          <Input />
        </Form.Item>
        <Form.Item<FieldType> label="标题-2" name="title_two">
          <Input />
        </Form.Item>
        <Form.Item<FieldType> label="内容-2" name="content_two">
          <Input />
        </Form.Item>
        <Form.Item>
          <h2>品牌</h2>
        </Form.Item>
        <Form.Item<FieldType> label="链接" name="brand_link">
          <Input />
        </Form.Item>
        <Form.Item<FieldType>
          label="LOGO"
          name="brand_logo"
          rules={[{ validator: validateLink }]}
        >
          <Input />
        </Form.Item>
        <Form.Item<FieldType> label="介绍" name="introduce">
          <Input />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
