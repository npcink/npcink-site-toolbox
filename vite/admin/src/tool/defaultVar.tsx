//默认变量
//准备布尔值
const boo: boolean = import.meta.env.VITE_BOOLEAN === true;

//准备字符串false
const str: string = "";

//准备数字
const num: number = 0;

//优化 站点
const OptimizeSite = {
  no_escape: boo, //禁止转义
  add_inks: boo, //关键词自动添加链接
  modify_login_link: boo, //登录页LOGO改首页链接
  remove_langue: boo, //移除登录页语言选择框
};

//优化  媒体
const OptimizeMedium = {
  img_add_tag: boo, //自动给媒体添加alt标签
  no_auto_size: boo, //禁止缩略图
  medium_add_svg: boo, //添加svg支持
  upload_auto_name: "false", //自动重命名
};

//优化  评论
const OptimizeComment = {
  interval: boo, //两次评论间隔
  interval_time: 5, //两次评论间隔
  words_number: boo, //是否开启字数控制
  words_number_min: num, //最少评论字数
  words_number_max: 120, //最多评论字数
  english: boo, //禁止纯英文评论
  only: boo, //单篇文章仅限评论一次
};

//优化 安全
const OptimizeSecure = {
  replace_login_error: boo, //替换登录报错信息
  modify_comment_user: boo, //修改评论区管理员样式ID
  remove_RSS_version: boo, //从RSS源中删除WordPress版本信息
};

//优化 其他
const OptimizeOther = {
  add_user: boo, //作者筛选
  add_time: boo, //时间筛选
  show_id: boo, //列表显示ID
  remove_single_link: boo, //移除文章内超链接
  add_last_update: boo, //添加最后更新时间
};

//个性化 页面特效
const StylePage = {
  color_tag: boo, //彩色标签云特效
  past_books: boo, //已写完的书
  comment_emote: boo, //评论区表情包特效
  custom_login_page: boo, //自定义登录页
  background_left: str, //左下角颜色
  background_right: str, //右上角颜色
  logo_size: 84, //LOGO尺寸
  top_logo: str, //顶部LOGO
  background_img: str, //文字背景图
};

//外观特效
const StyleAspect = {
  particle: boo, //粒子特效
  screen_hair: boo, //屏幕上的毛
  site_grey: boo, //网站变灰
  lantern:boo,//灯笼效果
  sakura:boo,//樱花效果
};

//权限控制 禁用
const AuthorityDisable = {
  renew: boo, //自动更新
  no_login_img: boo, //未登录模糊图片
};

//权限控制 辅助
const AuthorityAuxiliary = {
  single_count: boo, //文章统计
  no_malice_key: boo, //拒绝恶意关键词
  malice_keu_content: str, //恶意关键词内容
  login_code: "false", //登录验证码
  tecent_id: str, //腾讯ID
  tecent_key: str, //腾讯秘钥
};

//权限控制 b2
const AuthorityB2 = {
  add_order_menu: boo, //添加订单菜单
  b2_count: boo, //B2商城统计
};

//权限 微信小程序
const AuthorityWxXcx = {
  active: boo, //开关状态
  appid: str, //
  secret: str, //
  path: str, //路径
  query: str, //参数
};

// H5 首页
const H5Home = {
  switch: boo, //开关
  slide: [1], //幻灯片
  slide_all: str, //幻灯片 查看全部按钮
  more: 1, //待展示分类
};
//H5 联系
const H5Contact = {
  title: str, //联系标题
  title_one: str, //小标题
  content_one: str, //内容
  title_two: str, //小标题
  content_two: str, //内容
  brand_link: str, //跳转链接
  brand_logo: str, //LOGO
  introduce: str, //介绍
};

const App = {
  //优化
  optimize: {
    site: OptimizeSite, //站点
    medium: OptimizeMedium, //媒体
    comment: OptimizeComment, //评论
    secure: OptimizeSecure, //安全
    other: OptimizeOther, //其他
  },
  //权限控制
  authority: {
    disable: AuthorityDisable, //禁用
    auxiliary: AuthorityAuxiliary, //辅助功能
    b2: AuthorityB2, //B2
    wx_xcx: AuthorityWxXcx, //微信小程序链接生成
  },
  //个性化
  style: {
    page: StylePage, //页面特效
    aspect: StyleAspect, //外观特效
  },
  //H5
  h5: {
    home: H5Home, //首页
    contact: H5Contact, //联系
  },
};

export default App;
