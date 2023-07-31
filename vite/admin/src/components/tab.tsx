import React from "react";
import { Tabs } from "antd";
import type { TabsProps } from "antd";
import Test from "./test"

const onChange = (key: string) => {
  console.log(key);
};

const items: TabsProps["items"] = [
  {
    key: "1",
    label: `安全`,
    children: `Content of Tab Pane 1`,
  },
  {
    key: "2",
    label: `优化`,
    children: `Content of Tab Pane 2`,
  },
  {
    key: "3",
    label: `其他`,
    children: `Content of Tab Pane 3`,
  },
  {
    key: "4",
    label: `Test`,
    children: <Test/>,
  },
];

const App: React.FC = () => (
  <Tabs defaultActiveKey="1" items={items} onChange={onChange} />
);

export default App;
