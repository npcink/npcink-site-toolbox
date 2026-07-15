import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'WP Magick Toolbox',
  description: '面向中国 WordPress 站长的一站式实用工具箱插件',
  base: '/',
  lastUpdated: true,
  cleanUrls: true,

  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
    ['meta', { name: 'keywords', content: 'WordPress,插件,工具箱,SEO,优化,中国站长' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: '首页', link: '/' },
      { text: '快速开始', link: '/guide/getting-started' },
      { text: '功能文档', link: '/features/site-optimization/disable-title-escape' },
      { text: '开发指南', link: '/guide/development' },
      { text: '更新日志', link: '/guide/changelog' },
    ],

    sidebar: {
      '/guide/': [
        {
          text: '快速开始',
          items: [
            { text: '简介', link: '/guide/introduction' },
            { text: '安装与使用', link: '/guide/getting-started' },
            { text: '常见问题', link: '/guide/faq' },
          ],
        },
        {
          text: '站点管理',
          items: [
            { text: '体检中心', link: '/guide/health-center' },
            { text: '配置恢复', link: '/guide/config-recovery' },
            { text: '推荐方案', link: '/guide/presets' },
          ],
        },
        {
          text: '开发者',
          items: [
            { text: '开发规范', link: '/guide/development' },
            { text: '技术架构', link: '/guide/architecture' },
            { text: '架构决策', link: '/guide/adrs' },
            { text: '更新日志', link: '/guide/changelog' },
          ],
        },
      ],
      '/features/': [
        {
          text: '功能总览',
          items: [
            { text: '全部功能', link: '/features/overview' },
          ],
        },
        {
          text: '站点优化',
          items: [
            { text: '禁止 Title 转义', link: '/features/site-optimization/disable-title-escape' },
            { text: '隐藏顶部工具条', link: '/features/site-optimization/hide-admin-bar' },
            { text: '禁用自动更新', link: '/features/site-optimization/disable-auto-update' },
            { text: '移除 WP 版本号', link: '/features/site-optimization/remove-wp-version' },
            { text: '分类链接去 category', link: '/features/site-optimization/remove-category-base' },
            { text: '搜索链接优化', link: '/features/site-optimization/search-link-optimize' },
            { text: '移除站点地图用户信息', link: '/features/site-optimization/remove-sitemap-users' },
            { text: '用户列表展示昵称', link: '/features/site-optimization/user-list-nickname' },
          ],
        },
        {
          text: '媒体优化',
          items: [
            { text: '图片自动添加 Alt', link: '/features/media-optimization/auto-alt' },
            { text: '禁止生成缩略图', link: '/features/media-optimization/disable-thumbnails' },
            { text: 'SVG 图标支持', link: '/features/media-optimization/svg-support' },
            { text: '上传文件重命名', link: '/features/media-optimization/rename-uploads' },
          ],
        },
        {
          text: '后台优化',
          items: [
            { text: '作者筛选', link: '/features/admin-optimization/author-filter' },
            { text: '日期筛选', link: '/features/admin-optimization/date-filter' },
            { text: '列表显示 ID', link: '/features/admin-optimization/list-id' },
            { text: '缩略图切换', link: '/features/admin-optimization/thumbnail-switch' },
          ],
        },
        {
          text: '页面外观',
          collapsed: true,
          items: [
            { text: '阅读进度条', link: '/features/page-appearance/reading-progress' },
          ],
        },
        {
          text: '页面评论',
          items: [
            { text: '评论间隔限制', link: '/features/page-comment/comment-interval' },
            { text: '评论字数限制', link: '/features/page-comment/comment-length-limit' },
            { text: '禁止纯英文评论', link: '/features/page-comment/no-english-comment' },
            { text: '单篇文章限评一次', link: '/features/page-comment/one-comment-per-post' },
            { text: '敏感词过滤', link: '/features/page-comment/sensitive-words' },
          ],
        },
        {
          text: '页面功能',
          collapsed: true,
          items: [
            { text: '首图作特色图', link: '/features/page-function/first-image-featured' },
            { text: '关键词自动加链', link: '/features/page-function/auto-keyword-link' },
            { text: '文章显示更新时间', link: '/features/page-function/show-update-time' },
            { text: '未登录模糊图片', link: '/features/page-function/blur-image-for-guest' },
            { text: '维护提示页', link: '/features/page-function/maintenance-page' },
            { text: '隐藏指定内容', link: '/features/page-function/hide-content' },
            { text: '默认文章缩略图', link: '/features/page-function/default-thumbnail' },
            { text: '限制搜索频次', link: '/features/page-function/search-limit' },
            { text: '文章批量替换', link: '/features/page-function/batch-replace' },
            { text: '仅登录可搜索', link: '/features/page-function/login-only-search' },
            { text: '进阶防刷', link: '/features/page-function/anti-crawler' },
          ],
        },
        {
          text: 'SEO 功能',
          items: [
            { text: '首页 TDK', link: '/features/seo/home-tdk' },
            { text: '文章 SEO', link: '/features/seo/article-seo' },
            { text: '分类 SEO', link: '/features/seo/category-seo' },
            { text: '分类添加 Meta', link: '/features/seo/category-meta' },
            { text: '标签 SEO', link: '/features/seo/tag-seo' },
          ],
        },
        {
          text: '辅助功能',
          items: [
            { text: '文章统计', link: '/features/auxiliary/article-stats' },
            { text: '屏蔽恶意搜索', link: '/features/auxiliary/block-malicious-search' },
            { text: '百度统计', link: '/features/auxiliary/baidu-analytics' },
            { text: '谷歌统计', link: '/features/auxiliary/google-analytics' },
            { text: '必应统计', link: '/features/auxiliary/bing-analytics' },
          ],
        },
        {
          text: '登录安全',
          items: [
            { text: '数学验证码', link: '/features/login-security/math-captcha' },
            { text: '随机混合验证码', link: '/features/login-security/random-captcha' },
            { text: '腾讯防水墙', link: '/features/login-security/tencent-captcha' },
            { text: '失败锁定 IP', link: '/features/login-security/login-lock-ip' },
            { text: '自定义登录入口', link: '/features/login-security/custom-login-url' },
            { text: '登录日志', link: '/features/login-security/login-log' },
          ],
        },
        {
          text: '国内生态',
          collapsed: true,
          items: [
            { text: 'ICP/公安网备', link: '/features/domestic-ecosystem/icp-filing' },
            { text: 'Cookie 同意弹窗', link: '/features/domestic-ecosystem/cookie-consent' },
            { text: '版权信息模板', link: '/features/domestic-ecosystem/copyright-template' },
            { text: '百度推送', link: '/features/domestic-ecosystem/baidu-push' },
            { text: '微信 JSSDK 分享', link: '/features/domestic-ecosystem/wechat-jssdk' },
            { text: '微信引导遮层', link: '/features/domestic-ecosystem/wechat-overlay' },
            { text: 'OSS 对接', link: '/features/domestic-ecosystem/oss-integration' },
            { text: 'SEO 检查助手', link: '/features/domestic-ecosystem/seo-checker' },
            { text: '媒体库体检', link: '/features/domestic-ecosystem/media-health' },
            { text: '数据库清理', link: '/features/domestic-ecosystem/db-cleanup' },
          ],
        },
        {
          text: '其他功能',
          collapsed: true,
          items: [
            { text: '小工具选项', link: '/features/other/widgets' },
            { text: '隐藏邮件 IP', link: '/features/other/hide-email-ip' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/npcink/wp-magick-toolbox' },
      { icon: { svg: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>' }, link: 'https://www.npc.ink' },
    ],

    search: {
      provider: 'local',
    },

    outline: {
      level: [2, 3],
      label: '本页目录',
    },

    docFooter: {
      prev: '上一页',
      next: '下一页',
    },

    editLink: {
      pattern: 'https://gitee.com/gitgreat/wp-magick-toolbox/edit/main/docs-site/:path',
      text: '在 Gitee 上编辑此页',
    },

    footer: {
      message: '基于 GPL-2.0 许可发布',
      copyright: 'Copyright © 2022-present Npcink',
    },

    lastUpdated: {
      text: '最后更新于',
    },
  },

  lang: 'zh-CN',
  locales: {
    root: {
      label: '简体中文',
      lang: 'zh-CN',
    },
  },
})
