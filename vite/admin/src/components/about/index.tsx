//关于我
import type { CollapseProps } from "antd";
import { Collapse } from "antd";

import {
  AboutPlugin,
  Proposal,
  Link,
} from "@/components/about/collapse";

const items: CollapseProps["items"] = [
  {
    key: "1",
    label: "关于插件",
    children: <AboutPlugin />,
  },
  {
    key: "2",
    label: "我有建议",
    children: <Proposal />,
  },
  {
    key: "3",
    label: "联系方式",
    children: <Link />,
  },
];
const App: React.FC = () => {
  return (
    <>
      <Collapse accordion items={items} />
    </>
  );
};

export default App;
