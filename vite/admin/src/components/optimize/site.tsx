//站点 - 模版
import React from "react";
import { useState, useContext, useEffect } from "react";
import { Switch, Form } from "antd";
import { DataContext } from "@/tool/dataContext";
import { OptimizeSite } from "@/tool/interface";
import { defaultVarOption } from "@/tool/defaultVar";
import { AntConfig } from "@/tool/tool";

//选项类型
type FieldType = OptimizeSite;

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //拿到值
  const optionObj = useContext(DataContext) ?? { optimize: {} };

  //简化并提供默认值
  let publicData = optionObj.optimize?.site || defaultVarOption.optimize.site;

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
    //由于选项site可能不存在，这里需要使用复制来新建
    optionObj.optimize = {
      ...optionObj.optimize,
      site: formData,
    };
  }, [formData]);

  return (
    <>
      <Form
        name="site"
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
          <h2>站点</h2>
        </Form.Item>

        <Form.Item<FieldType>
          label="禁止title中的 “-” 被转义"
          name="no_escape"
          valuePropName="checked"
          extra={"让网页标题符号正常显示"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="禁用自动更新"
          name="renew"
          valuePropName="checked"
          extra={"WordPress、主题和插件不再提示更新"}
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="移除版本信息"
          name="remove_RSS_version"
          valuePropName="checked"
          extra={
            "从RSS源和网站中删除WordPress版本信息，如果您无法保持您的WordPres版本为最新，推荐开启"
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="分类链接简化"
          name="category_link_simplify"
          valuePropName="checked"
          extra={"去掉分类目录链接中的 category 字符。"}
        >
          <Switch />
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
