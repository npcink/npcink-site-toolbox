/**
 *
 * 登录：安全
 */
//权限 - 辅助功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Input, Select, Switch } from "antd";
import { DataContext } from "@/tool/dataContext";
import { LoginSecurity } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import FeatureSwitch from "@/basic/feature-switch";

//选项类型
type FieldType = LoginSecurity;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到默认选项值和修改方法
  const { optionData, updateOption } = useContext(DataContext);

  //简化并提供默认值
  let publicData =
    optionData.login?.security || defaultVarOption.login.security;

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
  //表单值发生变化时更新选项值
  useEffect(() => {
    updateOption("login", "security", formData);
  }, [formData]);

  return (
    <>
      <Form
        name="login_security"
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
          <h2>登录安全</h2>
        </Form.Item>
        <Form.Item<FieldType>
          id="login-security-replace_login_error"
          label="替换登录报错信息"
          name="replace_login_error"
          valuePropName="checked"
          extra={
            <span>
              <strong>修复中，禁用;</strong>
              默认登录报错信息会透露用户是用户名错误还是密码错误，统一信息后，可改善此情况，
              <b style={{ color: "red" }}>会覆盖下方登录验证码错误提示！</b>
            </span>
          }
        >
          <Switch disabled={true} />
        </Form.Item>

        <Form.Item<FieldType>
          label="登录验证码"
          name="login_code"
          extra={"登录时需填写验证码才可登录"}
        >
          <Select
            style={{ width: 200 }}
            options={[
              { value: "false", label: "禁用" },
              { value: "math", label: "数学验证码" },
              { value: "random", label: "随机混合验证码" },
              { value: "tecent", label: " 腾讯验证码-功能未验证" },
            ]}
          />
        </Form.Item>

        {formData.login_code === "tecent" && (
          <>
            <Form.Item<FieldType>
              label="App ID"
              name="tecent_id"
              extra={"貌似随便填也能用"}
            >
              <Input />
            </Form.Item>
            <Form.Item<FieldType>
              label="App Secret Key"
              name="tecent_key"
              extra={"貌似随便填也能用"}
            >
              <Input.Password />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
