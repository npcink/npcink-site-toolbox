//默认变量
//准备布尔值
const boo: boolean = import.meta.env.VITE_BOOLEAN === true;

//准备字符串false
const str: string = "false";

//准备数字
const num: number = 0;

//优化 站点
const OptimizeSite = {
  //禁止转义
  no_escape: boo,
  //关键词自动添加链接
  add_inks: boo,
  modify_login_link: boo, //登录页LOGO改首页链接
  remove_langue: boo, //移除登录页语言选择框
};

//优化  媒体
const OptimizeMedium = {
  img_add_tag: boo,
  no_auto_size: boo,
  medium_add_svg: boo,
  upload_auto_name: str,
};

//优化  评论
const OptimizeComment = {
  interval: boo, //两次评论间隔
  interval_time: num, //两次评论间隔
  words_number: boo, //是否开启字数控制
  words_number_min: num, //最少评论字数
  words_number_max: num, //最多评论字数
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
};

//个性化 页面特效
const StylePage = {
  particle: boo, //粒子特效
  color_tag: boo, //彩色标签云特效
  comment_emote: boo, //评论区表情包特效
  custom_login_page: boo, //自定义登录页
  background_left: str, //左下角颜色
  background_right: str, //右上角颜色
  logo_size: num, //LOGO尺寸
  top_logo: str, //顶部LOGO
  background_img: str, //文字背景图
};

//权限控制 禁用
const AuthorityDisable = {
  renew: boo, //自动更新
  no_login_img: boo, //未登录模糊图片
};

//权限控制 辅助
const AuthorityAuxiliary = {
  single_count: boo, //文章统计
  b2_count: boo, //B2商城统计
  no_malice_key: boo, //拒绝恶意关键词
  malice_keu_content: str, //恶意关键词内容
  login_code: str, //登录验证码
  tecent_id: str, //腾讯ID
  tecent_key: str, //腾讯秘钥
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
  },
  //个性化
  style: {
    page: StylePage, //页面特效
  },
};

export default App;
