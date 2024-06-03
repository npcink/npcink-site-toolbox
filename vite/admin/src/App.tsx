import React from "react";
import "@/App.css";
import { ConfigProvider, message, Layout, Affix } from "antd";
import zhCN from "antd/locale/zh_CN";
import Tab from "@/components/tab";
//统一弹窗
message.config({
  top: 50,
  duration: 2,
  maxCount: 3,
  rtl: true,
  prefixCls: "my-message",
});

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
  borderBottom: "1px solid #ccd0d4",
  background: "linear-gradient(#fefefe, #f5f5f5)",
};
const App: React.FC = () => {
  return (
    <ConfigProvider locale={zhCN}>
      <div className="MaBox_option">
        <Layout>
          <Affix offsetTop={20}>
            <Header style={headerStyle}>
              <HeaderBlock />
            </Header>
          </Affix>
          <Content className="mabox_content">
            <Tab />
          </Content>
          <Footer style={footerStyle}>
            <div className="float-right">
              <Save />
            </div>
          </Footer>
        </Layout>
      </div>
    </ConfigProvider>
  );
};

const HeaderBlock: React.FC = () => {
  return (
    <>
      <h1 className="text-2xl leading-7 font-medium">
        魔法工具箱
        <small className="text-xs font-light text-gray-400 ml-2 ">
          <a target="_blank" href="https://www.npc.ink" > For Npcink</a>
        </small>
      </h1>
      <Save />
    </>
  );
};

//保存按钮
//将拿到的值推送到服务器端
import { useContext } from "react";
import { Button } from "antd";
import {DataContext} from "@/tool/dataContext";
import { saceOption } from "@/axios/save";
const Save: React.FC = () => {
  //拿到值
  const {optionData} = useContext(DataContext);

  //提交动作
  const postData = async () => {
    //console.log("提交动作");
   // console.log(optionObj);
    saceOption(optionData);
  };
  return (
    <>
      <Button type="primary" onClick={postData}>
        保存
      </Button>
    </>
  );
};

export default App;
