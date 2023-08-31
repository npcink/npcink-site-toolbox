//站点 - 模版
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import DataContext from "@/tool/dataContext";
import defaultVar from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { StyleAspect } from "@/tool/interface";

//选项类型
type FieldType = StyleAspect;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { style: {} };

  //简化并提供默认值
  let publicData = optionObj.style?.aspect || defaultVar.style.aspect;

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

  useEffect(() => {
    optionObj.style = {
      ...optionObj.style,
      aspect: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="aspect"
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
          <h2>外观特效</h2>
        </Form.Item>
        <Form.Item<FieldType>
          label="粒子特效"
          name="particle"
          valuePropName="checked"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="屏幕上的毛"
          name="screen_hair"
          valuePropName="checked"
          extra={
            <>
              在网页上添加一根毛发，蛮有趣的
              <a href="https://mkblog.cn/2382/" target="_blank">
                详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="全站变灰"
          name="site_grey"
          valuePropName="checked"
          extra={
            <>
              特殊时间下让网站变灰，有特别的意义，
              <a href="https://www.npc.ink/14874.html" target="_blank">
                实现详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="添加喜庆灯笼"
          name="lantern"
          valuePropName="checked"
          extra={
            <>
              特殊时间下会有特别的意义，
              <a href="https://www.npc.ink/11073.html" target="_blank">
                实现详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="飘落樱花"
          name="sakura"
          valuePropName="checked"
          extra={
            <>
              全站飘洒樱花
              <a
                href="https://www.cnblogs.com/quaint/p/12291936.html"
                target="_blank"
              >
                实现详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
