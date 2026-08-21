//基础组件 - 效果预览
import { Image, Popover } from "antd";
import Disabled from "@/assets/basic/disabled.svg";
import { __, sprintf } from "@/tool/i18n";

interface PreviewProps {
  title: string; //标题
  img: string; //图片链接
}

const App: React.FC<PreviewProps> = (props: any) => {
  return (
    <>
      <Popover
        rootClassName="mabox-admin-modal"
        placement="rightTop"
        content={
          <Image
            src={props.img || Disabled}
            width={200}
            alt={props.title}
            preview={{ rootClassName: "mabox-admin-modal" }}
          />
        }
        title={sprintf(__("预览样式：%s"), __(props.title))}
      >
        <span className="mabox-preview-trigger">{__("预览效果")}</span>
        
        {
          //props.title
        }
      </Popover>
    </>
  );
};

export default App;
