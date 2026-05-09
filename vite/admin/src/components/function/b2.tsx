//权限 - 禁用
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionB2 } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = FunctionB2;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  const publicData = optionData.function?.b2 || defaultVarOption.function.b2;

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
    updateOption("function", "b2", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="b2"
        labelCol={fromConfig.labelCol}
        wrapperCol={fromConfig.wrapperCol}
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
          <h2>B2</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="添加订单菜单"
          name="add_order_menu"
          valuePropName="checked"
          extra={"添加商城订单快捷菜单"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="B2商城统计"
          name="b2_count"
          valuePropName="checked"
          extra={
            <p>
              开启后显示在仪表盘下,
              <a href="https://7b2.com/shop/35736.html?=mami" target="_blank">
                详细介绍
              </a>
            </p>
          }
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
