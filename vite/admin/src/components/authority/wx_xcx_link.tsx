//微信小程序生成跳转链接
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Switch, Input } from "antd";
import DataContext from "@/tool/dataContext";
import { AuthorityWxXcx } from "@/tool/interface";
import defaultVar from "@/tool/defaultVar";

//选项类型
type FieldType = AuthorityWxXcx;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { authority: {} };

  //简化并提供默认值
  const publicData = optionObj.authority?.wx_xcx || defaultVar.authority.wx_xcx;

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
    optionObj.authority = {
      ...optionObj.authority,
      wx_xcx: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="wx_xcx"
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
          <h2>微信小程序链接生成</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="是否启用"
          name="active"
          valuePropName="checked"
          extra={"生成小程序跳转指定页面链接功能"}
        >
          <Switch />
        </Form.Item>
        {formData.active && (
          <>
            <Form.Item<FieldType>
              label="AppId"
              name="appid"
              extra={
                <p>
                  微信小程序 - 开发管理 - 开发设置，
                  <a
                    href="https://mp.weixin.qq.com/wxamp/devprofile/get_profile?token=858704879&lang=zh_CN"
                    target="_blank"
                  >
                    前往微信小程序
                  </a>
                </p>
              }
            >
              <Input.Password />
            </Form.Item>
            <Form.Item<FieldType>
              label="AppSecret"
              name="secret"
              extra={
                <p>
                  微信小程序 - 开发管理 - 开发设置，
                  <a
                    href="https://mp.weixin.qq.com/wxamp/devprofile/get_profile?token=858704879&lang=zh_CN"
                    target="_blank"
                  >
                    前往微信小程序
                  </a>
                </p>
              }
            >
              <Input.Password />
            </Form.Item>

            <Form.Item<FieldType>
              label="路径参数"
              name="path"
              extra={
                <>
                  需跳转的页面，
                  <p>
                    例如
                    <pre className="pre-meat">pages/circle/index.html</pre>
                    则填写
                    <pre className="pre-meat">pages/circle</pre>
                  </p>
                  <p>
                    例如
                    <pre className="pre-meat">
                      pages/single/post.html?id=300485
                    </pre>
                    则填写
                    <pre className="pre-meat">pages/single/post</pre>
                  </p>
                </>
              }
            >
              <Input />
            </Form.Item>
            <Form.Item<FieldType>
              label="查询参数"
              name="query"
              extra={
                <p>
                  需跳转指定的页面，例如{" "}
                  <pre className="pre-meat">
                    pages/single/post.html?id=300485
                  </pre>
                  则填写
                  <pre className="pre-meat">id=300485</pre>
                </p>
              }
            >
              <Input />
            </Form.Item>
          </>
        )}
      </Form>
    </>
  );
};

export default App;
