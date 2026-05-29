export interface SearchItem {
  id: string;
  label: string;
  tabKey: string;
  tabLabel: string;
  section?: string;
  keywords?: string[];
  tags?: string[];
}

export const searchIndex: SearchItem[] = [
  { id: "optimize-site-hide_top_toolbar", label: "隐藏顶部工具条", tabKey: "2", tabLabel: "优化", section: "站点", keywords: ["toolbar", "顶部", "工具栏"], tags: ["推荐", "仅后台"] },
  { id: "optimize-site-no_escape", label: "禁止 Title 转义", tabKey: "2", tabLabel: "优化", section: "站点", keywords: ["title", "转义"], tags: ["推荐"] },
  { id: "optimize-site-remove_RSS_version", label: "移除 WP 版本号", tabKey: "2", tabLabel: "优化", section: "站点", keywords: ["version", "版本", "rss"], tags: ["推荐", "安全"] },
  { id: "optimize-site-renew", label: "禁用自动更新", tabKey: "2", tabLabel: "优化", section: "站点", keywords: ["update", "更新"], tags: ["谨慎"] },
  { id: "optimize-site-cdn_replace", label: "国内 CDN 替换", tabKey: "2", tabLabel: "优化", section: "站点", keywords: ["cdn", "加速"], tags: ["性能"] },
  { id: "optimize-medium-img_add_tag", label: "图片自动添加 Alt", tabKey: "2", tabLabel: "优化", section: "媒体", keywords: ["alt", "图片", "seo"], tags: ["推荐", "SEO"] },
  { id: "optimize-medium-no_auto_size", label: "禁止缩略图", tabKey: "2", tabLabel: "优化", section: "媒体", keywords: ["thumbnail", "缩略图"], tags: ["谨慎", "需主题兼容"] },
  { id: "optimize-medium-upload_auto_name", label: "上传文件重命名", tabKey: "2", tabLabel: "优化", section: "媒体", keywords: ["rename", "重命名", "上传"], tags: ["推荐"] },
  { id: "optimize-admin-add_user", label: "文章作者筛选", tabKey: "2", tabLabel: "优化", section: "后台", keywords: ["author", "作者", "筛选"] },
  { id: "optimize-admin-add_time", label: "文章日期筛选", tabKey: "2", tabLabel: "优化", section: "后台", keywords: ["date", "日期", "筛选"] },
  { id: "optimize-admin-show_id", label: "列表显示 ID 列", tabKey: "2", tabLabel: "优化", section: "后台", keywords: ["id", "列表"] },
  { id: "optimize-admin-thumbnail_switcher", label: "缩略图切换", tabKey: "2", tabLabel: "优化", section: "后台", keywords: ["thumbnail", "缩略图"] },

  { id: "page-jurisdiction-ban_copy", label: "禁止复制", tabKey: "1", tabLabel: "页面", section: "权限", keywords: ["copy", "复制"], tags: ["谨慎"] },
  { id: "page-jurisdiction-ban_open_weixing", label: "禁止微信打开", tabKey: "1", tabLabel: "页面", section: "权限", keywords: ["wechat", "微信"], tags: ["谨慎"] },
  { id: "page-function-maintenance_tips", label: "维护提示页", tabKey: "1", tabLabel: "页面", section: "功能", keywords: ["maintenance", "维护", "闭站"], tags: ["谨慎"] },
  { id: "function-seo-seo_home", label: "首页 TDK", tabKey: "5", tabLabel: "功能", section: "SEO", keywords: ["tdk", "首页", "seo", "标题", "描述"], tags: ["推荐", "SEO"] },
  { id: "function-seo-seo_single", label: "文章 SEO", tabKey: "5", tabLabel: "功能", section: "SEO", keywords: ["seo", "文章", "关键词"], tags: ["推荐", "SEO"] },
  { id: "login-security-login_code", label: "登录验证码", tabKey: "3", tabLabel: "登录页", section: "安全", keywords: ["captcha", "验证码"], tags: ["推荐", "安全"] },
  { id: "login-security-tecent", label: "腾讯防水墙", tabKey: "3", tabLabel: "登录页", section: "安全", keywords: ["tencent", "腾讯", "防水墙"] },
  { id: "domestic-compliance-icp", label: "ICP 备案号", tabKey: "10", tabLabel: "国内生态", section: "合规", keywords: ["icp", "备案", "合规"], tags: ["推荐"] },
  { id: "domestic-compliance-police", label: "公安网备号", tabKey: "10", tabLabel: "国内生态", section: "合规", keywords: ["公安", "网备", "备案"], tags: ["推荐"] },
  { id: "domestic-compliance-cookie", label: "Cookie 同意弹窗", tabKey: "10", tabLabel: "国内生态", section: "合规", keywords: ["cookie", "隐私", "弹窗"] },
  { id: "domestic-compliance-copyright", label: "版权信息", tabKey: "10", tabLabel: "国内生态", section: "合规", keywords: ["copyright", "版权"] },
  { id: "domestic-baidu-push", label: "百度收录推送", tabKey: "10", tabLabel: "国内生态", section: "百度推送", keywords: ["baidu", "百度", "推送", "收录"], tags: ["推荐", "SEO"] },
  { id: "domestic-wechat-jssdk", label: "微信 JSSDK 分享", tabKey: "10", tabLabel: "国内生态", section: "微信生态", keywords: ["wechat", "微信", "分享", "jssdk"] },
  { id: "domestic-wechat-guide", label: "微信打开引导", tabKey: "10", tabLabel: "国内生态", section: "微信生态", keywords: ["wechat", "微信", "引导", "遮层"] },
  { id: "domestic-comment-blacklist", label: "评论敏感词过滤", tabKey: "10", tabLabel: "国内生态", section: "评论安全", keywords: ["comment", "评论", "敏感词", "黑名单"], tags: ["推荐", "安全"] },
  { id: "domestic-comment-link-limit", label: "评论链接限制", tabKey: "10", tabLabel: "国内生态", section: "评论安全", keywords: ["comment", "评论", "链接", "垃圾"] },
  { id: "domestic-comment-ip-rate", label: "评论 IP 频率限制", tabKey: "10", tabLabel: "国内生态", section: "评论安全", keywords: ["comment", "评论", "ip", "频率"] },
  { id: "domestic-login-fail-limit", label: "登录失败限制", tabKey: "10", tabLabel: "国内生态", section: "登录安全", keywords: ["login", "登录", "限制", "暴力破解"], tags: ["推荐", "安全"] },
  { id: "domestic-login-custom-url", label: "自定义登录地址", tabKey: "10", tabLabel: "国内生态", section: "登录安全", keywords: ["login", "登录", "地址", "隐藏"] },
  { id: "domestic-login-ip-whitelist", label: "后台 IP 白名单", tabKey: "10", tabLabel: "国内生态", section: "登录安全", keywords: ["login", "登录", "ip", "白名单"], tags: ["安全"] },
  { id: "performance-oss", label: "对象存储 / OSS", tabKey: "11", tabLabel: "性能优化", section: "云存储", keywords: ["oss", "cos", "云存储", "阿里云", "腾讯云"], tags: ["性能"] },
  { id: "performance-seo-checker", label: "SEO 检查助手", tabKey: "11", tabLabel: "性能优化", section: "SEO", keywords: ["seo", "检查", "alt", "健康度"], tags: ["SEO"] },
  { id: "performance-media-health", label: "媒体库体检", tabKey: "11", tabLabel: "性能优化", section: "媒体", keywords: ["media", "媒体", "图片", "alt", "体检"] },
  { id: "performance-search-highlight", label: "搜索关键词高亮", tabKey: "11", tabLabel: "性能优化", section: "搜索", keywords: ["search", "搜索", "高亮", "关键词"] },
  { id: "performance-db-clean", label: "数据库清理优化", tabKey: "11", tabLabel: "性能优化", section: "数据库", keywords: ["db", "数据库", "清理", "优化", "修订版本"], tags: ["推荐", "性能"] },
];