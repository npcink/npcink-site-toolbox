import React from "react";
import "@/App.css";
import { ConfigProvider } from "antd";
import enUS from "antd/locale/en_US";
import zhCN from "antd/locale/zh_CN";
import Tab from "@/components/tab";

const App: React.FC = () => {
  const locale = window.npcinkSiteToolboxData?.locale || "zh_CN";
  const componentLocale = locale.toLowerCase().startsWith("en") ? enUS : zhCN;

  return (
    <ConfigProvider
      locale={componentLocale}
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
