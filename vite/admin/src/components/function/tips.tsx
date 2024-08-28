//功能 - 插件设置
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form, Input } from "antd";
import TimePeriod from "@/basic/timeInput";
import { DataContext } from "@/tool/dataContext";
import { FunctionTips } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import TextAreaHtml from "@/basic/htmlInput";

//选项类型
type FieldType = FunctionTips;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  const publicData =
    optionData.function?.config || defaultVarOption.function.config;

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
          label="弹窗提示"
          name="pop_tips"
          valuePropName="checked"
          extra={"添加页面提示"}
        >
          <Switch />
        </Form.Item>
        {formData.pop_tips && (
          <>
            <Form.Item<FieldType>
              label="提示内容"
              name="tips_content"
              extra={"支持HTML"}
            >
              <TextAreaHtml />
            </Form.Item>
            <Form.Item<FieldType> label="按钮文字" name="tips_button">
              <Input />
            </Form.Item>
            <Form.Item<FieldType> label="按钮链接" name="tips_link">
              <Input />
            </Form.Item>
            <Form.Item<FieldType> label="显示时间" name="tips_time">
              <TimePeriod />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
