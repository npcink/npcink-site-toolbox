=== Magick Toolbox ===
Donate link: https://www.npc.ink/
Tags: toolbox, optimization, security, performance
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An opt-in toolbox for WordPress site settings, media, SEO, security, China-focused integrations, and maintenance.

== Description ==

Magick Toolbox is a utility plugin for WordPress site owners. Version 3.0.1 provides 56 opt-in modules in seven task-oriented admin views. Features cover site and media settings, content and SEO, login and comment safeguards, China-focused integrations, diagnostics, and maintenance.

= Current features =

* Seven admin views: Overview, Site and Media, Content and Pages, SEO and Enhancements, China Ecosystem, Maintenance Tools, and About and Help.
* Site and media: link, upload, image, admin-list, and optional CDN settings.
* Content and SEO: comment controls, restricted content, reading tools, metadata, internal links, search health, and publishing statistics.
* Security: login-attempt protection and anonymous author-enumeration protection.
* China-focused integrations: ICP information, WeChat JSSDK, cookie notice, and optional object storage.
* Maintenance: diagnostics, SEO checks, media health, and guarded database cleanup.
* Admin experience: feature search, risk labels, change confirmation, secret-status handling, and responsive layouts.

= Important behavior =

All modules that contact a third party are disabled by default. An administrator must explicitly enable the related module or manually run a connectivity check. The plugin does not send telemetry to its developer.

== Installation ==

1. Install the ZIP through Plugins > Add Plugin > Upload Plugin, or copy the plugin directory to `/wp-content/plugins/`.
2. Activate Magick Toolbox from the Plugins screen.
3. Open Plugins > Magick Toolbox and enable only the features you need.

== Frequently Asked Questions ==

= Does it support multisite? =

The current release targets single-site installations. Multisite behavior is not guaranteed.

= Does it work with every theme? =

No. Features that depend on theme markup are identified in the admin interface. Test them on a staging site before enabling them in production.

= What happens on uninstall? =

Deactivate and delete the plugin. Its uninstall routine removes the options created by the plugin.

= Is it translation-ready? =

Yes. Its text domain is `magick-toolbox`.

== External Services ==

No external service is contacted merely by activating the plugin.

= WeChat JSSDK =

When an administrator enables WeChat JSSDK and configures an AppID and AppSecret, the server sends those credentials to the WeChat token API and later sends the access token to the ticket API. On singular content, the visitor's browser loads the remote JSSDK and supplies the current URL, title, excerpt, and thumbnail URL for sharing. [Service](https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/JS-SDK.html), [terms](https://weixin.qq.com/cgi-bin/readtemplate?lang=zh_CN&t=weixin_agreement&s=default), [privacy](https://weixin.qq.com/cgi-bin/readtemplate?lang=zh_CN&t=weixin_agreement&s=privacy).

= Object storage =

When an administrator enables object storage and selects a provider, each new media upload sends the file bytes, object key, bucket/region, access-key identifier, and signed authorization data to that provider. Credentials remain in the local WordPress database and are used only for these administrator-enabled requests. Providers: [Alibaba Cloud OSS](https://www.aliyun.com/product/oss) ([terms](https://terms.aliyun.com/legal-agreement/terms/suit_bu1_ali_cloud/suit_bu1_ali_cloud201912232313_55403.html), [privacy](https://terms.aliyun.com/legal-agreement/terms/suit_bu1_ali_cloud/suit_bu1_ali_cloud202107091605_49213.html)); [Tencent Cloud COS](https://cloud.tencent.com/product/cos) ([terms](https://cloud.tencent.com/document/product/301/1967), [privacy](https://cloud.tencent.com/document/product/301/11470)); [Qiniu Kodo](https://www.qiniu.com/products/kodo) ([terms](https://www.qiniu.com/agreements/user-agreement), [privacy](https://www.qiniu.com/agreements/privacy-right)).

= Baidu Analytics =

When its module is enabled and a site ID is saved, front-end pages load Baidu Analytics. The visitor's browser may send the page URL, referrer, IP address, User-Agent, and data described by Baidu. [Service](https://tongji.baidu.com/), [terms](https://tongji.baidu.com/web/help/article?id=314&type=0), [privacy](https://tongji.baidu.com/web/help/article?id=330&type=0).

= CDN replacement =

When an administrator enables CDN replacement and a child option, visitors request Gravatar, Google Fonts, or Google Hosted Libraries assets through the configured mirror. Built-in defaults use [u.sb / Loli.net](https://u.sb/css-cdn/) ([terms](https://u.sb/terms/), [privacy](https://u.sb/privacy/)), including `gravatar.loli.net`, `fonts.loli.net`, `gstatic.loli.net`, and `ajax.loli.net`. Requests can expose the requested URL, IP address, HTTP headers, and, for avatars, an email-derived hash. Custom rules send requests to administrator-chosen destinations, whose terms and privacy policies the administrator must review.

= Manual connectivity check =

Only when an administrator runs the check does the server request test resources from [Google Fonts](https://developers.google.com/fonts/faq/privacy), [Google Hosted Libraries](https://developers.google.com/speed/libraries), [Gravatar](https://gravatar.com/), and the [WordPress.org API](https://api.wordpress.org/). No site content or plugin configuration is included, but providers receive the server IP, requested URL, and normal HTTP headers. See [Google terms](https://policies.google.com/terms) and [privacy](https://policies.google.com/privacy), [Automattic privacy](https://automattic.com/privacy/), and [WordPress.org privacy](https://wordpress.org/about/privacy/).

Google Search Console and Bing Webmaster Tools options only print administrator-supplied verification meta tags. They do not make outbound requests.

== Source Code and Build ==

The public, maintained source for the minified JavaScript and CSS shipped in this plugin is available at [GitHub](https://github.com/muze-page/wp-magick-toolbox). Reproduce the Admin and Count assets with:

`git clone https://github.com/muze-page/wp-magick-toolbox.git`

`cd wp-magick-toolbox/vite`

`corepack enable`

`pnpm install --frozen-lockfile`

`pnpm run build`

The generated files are written to `vite/admin/dist/` and `vite/count/dist/`.

== Changelog ==

= 3.0.1 =
* Release date: 2026-07-16.
* Changed the public plugin name to Magick Toolbox and aligned the WordPress.org slug and text domain to `magick-toolbox`.
* Completed the plugin header, documented external services and reproducible front-end builds, and removed screenshot captions that had no assets.
* Restored TLS certificate verification for connectivity checks and aligned automatic mirror suggestions with the documented Loli.net defaults.
* Kept existing runtime constants and option keys unchanged; this release does not migrate or reset settings.

= 3.0.0 =
* 发布日期：2026-07-16
* Pre-GA clean break：后台收口为七个语义化视图和 57 个注册模块
* Registry 与配置 Schema 成为单一事实源，前端类型、敏感路径和搜索索引由契约生成
* 敏感设置只返回配置状态，替换或清除必须显式提交；保存前展示真实差异与风险确认
* 移除 AI Provider Runtime、不可信登录验证码、防爬虫/防水墙遗留、百度推送和无消费者 REST 表面
* 统一管理 REST 客户端，重建加载、错误、空状态、键盘路径和移动端布局

= 2.6.1 =
* PHPStan 静态分析门禁（CI 与本地命令统一）
* 搜索健康中心：开启 hotwords 后自动挂载主查询采集与无结果追踪
* REST 搜索日志端点兼容 keyword 参数，移除 check_ajax_referer 依赖
* 版本号同步到 2.6.1
* Vite base 路径修复

= 2.6.0 =
* 修复百度推送模块语法错误（类体提前关闭）
* 修复搜索增强模块语法错误（同模式）
* 修复短代码运行器语法错误（PHP 标签混合）
* 修复 PHP 8.2 废弃警告（${var} → {$var}）
* 新增全量 PHP 语法检查脚本（bin/php-lint.sh）
* 新增首次配置向导（3 场景：博客/企业/内容站）
* 新增一键国内环境适配（检测 + 修复 Gravatar/Google Fonts/Ajax）
* 体检中心新增"中国访问适配"评分项
* 数据库清理、数据库导出移入高风险层级
* SVG 上传移入进阶层级
* 高风险功能默认折叠，开启前必须确认
* 数据库清理必须先预览再执行
* 修复登录安全 IP 锁定 IP 伪造风险
* 诊断报告导出脱敏（隐藏 API Key/Secret）
* 统一 Dashboard 双评分卡为后端诊断驱动
* 新增"导出诊断报告并反馈"引导入口

= 2.5.0 =
* 新增站点体检中心（后端诊断评分 + 前端 Dashboard 展示）
* 保存配置前新增 diff 确认弹窗，高风险变更标红提示
* 支持按模块恢复默认值（9 个顶层模块）
* 新增诊断报告导出（Markdown 格式）
* 优化 3 个官方推荐配置方案（博客/企业/内容站）
* 统一 REST response body 格式
* 修复 saveOption 拼写错误

= 2.4.0 =
* 重构配置层架构，REST API 替代 Ajax
* 添加 PHP Schema 校验
* 添加审计日志中心
* 添加频率限制器
* 添加站点健康检测
* 前端数据流优化
* 稳定性整改

= 2.3.0 =
* 添加 AI 审核引擎

= 2.2.0 =
* 添加国内生态模块
* 添加性能优化模块
* 配置拆分管理

= 2.1.0 =
* 配置存储拆分
* 添加批量替换功能
* 添加数据库导出安全脱敏

= 2.0.0 =
* 全新 React 管理后台
* 按需加载架构
* Vite 构建工具迁移

== Upgrade Notice ==

= 3.0.1 =
This release aligns public WordPress.org metadata and documentation. It does not reset existing settings or change runtime option keys.

= 3.0.0 =
这是 Pre-GA clean-break 基线，不提供旧后台或已清退功能的兼容入口。升级后请重新核对设置，并在启用高风险维护能力前完成预览和确认。

= 2.6.1 =
修复搜索健康采集链路、REST 权限模型、版本一致性。建议所有用户升级。

= 2.6.0 =
建议所有用户升级。本次更新修复百度推送和搜索增强模块语法错误，新增首次配置向导和国内环境适配，加强高风险功能治理。

= 2.5.0 =
建议所有用户升级。本次更新新增站点体检中心、保存前变更确认、模块级恢复等功能，升级后建议进入 Dashboard 查看体检结果并重新保存一次设置。

= 2.4.0 =
建议所有用户升级。本次更新包含安全加固和架构优化，建议升级后重新保存一次设置。

== Privacy Policy ==

Search Health can store search terms and counters in the local WordPress database. Login protection, audit, and diagnostic features can store login failures, IP addresses, actions, and diagnostic results locally when enabled. Site administrators are responsible for an appropriate privacy notice and retention policy.

The plugin does not automatically upload this local data or telemetry to its developer. Third-party requests occur only under the triggers documented in External Services. Credentials are stored in WordPress options and are sent only to the administrator-selected WeChat or object-storage provider when required for authentication.
