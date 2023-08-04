//优化菜单
import React from "react";
import { useContext } from "react";
import { Switch, Form, Input } from "antd";
import DataContext from "../dataContext";
//选项类型
type FieldType = {
  //站点
  site: {
    //禁止转义
    no_escape: boolean;
    //关键词自动添加链接
    add_inks: boolean;
  };
  //筛选
  screen: {
    Article_Menu_Author: boolean;
  };
  //显示ID
  show_id: {
    all: boolean;
  };
};
const App: React.FC = () => {
  const obj = useContext(DataContext);
  return (
    <>
      优化
      {obj.screen.Article_Menu_Author.toString()}
      <Form
        name="opt"
        labelCol={{ span: 16 }}
        wrapperCol={{ span: 8 }}
        style={{ maxWidth: 600 }}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数

        //指定当表单字段值发生变化时要执行的回调函数
      >
        <Form.Item<FieldType>
          label="禁止网站title中的 “-” 被转义"
          name="site.no_escape"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="文章关键词自动添加内链链接代码"
          name="site.add_inks"
          valuePropName="checked"
          extra={
            <a
              href="https://www.npc.ink/15286.html?=magick-plugin"
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
