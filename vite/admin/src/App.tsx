import React from "react";
import "@/App.css";
import { ConfigProvider, message, Layout, Affix, Space } from "antd";
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
          <Affix offsetTop={30}>
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
          <a target="_blank" href="https://www.npc.ink">
            For Npcink
          </a>
        </small>
      </h1>
      <Save />
    </>
  );
};

//保存按钮
//将拿到的值推送到服务器端
import { useContext, useState, useEffect } from "react";
import { Button } from "antd";
import { DataContext } from "@/tool/dataContext";
import { saceOption } from "@/axios/save";
import { UpOutlined } from "@ant-design/icons";
const Save: React.FC = () => {
  //拿到值
  const { optionData } = useContext(DataContext);

  //提交动作
  const postData = async () => {
    //console.log("提交动作");
    // console.log(optionObj);
    saceOption(optionData);
  };

  //返回顶部
  const [showButton, setShowButton] = useState(false);
  useEffect(() => {
    const handleScroll = () => {
      // 获取当前滚动的垂直距离
      const scrollY = window.scrollY || window.pageYOffset;
      // 设置一个阈值，例如 50vh，即页面高度的一半
      const threshold = window.innerHeight * 0.5;

      if (scrollY > threshold) {
        setShowButton(true);
      } else {
        setShowButton(false);
      }
    };

    window.addEventListener("scroll", handleScroll);
    return () => {
      window.removeEventListener("scroll", handleScroll);
    };
  }, []);

  const handleButtonClick = () => {
    // 滚动到页面顶部
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div>
      <Space size={"large"}>
        {showButton && (
          <Button
            type="text"
            shape="circle"
            onClick={handleButtonClick}
            icon={<UpOutlined />}
          ></Button>
        )}
        <Button type="primary" onClick={postData}>
          保存
        </Button>
      </Space>
    </div>
  );
};

export default App;
