import React from "react";
import "@/App.css";
import { ConfigProvider } from "antd";
import zhCN from "antd/locale/zh_CN";
import Tab from "@/components/tab";

const App: React.FC = () => {
  return (
    <ConfigProvider
      locale={zhCN}
      theme={{
        token: {
          colorPrimary: "#3858e9",
          colorTextLightSolid: "#fff",
        },
      }}
    >
      <Tab />
    </ConfigProvider>
  );
};

export default App;
