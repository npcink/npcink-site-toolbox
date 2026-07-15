# ADR: Pre-GA 管理后台清场式重构

- 状态：已接受，工作包 1-4 已验收（含真实 Local 站点烟测）
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

## 工作包 4：敏感设置契约变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | WordPress 设置读取/保存契约，以及直接承载凭据的管理端字段 |
| 失败证据 | 管理页注入、`GET /settings`、React 状态、diff 和 `localStorage` 快照均可获得已保存凭据；百度 Token 还会通过 HTTP 查询串发送 |
| 预期变更 | GET 只返回非敏感设置和 `secretStatus`；保存以 `settings + secretChanges` 表达保留/替换/清除；删除浏览器快照、设置导入导出和旧迁移备份；清退失效腾讯集成与不安全百度推送 |
| 保留凭据 | `domestic.wechat.appsecret`、`performance.oss.access_key`、`performance.oss.secret_key` |
| 清退能力 | `page.anti_crawler` 腾讯防刷整条链路；登录验证码中的腾讯分支；`domestic.baidu_push` 及其 REST/Hook/UI/文档 |
| 明确非目标 | 不修改数学/随机登录验证码、不重写微信或 OSS 运行时、不建设外部密钥服务、不修改兄弟仓库、不升级前端组件库 |
| 公共契约 | `GET /mabox/v1/settings -> { success, data, secretStatus }`；`POST /mabox/v1/settings -> { settings, secretChanges }`；secret operation 仅允许 `replace`/`clear`，缺省表示保留 |
| 安全规则 | 页面注入、GET、diff、日志、快照、导出和错误信息不得出现已保存原值；读取失败禁用保存；未知路径、未知操作和空替换必须拒绝 |
| 预期文件 | 配置 Schema/Manager、Admin REST、模块 registry/autoload、凭据运行模块、React DataContext/Save/SecretField/表单/API/测试、当前事实文档 |
| 不得改变 | 其他站点工具、用户未跟踪排障文档、`ai/reference/**`、pnpm/CI 基线、兄弟仓库 |
| 必需门禁 | canary 泄露测试、保留/替换/清除/回滚测试、REST 权限与参数测试、admin Vitest/TypeScript/lint/build、PHPUnit/PHPStan、残留扫描、`git diff --check` |
| 跨仓矩阵 | 不需要；设置最终真相和 WordPress 权限都在本插件内 |
| 回滚计划 | 本工作包保持为独立未提交变更；验收后单独提交，回滚该提交即可恢复旧设置契约，不建立兼容写入口 |

### 工作包 4 结果复核

- `window.dataLocal` 不再注入配置或默认值；`GET /mabox/v1/settings` 通过服务端 Schema 补齐完整非敏感设置，只额外返回三条凭据的 `configured` 状态。新安装不会因空配置树进入可编辑状态后崩溃。
- `POST /mabox/v1/settings` 只接受 `{ settings, secretChanges }`。凭据路径从 Schema 的 `sensitive` 标记派生；缺省表示保留，操作只允许 `replace` 或 `clear`，未知路径/操作、空白值、控制字符、超长值和普通设置夹带凭据均被拒绝。
- Config Manager 将“同值写入返回 false”视为成功；实际跨模块写入失败时回滚本次已更新模块，并覆盖了首次创建 Option 后失败的删除回滚。
- React 只在设置 GET 成功且响应不含敏感键时进入可编辑状态；失败、畸形响应或意外凭据回显都会禁用保存。通用 `SecretField` 提供已配置状态、替换、清除和撤销，diff 只显示状态词。
- 删除浏览器配置快照、设置导入导出、旧迁移/备份/rollback、预设向导遗留端点，以及百度推送、整套 anti-crawler 和登录腾讯验证码链路。微信与 OSS 运行时保留，原值只在服务端读取。
- 写入端进一步要求完整、无未知字段且 JSON 类型正确的非敏感设置树；空对象、部分成功响应、缺字段、未知字段或错类型均不得进入可编辑/可保存状态，避免默认值补齐演变成静默重置。国内环境修复也只加入统一编辑态，不再绕过全局 diff 直接写入。
- 主代理组合门禁：PHPUnit 301 项测试/2502 个断言，PHPStan 0 error，全仓 PHP lint；admin Vitest 14 个文件/86 项测试、TypeScript、ESLint 0 error、Vite build；VitePress build、残留扫描和 `git diff --check` 全部通过。数组字段的元素契约同样由 PHP Schema 强制执行，不能绕过前端提交畸形列表。
- 真实 Local 站点登录态烟测已完成；admin 主 chunk 约 1.255 MB、145 个历史 ESLint warning 继续作为后续工作，不在本安全契约中混改。

### 工作包 4 真实 Local 站点复验

- 在 `http://magick-toolbox.local/wp-admin/` 的真实管理员登录态中，设置页、语义导航和维护工具正常加载；浏览器 console 没有 error 或 warning，设置加载没有进入错误态。
- `GET /mabox/v1/settings` 返回 HTTP 200，顶层精确为 `success`、`data`、`secretStatus`。三个敏感路径只返回 `configured`，完整非敏感设置中没有 `appsecret`、`access_key` 或 `secret_key`。
- 使用一次性本地 canary 完成微信 AppSecret 和 OSS 双密钥的替换、保留、清除闭环。POST 顶层只包含 `settings` 与 `secretChanges`；替换操作包含 `operation: replace`，清除只包含 `operation: clear`；diff 只显示状态变化，不显示值。
- 保留路径没有产生设置 POST；清除后回读三个 `configured` 均为 `false`，对象存储开关恢复为关闭。数据库扫描确认没有残留 `codex-local-` canary。
- 首页请求返回 HTTP 200，未登录后台请求按 WordPress 规则返回 HTTP 302；修复 `MaBox_Widgets` 与 `MaBox_Performance_Oss` 的接口声明和 `run($config = array())` 签名后，新的真实请求没有追加 PHP fatal 或模块接口 warning。
- 本轮没有使用真实微信或 OSS 凭据，也没有向外部 Provider 发起上传/JSSDK 鉴权请求；验证范围是 WordPress 设置契约、模块加载和禁用态安全短路。
- 注册表静态复核另发现：59 个注册模块中仍有 56 个未实现 `MaBox_Module_Interface`，其中 10 个模块的必传参数与 loader 调用方式不匹配，启用后可能触发 `ArgumentCountError`。这是独立的运行时模块契约工作包，不在本安全设置提交中机械修改 56 个模块。

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

浏览器烟测先使用 Vite 默认数据验证桌面/移动结构、语义路由、浏览器返回、未知路由、搜索定位和接口失败状态；工作包 4 又在真实 Local WordPress 管理员登录态中验证了 WordPress 管理栏、服务端成功响应和敏感设置闭环。

整个 Pre-GA Reset 尚未完成；AI Provider Runtime 清退已由工作包 2 收口，pnpm/CI 可重复基线已由工作包 3 收口，敏感设置契约已由工作包 4 收口。其余发布阻断项包括注册模块与 loader 的运行时契约不一致、重复 manifest/schema，以及 Ant Design/Tailwind 和大 chunk 带来的前端维护与性能成本。
