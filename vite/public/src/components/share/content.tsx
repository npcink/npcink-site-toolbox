//准备内容

import Pictorial from "@/assets/share/画报.svg";
import CopyLink from "@/assets/share/链接.svg";
import WeXin from "@/assets/share/微信.svg";
import Mail from "@/assets/share/邮件.svg";
import WeiBo from "@/assets/share/微博.svg";
import Qzone from "@/assets/share/QQ空间.svg";
import Facebook from "@/assets/share/Facebook.svg";
import X from "@/assets/share/X.svg";

import { useState } from "react";
import { message, Drawer } from "antd";
import Poster from "@/components/share/poster";
import QRCode from "@/components/share/QRcode";
import { publicShareData } from "@/store/index";
interface AppProps {
  toggleDrawer: () => void;
}

const App: React.FC<AppProps> = ({ toggleDrawer }) => {
  //当前页面标题
  const page_title = publicShareData.page.title;

  //准备当前网页链接
  const page_url = encodeURIComponent(publicShareData.page.url);

  //准备宣传语
  const shareText = publicShareData.button.shareText;
  const promo = encodeURIComponent(shareText + page_title + "：");

  //当前弹窗展示内容
  const [drawerContent, setDrawerContent] = useState("");

  //生成海报
  const poster = () => {
    //关闭弹窗
    toggleDrawer();

    // 1 秒后执行 showDrawer
    setTimeout(() => {
      //开海报弹窗
      setDrawerContent("poster");
      showDrawer();
    }, 300);
  };

  //复制当前链接
  const copyLink = () => {
    navigator.clipboard.writeText(page_url).then(() => {
      message.info("链接已复制到剪贴板");
    });
  };

  //生成二维码
  const qrCode = () => {
    //关闭弹窗
    toggleDrawer();
    // 1 秒后执行 showDrawer
    setTimeout(() => {
      //开二维码弹窗
      setDrawerContent("QRCode");
      showDrawer();
    }, 300);
  };

  //发出邮件
  const sendEmail = () => {
    const mail = publicShareData.email.email;
    const title = publicShareData.email.title;
    const content = publicShareData.email.content;
    const url = `mailto:${mail}?subject=${title} - ${page_title}&body=${content} - ${page_url}`;
    window.open(url);
  };

  //分享到微博
  const shareWeibo = () => {
    // 替换下面的 URL 和文本为你想分享的内容
    const url = page_url;
    const text = promo;

    // 构建微博分享链接
    const shareUrl =
      "http://service.weibo.com/share/share.php?url=" + url + "&title=" + text;

    // 打开分享链接
    window.open(shareUrl, "_blank");
  };

  //分享到QQ 空间
  const shareQzone = () => {
    // 替换下面的 URL 和标题为你想分享的内容
    const url = page_url;
    const title = promo;

    // 构建QQ空间分享链接
    const shareUrl =
      "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=" +
      url +
      "/&title=" +
      title;

    // 打开分享链接
    window.open(shareUrl, "_blank");
  };

  //分享到FacebookTODO:待验证
  const shareToFacebook = () => {
    // 替换下面的 URL 为你想分享的网站链接
    const url = page_url;

    // 构建 Facebook 分享链接
    const shareUrl = "https://www.facebook.com/sharer/sharer.php?u=" + url;

    // 打开分享链接
    window.open(shareUrl, "_blank");
  };

  //分享到X
  const shareToX = () => {
    // 替换下面的 URL 和文本为你想分享的内容
    const url = page_url;
    const text = promo;

    // 构建 Twitter 分享链接
    const shareUrl = "https://x.com/intent/tweet?url=" + url + "&text=" + text;

    // 打开分享链接
    window.open(shareUrl, "_blank");
  };

  //海报弹窗
  const [open, setOpen] = useState(false);

  //开海报弹窗
  const showDrawer = () => {
    setOpen(true);
  };

  //关海报弹窗
  const onClose = () => {
    setOpen(false);
  };

  //准备样式
  const classNameNames = {
    content: "drawer_content_poster",
  };

  return (
    <>
      <section className="site-sharing-container site-overlay opened">
        <div className="site-sharing-content">
          <span className="title">分享</span>
          <ul>
            <li onClick={poster}>
              <span className="icon">
                <img src={Pictorial} />
              </span>
              <span className="title">创建画报</span>
            </li>
            <li onClick={copyLink}>
              <span className="icon">
                <img src={CopyLink} />
              </span>
              <span className="title">复制链接</span>
            </li>
            <li onClick={qrCode}>
              <span className="icon">
                <img src={WeXin} />
              </span>
              <span className="title">微信</span>
            </li>
            <li onClick={sendEmail}>
              <span className="icon">
                <img src={Mail} />
              </span>
              <span className="title">邮件</span>
            </li>
            <li onClick={shareWeibo}>
              <span className="icon">
                <img src={WeiBo} />
              </span>
              <span className="title">微博</span>
            </li>
            <li onClick={shareQzone}>
              <span className="icon">
                <img src={Qzone} />
              </span>
              <span className="title">QQ 空间</span>
            </li>
            <li onClick={shareToFacebook}>
              <span className="icon">
                <img src={Facebook} />
              </span>
              <span className="title">Facebook</span>
            </li>
            <li onClick={shareToX}>
              <span className="icon">
                <img src={X} />
              </span>
              <span className="title">X</span>
            </li>
          </ul>
        </div>
      </section>

      {/**弹窗 */}
      <Drawer
        placement="bottom"
        closable={false}
        onClose={onClose}
        open={open}
        size="large"
        rootClassName="poster_drawer"
        classNames={classNameNames}
      >
        {drawerContent === "poster" && <Poster closePoster={onClose} />}
        {drawerContent === "QRCode" && <QRCode />}
      </Drawer>
    </>
  );
};
export default App;
