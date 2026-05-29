//默认变量

//准备布尔值
const boo: boolean = import.meta.env.VITE_BOOLEAN === true;

//准备字符串false
const str: string = "";

//准备数字
const num: number = 0;

//准备昨天的时间
// 获取昨天的日期
const yesterday = new Date();
yesterday.setDate(yesterday.getDate() - 1);

// 设置开始时间和结束时间
const startTime = new Date(
  yesterday.getFullYear(),
  yesterday.getMonth(),
  yesterday.getDate(),
  9,
  0,
  0
);
const endTime = new Date(
  yesterday.getFullYear(),
  yesterday.getMonth(),
  yesterday.getDate(),
  12,
  0,
  0
);

// 格式化时间
function formatTime(date: Date) {
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, "0");
  const day = date.getDate().toString().padStart(2, "0");
  const hours = date.getHours().toString().padStart(2, "0");
  const minutes = date.getMinutes().toString().padStart(2, "0");
  const seconds = date.getSeconds().toString().padStart(2, "0");
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// 构建数组
const timeArray = [formatTime(startTime), formatTime(endTime)];

//优化 站点
const OptimizeSite = {
  hide_top_toolbar: boo, //隐藏顶部工具条
  no_escape: boo, //禁止转义
  remove_RSS_version: boo, //从RSS源中删除WordPress版本信息
  renew: boo, //自动更新
  category_link_simplify: boo, //分类链接简化
  search_link_simplify: boo, //搜索链接简化
  remove_sitemap_users: boo, //安全 - 移除 wp-sitemap-users
  user_list_show_nickname: boo, //用户列表展示昵称
  cdn_replace: boo, //国内CDN替换
  cdn_gravatar: boo, //Gravatar头像替换
  cdn_gravatar_mirror: 'gravatar.loli.net/avatar/', //Gravatar镜像地址
  cdn_google_fonts: boo, //Google Fonts替换
  cdn_google_fonts_mirror: 'fonts.loli.net', //Google Fonts镜像地址
  cdn_google_ajax: boo, //Google Ajax替换
  cdn_custom: '', //自定义CDN替换规则
  hide_email_ip: boo, //隐藏邮件中的IP
};

//优化  媒体
const OptimizeMedium = {
  img_add_tag: boo, //自动给媒体添加alt标签
  no_auto_size: boo, //禁止缩略图
  medium_add_svg: boo, //添加svg支持
  upload_auto_name: "false", //自动重命名
};

//优化 其他
const OptimizeAdmin = {
  add_user: boo, //作者筛选
  add_time: boo, //时间筛选
  show_id: boo, //列表显示ID
  thumbnail_switcher: boo, //缩略图切换
};

//页面 功能特效
const PageComment = {
  interval: boo, //两次评论间隔
  interval_time: 5, //两次评论间隔
  words_number: boo, //是否开启字数控制
  words_number_min: num, //最少评论字数
  words_number_max: 120, //最多评论字数
  english: boo, //禁止纯英文评论
  only: boo, //单篇文章仅限评论一次
  sensitive_words: boo, //敏感词过滤
  sensitive_words_list: '', //敏感词列表
  sensitive_words_action: 'replace', //处理方式
  sensitive_words_replace_char: '***', //替换字符
};

//页面 - 外观特效
const PageFeature = {
  reading_progress: boo, //页顶阅读进度条
  reading_progress_color: "#1677ff", //进度条颜色
  reading_progress_height: 3, //进度条高度
};

//页面 功能
const PageFunction = {
  first_picture: boo, //首图作特色图
  add_inks: boo, //关键词自动添加链接
  add_last_update: boo, //添加最后更新时间
  no_login_img: boo, //未登录模糊图片
  maintenance_tips: "false", //维护提示
  //countdown: ["2024-06-01 00:00:00","2024-06-02 00:00:00"], //维护结束倒计时
  countdown: timeArray,
  countdown_title: "", //维护标题
  countdown_image: "", //维护图片
  countdown_content: "", //维护内容
  default_thumbnail: "", //默认文章缩略图
  search_limit: boo, //限制搜索频次
  search_limit_count: 10, //每分钟最大搜索次数
  batch_replace: boo, //文章批量替换
  batch_replace_pairs: [], //替换规则
  login_search: boo, //仅登录可搜索

  anti_crawler: boo, //进阶防刷
  anti_crawler_max_requests: 60, //最大请求数
  anti_crawler_time_window: 60, //时间窗口(秒)
  anti_crawler_tecent_id: '', //腾讯防水墙AppID
  anti_crawler_tecent_key: '', //腾讯防水墙AppKey
};

// 页面 - 权限
const PageJurisdiction = {
  category_id: [], //分类ID
  tag_id: [], //标签ID
  page_id: [], //页面ID
  single_id: [], //文章ID
  tip_content: str, //提示内容
};

//权限控制 辅助
const FunctionAuxiliary = {
  single_count: boo, //文章统计
  no_malice_key: boo, //拒绝恶意关键词
  malice_keu_content: str, //恶意关键词内容
  baidu_tonji: str, //  百度统计
  google_tonji: str, // 谷歌统计
  biying_tonji: str, // 必应统计
  uniqueKey: 0,
};



//简单SEO功能
const FunctionSeo = {
  title: str, //网站标题
  keywords: str, //网站关键字
  description: str, //网站描述
  seo_single: boo, //文章SEO
  seo_category: boo, //分类和标签SEO
};

//功能 插件设置
const FunctionTips = {
  pop_tips: boo, //弹窗提示
  tips_time: num, //提示时间段
  tips_content: str, //提示内容
  tips_button: str, //按钮文字
  tips_link: str, //按钮链接
};

//登录安全
const LoginSecurity = {
  login_code: "false", //登录验证码
  tecent_id: str, //腾讯ID
  tecent_key: str, //腾讯秘钥
};

//国内生态 - 备案与合规
const DomesticCompliance = {
  icp_enabled: boo,
  icp_number: '',
  icp_link: 'https://beian.miit.gov.cn/',
  police_enabled: boo,
  police_number: '',
  police_link: 'https://www.beian.gov.cn/portal/registerSystemInfo',
  cookie_enabled: boo,
  cookie_style: 'bottom',
  cookie_title: 'Cookie 同意',
  cookie_content: '本网站使用 Cookie 来改善您的体验。继续浏览即表示您同意我们的 Cookie 政策。',
  cookie_button: '我知道了',
  copyright_enabled: boo,
  copyright_html: '',
};

//国内生态 - 百度推送
const DomesticBaiduPush = {
  active_push_enabled: boo,
  site: '',
  token: '',
  auto_push_enabled: boo,
  batch_push_enabled: boo,
};

//国内生态 - 微信生态
const DomesticWechat = {
  jssdk_enabled: boo,
  appid: '',
  appsecret: '',
  guide_overlay_enabled: boo,
  guide_mode: 'guide',
  guide_text: '点击右上角 ··· 在浏览器中打开',
  guide_qrcode: '',
};

//国内生态 - 评论安全
const DomesticCommentSecurity = {
  blacklist_enabled: boo,
  blacklist_words: '',
  blacklist_action: 'block',
  link_limit_enabled: boo,
  link_limit_count: 2,
  nickname_filter_enabled: boo,
  nickname_filter_words: '',
  email_domain_enabled: boo,
  email_domain_blacklist: '10minutemail.com,guerrillamail.com,temp-mail.org',
  duplicate_enabled: boo,
  ip_rate_enabled: boo,
  ip_rate_limit: 5,
  ip_rate_window: 60,
  log_enabled: boo,
};

//国内生态 - 登录安全
const DomesticLoginSecurity = {
  fail_limit_enabled: boo,
  fail_limit_count: 5,
  fail_lock_duration: 30,
  ip_lock_enabled: boo,
  ip_lock_count: 10,
  ip_lock_duration: 60,
  custom_login_enabled: boo,
  custom_login_slug: 'my-login',
  ban_enumeration_enabled: boo,
  login_notify_enabled: boo,
  login_log_enabled: boo,
  ip_whitelist_enabled: boo,
  ip_whitelist: '',
};

//性能优化 - 对象存储
const PerformanceOss = {
  enabled: boo,
  provider: 'aliyun',
  access_key: '',
  secret_key: '',
  bucket: '',
  region: '',
  domain: '',
  delete_local: boo,
};

//性能优化 - SEO检查
const PerformanceSeoChecker = {
  enabled: boo,
};

//性能优化 - 媒体库体检
const PerformanceMediaHealth = {
  enabled: boo,
};

//性能优化 - 搜索增强
const PerformanceSearchEnhance = {
  highlight_enabled: boo,
  recommend_enabled: boo,
  hotwords_enabled: boo,
};

//性能优化 - 数据库清理
const PerformanceDbClean = {
  enabled: boo,
  clean_revisions: boo,
  clean_drafts: boo,
  clean_spam_comments: boo,
  clean_transients: boo,
  auto_clean: boo,
  auto_clean_schedule: 'weekly',
};

// AI 审核 - Provider
const AiReview = {
  enabled: boo,
  provider: 'local',
  mode: 'mark',
  deepseek_api_key: '',
  deepseek_api_url: 'https://api.deepseek.com/v1/chat/completions',
  deepseek_model: 'deepseek-chat',
  aliyun_access_key: '',
  aliyun_secret_key: '',
  aliyun_region: 'cn-shanghai',
  custom_api_url: '',
  custom_api_method: 'POST',
  custom_api_headers: '',
  custom_api_body_template: '',
  local_rules_enabled: boo,
  local_keywords: '',
  local_regex: '',
  strict_mode: boo,
  log_enabled: boo,
  log_max_entries: 500,
};





export const defaultVarOption = {
  //优化
  optimize: {
    site: OptimizeSite, //站点
    medium: OptimizeMedium, //媒体
    admin: OptimizeAdmin, //其他
  },
  //权限控制
  function: {
    auxiliary: FunctionAuxiliary, //辅助功能

    seo: FunctionSeo, //简单 SEO 功能
    config: FunctionTips, //设置
  },
  //页面
  page: {
    comment: PageComment, //评论
    feature: PageFeature, //外观特效
    function: PageFunction, //页面功能
    jurisdiction: PageJurisdiction, //权限
  },
  //登录
  login: {
    security: LoginSecurity, //安全
  },
  //国内生态
  domestic: {
    compliance: DomesticCompliance,
    baidu_push: DomesticBaiduPush,
    wechat: DomesticWechat,
    comment_security: DomesticCommentSecurity,
    login_security: DomesticLoginSecurity,
  },
  //性能优化
  performance: {
    oss: PerformanceOss,
    seo_checker: PerformanceSeoChecker,
    media_health: PerformanceMediaHealth,
    search_enhance: PerformanceSearchEnhance,
    db_clean: PerformanceDbClean,
  },
  // AI 审核
  ai_review: AiReview,

};
export const defaultVarData = {
  option: defaultVarOption,
  url_site: "http://localhost:10029",
};
