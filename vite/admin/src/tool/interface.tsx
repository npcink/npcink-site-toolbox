//准备对象类型

//准备类型
export type DataLocal = {
  option: Option;
  url_site: string;
};
export type Option = {
  [key: string]: any;
  optimize: {
    site: OptimizeSite;
    medium: OptimizeMedium;
    admin: OptimizeAdmin;
  };
  //页面
  page: {
    comment: PageComment; //评论
    feature: PageFeature; //外观
    function: PageFunction; //功能
    jurisdiction: PageJurisdiction; //权限
  };
  //权限
  authority: {
    auxiliary: AuthorityAuxiliary; //辅助功能
    b2: AuthorityB2; //B2主题
    wx_xcx: AuthorityWxXcx; //微信小程序链接生成
    seo: FunctionSeo; //简单 SEO 功能
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
  remove_RSS_version: boolean; //从RSS源中删除WordPress版本信息
  renew: boolean; //禁用自动更新
  category_link_simplify: boolean; //分类链接简化
};

//优化 媒体
export type OptimizeMedium = {
  img_add_tag: boolean; //自动给媒体添加alt标签
  no_auto_size: boolean; //禁止缩略图
  medium_add_svg: boolean; //添加svg支持
  upload_auto_name: string; //自动重命名
};

//优化 其他
export type OptimizeAdmin = {
  //筛选
  add_user: boolean; //作者筛选
  add_time: boolean; //时间筛选
  //显示ID
  show_id: boolean; //列表显示ID
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
  modify_comment_user: boolean; //修改评论区管理员样式ID
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
  copy_pop_up: string; //鼠标点击复制弹窗
};

//页面 - 功能
export type PageFunction = {
  add_inks: boolean; //关键词自动添加链接
  go_middle: string; //链接跳转中间页
  remove_single_link: boolean; //移除文章内超链接
  color_tag: boolean; //彩色标签云特效
  add_last_update: boolean; //添加最后更新时间
  no_login_img: boolean; //未登录模糊图片
  maintenance_tips: string; //维护提示
  countdown: string[]; //维护结束倒计时
  countdown_title: string; //维护标题
  countdown_image: string; //维护图片
  countdown_content: string; //维护内容
  share: boolean; //分享
  share_position: string; //按钮位置
  share_top: string; //按钮距离顶部距离
  share_img_home: string; //首页默认图
  share_img_page: string; //页面默认图
  share_img_about: string; //其他默认图
};

// 页面 - 权限
export type PageJurisdiction = {
  category_id: number[]; //分类ID
  tag_id: number[]; //标签ID
  page_id: number[]; //页面ID
  single_id: number[]; //文章ID
};

//权限 辅助
export type AuthorityAuxiliary = {
  single_count: boolean; //文章统计
  no_malice_key: boolean; //拒绝恶意关键词
  malice_keu_content: string; //恶意关键词内容
  baidu_tonji: string; //  百度统计
  google_tonji: string; // 谷歌统计
  biying_tonji: string; // 必应统计
  uniqueKey: number;
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

export type FunctionSeo = {
  title: string; //网站标题
  keywords: string; //网站关键字
  description: string; //网站描述
  seo_single: boolean; //文章SEO
  seo_category: boolean; //分类和标签SEO
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
