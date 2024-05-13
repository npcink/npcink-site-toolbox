//页面 - 外观优化
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Switch, Input, Select } from "antd";
import DataContext from "@/tool/dataContext";
import defaultVar from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFeature } from "@/tool/interface";

//选项类型
type FieldType = PageFeature;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { page: {} };

  //简化并提供默认值
  let publicData = optionObj.page?.feature || defaultVar.page.feature;

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
    optionObj.page = {
      ...optionObj.page,
      feature: formData,
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
          label="动态标题"
          name="title"
          valuePropName="checked"
          extra={
            <>
              离开当前页面后，在标签页上显示有趣的文本，
              <a
                href="https://www.cnblogs.com/HaoranZing/p/16917421.html"
                target="_blank"
              >
                详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        {formData.title && (
          <>
            <Form.Item<FieldType> label="回到当前页" name="title_front">
              <Input style={{ width: "50%" }} />
            </Form.Item>
            <Form.Item<FieldType> label="离开当前页" name="title_after">
              <Input style={{ width: "50%" }} />
            </Form.Item>
          </>
        )}
        <Form.Item<FieldType>
          label="点击特效"
          name="particle"
          extra={"考虑到性能以及操作问题，移动端不加载此特效"}
        >
          <Select
            style={{ width: "20%" }}
            //TODO:默认值有问题
            options={[
              { value: "false", label: "禁用" },
              { value: "diffuse", label: "爆炸粒子" },
              { value: "text", label: "循环文字" },
              { value: "number", label: "随机数字" },
              
            ]}
          />
        </Form.Item>

        <Form.Item<FieldType>
          label="美化滚动条"
          name="scrol"
          extra={
            <>
              让你的页面滚动条更美观，
              <a href="https://www.npc.ink/6217.html" target="_blank">
                详情
              </a>
            </>
          }
        >
          <Select
            style={{ width: "20%" }}
            //TODO:默认值有问题
            options={[
              { value: "default", label: "默认" },
              { value: "color", label: "彩条" },
              { value: "false", label: "禁用" },
            ]}
          />
        </Form.Item>

        <Form.Item<FieldType>
          label="细线联结"
          name="coupling"
          valuePropName="checked"
          extra={
            <>
              网页上添加若干蛛网围绕鼠标汇聚，若需进一步个性化配置，请使用
              <pre className="pre-meat">Canvas-Nest.js</pre>插件，
              <a
                href="https://blog.csdn.net/weixin_42077074/article/details/121031327"
                target="_blank"
              >
                详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>

        <Form.Item<FieldType>
          label="屏幕上的毛"
          name="screen_hair"
          valuePropName="checked"
          extra={
            <>
              在网页上添加一根毛发，蛮有趣的，
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
              特殊时间下会有特别的意义，移动端不展示，
              <a href="https://www.npc.ink/11073.html" target="_blank">
                实现详情
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        {formData.lantern && (
          <>
            <Form.Item<FieldType>
              label="左"
              name="lantern_left"
              extra={<>展示在左边</>}
            >
              <Input style={{ width: "20%" }} />
            </Form.Item>
            <Form.Item<FieldType>
              label="右"
              name="lantern_right"
              extra={<>展示在右边</>}
            >
              <Input style={{ width: "20%" }} />
            </Form.Item>
          </>
        )}

        <Form.Item<FieldType>
          label="飘落樱花"
          name="sakura"
          valuePropName="checked"
          extra={
            <>
              全站飘洒樱花，
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
        <Form.Item<FieldType>
          label="页脚添加已读完的书"
          name="past_books"
          valuePropName="checked"
          extra={
            <>
              统计您撰写的文章总字数，相当于那本书。
              <a href="https://www.npc.ink/276901.html" target="_blank">
                详细信息
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
