//优化菜单
import React from "react";
import { useContext } from "react";
import { Switch, Form } from "antd";
import DataContext from "../dataContext";
//选项类型
type FieldType = {
  opt: boolean;
  //测试用
  test: {
    switch: boolean;
  };
  //站点
  site: {
    //禁止转义
    no_escape: boolean;
    //关键词自动添加链接
    add_inks: boolean;
  };
  //筛选
  screen: {
    menu_add_author: boolean;
    time: boolean;
  };
  //显示ID
  show_id: {
    all: boolean;
  };
};
const App: React.FC = () => {
  const optionObj = useContext(DataContext);
  return (
    <>
      优化
      
      <Form
        name="opt"
        labelCol={{ span: 12 }}
        wrapperCol={{ span: 8 }}
        style={{ maxWidth: 600 }}
        //表单默认值，只有初始化以及重置时生效
        initialValues={optionObj}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数

        //指定当表单字段值发生变化时要执行的回调函数
      >
        <Form.Item>
          <h2>测试</h2>
        </Form.Item>
        <Form.Item<FieldType>
          label="是否开启顶部显示"
          name="opt"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType> label="测试" name="opt" valuePropName="checked">
          <Switch />
        </Form.Item>
        <Form.Item>
          <h2>优化</h2>
        </Form.Item>

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
              href="https://www.npc.ink/15286.html?=magick-mami"
              target="_blank"
            >
              详细介绍
            </a>
          }
        >
          <Switch />
        </Form.Item>
        <Form.Item>
          <h2>筛选</h2>
        </Form.Item>
        <Form.Item<FieldType>
          label="文章菜单添加作者选项"
          name="screen.menu_add_author"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>
        <Form.Item<FieldType>
          label="文章和媒体菜单添加时间筛选"
          name="screen.time"
          valuePropName="checked"
          extra={"媒体菜单需为列表布局"}
        >
          <Switch />
        </Form.Item>
        <Form.Item>
          <h2>显示ID</h2>
        </Form.Item>
        <Form.Item<FieldType>
          label="各个列表显示链接ID"
          name="show_id.all"
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

//},
//site: {
//    //禁止转义
//    no_escape: false,
//    //关键词自动添加链接
//    add_inks: true,
//  },
//  //筛选
//  screen: {
//    //菜单添加作者筛选
//    menu_add_author: true,
//    //文章和媒体添加时间筛选
//    time: false,
//  },
//  //显示ID
//  show_id: {
//    all: false,
//  },