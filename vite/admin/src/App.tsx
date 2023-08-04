import React from "react";
import { Button, Space } from "antd";

import Tab from "./components/tab";

const App: React.FC = () => (
    <>
      <Tab />
      <Space wrap>
        <Button type="primary" >保存</Button>
      </Space>
    </>
  );


export default App;
