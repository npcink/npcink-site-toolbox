//功能 - 下载数据库文件
import React from "react";
import { useState, useEffect } from "react";
import { Form, Select, Button } from "antd";
import { DownloadOutlined } from "@ant-design/icons";
import { AntConfig } from "@/tool/tool";
import { ListData } from "@/tool/interface";
import { get_all_table_name, get_table_data } from "@/tool/axios";

//Ant 组件配置
const fromConfig = AntConfig.from;

const App: React.FC = () => {
  //存储列表值
  const [table_list, set_table_list] = useState<ListData[]>([]);

  //存储选中的数据表名
  const [selected, setSelected] = useState<string>("");

  //选中的结果
  const onChange = (value: string) => {
    setSelected(value);
    console.log(`selected ${value}`);
  };

  //搜索
  const filterOption = (
    input: string,
    option?: { label: string; value: string }
  ) => (option?.label ?? "").toLowerCase().includes(input.toLowerCase());

  //替换列表
  const b: { [key: string]: string } = {
    users: "用户",
    usermeta: "用户元数据",
    posts: "文章",
    comments: "评论",
    links: "友情链接",
    options: "选项",
    postmeta: "文章元数据",
    terms: "目录、分类和标签",
    term_taxonomy: "目录或标签对应的分类关系",
    term_relationships: "文章或链接的分类关系",
    termmeta: "分类的元数据",
    commentmeta: "评论元数据",
  };
  //获取列表值
  const get_table = async () => {
    //获取原始数据
    const list = await get_all_table_name();

    //数据替换
    const modifiedA = Object.keys(list).map((key) => ({
      [b[key as keyof typeof b] || key]: list[key],
    }));
    console.log(modifiedA);

    //修改为筛选所需结构
    const newArray = modifiedA.map((item) => {
      const key = Object.keys(item)[0];
      return {
        label: key,
        value: item[key],
      };
    });

    set_table_list(newArray);
    console.log(newArray);
  };

  const get_data = async() => {
    await get_table_data(selected);
  };

  useEffect(() => {
    // 在页面加载完成后执行 函数，获取数据并更新状态
    get_table();
  }, []);

  return (
    <>
      <Form
        name="down_database"
        labelCol={{ span: fromConfig.labelCol }}
        wrapperCol={{ span: fromConfig.wrapperCol }}
        style={{ maxWidth: fromConfig.maxWidth }}
        //自动填充功能禁用
        autoComplete="off"
        //指定当表单提交时要执行的回调函数
        onFinish={() => {}}
      >
        <Form.Item>
          <h2>下载指定数据库表内容</h2>
        </Form.Item>

        <Form.Item label="选择数据库" extra={"选中您需要下载的数据库"}>
          <Select
            showSearch
            optionFilterProp="children"
            style={{ width: 200 }}
            onChange={onChange}
            filterOption={filterOption}
            options={table_list}
          />
        </Form.Item>
        <Form.Item label="点击">
          <Button
            type="primary"
            icon={<DownloadOutlined />}
            onClick={()=>get_data()}
          >
            下载 {selected}
          </Button>
        </Form.Item>
      </Form>
    </>
  );
};

export default App;
