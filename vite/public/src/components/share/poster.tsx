/**
 * 生成海报
 * @returns
 */
import "./poster.css";
import DefaultImg from "@/assets/default/file-dark-1920x1280.jpg";
import { useRef, useEffect } from "react";
import { QRCode } from "antd";
import html2canvas from "html2canvas";

interface AppProps {
  closePoster: () => void;//关闭弹窗
}
//弹窗内容
const App: React.FC<AppProps> = ({ closePoster }) => {
  //准备当前网页链接
  const site_url = encodeURIComponent(window.location.href);

  //海报
  const posterRef = useRef<HTMLDivElement>(null); // 创建一个持久的引用

  //海报图
  const aaaRef = useRef<HTMLDivElement>(null);
  useEffect(() => {
    const capturePoster = async () => {
      if (posterRef.current && aaaRef.current) {
        // 确保引用已经存在
        const canvas = await html2canvas(posterRef.current);
        const canvasContainer = document.createElement("div");
        canvasContainer.classList.add("canvas-container");
        canvasContainer.appendChild(canvas);

        // 清空节点b中的内容
        aaaRef.current.innerHTML = "";

        // 将生成的canvas元素添加到节点b中
        aaaRef.current.appendChild(canvasContainer);

        // 销毁节点a
        const posterNode = posterRef.current;
        if (posterNode && posterNode.parentNode) {
          posterNode.parentNode.removeChild(posterNode);
        }
      }
    };

    capturePoster();

    return () => {
      // 在组件卸载时清除副作用
      // 这里可以做一些清理操作，比如移除添加到 box 的 canvas 容器
    };
  }, []); // 空数组表示只在组件挂载时执行一次

  return (
    <>
      <div className="poster" ref={posterRef}>
        <div className="bg">
          <img src={DefaultImg} />
          <div className="meat">
            <p id="formattedDay">06</p>
            <p id="formattedDate">2024 / 06</p>
          </div>
        </div>
        <div className="content">
          <h2>关于 - ZAXU</h2>
          <div className="meat">
            Our CustomerHit it Off, Never Change.Hi, I’m Jony Zhang. I based in
            Shanghai, China. A freelance Graphic, UX, UI Designer and Website
            Develo...
          </div>
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
        <span className="icon background-blur"></span>
      </div>
      {/**放图 */}
      <div className="poster_canvas" ref={aaaRef}></div>
    </>
  );
};

export default App;
