//页面 - 功能
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Form, Switch, Select } from "antd";
import DataContext from "@/tool/dataContext";
import defaultVar from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";
import { PageFunction } from "@/tool/interface";

//选项类型
type FieldType = PageFunction;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { page: {} };

  //简化并提供默认值
  let publicData = optionObj.page?.function || defaultVar.page.function;

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
      function: formData,
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
          <h2>功能</h2>
        </Form.Item>
        <Form.Item<FieldType>
          label="彩色背景标签云"
          name="color_tag"
          valuePropName="checked"
          extra={"可在小工具中添加圆角彩色背景标签云，前台即可看到效果"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="文章内关键词添加内链"
          name="add_inks"
          valuePropName="checked"
          extra={
            <>
              文章内的内容与添加的标签相同，则添加对应标签的链接
              <a
                href="https://www.npc.ink/15286.html?=magick-mami"
                target="_blank"
              >
                详细介绍
              </a>
            </>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="移除文章内超链接"
          name="remove_single_link"
          valuePropName="checked"
          extra={"关闭此选项可恢复"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="未登录模糊文章内图片"
          name="no_login_img"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="添加最后更新时间"
          name="add_last_update"
          valuePropName="checked"
          extra={"文章末尾添加最后更新时间，文章发布24小时后再次修改，即可展示"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="外链跳转中间页"
          name="go_middle"
          extra={
            <>
              文章中的外链会先跳转到中间页，再跳转第三方，
              <br />
              此选项仅外观不同，功能相同;
              <br /> 推荐给robots.txt添加内容：
              <pre className="pre-meat">Disallow: /go_to/=*</pre>
              屏蔽搜索引擎对中间页的抓取,
              <a href="https://www.dujin.org/12762.html" target="_blank">
                详情
              </a>
            </>
          }
        >
          <Select
            style={{ width: 200 }}
            options={[
              { value: "false", label: "禁用" },
              { value: "zhihu", label: "知乎" },
              { value: "tencent", label: "腾讯云" },
              { value: "shimo", label: "石墨文档" },
              { value: "jianshu", label: "简书" },
              { value: "csdn", label: "CSDN" },
              { value: "wx_community", label: "微信社区" },
              { value: "ssp", label: "少数派" },
            ]}
          />
        </Form.Item>
        <Form.Item<FieldType>
          label="维护提示"
          name="maintenance_tips"
          extra={
            <>
              进行可能影响前端页面的配置时，可临时关闭前端页面，避免影响用户体验。（管理员不影响）
            </>
          }
        >
          <Select
            style={{ width: 200 }}
            options={[
              { value: "false", label: "禁用" },
              { value: "default", label: "默认简洁" },
              { value: "default_img", label: "默认带图" },
              { value: "red", label: "红色纯粹" },
              { value: "purple", label: "紫色期待" },
              { value: "lighting", label: "灯光聚焦" },
              { value: "running_plan", label: "跑路计划" },
            ]}
          />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
