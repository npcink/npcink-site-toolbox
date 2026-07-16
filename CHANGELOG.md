# Changelog

All notable changes to Magick Toolbox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [3.0.1] - 2026-07-16

### Changed
- WordPress.org 公开显示名改为 Magick Toolbox，发布 slug 与文本域统一为 `magick-toolbox`
- 插件 Header 补齐 GPL 许可证和文本域，WordPress.org readme 补齐英文产品说明、第三方服务披露、公开源码和可复现 Vite 构建命令
- 删除没有对应资产的 Screenshots 说明；品牌资产留待目录审核通过并取得 SVN 后提交

### Removed
- 移除会阻断 WordPress 核心、插件和主题更新检查的 `optimize.ban_update` 模块；WordPress.org 版本不再提供禁用更新能力

### Fixed
- 恢复国内访问连通性检测的 TLS 证书校验，并把自动镜像建议统一为已披露的 Loli.net 默认值

### Compatibility
- 保持仓库名 `wp-magick-toolbox`、主文件名、`MAGICK_MIXTURE_*` 常量名和既有 Option key 不变；本版本不迁移或重置设置

## [3.0.0] - 2026-07-16

### Changed
- 以 Pre-GA clean break 重建管理后台：七个语义化视图承载 57 个注册模块，旧数字导航不再作为兼容入口
- 模块 Registry 与配置 Schema 成为单一事实源；前端设置类型、敏感路径和 33 条功能搜索索引由 PHP 契约生成
- 设置读取不再返回敏感原值，凭据保存明确区分保留、替换和清除；普通设置继续经过完整 Schema 校验和差异确认
- 管理 REST 请求统一经过一个客户端，并补齐可信的加载、错误、空状态、键盘路径和移动端反馈
- 前端收敛为一个 pnpm 前端工程、Admin 与 Count 两个独立产物；Admin 外壳改用原生 React/WordPress 样式并按需加载复杂表单

### Removed
- 删除 AI Provider Runtime 正式产品代码；移植思路与核心实现只保留在不进入发布包的只读参考目录
- 删除不可信登录验证码、anti-crawler/腾讯防水墙遗留、百度推送以及对应设置、REST 和前端入口
- 删除旧 Dashboard 默认分、首次向导、预设市场、收藏拖拽、浏览器设置快照及旧迁移/备份兼容层
- 删除没有可用管理界面或真实消费者的文章批量替换模块及 3 条手动 REST 路由
- 删除数据库表导出、文章评分和微信解锁遗留的 4 条不可调用 REST 路由

### Fixed
- 登录失败限制、可信代理 IP、匿名作者枚举保护和锁定恢复路径按独立设置正确加载，并补齐回归门禁
- 模块 Loader、Registry、设置 Schema、生成式前端契约和发布 ZIP 均增加精确一致性验证
- 分类、标签和页面选项接口改用受管理员权限保护的标准 WordPress REST 响应
- REST Registry 增加 callback 可调用性与精确产品表面回归门禁

## [2.6.1] - 2026-05-28

### Added
- PHPStan 静态分析门禁（CI 与本地命令统一，`--memory-limit=1G`）
- 搜索健康中心：`hotwords_enabled` 开启后自动挂载主查询采集 `pre_get_posts`
- 搜索健康中心：无结果搜索路径通过 `loop_no_results` 单独递增 `no_result_count`
- REST 搜索日志端点兼容 `keyword` 参数，权限由 route nonce + rate limiter 负责
- REST 搜索日志与无结果采集 PHPUnit 测试覆盖

### Fixed
- REST `/mabox/v1/public/search-log` 回调不再依赖 `check_ajax_referer()`，与 REST 权限模型一致
- 版本号同步到 `2.6.1`（readme.txt、README.md、CHANGELOG.md、docs-site changelog）
- Vite base 路径修复

## [2.6.0] - 2026-05-27

### Added
- 首次配置向导：提供个人博客、企业官网、内容 SEO 站 3 个场景预设，diff 确认后保存
- 中国访问适配检测与一键修复建议：Gravatar、Google Fonts、Google Ajax 替换需 diff 确认
- 诊断中心增加中国访问适配检查项，诊断报告导出增加敏感信息脱敏
- 数据库清理改为先预览、按类型门控、确认后显式 `dry_run: false` 执行
- 数据库清理、数据库导出等移入高风险层级；SVG 上传移入进阶层级
- 首次配置向导 diff 确认、快照、统一保存、刷新配置后再完成
- PHPUnit risky 清理，测试在 PHP 8.5 下无 failure、无 risky

### Fixed
- 百度推送 REST API 缺失回调，补齐 `rest_batch_push`
- SVG 安全清洗，覆盖 `javascript:`、`vbscript:`、`expression(` 等风险
- 搜索增强、短代码运行器等 PHP 语法问题
- README、readme.txt、docs-site changelog 同步到 2.6.0

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
