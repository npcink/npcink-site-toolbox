# Changelog

All notable changes to WP Magick Toolbox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [2.5.0] - 2026-05-27

### Added
- 站点体检中心：后端 `MaBox_Diagnostics` 聚合诊断 + Dashboard 实时展示评分/风险/建议
- 保存前 diff 确认弹窗：`diffConfig()` 递归比较 + 高风险路径自动标红
- 单模块恢复默认值：支持 optimize/page/function/login 等 9 个顶层模块独立恢复
- 诊断报告导出：基于 `DiagnosticSummary` 生成 Markdown，支持剪贴板/下载
- REST 诊断端点 `GET /mabox/v1/diagnostics/summary`（权限 `manage_options`）
- 诊断单元测试：15 测试用例 / 44 断言，覆盖分数/状态/推荐/风险/边界

### Changed
- 优化 3 个官方推荐方案：`blog_stable`、`company_compliance`、`content_seo_safe`
- 统一 AI 审核与反馈组件的 REST response body 格式

### Fixed
- `vite/admin/src/axios/save.tsx` 导出命名错误（`saceOption` → `saveOption`）
- 前端诊断类型定义补全（`DiagnosticSummary` / `DiagnosticItem` / `ConfigDiffItem`）
- 批量替换/批量回滚/单篇回滚权限校验保持 `manage_options`

### Fixed
- 文章统计功能 wp_localize_script 句柄不匹配
- 小程序接口缺失错误提示处理
- 外链跳转中间页 XSS 漏洞
- function.tsx 重复 share Form.Item
- b2/add_menu.php 调试 console.log 遗留
- 全站变灰与 OwO 表情选择器冲突
- 隐藏分类文章时下载框 HTML 泄露
- 7 个前台 JS 文件头部加载改为底部

### Added
- PHPUnit 测试框架配置
- Vitest + React Testing Library 配置
- GitHub Actions CI/CD 流水线

## [2.0.84] - 2024-XX-XX

### Added
- 设置导入导出功能
- 默认文章缩略图
- 限制搜索频次
- 顶部广告位
- 文章批量替换
- 仅登录可搜索
- 文章评分功能
- 页眉通知栏

## [2.0.83] - 2024.09.02

### Added
- 返回顶部功能 - 偷瞄猫猫
- 返回顶部功能 - 圆角箭头
- 未登录隐藏内容时支持自定义提示信息
- 跳转引导页：wps
- 页面选项中添加快捷二级菜单

### Fixed
- 初次使用时会触发部分功能
- 删除插件时的报错问题
- 多个选项时选项不准的问题

## [2.0.82] - 2024.08.13

### Fixed
- 预览图和部分短代码细节
- 添加地图时的序号混乱问题

## [2.0.81] - 2024.08.09

### Added
- 用户列表展示昵称
- 背景：质感圆球
- 底部背景：鱼群跳动
- 页面模版：文章列表、立体三角

### Changed
- 搜索页链接优化：`?s=关键词` → `/search/关键词`

## [2.0.8] - 2024.07.19

### Added
- 在线运行代码的短代码
- 复制短代码
- 文章列表短代码
- 短代码古藤堡支持
- 足迹地图功能
- 自动设文章首图为特色图功能
- 禁止在微信或 QQ 中打开的提示
- 背景：流动线条、滴墨水、流动彩带、随机彩带

### Changed
- 移除原生站点地图中关于用户信息部分的内容
- 优化设置外观

## [2.0.0] - 2024.06.01

### Changed
- 全新改版
- 重构设置界面
- 拆分功能模块
