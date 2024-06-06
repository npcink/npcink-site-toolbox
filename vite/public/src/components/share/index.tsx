//分享
import { useState } from "react";
import { Drawer, Button } from "antd";
import ShareContent from "@/components/share/content";
import "./index.css";
const App: React.FC = () => {
  const [open, setOpen] = useState(false);

  //开弹窗
  const showDrawer = () => {
    setOpen(true);
  };

  //关弹窗
  const onClose = () => {
    setOpen(false);
  };

  //准备样式

  const classNameNames = {
    //  body: "drawer_body",
    //  mask: "drawer_mask",
    //  header: "drawer_header",
    //  footer: "drawer_footer",
    content: "drawer_content",
  };

  //准备内容

  return (
    <>
      <Button type="primary" onClick={showDrawer}>
        分享
      </Button>

      <Drawer
        placement="bottom"
        closable={false}
        onClose={onClose}
        open={open}
        rootClassName="share"
        classNames={classNameNames}
      >
        <ShareContent toggleDrawer={onClose} />
      </Drawer>
    </>
  );
};

export default App;
