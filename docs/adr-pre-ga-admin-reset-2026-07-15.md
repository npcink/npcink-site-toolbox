# ADR: Pre-GA 管理后台清场式重构

- 状态：已接受，首个垂直切片已验收
- 日期：2026-07-15
- 决策范围：WP Magick Toolbox 管理后台应用外壳

## 变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | 管理后台应用外壳，以及新的概览页垂直切片 |
| 预期变更 | 建立稳定的语义化导航、URL 深链接、克制的 WordPress 后台视觉、真实的加载/错误/空状态，并统一首批 REST 调用 |
| 明确非目标 | 本里程碑不一次迁移全部功能、不改前台展示、不引入 Cloud/Core/支付/队列/Provider Runtime、不承诺旧 UI 或旧导航兼容 |
| 公共契约 | 管理后台 URL 的 `view` 参数；`window.dataLocal.apiBase` 与 `restNonce`；`/mabox/v1` 管理 REST 响应 |
| 预期文件 | `vite/admin/src/components/tab.tsx`、`vite/admin/src/components/dashboard/**`、`vite/admin/src/App.css`、首批 REST 使用组件及对应测试 |
| 不得改变 | 插件前台行为、WordPress 数据库结构、公开 shortcode、用户未跟踪的排障文档、兄弟仓库 |
| 必需门禁 | admin Vitest、TypeScript、Vite build、`composer test`、PHPStan、`git diff --check` |
| 跨仓矩阵 | 不需要；本里程碑只修改本仓库管理后台 |
| 回滚计划 | 本轮以单一聚焦提交交付；回滚该提交即可恢复旧后台，不建立长期 feature flag 或双写层 |

## 背景

项目处于 Pre-GA 阶段，没有真实用户、兼容承诺或历史数据迁移负担。现有后台已经具备搜索、设置差异、诊断、模块设置等能力，但同时存在数字 Tab、多套导航状态、超大 Dashboard、多份模块事实源、散落 REST 调用和不一致的视觉语言。

继续在旧结构上增加皮肤和兼容层，会把一次性重构成本转化为长期维护成本。当前阶段应优先形成一个干净基线。

## 主要矛盾

主要矛盾是：不断累积的功能入口与多套实现机制，对站长需要的简单、可信、任务导向后台。

它属于必须明确取舍的结构性矛盾。解决它后，导航不可达、概览误导、视觉割裂、REST 调用漂移和测试脆弱等问题会同时缓解。因此选择 clean break，不保留数字 Tab、旧 Dashboard 组合逻辑或 UI 兼容壳。

## 决策

### 1. 设计读法

> 面向中国站长的 WordPress 管理工具，克制、可信、任务导向；视觉变化度 4/10、动效 2/10、信息密度 6/10。

后台采用中性背景、白色内容面、细边框、单一强调色、小圆角和极少阴影。卡片只用于概览和状态摘要，设置主体采用紧凑行式布局。现代化来自清晰层级、稳定反馈和较短操作路径，而不是渐变、玻璃效果或装饰性动效。

本里程碑不依赖实验性的 WPDS API。实现以当前仓库可验证的 React、TypeScript、WordPress Dashicons 和 CSS 为基础；后续替换组件时优先采用稳定的 WordPress 组件能力。

### 2. 信息架构

首个切片建立以下语义分区：

- 概览
- 站点与媒体
- 内容与 SEO
- 安全
- 国内生态
- 维护工具
- 关于与帮助

导航项使用语义化 `view` 值，不再使用 `0`、`1`、`13` 等数字约定。当前功能页面可作为迁移期内容挂载到新分区，但不得新增数字路由。AI 审核不再出现在主导航；Provider Runtime 已在工作包 2 从正式插件清退。

### 3. 概览页职责

概览页只回答：

1. 当前设置与站点诊断是否可用。
2. 有哪些明确、可执行的下一步。
3. 从哪里进入具体设置或维护任务。

诊断接口失败时必须显示不可用状态，禁止使用默认分数伪装真实结果。首次向导、方案市场、收藏拖拽和备份管理不再堆叠在概览首屏；有保留价值的能力在后续迁移为独立任务入口。

### 4. 单一事实源

目标架构只有一份模块 manifest 和一份配置 schema：

- manifest 描述模块标识、所属分区、风险、路由和能力状态。
- schema 描述默认值、校验、敏感字段与 UI 提示。
- 前端类型、搜索索引和配置默认值应由上述事实源生成或验证。
- 删除运行时 `.meta.php` 扫描、手写静态索引和并行 registry 所形成的重复真相。

该目标将在后续工作包完成。本里程碑不得再新增新的手写事实源。

### 5. REST 契约

管理后台请求必须经过统一 REST 客户端：

- 基础地址来自 `window.dataLocal.apiBase`，支持子目录安装和自定义 REST 前缀。
- nonce 使用 `window.dataLocal.restNonce`。
- 统一解析成功、错误和 WordPress REST 错误结构。
- 组件不得硬编码 `/wp-json/mabox/v1`。
- 本里程碑迁移数据库清理、媒体健康、SEO 检查和百度推送等已确认的散落调用。

### 6. 安全边界

- Provider 密钥、OSS 密钥、百度 Token、微信 Secret 和自定义 Header 不应随完整配置注入浏览器。
- 浏览器只获得“是否已配置”和必要掩码；更新接口明确区分保留、替换、清除。
- AI Provider 调用、计费、队列和运行时不属于 Toolbox，后续从本仓删除。
- 设置写入继续要求 `manage_options`、REST nonce、服务端 schema 校验和输出转义。

## 删除、保留与迁移矩阵

| 能力 | 决定 | 本里程碑 |
| --- | --- | --- |
| 语义化导航与 URL | 重建 | 实施 |
| 现代后台外壳 | 重建 | 实施 |
| 简化概览页 | 重建 | 实施 |
| 设置差异与明确保存 | 保留并迁移 | 保留现有能力，后续精简 |
| 统一 REST 客户端 | 保留并收口 | 迁移首批散落调用 |
| 设置功能页 | 保留后逐域迁移 | 先挂载到新外壳 |
| 数字 Tab 与不可达 `13` | 删除 | 实施 |
| Dashboard 默认 60 分 | 删除 | 实施 |
| 收藏拖拽 | 删除 | 已删除面板和 DnD 依赖；设置项收藏暂保留 |
| 首次向导与预设方案 | 删除 | 已删除向导组件和预设实现 |
| Dashboard 内备份中心 | 迁移为独立维护任务 | 本轮不实现 |
| AI 审核 Provider Runtime | 移出产品边界 | 工作包 2 已删除正式运行代码并保存只读参考快照 |
| 多份 manifest/schema/index | 合并 | 后续工作包 |
| 旧配置迁移器与兼容回调 | 删除 | 后续在契约重建时完成 |

## 首个垂直切片验收条件

- 左侧导航与移动导航均可用，采用语义化路由并写入 URL。
- 刷新或直接打开 URL 能恢复当前页面；未知 `view` 安全回到概览。
- 概览页不再包含默认 60 分、不可达路由、收藏拖拽、预设市场或向导编排。
- 概览页对诊断、搜索健康分别提供加载、成功、失败和无数据状态。
- 首批组件不再硬编码 REST 根地址或读取错误的 `dataLocal.nonce`。
- 新增或更新的行为有自动化测试覆盖。
- admin 单测、TypeScript、构建、PHPUnit、PHPStan 和 whitespace 门禁均给出真实结果。

## 后续工作包

1. 建立单一模块 manifest/config schema，并生成前端类型与搜索索引。
2. 按“站点与媒体、内容与 SEO、安全、国内生态、维护工具”逐域迁移设置页面。
3. 删除 Ant Design、Tailwind 和孤立的管理后台组件，稳定到 WordPress 组件体系。
4. 继续收口 OSS、百度、微信等非 AI 模块的浏览器端敏感配置注入。
5. 统一 pnpm 锁文件、CI 命令、版本与功能文档（工作包 3 已完成），再执行发布前人工验收。

## 工作包 2：AI Provider Runtime 清退变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | AI 评论审核 Provider Runtime，以及与其直接相连的配置、REST、前端和发布边界 |
| 预期变更 | 在 `ai/reference/ai-review-runtime/` 保存只读参考快照和移植说明；从正式插件删除外部 Provider、评论审核运行链路、配置字段、REST 路由和前端入口 |
| 明确非目标 | 不实现 Cloud/Addon 接口、不建立兼容类或数据迁移、不修改兄弟仓库、不把参考快照建设成第二个可运行项目 |
| 公共契约 | 删除 `/mabox/v1/ai-review/*`、`ai_review` 设置域和相关前端类型；这是无用户、无兼容负担的 Pre-GA clean break |
| 预期文件 | `ai/reference/ai-review-runtime/**`、`admin/partials/ai_review/**`、autoload/registry/config、`vite/admin/src/components/ai_review/**`、前端类型/API/默认值及相关测试和文档 |
| 不得改变 | 其他站点工具、前台展示、数据库结构、用户未跟踪的排障文档、兄弟仓库 |
| 必需门禁 | 参考快照清单与校验、敏感信息扫描、残留引用扫描、PHP lint、PHPUnit、PHPStan、admin Vitest/TypeScript/lint/build、发布 ZIP 内容检查、`git diff --check` |
| 跨仓矩阵 | 不需要；只删除本仓重复 Provider Runtime，不接入外部运行时 |
| 回滚计划 | 正式代码通过单一清退变更恢复；参考快照保留原始实现和来源路径，不建立运行时回退开关 |

### 工作包 2 结果复核

- `ai/reference/ai-review-runtime/` 保存 5 份边界/设计/移植/安全/清单文档和 10 个核心代码快照；来源路径、GPL-2.0 和 SHA-256 已记录，未发现高置信度真实密钥。
- 正式插件已删除 Provider 接口与 Manager、DeepSeek/阿里云/自定义 API/本地规则实现、评论 Hook、审核日志和 4 个 REST 端点。
- autoload、模块 registry/tiers、配置 Schema/Manager、隐私声明、Option 常量、卸载残留、前端 API/类型/默认值/UI 和当前事实文档已同步清理；没有兼容类、迁移器或隐藏入口。
- 发布 CI 改用明确的 ZIP 排除数组；实际构建检查确认 `ai/`、开发目录和 AI Runtime 标识均不在插件包中。
- 验收快照：PHPUnit 303 项测试、2422 个断言，admin Vitest 68 项测试；PHP lint、PHPStan、TypeScript、ESLint error gate、admin build、docs build、Composer 校验、生产依赖审计、whitespace 和发布包完整性均通过。

## 工作包 3：pnpm 与 CI 可重复基线变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `vite/` 前端工作区的包管理与 GitHub Actions 安装/测试/构建基线 |
| 失败证据 | CI 使用 `pnpm install --frozen-lockfile`，但 `vite/pnpm-lock.yaml` 不存在；同一命令在本机稳定返回 `ERR_PNPM_NO_LOCKFILE` |
| 预期变更 | pnpm 成为唯一包管理器；固定 pnpm 版本；生成一份工作区锁文件；删除三个 npm lockfile；对齐 CI 与开发文档 |
| 明确非目标 | 不修改 WordPress 运行逻辑、不改敏感设置契约、不升级前端依赖大版本、不处理 Ant Design/Tailwind 或 lint warning |
| 公共契约 | 不改变产品 REST/UI 契约；只改变开发安装、测试和构建命令 |
| 预期文件 | `vite/package.json`、`vite/pnpm-lock.yaml`、`vite/*/package-lock.json`、`.github/workflows/ci.yml`、开发命令文档 |
| 不得改变 | PHP/React 业务源码、构建产物内容、用户未跟踪的排障文档、`ai/reference/**`、兄弟仓库 |
| 必需门禁 | `pnpm install --frozen-lockfile`、admin Vitest、三项目 TypeScript、`pnpm -r build`、Composer 门禁、ZIP 排除检查、`git diff --check` |
| 跨仓矩阵 | 不需要；仅本仓前端工作区工具链 |
| 回滚计划 | 回滚本工作包即可恢复旧安装方式；不保留双 lockfile 或双包管理器兼容层 |

### 工作包 3 结果复核

- `vite/package.json` 将包管理器固定为 `pnpm@10.33.0`，`vite/pnpm-lock.yaml` 成为覆盖 admin/count/public 的唯一工作区锁文件；三份 npm lockfile 已删除。
- GitHub Actions 从 `vite/package.json` 读取 pnpm 版本，并统一在 `vite/` 根目录执行 frozen install；Dependabot 同样收敛到 `/vite`。
- README 和当前构建指南已改为 pnpm workspace 命令；发布 ZIP 明确排除工作区 package、lock 和 workspace 配置文件。
- 独立干净目录中的 `pnpm install --frozen-lockfile` 通过；Node 18.20.8 下的 frozen lockfile 检查通过。
- admin Vitest 11 个文件、68 项测试，三项目 TypeScript、三项目 Vite build、PHPUnit 303 项测试/2422 个断言、PHPStan、Composer 校验、YAML 解析、ZIP 内容检查和 `git diff --check` 均通过。
- ESLint 为 0 error，仍有 admin 167 个、count 4 个历史 warning；admin/count 大 chunk warning 保留为后续性能工作。npm registry audit 端点返回 HTTP 410，本轮没有形成新的依赖审计结论。

## 结果复核

首个垂直切片已完成独立代码审查和浏览器烟测，改进假说成立：

- 数字 Tab 已由 8 个语义化 `view` 取代，刷新、返回和未知路由回退行为可用。
- Dashboard 删除了预设市场、拖拽收藏、向导、备份编排、默认 60 分和不可达 `13`。
- 数据库清理、媒体健康、SEO 检查和百度推送不再硬编码 REST 根地址或读取错误 nonce。
- 数据库清理回调改为直接读取 `WP_REST_Request` JSON；百度批推从前后端共同阻止游标停滞。
- “补全 Alt”改为写入 WordPress 的 `_wp_attachment_image_alt`，不再误写附件说明。
- 功能搜索使用可聚焦按钮，并能通过语义路由定位维护工具中的具体设置。
- 已删除孤立向导、预设、拖拽收藏面板和三个 DnD 包。

验收快照：admin Vitest 11 个文件、68 项测试通过；TypeScript、Vite build、PHP 语法、PHPUnit 304 项测试、PHPStan、Composer 校验和 `git diff --check` 通过；ESLint 0 error，仍有 179 个历史 warning。生产依赖 `npm audit --omit=dev` 为 0，开发依赖仍有需要升级工具链才能消除的 advisory。

浏览器烟测使用 Vite 默认数据完成，验证了桌面/移动结构、语义路由、浏览器返回、未知路由、搜索定位和接口失败状态。由于本机没有运行对应 WordPress 实例，真实 WordPress 管理栏、字体和服务端成功响应仍需在后续人工验收中确认。

整个 Pre-GA Reset 尚未完成；AI Provider Runtime 清退已由工作包 2 收口，pnpm/CI 可重复基线已由工作包 3 收口。其余发布阻断项仍包括非 AI 模块的浏览器端敏感配置注入、重复 manifest/schema，以及 Ant Design/Tailwind 和大 chunk 带来的前端维护与性能成本。
