import React from "react";
import { useState } from "react";
import { Tabs, Layout, Affix } from "antd";
import type { TabsProps } from "antd";

import { defaultOption, DataContext } from "@/tool/dataContext";
import Save from "@/tool/save";

import Optimize from "@/components/optimize/index";
import Page from "@/components/page/index";
import Function from "@/components/function/index";
import Login from "@/components/login/index";
import H5 from "@/components/h5/index";
import About from "@/components/about/index";
import Shortcode from "@/components/shortcode/index";
import Template from "@/components/template/index";

const items: TabsProps["items"] = [
  {
    key: "1",
    label: `页面`,
    children: <Page />,
  },
  {
    key: "2",
    label: `优化`,
    children: <Optimize />,
  },
  {
    key: "3",
    label: `登录页`,
    children: <Login />,
  },
  {
    key: "4",
    label: `H5`,
    children: <H5 />,
  },
  {
    key: "5",
    label: `功能`,
    children: <Function />,
  },
  {
    key: "7",
    label: `短代码`,
    children: <Shortcode />,
  },
  {
    key: "8",
    label: `页面模版`,
    children: <Template />,
  },
  {
    key: "9",
    label: `关于`,
    children: <About />,
  },
];

const App: React.FC = () => {
  //准备传来的选项值
  const [optionData, setOptionData] = useState(defaultOption);

  //修改选项值方法
  /**
   *
   * @param father 父级对象键
   * @param son 子级对象键
   * @param newValue 更改的对象值
   */
  const updateOption = (father: string, son: string, newValue: any) => {
    setOptionData((prevOptionData) => {
      const updatedOptionData = { ...prevOptionData };

      if (!updatedOptionData[father]) {
        updatedOptionData[father] = {};
      }

      updatedOptionData[father][son] = newValue;

      return updatedOptionData;
    });
  };

  const { Header, Footer, Content } = Layout;

  const headerStyle: React.CSSProperties = {
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    height: 64,
    paddingInline: 48,
    lineHeight: "64px",
    borderBottom: "1px solid #ccd0d4",
    background: "linear-gradient(#fefefe, #f5f5f5)",
  };

  const footerStyle: React.CSSProperties = {
    float: "right",
    /*borderBottom: "1px solid #ccd0d4",*/
    background: "linear-gradient(#fefefe, #f5f5f5)",
  };
  return (
    <>
      <DataContext.Provider value={{ optionData, updateOption }}>
        <div className="MaBox_option">
          <Layout>
            <Affix offsetTop={30}>
              <Header style={headerStyle}>
                <HeaderBlock />
              </Header>
            </Affix>
            <Content className="mabox_content">
              <Tabs defaultActiveKey="1" tabPosition="left" items={items} />
            </Content>
            <Footer style={footerStyle}>
              <div className="float-right">
                <Save />
              </div>
            </Footer>
          </Layout>
        </div>
      </DataContext.Provider>
    </>
  );
};

const HeaderBlock: React.FC = () => {
  return (
    <>
      <h1 className="text-2xl leading-7 font-medium">
        魔法工具箱
        <small className="text-xs font-light text-gray-400 ml-2 ">
          <a target="_blank" href="https://www.npc.ink">
            For Npcink
          </a>
        </small>
      </h1>
      <Save />
    </>
  );
};

export default App;
