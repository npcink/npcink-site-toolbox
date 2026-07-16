# WP Magick Toolbox

> 面向中国 WordPress 站长的一站式实用工具箱插件
> 版本：**3.0.0** | 阶段：**预备发布** | 授权：**GPL-2.0**

[![CI](https://github.com/muze-page/wp-magick-toolbox/actions/workflows/ci.yml/badge.svg)](https://github.com/muze-page/wp-magick-toolbox/actions/workflows/ci.yml)
[![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0%2B-blue)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%202.0-orange)](LICENSE)

---

## 简介

WP Magick Toolbox 是一款面向中国 WordPress 站长的免费工具箱插件。3.0.0 以 57 个注册模块为运行边界，通过七个语义化管理视图提供站点优化、内容与 SEO、登录安全、国内生态和维护诊断等能力。

**核心定位**：在一个插件内集中提供可按需启用的常见站点设置与维护工具。

- 📖 **在线文档**：[docs.npc.ink](https://docs.npc.ink)（搭建中）
- 🌐 **作者博客**：[npc.ink](https://www.npc.ink)
- 📦 **GitHub 仓库**：[github.com/muze-page/wp-magick-toolbox](https://github.com/muze-page/wp-magick-toolbox)

---

## 安装与使用

### WordPress 中安装

1. 下载本插件 ZIP 包
2. WordPress 后台 → 插件 → 安装插件 → 上传并安装
3. 启用插件
4. 在左侧菜单找到「魔法工具箱」并按需启用功能

### 本地开发

```bash
# 克隆仓库
git clone https://github.com/muze-page/wp-magick-toolbox.git
cd wp-magick-toolbox

# 安装前端依赖（单一前端工程）
corepack enable
cd vite
pnpm install --frozen-lockfile

# 启动开发服务器（已配置代理到本地 WordPress）
pnpm dev:admin
```

> Admin 开发代理位于 `vite/admin/vite.config.ts`；Count 当前只消费页面注入数据，不依赖开发代理。

### 打包部署

`vite/` 是唯一的前端工程，共享一份 `package.json`、锁文件和质量工具链，并生成两个按页面加载的独立产物：

- `admin/dist/` — 后台设置界面（React 原生管理外壳 + 按需 Ant Design 复杂表单）
- `count/dist/` — 发文统计图表（React + ECharts）

在 `vite/` 下执行 `pnpm build` 会构建两个目标；也可使用 `pnpm build:admin` 或 `pnpm build:count` 单独构建。已退役的 `vite/public` 不属于发布包；仓库根目录 `public/` 仍是 WordPress 前台 PHP/CSS 运行层，二者不要混淆。

---

## 功能概览

| 模块 | 核心能力 |
|------|----------|
| 站点与媒体 | 链接、上传、图片和后台列表优化 |
| 内容与 SEO | 评论治理、TDK、统计和内容维护 |
| 安全 | 登录尝试保护，以及面向匿名请求的作者枚举限制 |
| 国内生态 | 备案合规、微信 JSSDK、Cookie 弹窗和 OSS 对接 |
| 维护工具 | 站点体检、SEO 检查、媒体体检和数据库清理 |

> 完整功能清单见 [功能清单.md](功能清单.md)

---

## 技术架构

- **后端**：PHP 7.4+，WordPress Plugin Boilerplate 变体，模块注册表机制
- **前端工程**：React + TypeScript + Vite，共享依赖与工具链
- **独立产物**：后台设置页的导航、搜索、状态和保存外壳使用原生 React/WordPress 管理样式，复杂表单按需加载 Ant Design；发文统计页使用 ECharts，两组产物均只在对应后台页面加载
- **图表**：ECharts + 自研统计组件
- **数据存储**：WordPress `wp_options` 表（按模块拆分）
- **通信方式**：WordPress REST API 为主，少量独立后台交互使用 WordPress AJAX

### 安全加固

- SQL 注入防护（全部使用 `$wpdb->prepare()`）
- CSRF 防护（nonce 验证）
- XSS 防护（输出转义 `esc_html()` / `esc_url()` / `esc_attr()`）
- 权限检查（`current_user_can('manage_options')`）
- 敏感设置不注入页面、不随读取接口返回；管理端只显示配置状态，替换或清除必须显式提交

### 隐私与外部请求

- 启用搜索健康后，热词与无结果统计会在站点本地数据库记录搜索词和计数；这些数据不由插件自动上传给作者。
- 启用相关能力后，登录安全、审计与诊断可能在站点本地记录登录失败、IP 地址、操作事件和诊断结果，站点管理员应按自身隐私政策和保留周期管理这些数据。
- 第三方集成只在管理员显式启用并配置后发起请求；国内访问连通性检测只在管理员主动运行检查时请求目标服务。传输内容及后续处理受所选服务和站点配置约束。

---

## 更新记录

### 3.0.0 — 2026-07-16

- Pre-GA clean break：后台收口为七个语义化视图和 57 个注册模块，不保留旧数字导航或已清退功能的兼容入口
- 模块 Registry 与配置 Schema 成为单一事实源，前端设置类型、敏感路径和搜索索引由契约生成
- 敏感设置改为只读配置状态及显式替换/清除；保存前展示真实差异和高风险确认
- 移除 AI Provider Runtime、不可信登录验证码、防爬虫/防水墙遗留、百度推送及无消费者 REST 表面
- 统一管理 REST 客户端、现代化后台外壳、错误/空状态、键盘路径与响应式体验

### 2.6.1 — 2026-05-28

- PHPStan 静态分析门禁（CI 与本地命令统一，`--memory-limit=1G`）
- 搜索健康中心：`hotwords_enabled` 开启后自动挂载主查询采集与无结果追踪
- REST `/mabox/v1/public/search-log` 兼容 `keyword` 参数，移除 `check_ajax_referer` 依赖
- 版本号同步到 `2.6.1`
- Vite base 路径修复

### 2.6.0 — 2026-05-27

- 修复百度推送 REST API（实现 `rest_batch_push`，替代缺失回调导致的致命错误）
- 修复 SVG 安全清洗（移除属性值中的 `javascript:`/`vbscript:`/`expression(` 协议）
- 修正首次配置向导（移除高风险功能默认开启项）
- 修正国内环境适配（一键修复返回建议变更 diff，不再直接落库；CDN 替换标记为高风险需确认）
- 修正数据库清理执行链路（前端必须传 `dry_run: false` 才执行；按类型预览门控；显示真实影响数量）
- 修正审计日志调用签名（`MaBox_Audit_Logger::log($level, $category, $message, $context)`）

### 2.3.0 — 2026-05-09

- 新增 AI 审核引擎（DeepSeek / 阿里云 / 自定义 API / 本地规则引擎，自动降级）
- 新增隐藏邮件中的 IP
- 新增文章链接添加来源标识 `from=npc`
- 新增小工具选项（站点统计 + 最新文章带图）
- 新增日记文章类型（自定义文章类型 + 心情分类）
- 完善闭站页响应式适配
- 修复文章统计功能（hook 名称不匹配）
- 修复限制搜索频次（wp_die 转义）
- 修复未登录隐藏分类时下载框仍显示（嵌套 div 深度匹配）
- 修复统一登录报错信息（清理前端残留配置）

### 2.2.0 — 2026-05-09

- 新增仪表盘（站点健康评分 + 建议列表 + 安全状态）
- 新增一键配置方案（7 套内置方案 + 自定义保存）
- 新增常用功能收藏（星标 + 拖拽排序）
- 新增配置备份中心（自动快照 5 个 + 恢复/删除 + 恢复默认）
- 新增移动端适配（Tab 响应式 + 表单响应式）
- 新增高风险功能开启提示（9 项风险检测 + 确认弹窗）

### 2.1.0 — 2026-05-08

- 配置存储拆分（7 模块独立 Option，迁移/回滚机制）
- 模块注册表机制（替代 800+ 行硬编码加载）
- 前端资源按需加载（后台隔离 + 前台条件加载）
- 新增功能搜索（90+ 功能索引 + 实时过滤 + 高亮跳转）
- 新增功能风险标签
- 修复 5 个已知 Bug

### 2.0.83 — 2024-09-02

- 添加页面辅助组件
- 未登录隐藏内容支持自定义提示信息
- 未登录隐藏内容时隐藏主题下载框
- 添加 WPS 跳转引导页
- 页面选项添加快捷二级菜单

### 2.0.82 — 2024-08-13

- 修复地图序号混乱、多选项不准、初次使用触发部分功能等问题

### 2.0.81 — 2024-08-09

- 用户列表展示昵称、搜索链接优化

### 2.0.8 — 2024-07-19

- 内容展示组件、禁止微信/QQ 打开

### 2.0.0 — 2024-06-01

- 全新改版，重构设置界面，拆分功能模块

---

## 待实现

- 集成文档在线预览功能（WPS / 永中等）

## 放弃实现

- 禁止自动换行、自动添加 p 标签（与 WordPress 编辑器核心逻辑冲突）

---

## 开发与贡献

### 添加新功能

1. **PHP 后端**：在 `admin/partials/[category]/` 下创建功能文件，使用静态类 + `run($config)` 方法
2. **注册模块**：在 `admin/modules/registry.php` 中添加模块注册记录
3. **设置契约**：在 `MaBox_Config_Schema` 添加类型、默认值、必要的 UI 风险元数据和搜索元数据，然后运行 `composer settings-contract:generate`
4. **React 前端**：在对应 Tab 组件中添加 UI 控件；`Option`、设置子类型、敏感字段路径和搜索索引均由 PHP Schema 生成，不再手写镜像
5. **详细规范**：见 [项目现状与开发指南.md](项目现状与开发指南.md)

### CI/CD

项目使用 GitHub Actions 进行持续集成，覆盖 PHP 7.4 ~ 8.3 多版本测试。详见 `.github/workflows/ci.yml`。

---

## 许可证

[GPL-2.0](LICENSE) — 全部功能免费开放，无付费墙或功能限制。
