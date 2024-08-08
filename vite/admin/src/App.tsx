import React from "react";
import "@/App.css";
import { ConfigProvider, message } from "antd";
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


const App: React.FC = () => {
  return (
    <ConfigProvider locale={zhCN}>
     
            <Tab />
          
    </ConfigProvider>
  );
};




export default App;
