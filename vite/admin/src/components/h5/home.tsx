//h5 - 首页
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form, Input } from "antd";
import DataContext from "@/tool/dataContext";
import { H5Home } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";

//选项类型
type FieldType = H5Home;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { h5: {} };

  //简化并提供默认值
  const publicData = optionObj.h5?.home || defaultVar.h5.home;

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
      home: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="home"
        labelCol={{ span: 8 }}
        wrapperCol={{ span: 16 }}
        style={{ maxWidth: 800 }}
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
          <h2>H5介绍</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="开启功能"
          name="switch"
          valuePropName="checked"
          extra={
            <>
              使用WordPress提供的Rest API，
              <br />
              可通过H5单页来展示有趣的内容。
              <a href="https://www.npc.ink/276746.html?mima" target="_blank">
                详情介绍
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item>
          <h2>幻灯片</h2>
        </Form.Item>
        <Form.Item>
          <h2>幻灯片选择</h2>
        </Form.Item>
        <Form.Item<FieldType> label="查看全部按钮的链接" name="slide_all">
          <Input />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
