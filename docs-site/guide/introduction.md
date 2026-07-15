# 简介

WP Magick Toolbox 是一款面向中国 WordPress 站长的免费工具箱插件，涵盖站点优化、SEO、安全防护、媒体维护和国内生态对接等常用场景。

## 为什么选择 WP Magick Toolbox？

### 一个插件，解决 80% 日常需求

WordPress 生态中有大量单一功能插件。WP Magick Toolbox 将常用能力整合在同一套按需加载架构中，减少重复设置入口和维护成本。

### 专为中国站长设计

- **备案合规**：ICP 备案号、公安网备号、Cookie 同意弹窗
- **微信生态**：JSSDK 分享配置、微信内打开引导遮层
- **对象存储**：阿里云 OSS、腾讯云 COS、七牛云一键对接

### 完全免费，无付费墙

基于 GPL-2.0 开源协议发布，全部功能免费开放，不做功能分层，不做付费墙。

## 核心特性

| 特性 | 说明 |
|------|------|
| 模块注册表 | 所有功能通过统一注册表加载，新增功能只需添加一条记录 |
| 按需加载 | 未启用的功能不注册 Hook、不占用内存 |
| 配置拆分 | 按模块拆分为独立 Option，避免 JSON 膨胀 |
| 明确保存 | 保存前展示设置差异，读取失败时禁止覆盖服务端配置 |
| 凭据隔离 | 已保存密钥不进入页面、读取响应或浏览器持久化存储 |

## 技术栈

- **后端**：PHP 7.4+，WordPress Plugin Boilerplate 变体
- **前端**：React + TypeScript + Vite + Ant Design + TailwindCSS
- **图表**：ECharts + 自研统计组件
- **通信**：WordPress REST API 为主，少量独立后台交互使用 WordPress AJAX

## 快速导航

- [安装与使用](/guide/getting-started) — 如何安装和配置插件
- [功能文档](/features/site-optimization/disable-title-escape) — 当前功能的详细说明
- [开发指南](/guide/development) — 如何添加新功能
- [更新日志](/guide/changelog) — 版本变更记录
