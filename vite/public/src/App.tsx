import { ConfigProvider } from "antd";
import zhCN from "antd/locale/zh_CN";
import { message } from "antd";
import Share from "@/components/share";
message.config({
  top: 50,

  duration: 2,

  maxCount: 3,

  rtl: true,

  prefixCls: "my-message",
});

function App() {
  return (
    <>
      <ConfigProvider locale={zhCN}>
        <Share />
      </ConfigProvider>
    </>
  );
}

export default App;
