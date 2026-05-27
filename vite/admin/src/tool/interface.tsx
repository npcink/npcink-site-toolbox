//准备对象类型

//准备类型
export type DataLocal = {
  option: Option;
  url_site: string;
  ajaxurl?: string;
  nonce?: string;
  apiBase?: string;
  restNonce?: string;
  defaults?: Option;
};

//选项
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
  //功能
  function: {
    auxiliary: FunctionAuxiliary; //辅助功能
    b2: FunctionB2; //B2主题
    wx_xcx: FunctionWxXcx; //微信小程序链接生成
    seo: FunctionSeo; //简单 SEO 功能
    config: FunctionTips; //简单提示
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
  //短代码
  shortcode: {
    compose: CodeCompose; //板式
    pendant: CodePendant; //挂件
  };
  //页面模版
  template: {
    static: TemplateStatic; //静态
    trends: TemplateTrends; //动态
  };
  //国内生态
  domestic: {
    compliance: DomesticCompliance; //备案与合规
    baidu_push: DomesticBaiduPush; //百度推送
    wechat: DomesticWechat; //微信生态
    comment_security: DomesticCommentSecurity; //评论安全
    login_security: DomesticLoginSecurity; //登录安全
  };
  //性能优化
  performance: {
    oss: PerformanceOss; //对象存储
    seo_checker: PerformanceSeoChecker; //SEO检查
    media_health: PerformanceMediaHealth; //媒体库体检
    search_enhance: PerformanceSearchEnhance; //搜索增强
    db_clean: PerformanceDbClean; //数据库清理
  };
  // AI 审核
  ai_review: AiReview; //AI 审核助手
  // 增值服务
  services: Services; //技术支持与服务
  // 用户反馈
  feedback: Feedback; //反馈与数据洞察
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

/**
 * 诊断相关类型
 * @since 2.5.0
 */
export interface DiagnosticItem {
  id: string;
  title: string;
  status: "good" | "warning" | "critical";
  message: string;
  action?: string;
}

export interface DiagnosticRecommendation {
  id: string;
  title: string;
  module: string;
  field: string;
  reason: string;
}

export interface DiagnosticRisk {
  module_id: string;
  tier: "config" | "high_risk" | "experimental";
  title: string;
  message: string;
}

export interface DiagnosticServiceHint {
  type: string;
  message: string;
}

export interface ConfigDiffItem {
  path: string;
  label: string;
  module: string;
  before: any;
  after: any;
  riskLevel: "none" | "low" | "high";
}

export interface DiagnosticSummary {
  score: number;
  status: "good" | "warning" | "critical";
  items: DiagnosticItem[];
  recommendations: DiagnosticRecommendation[];
  risks: DiagnosticRisk[];
  service_hints: DiagnosticServiceHint[];
}

export interface SearchHealthTerm {
  term: string;
  count: number;
  no_result_count: number;
}

export interface SearchHealthSuspicious {
  term: string;
  count: number;
  reason: string;
}

export interface SearchHealthRecommendation {
  id: string;
  title: string;
  reason: string;
}

export interface SearchHealthSummary {
  range_days: number;
  total_searches: number;
  unique_terms: number;
  top_terms: SearchHealthTerm[];
  no_result_terms: SearchHealthTerm[];
  suspicious_terms: SearchHealthSuspicious[];
  recommendations: SearchHealthRecommendation[];
}

//优化 站点
export type OptimizeSite = {
  hide_top_toolbar: boolean; //隐藏顶部工具条
  no_escape: boolean; //禁止转义
  remove_RSS_version: boolean; //从RSS源中删除WordPress版本信息
  renew: boolean; //禁用自动更新
  category_link_simplify: boolean; //分类链接简化
  search_link_simplify: boolean; //搜索链接简化
  remove_sitemap_users: boolean; //安全 - 移除 wp-sitemap-users
  user_list_show_nickname: boolean; //用户列表展示昵称
  cdn_replace: boolean; //国内CDN替换
  cdn_gravatar: boolean; //Gravatar头像替换
  cdn_gravatar_mirror: string; //Gravatar镜像地址
  cdn_google_fonts: boolean; //Google Fonts替换
  cdn_google_fonts_mirror: string; //Google Fonts镜像地址
  cdn_google_ajax: boolean; //Google Ajax替换
  cdn_custom: string; //自定义CDN替换规则
  hide_email_ip: boolean; //隐藏邮件中的IP
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
  show_id: boolean; //列表显示ID
  thumbnail_switcher: boolean; //缩略图切换
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
  sensitive_words: boolean; //敏感词过滤
  sensitive_words_list: string; //敏感词列表
  sensitive_words_action: string; //处理方式: replace/block
  sensitive_words_replace_char: string; //替换字符
  baidu_moderation: boolean; //百度文本审核
  baidu_moderation_api_key: string; //百度API Key
  baidu_moderation_secret_key: string; //百度Secret Key
  baidu_moderation_action: string; //审核不通过处理: block/mark
};

//页面 - 外观特效
export type PageFeature = {
  title: boolean; //动态标题
  title_front: string; //回到当前标签
  title_after: string; //离开标签后
  top_loading: boolean; //顶部加载进度条
  particle: string; //粒子特效
  scrol: string; //美化滚动条
  screen_hair: boolean; //屏幕上的毛
  site_grey: boolean; //网站变灰
  lantern: boolean; //灯笼效果
  lantern_left: string; //左边的字
  lantern_right: string; //右边的字
  pixel_chicken: boolean; //像素小鸡
  past_books: boolean; //已写完的书
  go_top: string; //返回顶部
  page_back_top_cat_right: number; //右边距
  copy_pop_up: string; //鼠标点击复制弹窗
  bottom_effect: string; //页底特效
  page_scrolling: boolean; //平滑滚动

  background_effect: string; //背景特效
  reading_progress: boolean; //页顶阅读进度条
  reading_progress_color: string; //进度条颜色
  reading_progress_height: number; //进度条高度
  font_switch: boolean; //字体切换
  fonts: string; //字体列表
  font_position: string; //按钮位置
};

//页面 - 功能
export type PageFunction = {
  first_picture: boolean; //首图作特色图
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
  share_margins: string; //按钮距离侧边位置
  share_text: string; //分享用文本
  share_email_email: string; //邮箱地址
  share_email_title: string; //邮箱标题
  share_email_content: string; //邮箱内容
  share_img_home: string; //首页默认图
  share_img_page: string; //页面默认图
  share_img_about: string; //其他默认图

  switch_lang_jf: boolean; //简繁切换
  default_thumbnail: string; //默认文章缩略图
  search_limit: boolean; //限制搜索频次
  search_limit_count: number; //每分钟最大搜索次数
  top_ad: boolean; //顶部广告位
  top_ad_content: string; //广告内容
  top_ad_position: string; //广告位置
  batch_replace: boolean; //文章批量替换
  batch_replace_pairs: Array<{find: string; replace: string}>; //替换规则
  login_search: boolean; //仅登录可搜索
  article_rating: boolean; //文章评分
  header_notice: boolean; //页眉通知栏
  header_notice_text: string; //通知文本
  header_notice_color: string; //通知颜色
  header_notice_link: string; //通知链接
  header_notice_dismissible: boolean; //可关闭
  anti_crawler: boolean; //进阶防刷
  anti_crawler_max_requests: number; //最大请求数
  anti_crawler_time_window: number; //时间窗口(秒)
  anti_crawler_tecent_id: string; //腾讯防水墙AppID
  anti_crawler_tecent_key: string; //腾讯防水墙AppKey
  link_source: boolean; //文章链接添加来源
  source_key: string; //来源标识
  ticket: boolean; //工单系统
  diary: boolean; //日记类型
};

// 页面 - 权限
export type PageJurisdiction = {
  ban_open_weixing: boolean; //禁止在微信中打开
  ban_open_weixing_mode: string; //微信处理方式: alert/optimize
  wechat_guide_text: string; //微信引导语
  wechat_xcx_guide: boolean; //显示小程序引导
  wechat_xcx_guide_text: string; //小程序引导文字
  wechat_xcx_link: string; //小程序链接
  ban_open_qq: boolean; //禁止在QQ中打开
  front_debug: boolean; //前端调试
  ban_copy: boolean; //禁止复制
  category_id: number[]; //分类ID
  tag_id: number[]; //标签ID
  page_id: number[]; //页面ID
  single_id: number[]; //文章ID
  tip_content: string; //提示内容
};

//功能 辅助
export type FunctionAuxiliary = {
  single_count: boolean; //文章统计
  no_malice_key: boolean; //拒绝恶意关键词
  malice_keu_content: string; //恶意关键词内容
  baidu_tonji: string; //  百度统计
  google_tonji: string; // 谷歌统计
  biying_tonji: string; // 必应统计
  uniqueKey: number;
};

//功能 B2
export type FunctionB2 = {
  add_order_menu: boolean; //添加订单菜单
  b2_count: boolean; //B2商城统计
};

//功能 弹窗提示
export type FunctionTips = {
  pop_tips: boolean; //弹窗提示
  tips_time: number; //提示时间段
  tips_content: string; //提示内容
  tips_button: string; //按钮文字
  tips_link: string; //按钮链接
};

//功能 微信小程序
export type FunctionWxXcx = {
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
  login_code: string; //登录验证码
  tecent_id: string; //腾讯ID
  tecent_key: string; //腾讯秘钥
};

//板式
export type CodeCompose = {
  single_list: boolean; //文章列表
  single_copy: boolean; //复制
  runcode: boolean; //运行代码
  bilibili: boolean; //Bilibili视频嵌入
  wx_unlock: boolean; //公众号解锁内容
  wx_unlock_name: string; //公众号名称
  wx_unlock_qrcode: string; //公众号二维码
  wx_unlock_codes: string; //验证码列表
  wx_unlock_tip: string; //解锁提示
  wx_unlock_keyword_tip: string; //关键词提示
  reward: boolean; //打赏模块
  reward_wx_qr: string; //微信收款码
  reward_ali_qr: string; //支付宝收款码
  reward_title: string; //打赏标题
  reward_wx_text: string; //微信标签
  reward_ali_text: string; //支付宝标签
  reward_btn_text: string; //按钮文字
};

//挂件
export type CodePendant = {
  merc_map: boolean; //足迹
  merc_location: mapData[]; //地点
};
//下拉列表类型
export type ListData = {
  label: string;
  value: string;
};

//地图数据表类型
type mapData = {
  latLng: number[];
  name: string;
};

//静态
export type TemplateStatic = {
  triangle: boolean; //立体三角
};

//动态
export type TemplateTrends = {
  special: boolean; //专题列表
};

// ===== 国内生态 =====
export type DomesticCompliance = {
  icp_enabled: boolean;
  icp_number: string;
  icp_link: string;
  police_enabled: boolean;
  police_number: string;
  police_link: string;
  cookie_enabled: boolean;
  cookie_style: string;
  cookie_title: string;
  cookie_content: string;
  cookie_button: string;
  copyright_enabled: boolean;
  copyright_html: string;
};

export type DomesticBaiduPush = {
  active_push_enabled: boolean;
  site: string;
  token: string;
  auto_push_enabled: boolean;
  batch_push_enabled: boolean;
};

export type DomesticWechat = {
  jssdk_enabled: boolean;
  appid: string;
  appsecret: string;
  guide_overlay_enabled: boolean;
  guide_mode: string;
  guide_text: string;
  guide_qrcode: string;
};

export type DomesticCommentSecurity = {
  blacklist_enabled: boolean;
  blacklist_words: string;
  blacklist_action: string;
  link_limit_enabled: boolean;
  link_limit_count: number;
  nickname_filter_enabled: boolean;
  nickname_filter_words: string;
  email_domain_enabled: boolean;
  email_domain_blacklist: string;
  duplicate_enabled: boolean;
  ip_rate_enabled: boolean;
  ip_rate_limit: number;
  ip_rate_window: number;
  log_enabled: boolean;
};

export type DomesticLoginSecurity = {
  fail_limit_enabled: boolean;
  fail_limit_count: number;
  fail_lock_duration: number;
  ip_lock_enabled: boolean;
  ip_lock_count: number;
  ip_lock_duration: number;
  custom_login_enabled: boolean;
  custom_login_slug: string;
  ban_enumeration_enabled: boolean;
  login_notify_enabled: boolean;
  login_log_enabled: boolean;
  ip_whitelist_enabled: boolean;
  ip_whitelist: string;
};

// ===== 性能优化 =====
export type PerformanceOss = {
  enabled: boolean;
  provider: string;
  access_key: string;
  secret_key: string;
  bucket: string;
  region: string;
  domain: string;
  delete_local: boolean;
};

export type PerformanceSeoChecker = {
  enabled: boolean;
};

export type PerformanceMediaHealth = {
  enabled: boolean;
};

export type PerformanceSearchEnhance = {
  highlight_enabled: boolean;
  recommend_enabled: boolean;
  hotwords_enabled: boolean;
};

export type PerformanceDbClean = {
  enabled: boolean;
  clean_revisions: boolean;
  clean_drafts: boolean;
  clean_spam_comments: boolean;
  clean_transients: boolean;
  auto_clean: boolean;
  auto_clean_schedule: string;
};

// ===== AI 审核 =====
export type AiReview = {
  enabled: boolean;
  provider: string;
  mode: string;
  deepseek_api_key: string;
  deepseek_api_url: string;
  deepseek_model: string;
  aliyun_access_key: string;
  aliyun_secret_key: string;
  aliyun_region: string;
  custom_api_url: string;
  custom_api_method: string;
  custom_api_headers: string;
  custom_api_body_template: string;
  local_rules_enabled: boolean;
  local_keywords: string;
  local_regex: string;
  strict_mode: boolean;
  log_enabled: boolean;
  log_max_entries: number;
};

export type Services = {
  enabled: boolean;
  wechat_qr: string;
  wechat_id: string;
  email: string;
  website: string;
  service_custom_dev: boolean;
  service_deployment: boolean;
  service_theme_adapt: boolean;
  service_support: boolean;
  cases: Array<{title: string; description: string; logo: string}>;
};

export type Feedback = {
  enabled: boolean;
  feedback_enabled: boolean;
  feedback_email: string;
  feedback_auto_reply: string;
  telemetry_enabled: boolean;
  telemetry_anonymous: boolean;
  show_insights: boolean;
};
