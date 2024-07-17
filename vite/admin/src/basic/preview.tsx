//基础组件 - 效果预览
import { Image, Popover } from "antd";
import Disabled from "@/assets/basic/禁用.svg";

interface PreviewProps {
  title: string; //标题
  img: string; //图片链接
}

const App: React.FC<PreviewProps> = (props: any) => {
  return (
    <>
      <Popover
        placement="rightTop"
        content={
          <Image src={props.img || Disabled} width={200} alt={props.title} />
        }
        title={"预览样式：" + props.title}
      >
        预览效果
        {
          //props.title
        }
      </Popover>
    </>
  );
};

export default App;
