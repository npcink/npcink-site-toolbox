export interface PublicShareData {
  button: ShareButton; //按钮位置
  page: PageData; //页面数据
  email: EmailData; //邮箱数据
}

//分享按钮选项
interface ShareButton {
  position: string; //位置
  top: string; //距离顶部
  margins: string; //边距
}

//页面数据
interface PageData {
  title: string; //页面标题
  description: string; //页面描述
  image: string; //特色图地址
  url: string; //当前网址
  type: string; //页面类型
}

//分享邮箱数据
interface EmailData {
  email: string; //邮箱地址
  title: string; //邮箱标题
  content: string; //邮箱内容
}
