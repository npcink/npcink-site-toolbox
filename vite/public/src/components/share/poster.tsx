/**
 * 生成海报
 * @returns
 */
import "@/components/share/poster.css";
import DefaultImg from "@/assets/default/file-dark-1920x1280.jpg";
//import DefaultImg from "@/assets/default/height.jpg";
import { useRef, useEffect, useState } from "react";
import { QRCode, Button } from "antd";
import { DownloadOutlined } from "@ant-design/icons";
import html2canvas from "html2canvas";
import { publicShareData } from "@/store";

interface AppProps {
  closePoster: () => void; //关闭弹窗
}
//弹窗内容
const App: React.FC<AppProps> = ({ closePoster }) => {
  //准备当前网页链接
  const site_url = encodeURIComponent(publicShareData.page.url);

  //海报节点
  const posterRef = useRef<HTMLDivElement>(null); // 创建一个持久的引用

  //BASE64 图片
  const [posterData, setPosterData] = useState("");

  //TODO:执行这个时会报错
  useEffect(() => {
    const capturePoster = async () => {
      if (posterRef.current) {
        // 确保引用已经存在
        try {
          const canvas = await html2canvas(posterRef.current, {
            useCORS: true,
          });
          // 将canvas转换为base64格式
          const base64Image = canvas.toDataURL("image/png");
          setPosterData(base64Image);

          // 销毁节点a
          const posterNode = posterRef.current;
          if (posterNode && posterNode.parentNode) {
            posterNode.parentNode.removeChild(posterNode);
          }
        } catch (error) {
          console.error("Failed to capture poster:", error);
        }
      }
    };

    capturePoster();

    return () => {
      // 在组件卸载时清除副作用
      // 这里可以做一些清理操作，比如移除添加到 box 的 canvas 容器
    };
  }, []); // 空数组表示只在组件挂载时执行一次

  //获取年月日
  // 获取当前日期
  const currentDate = new Date();

  // 提取年份和月份
  const year = currentDate.getFullYear();

  // 月份从0开始，需要加1
  const month = currentDate.getMonth() + 1;

  // 格式化输出
  const formattedDate = year + " / " + (month < 10 ? "0" : "") + month;

  // 获取当前日期中的日
  const day = currentDate.getDate();

  // 格式化日，确保输出两位数
  const formattedDay = day < 10 ? "0" + day : day;

  // 获取页面标题
  const page_title = publicShareData.page.title;

  // 获取页面描述
  const metaDescription = publicShareData.page.description;
  const description = metaDescription
    ? publicShareData.page.description
    : "还没有拿到页面描述，也许你可以亲自来看看";

  //获取特色图
  const posterImage = publicShareData.page.image
    ? publicShareData.page.image
    : DefaultImg;
  //下载海报按钮TODO:完善下载海报功能
  
  const downloadButton = () => {};
  return (
    <>
      <div className="scroll-content">
        <div className="posterBox">
          <div className="poster" ref={posterRef}>
            <div className="bg">
              <img src={posterImage} />
              <div className="meat">
                <p>{formattedDay}</p>
                <p>{formattedDate}</p>
              </div>
            </div>
            <div className="content">
              <h2>{page_title}</h2>
              <div className="meat">{description}</div>
              <div className="qr">
                <QRCode
                  errorLevel="H"
                  value={site_url}
                  size={150}
                  style={{ border: "0px" }}
                />
                <p>扫描二维码了解详情</p>
              </div>
            </div>
          </div>
          {/**关闭 */}
          <div className="close" onClick={closePoster}>
            <span className="icon"></span>
          </div>
          {/**放图 TODO:做长和宽两种比例，智能一点*/}

          <img src={posterData} />
        </div>
      </div>
      <Button
        className="dowload-btn"
        onClick={downloadButton}
        icon={<DownloadOutlined />}
        iconPosition="end"
        style={{ display: "none" }}
      >
        下载海报
      </Button>
    </>
  );
};

export default App;
