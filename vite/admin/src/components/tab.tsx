import React from "react";
import { useState } from "react";
import { Tabs } from "antd";
import type { TabsProps } from "antd";

import { defaultOption, DataContext } from "@/tool/dataContext";

import Optimize from "@/components/optimize/index";
import Page from "@/components/page/index";
import Function from "@/components/function/index";
import Login from "@/components/login/index";
import H5 from "@/components/h5/index";
import About from "@/components/about/index";

const items: TabsProps["items"] = [
  {
    key: "1",
    label: `页面`,
    children: <Page />,
  },

  {
    key: "2",
    label: `功能`,
    children: <Function />,
  },
  {
    key: "3",
    label: `优化`,
    children: <Optimize />,
  },

  {
    key: "4",
    label: `登录页`,
    children: <Login />,
  },
  {
    key: "5",
    label: `H5`,
    children: <H5 />,
  },
  {
    key: "6",
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
    setOptionData((optionData) => {
      // 创建新的 optionData 对象，而不是直接修改 prevState
      const newOptionData = { ...optionData };

      // 检查父级对象键和子级对象键是否存在，并且进行值的更新
      if (newOptionData[father] && newOptionData[father][son]) {
        newOptionData[father][son] = newValue;
      }

      return newOptionData; // 返回新的 optionData 对象
    });
  };
  return (
    <>
      <DataContext.Provider value={{ optionData, updateOption }}>
        <Tabs defaultActiveKey="1" tabPosition="left" items={items} />
      </DataContext.Provider>
    </>
  );
};

export default App;
