//分享
import { useState } from "react";
import { Drawer, Button } from "antd";
import { ShareAltOutlined } from "@ant-design/icons";
import ShareContent from "@/components/share/content";
import "@/components/share/index.css";
import { publicShareData } from "@/store";

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
    content: "drawer_content",
  };

  //分离按钮样式
  const { top, position, margins } = publicShareData.button;
  const buttonStyle = {
    top: `${top}px`,
    [position]: `${margins}px`,
  };
  //  const buttonStyle = {
  //    top: `${publicShareData.button.top}px`,
  //    ...(publicShareData.button.position === "left" && {
  //      left: `${publicShareData.button.margins}px`,
  //    }),
  //    ...(publicShareData.button.position === "right" && {
  //      right: `${publicShareData.button.margins}px`,
  //    }),
  //  };

  return (
    <>
      <Button
        shape="circle"
        icon={<ShareAltOutlined />}
        onClick={showDrawer}
        className="open_share"
        //TODO:太长了，想办法优化下
        style={buttonStyle}
      />

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
