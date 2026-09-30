import { Table } from "antd";
import { __ } from "@/tool/i18n";

import { renderSource } from "./renderSource";

const App: React.FC = () => {
  const dataSource = [
    {
      key: "1",
      name: __("去除分类链接中的 category 字符"),
      type: __("功能"),
      source: "https://www.npc.ink/5783.html",
    },
    {
      key: "2",
      name: __("复制文字跳出弹窗提示"),
      type: __("美化"),
      source: "https://www.npc.ink/5032.html",
    },
    {
      key: "3",
      name: __("其他"),
      type: __("其他"),
      source: __("待完善"),
    },
  ];

  const columns = [
    {
      title: __("类型"),
      dataIndex: "type",
      key: "type",
    },
    {
      title: __("效果"),
      dataIndex: "name",
      key: "name",
    },

    {
      title: __("来源"),
      dataIndex: "source",
      key: "source",
      render: (_: unknown, { source }: { source: string }) => renderSource(source),
    },
  ];
  return (
    <>
      <Table dataSource={dataSource} columns={columns} />
    </>
  );
};

export default App;
