# WP Magick Toolbox

> 面向中国 WordPress 站长的一站式实用工具箱插件
> 版本：**2.6.1** | 功能数：**90+** | 授权：**GPL-2.0**

[![CI](https://github.com/npcink/wp-magick-toolbox/actions/workflows/ci.yml/badge.svg)](https://github.com/npcink/wp-magick-toolbox/actions/workflows/ci.yml)
[![WordPress Plugin](https://img.shields.io/badge/WordPress-4.6%2B-blue)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%202.0-orange)](LICENSE)

---

## 简介

WP Magick Toolbox 是一款面向中国 WordPress 站长的免费工具箱插件，集成了 **90+ 实用功能**，涵盖站点优化、SEO、安全防护、国内生态对接（百度推送、微信生态、备案合规）、性能优化等多个维度。

**核心理念**：一个插件，解决站长 80% 的日常需求。

- 📖 **在线文档**：[docs.npc.ink](https://docs.npc.ink)（搭建中）
- 🌐 **作者博客**：[npc.ink](https://www.npc.ink)
- 📦 **Gitee 仓库**：[gitee.com/gitgreat/wp-magick-toolbox](https://gitee.com/gitgreat/wp-magick-toolbox)

---

## 安装与使用

### WordPress 中安装

1. 下载本插件 ZIP 包
2. WordPress 后台 → 插件 → 安装插件 → 上传并安装
3. 启用插件
4. 在左侧菜单找到「魔法工具箱」，进入后点击一次「保存」按钮即可生效

### 本地开发

```bash
# 克隆仓库
git clone https://gitee.com/gitgreat/wp-magick-toolbox.git
cd wp-magick-toolbox

# 安装前端依赖（3 个独立 Vite 项目）
cd vite/admin && npm install && cd ../..
cd vite/count && npm install && cd ../..
cd vite/public && npm install && cd ../..

# 启动开发服务器（已配置代理到本地 WordPress）
cd vite/admin && npm run dev
```

> 代理地址在 `vite.config.ts` 底部，替换为您的本地开发地址即可。

### 打包部署

`vite/` 文件夹下包含 3 个独立项目：
- `admin/` — 后台设置界面（React + Ant Design）
- `count/` — 图表展示组件
- `public/` — 前端展示组件

修改后分别执行 `npm run build`，仅保留各项目中 `dist/` 目录下的文件即可。

---

## 功能概览

| 模块 | 功能数 | 核心能力 |
|------|--------|----------|
| 站点优化 | 8 | 禁止 Title 转义、分类去 category、搜索链接优化等 |
| 媒体优化 | 4 | 自动 Alt、禁止缩略图、SVG 支持、上传重命名 |
| 后台优化 | 4 | 作者/日期筛选、列表显示 ID、缩略图切换 |
| 页面外观 | 6 | 顶部加载进度条、复制弹窗、美化滚动条等 |
| 页面评论 | 6 | OwO 表情、间隔限制、字数限制、禁止纯英文等 |
| 页面功能 | 16 | 外链跳转、维护页、简繁切换、禁止复制等 |
| SEO 功能 | 5 | 首页 TDK、文章 SEO、分类/标签 SEO |
| 辅助功能 | 5 | 文章统计、屏蔽恶意搜索、百度/谷歌/必应统计 |
| 国内生态 | 10 | 备案合规、百度推送、微信 JSSDK、Cookie 弹窗、OSS 对接 |
| 其他 | 4 | AI 审核、字体切换、小工具选项、隐藏邮件 IP |

> 完整功能清单见 [功能清单.md](功能清单.md)

---

## 技术架构

- **后端**：PHP 7.4+，WordPress Plugin Boilerplate 变体，模块注册表机制
- **前端设置页**：React + TypeScript + Vite + Ant Design + TailwindCSS
- **前端展示**：React + TypeScript + Vite（按需加载）
- **图表**：ECharts + 自研统计组件
- **数据存储**：WordPress `wp_options` 表（按模块拆分，支持快照备份）
- **通信方式**：WordPress REST API 为主，少量独立后台交互使用 WordPress AJAX

### 安全加固

- SQL 注入防护（全部使用 `$wpdb->prepare()`）
- CSRF 防护（nonce 验证）
- XSS 防护（输出转义 `esc_html()` / `esc_url()` / `esc_attr()`）
- 权限检查（`current_user_can('manage_options')`）

---

## 更新记录

### 2.6.1 — 2026-05-28

- PHPStan 静态分析门禁（CI 与本地命令统一，`--memory-limit=1G`）
- 搜索健康中心：`hotwords_enabled` 开启后自动挂载主查询采集与无结果追踪
- REST `/mabox/v1/public/search-log` 兼容 `keyword` 参数，移除 `check_ajax_referer` 依赖
- 版本号同步到 `2.6.1`
- Vite base 路径修复

### 2.6.0 — 2026-05-27

- 修复百度推送 REST API（实现 `rest_batch_push`，替代缺失回调导致的致命错误）
- 修复 SVG 安全清洗（移除属性值中的 `javascript:`/`vbscript:`/`expression(` 协议）
- 修正首次配置向导（移除高风险功能默认开启：`cdn_replace`/`ban_copy`/`ban_open_weixing`）
- 修正国内环境适配（一键修复返回建议变更 diff，不再直接落库；CDN 替换标记为高风险需确认）
- 修正数据库清理执行链路（前端必须传 `dry_run: false` 才执行；按类型预览门控；显示真实影响数量）
- 修正审计日志调用签名（`MaBox_Audit_Logger::log($level, $category, $message, $context)`）

### 2.3.0 — 2026-05-09

- 新增 AI 审核引擎（DeepSeek / 阿里云 / 自定义 API / 本地规则引擎，自动降级）
- 新增隐藏邮件中的 IP
- 新增文章链接添加来源标识 `from=npc`
- 新增字体切换功能
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
3. **React 前端**：在 `vite/admin/src/tool/interface.tsx` 添加类型定义，在 `defaultVar.tsx` 添加默认值，在对应 Tab 组件中添加 UI 控件
4. **详细规范**：见 [项目现状与开发指南.md](项目现状与开发指南.md)

### CI/CD

项目使用 GitHub Actions 进行持续集成，覆盖 PHP 7.4 ~ 8.3 多版本测试。详见 `.github/workflows/ci.yml`。

---

## 许可证

[GPL-2.0](LICENSE) — 全部功能免费开放，无付费墙或功能限制。
