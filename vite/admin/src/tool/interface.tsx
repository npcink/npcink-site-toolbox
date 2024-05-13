//准备对象类型

//准备类型
export type DataLocal = {
  optimize: {
    site: OptimizeSite;
    medium: OptimizeMedium;
    secure: OptimizeSecure;
    other: OptimizeOther;
  };
  //个性化
  page: {
    comment: PageComment; //评论
    feature: PageFeature; //外观
    function: PageFunction; //功能
  };
  //权限
  authority: {
    //禁用
    disable: AuthorityDisable; //禁用
    auxiliary: AuthorityAuxiliary; //辅助功能
    b2: AuthorityB2; //B2主题
    wx_xcx: AuthorityWxXcx; //微信小程序链接生成
  };
  h5: {
    home: H5Home;
    contact: H5Contact;
  };
  //登录
  login: {
    beautify: LoginBeautify; //美化
    security: LoginSecurity; //安全
  };
};

/**
 * Axios 返回类型
 */
export interface axiosType {
  success: boolean; //状态
  data: {
    data?: any; //返回值
    message?: string; //成功信息
    error?: string; //失败信息
  };
}

//优化 站点
export type OptimizeSite = {
  no_escape: boolean; //禁止转义
  
};

//优化 媒体
export type OptimizeMedium = {
  img_add_tag: boolean; //自动给媒体添加alt标签
  no_auto_size: boolean; //禁止缩略图
  medium_add_svg: boolean; //添加svg支持
  upload_auto_name: string; //自动重命名
};

//优化 安全
export type OptimizeSecure = {
  modify_comment_user: boolean; //修改评论区管理员样式ID
  remove_RSS_version: boolean; //从RSS源中删除WordPress版本信息
};

//优化 其他
export type OptimizeOther = {
  //筛选
  add_user: boolean; //作者筛选
  add_time: boolean; //时间筛选
  //显示ID
  show_id: boolean; //列表显示ID

  add_last_update: boolean; //添加最后更新时间
};

//页面 - 评论
export type PageComment = {

  comment_emote: boolean; //评论区表情包特效
  interval: boolean; //两次评论间隔
  interval_time: number; //间隔时间
  words_number: boolean; //是否开启字数控制
  words_number_min: number; //最少评论字数
  words_number_max: number; //最多评论字数
  english: boolean; //禁止纯英文评论
  only: boolean; //单篇文章仅限评论一次
};

//页面 - 外观特效
export type PageFeature = {
  title: boolean; //动态标题
  title_front: string; //回到当前标签
  title_after: string; //离开标签后
  particle: string; //粒子特效
  scrol: string; //美化滚动条
  coupling: boolean; //细线联结
  screen_hair: boolean; //屏幕上的毛
  site_grey: boolean; //网站变灰
  lantern: boolean; //灯笼效果
  lantern_left: string; //左边的字
  lantern_right: string; //右边的字
  sakura: boolean; //樱花效果
  past_books: boolean; //已写完的书
};

export type PageFunction = {
  add_inks: boolean; //关键词自动添加链接
  go_middle: string; //链接跳转中间页
  remove_single_link: boolean; //移除文章内超链接
  color_tag: boolean; //彩色标签云特效
};

//权限 禁用
export type AuthorityDisable = {
  renew: boolean; //禁用自动更新
  no_login_img: boolean; //未登录模糊图片
};

//权限 辅助
export type AuthorityAuxiliary = {
  single_count: boolean; //文章统计
  no_malice_key: boolean; //拒绝恶意关键词
  malice_keu_content: string; //恶意关键词内容
};

//权限 B2
export type AuthorityB2 = {
  add_order_menu: boolean; //添加订单菜单
  b2_count: boolean; //B2商城统计
};

//权限 微信小程序
export type AuthorityWxXcx = {
  active: boolean; //开关状态
  appid: string; //
  secret: string; //
  site: string; //小程序中打开的网址
  path: string; //路径
  query: string; //参数
};

//H5 首页
export type H5Home = {
  switch: boolean; //开关
  slide: Array<number>; //幻灯片
  slide_all: string; //幻灯片 查看全部
  more: number;
};

//H5 联系
export type H5Contact = {
  title: string; //联系标题
  title_one: string; //小标题
  content_one: string; //内容
  title_two: string; //小标题
  content_two: string; //内容
  brand_link: string; //跳转链接
  brand_logo: string; //LOGO
  introduce: string; //介绍
};

//登录 美化
export type LoginBeautify = {
  modify_login_link: boolean; //登录页LOGO改首页链接
  remove_langue: boolean; //移除登录页语言选择框
  custom_login_page: boolean; //自定义登录页
  background_left: string; //左下角颜色
  background_right: string; //右上角颜色
  logo_size: number; //LOGO尺寸
  top_logo: string; //顶部LOGO
  background_img: string; //文字背景图
};

//登录安全
export type LoginSecurity = {
  replace_login_error: boolean; //替换登录报错信息
  login_code: string; //登录验证码
  tecent_id: string; //腾讯ID
  tecent_key: string; //腾讯秘钥
};

//下拉列表类型
export type ListData = {
  label: string;
  value: string;
};
