import { useState } from "react";
import axios from "axios";
import { Button, Switch, Form, Input, InputNumber } from "antd";

//准备类型
type DataLocal = {
  option: FieldType;
};

type FieldType = {
  name?: string;
  age?: number;
  handle?: boolean;
};

//开发环境状态
const state: boolean = import.meta.env.VITE_STATE;

//组建开发环境下的对象
const option = {
  option: {
    name: import.meta.env.VITE_OPTION_NAME,
    age: parseInt(import.meta.env.VITE_OPTION_AGE),
    handle: import.meta.env.VITE_OPTION_HANDLE === "true",
  },
};

//输出ajaxurl
function getAjaxurl(): string {
  if (state) {
    //开发
    return import.meta.env.VITE_AJAXURL;
  } else {
    //打包
    return (window as any).ajaxurl;
  }
}
//传值
const ajaxurl = getAjaxurl();

//输出选项值
function getDataLocal(): DataLocal {
  if (state) {
    //开发
    return option;
  } else {
    //打包
    return (window as any).dataLocal;
  }
}

//传值
const dataLocal: DataLocal = getDataLocal();

//获取需要的值
const getOption = dataLocal?.option;

const App = () => {
  //创建变量并设默认值
  const [formData, setFormData] = useState<FieldType>(getOption || {});

  //changedValues表示发生变化的字段及其新值的对象，
  //allValues表示所有字段及其当前值的对象。
  //通过使用onValuesChange，可以在表单字段值发生变化时及时更新组件的状态或进行其他操作。
  const onValuesChange = (allValues: FieldType) => {
    setFormData(allValues);
  };

  //表单提交逻辑
  const onFormSubmit = async (values: FieldType) => {
    console.log(values);
    //准备穿出值
    const params = new URLSearchParams();
    params.append("action", "save_object_option");
    params.append("object_data", JSON.stringify(values));
    //发出请求
    try {
      const response = await axios.post(ajaxurl, params);
      console.log("设置选项已保存！");
      console.log(response);
      alert("保存成功，现在可以使用查询功能了");
    } catch (error: any) {
      console.error("保存设置选项时出错：" + error.response.data);
    }
  };

  return (
    <>
      <h1>{formData.name}</h1>
      <p>Age: {formData.age}</p>
      <p>状态: {String(formData.handle)}</p>

      <Form
        name="basic"
        labelCol={{ span: 8 }}
        wrapperCol={{ span: 16 }}
        style={{ maxWidth: 600 }}
        initialValues={getOption}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={onFormSubmit}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item<FieldType> label="用户名" name="name">
          <Input />
        </Form.Item>

        <Form.Item<FieldType> label="年龄" name="age">
          <InputNumber min={1} max={100} />
        </Form.Item>

        <Form.Item<FieldType>
          label="是否展示"
          name="handle"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>

        <Form.Item wrapperCol={{ offset: 8, span: 16 }}>
          <Button type="primary" htmlType="submit">
            提交
          </Button>
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
