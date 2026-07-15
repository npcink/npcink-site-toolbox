//准备对象类型

//准备类型
export type DataLocal = {
  url_site: string;
  ajaxurl?: string;
  nonce?: string;
  apiBase?: string;
  restNonce?: string;
};

export const SECRET_PATHS = [
  "domestic.wechat.appsecret",
  "performance.oss.access_key",
  "performance.oss.secret_key",
] as const;

export type SecretPath = (typeof SECRET_PATHS)[number];

export interface SecretStatusEntry {
  configured: boolean;
}

export type SecretStatus = Record<SecretPath, SecretStatusEntry>;

export type SecretChange =
  | { operation: "replace"; value: string }
  | { operation: "clear" };

export type SecretChanges = Partial<Record<SecretPath, SecretChange>>;

export interface SettingsResponse {
  success: boolean;
  data: Option;
  secretStatus: SecretStatus;
}

export interface SettingsSavePayload {
  settings: Option;
  secretChanges: SecretChanges;
}

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

    seo: FunctionSeo; //简单 SEO 功能
    config: FunctionTips; //简单提示
  };
  //登录
  login: {
    security: LoginSecurity; //安全
  };
  //国内生态
  domestic: {
    compliance: DomesticCompliance; //备案与合规
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

export interface DiagnosticFixChange {
  path: string;
  label: string;
  before: any;
  after: any;
  risk_level: "none" | "low" | "high";
}

export interface DiagnosticFixSuggestion {
  id: string;
  title: string;
  reason: string;
  severity: "low" | "medium" | "high";
  module: string;
  requires_confirmation: boolean;
  changes: DiagnosticFixChange[];
}

export interface DiagnosticEnvironment {
  php_version: string;
  wp_version: string;
  plugin_version: string;
  permalink: string;
  object_cache: boolean;
  rest_api_available: boolean;
  site_url: string;
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
  generated_at?: string;
  environment?: DiagnosticEnvironment;
  fix_suggestions?: DiagnosticFixSuggestion[];
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
  interval: boolean; //两次评论间隔
  interval_time: number; //间隔时间
  words_number: boolean; //是否开启字数控制
  words_number_min: number; //最少评论字数
  words_number_max: number; //最多评论字数
  english: boolean; //禁止纯英文评论
  only: boolean; //单篇文章仅限评论一次
  sensitive_words: boolean; //敏感词过滤
  sensitive_words_list: string; //敏感词列表
  sensitive_words_action: string; //处理方式: replace/block
  sensitive_words_replace_char: string; //替换字符
};

//页面 - 外观特效
export type PageFeature = {
  reading_progress: boolean;
  reading_progress_color: string;
  reading_progress_height: number;
};

//页面 - 功能
export type PageFunction = {
  first_picture: boolean; //首图作特色图
  add_inks: boolean; //关键词自动添加链接
  add_last_update: boolean; //添加最后更新时间
  no_login_img: boolean; //未登录模糊图片
  maintenance_tips: string; //维护提示
  countdown: string[]; //维护结束倒计时
  countdown_title: string; //维护标题
  countdown_image: string; //维护图片
  countdown_content: string; //维护内容
  default_thumbnail: string; //默认文章缩略图
  search_limit: boolean; //限制搜索频次
  search_limit_count: number; //每分钟最大搜索次数
  batch_replace: boolean; //文章批量替换
  batch_replace_pairs: Array<{find: string; replace: string}>; //替换规则
  login_search: boolean; //仅登录可搜索
};

// 页面 - 权限
export type PageJurisdiction = {
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

//功能 弹窗提示
export type FunctionTips = {
  pop_tips: boolean; //弹窗提示
  tips_time: number; //提示时间段
  tips_content: string; //提示内容
  tips_button: string; //按钮文字
  tips_link: string; //按钮链接
};



export type FunctionSeo = {
  title: string; //网站标题
  keywords: string; //网站关键字
  description: string; //网站描述
  seo_single: boolean; //文章SEO
  seo_category: boolean; //分类和标签SEO
};

//登录安全
export type LoginSecurity = {
  login_code: string; //登录验证码
};

//下拉列表类型
export type ListData = {
  label: string;
  value: string;
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

export type DomesticWechat = {
  jssdk_enabled: boolean;
  appid: string;
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

export interface RiskInfo {
  level: string;
  title: string;
  warning: string;
  suggestion: string;
  noDismiss?: boolean;
}

export interface UiSchemaEntry {
  path: string;
  type: string;
  label?: string;
  group?: string;
  feature_id?: string;
  risk?: RiskInfo;
  depends_on?: string | string[];
  preset_tags?: string[];
  risk_tags?: string[];
}

export type UiSchemaMap = Record<string, UiSchemaEntry>;
