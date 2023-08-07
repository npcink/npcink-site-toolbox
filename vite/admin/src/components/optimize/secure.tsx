//站点 - 模版
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import DataContext from "@/tool/dataContext";
import { OptimizeSecure } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";

//选项类型
type FieldType = OptimizeSecure;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { optimize: {} };

  //简化并提供默认值
  let publicData = optionObj.optimize?.secure || defaultVar.optimize.secure;

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
      secure: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="secure"
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
          <h2>安全</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="替换默认登录报错信息，"
          name="replace_login_error"
          valuePropName="checked"
          extra={
            <span>
              默认登录报错信息会透露用户是用户名错误还是密码错误，统一信息后，可改善此情况，
              <b style={{ color: "red" }}>会影响验证码错误提示！</b>
            </span>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="修改评论样式中的管理员ID"
          name="modify_comment_user"
          valuePropName="checked"
          extra={"默认的评论样式中，会包含管理员登录ID，修改后，可改善此情况"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="删除WordPress版本信息"
          name="remove_RSS_version"
          valuePropName="checked"
          extra={
            "从RSS源和网站中删除，如果您无法保持您的WordPres版本为最新，推荐开启"
          }
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
