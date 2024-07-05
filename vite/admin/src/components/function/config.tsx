//功能 - 插件设置
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { FunctionConfig } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = FunctionConfig;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  const publicData = optionData.function?.config || defaultVarOption.function.config;

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
    updateOption("function", "config", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="config"
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
          <h2>插件设置</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="待定"
          name="remove_config"
          valuePropName="checked"
          extra={"待定"}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
