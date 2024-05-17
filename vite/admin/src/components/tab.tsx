import React from "react";
import { Tabs } from "antd";
import type { TabsProps } from "antd";
import Optimize from "@/components/optimize/index";
import Page from "@/components/page/index";
import Authority from "@/components/authority/index";
import Login from "@/components/login/index";
import H5 from "@/components/h5/index";
import About from "@/components/about/index";

const items: TabsProps["items"] = [
  {
    key: "1",
    label: `功能`,
    children: <Authority />,
  },
  {
    key: "3",
    label: `优化`,
    children: <Optimize />,
  },
  {
    key: "2",
    label: `页面`,
    children: <Page />,
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

const App: React.FC = () => (
  <Tabs defaultActiveKey="1" tabPosition="left" items={items} />
);

export default App;
