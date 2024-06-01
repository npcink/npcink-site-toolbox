//其他
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeAdmin } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = OptimizeAdmin;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { optimize: {} };

  //简化并提供默认值
  let publicData = optionObj.optimize?.admin || defaultVarOption.optimize.admin;

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

  // 表单值发生变化时更新dataContext的值
  useEffect(() => {
    optionObj.optimize = {
      ...optionObj.optimize,
      admin: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="admin"
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
          <h2>后台-文章</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="添加作者筛选项"
          name="add_user"
          valuePropName="checked"
          extra={"文章菜单添加作者筛选项"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="添加时间筛选项"
          name="add_time"
          valuePropName="checked"
          extra={"文章和媒体菜单添加时间筛选项，媒体菜单需为列表布局"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="各个列表显示链接ID"
          name="show_id"
          valuePropName="checked"
          extra={"支持 文章、页面、链接、多媒体、评论、分类、标签、用户 等"}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
