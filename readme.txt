=== WP Magick Toolbox ===
Contributors: npcink
Donate link: https://www.npc.ink/
Tags: toolbox, optimization, security, performance, shortcode
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

面向中国 WordPress 站长的一站式实用工具箱插件，集优化、安全、美化、短代码于一体。

== Description ==

WP Magick Toolbox（魔法工具箱）是一款面向中国 WordPress 站长的一站式实用工具箱插件，提供诸多实用且有趣的功能合集，简单易用。

= 主要功能 =

**优化模块**
- 媒体库 SVG 支持（安全模式）
- 图片自动重命名与 Alt 补全
- 数据库清理与优化
- SEO 检测与修复
- CDN 替换（Gravatar、Google Fonts 等）

**安全模块**
- 登录安全验证
- 评论敏感词过滤
n- 百度文本审核
- 防爬虫验证
- 防水墙人机验证

**页面功能**
- 文章批量替换（支持预览与回滚）
- 页眉通知栏
- 维护模式
- 友情链接跳转中间页
- 关键词自动内链

**美化与特效**
- 阅读进度条
- 点击特效
- 背景动态特效
- 灯笼、樱花飘落等节日特效
- 返回顶部按钮

**短代码**
- Bilibili 视频嵌入
- 微信公众号解锁
- 打赏按钮
- 代码运行器
- 高德地图

**国内生态**
- 微信生态集成
- 百度推送
- ICP 备案信息展示
- 登录安全合规

= 适用场景 =

- 个人博客美化与优化
- 企业网站安全加固
- 内容站 SEO 提升
- 电商站功能增强（B2 主题兼容）

== Installation ==

1. 上传 `wp-magick-toolbox` 文件夹到 `/wp-content/plugins/` 目录
2. 在 WordPress 后台的“插件”菜单中激活该插件
3. 进入“插件 > 魔法工具箱”进行设置

== Frequently Asked Questions ==

= 是否支持多站点？ =

当前版本主要针对单站点优化，多站点支持正在开发中。

= 是否兼容所有主题？ =

已测试兼容 WordPress 官方主题及主流商业主题。部分功能（如 B2 主题专属统计）需要特定主题支持。

= 如何卸载？ =

停用并删除插件即可。卸载时会自动清理插件创建的选项数据。

= 是否支持国际化？ =

支持。插件文本域为 `magick-toolbox`，可通过标准 WordPress 翻译流程进行本地化。

== Screenshots ==

1. 设置页面总览
2. 优化模块设置
3. 安全模块设置
4. 短代码使用示例

== Changelog ==

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
* 添加增值服务基础设施
* 添加用户反馈与数据洞察

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

= 2.5.0 =
建议所有用户升级。本次更新新增站点体检中心、保存前变更确认、模块级恢复等功能，升级后建议进入 Dashboard 查看体检结果并重新保存一次设置。

= 2.4.0 =
建议所有用户升级。本次更新包含安全加固和架构优化，建议升级后重新保存一次设置。

== Privacy Policy ==

本插件尊重用户隐私，不会收集任何个人数据。部分可选功能（如百度推送）需要向第三方服务发送数据，仅在用户明确开启后生效。
