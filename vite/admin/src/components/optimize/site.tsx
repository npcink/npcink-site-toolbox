//站点
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import DataContext from "@/tool/dataContext";
import { OptimizeSite } from "@/tool/interface";
import option from "@/tool/defaultVar";

//选项类型
type FieldType = OptimizeSite;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { optimize: {} };

  if (!optionObj.optimize) {
    optionObj.optimize = option.optimize;
  }

  //创建变量并设默认值
  const [FormData, setFormData] = useState(optionObj.optimize.site || {});

  //表单同步修改值
  const onValuesChange = (
    changedValues: Partial<FieldType>,
    _allValues: FieldType
  ) => {
    setFormData((prevState) => ({
      ...prevState,
      ...changedValues,
    }));
  };

  //打印修改后的值
  //const printData = (value: FieldType) => {
  //  console.log(value);
  //};

  useEffect(() => {
    // 表单值发生变化时更新dataContext的值
    optionObj.optimize.site = FormData;
  }, [FormData]);

  return (
    <>
      <Form
        name="opts"
        labelCol={{ span: 8 }}
        wrapperCol={{ span: 16 }}
        style={{ maxWidth: 800 }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={optionObj.optimize.site}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={()=>{}}
        //指定当表单字段值发生变化时要执行的回调函数
        onValuesChange={onValuesChange}
      >
        <Form.Item>
          <h2>站点</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="禁止网站title中的 “-” 被转义"
          name="no_escape"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="文章关键词自动添加内链链接代码"
          name="add_inks"
          valuePropName="checked"
          extra={
            <a
              href="https://www.npc.ink/15286.html?=magick-mami"
              target="_blank"
            >
              详细介绍
            </a>
          }
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
