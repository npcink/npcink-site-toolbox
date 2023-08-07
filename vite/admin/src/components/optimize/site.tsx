//站点 - 模版
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import DataContext from "@/tool/dataContext";
import { OptimizeSite } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";

//选项类型
type FieldType = OptimizeSite;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { optimize: {} };

  //简化并提供默认值
  let publicData = optionObj.optimize?.site || defaultVar.optimize.site;

  //创建变量并设默认值
  const [formData, setFormData] = useState(publicData || {});

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

  // 表单值发生变化时更新dataContext的值
  //useEffect(() => {
  //  optionObj.optimize.site = formData;
  //}, [formData]);

  useEffect(() => {
    optionObj.optimize = {
      ...optionObj.optimize,
      site: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="site"
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
        <Form.Item<FieldType>
          label="登录页LOGO改为首页链接"
          name="modify_login_link"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="移除登录页面语言选择框"
          name="remove_langue"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
