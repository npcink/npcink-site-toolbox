# ADR: Pre-GA 管理后台清场式重构

- 状态：已接受；工作包 1-7 已验收，工作包 8A 已完成自动化与 Local 验收
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

当前管理后台收口为以下七个语义分区：

- 概览
- 站点与媒体
- 内容与页面
- SEO 与增强
- 国内生态（含登录与评论安全）
- 维护工具
- 关于与帮助

导航项使用语义化 `view` 值，不再使用 `0`、`1`、`13` 等数字约定。当前功能页面可作为迁移期内容挂载到新分区，但不得新增数字路由。AI 审核不再出现在主导航；Provider Runtime 已在工作包 2 从正式插件清退。工作包 6 又删除了只承载不可信验证码的重复“登录与安全”入口，真实登录安全设置统一归入国内生态。

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

1. 完成工作包 7 的自动化门禁、紧急恢复和真实 Local 登录烟测。
2. 完成 `category_link_simplify` 生命周期修复后，继续狭窄修复 `ban_auto_size` 缺少过滤返回值的已确认行为债务。
3. 建立单一模块 manifest/config schema，并生成或校验前端类型与搜索索引，继续减少手写重复真相。
4. 逐步缩减 Ant Design、Tailwind 和大体积共享 chunk，稳定到更轻的 WordPress 管理界面组件边界。
5. 完成发布前人工验收、风险功能恢复路径验证和打包检查。

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

## 工作包 5：运行时模块契约变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | 59 个注册模块、模块 Loader 与 Registry 的内部运行时契约 |
| 失败证据 | 56 个注册类未实现接口；10 个入口要求必传参数，而 Loader 在没有 `config_path` 时零参调用；Loader 对非接口模块只告警仍继续，并保留无实际消费者的 `runs()` 兼容分支 |
| 预期变更 | 所有注册类统一实现 `MaBox_Module_Interface`；唯一入口为 `public static function run($config = array())`；Loader 始终传数组、配置缺失时传空数组、违约时拒绝加载 |
| 明确非目标 | 不顺带重写模块业务行为，不修改管理界面、REST/敏感设置契约、数据库结构或兄弟仓库，不保留零参、标量参数或 `runs()` 双轨兼容 |
| 公共契约 | 仅收口插件内部模块初始化契约；9 个需要关联设置的模块通过 Registry `config_path` 接收对应子树，`hide_email_ip` 保持无配置依赖 |
| 预期文件 | `admin/modules/{loader,registry}.php`、56 个未合规模块文件、`includes/autoload.php`、全局契约与 Loader 回归测试、本 ADR |
| 不得改变 | 前端源码与构建产物、工作包 4 设置契约、AI 参考快照、用户未跟踪排障文档、兄弟仓库 |
| 必需门禁 | 注册表反射契约、Loader 配置路由/拒绝违约测试、PHP lint、`composer test`、`composer phpstan`、自动加载隔离验证、Local 前后台请求与新增日志检查、`git diff --check` |
| 跨仓矩阵 | 不需要；契约及全部消费者都在本仓库 |
| 回滚计划 | 以一个聚焦提交交付；回滚该提交即恢复旧契约，不增加 feature flag 或长期兼容层 |

### 工作包 5 结果复核

- 59/59 个注册模块现在都显式实现 `MaBox_Module_Interface`，并统一为一个 public static `run($config = array())` 入口；反射门禁逐项锁定类、接口、方法可见性、static、参数数量/名称/默认值，并禁止 `runs()` 复活。
- Loader 不再零参调用模块，也不再尝试 `runs()`；无 `config_path`、路径缺失或结果非数组时统一传空数组。未实现接口的类会记录 error 并立即返回，不再“告警后继续”。
- 原 10 个参数数量风险已消除：维护提示、三个隐藏内容模块、恶意搜索、三个统计验证和登录验证码按对应配置子树取值；隐藏邮件 IP 不依赖配置但接受统一数组参数。
- `MaBox_Module_Interface` 已纳入项目自动加载映射；隔离 PHP 进程直接自动加载模块类时，接口也能按契约解析，不再依赖 Loader 必须先被触发的隐式顺序。
- 自动化门禁：PHPUnit 309 项测试/3170 个断言，PHPStan 0 error，全部变更 PHP 文件语法检查、自动加载隔离验证和 `git diff --check` 通过。
- Local 站点通过符号链接直接挂载当前仓库。新触发前台、未登录后台和受保护设置 REST 请求分别返回 HTTP 200/302/401，符合当前访问边界；PHP error 与 PHP-FPM 日志均无新增字节，没有新的 fatal、参数错误或模块接口告警。
- 本工作包未混入模块业务重写。复核时另记录三项既有行为债务：`category_link_simplify` 在运行时才注册激活/停用 Hook，大概率错过 WordPress 生命周期；`ban_auto_size` 的尺寸过滤回调没有返回 `$sizes`；`login.login_verify` 被标记为 `admin` scope，但 Local 当前 WordPress 核心的 `is_admin()` 在没有 `current_screen` 或 `WP_ADMIN` 时返回 false，`wp-login.php` 也未定义 `WP_ADMIN`，因此模块可能不会在真实登录页加载；其数学与随机验证又都信任客户端隐藏字段中回传的挑战值，且随机字符串在校验时被强制转为整数，不能作为可信的登录保护。下一工作包应优先删除或重做该登录验证，而不是只改 scope；另两项再作狭窄的功能正确性处理，都不回流到本次机械契约变更。

## 工作包 6：不可信登录验证码清退变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | 旧 `login.login_verify` 运行时、空顶层 `login` 设置域，以及对应的管理界面、诊断和当前文档 |
| 失败证据 | 模块不会可靠进入 `wp-login.php` 请求；数学挑战由客户端回传参与校验；随机挑战还会被强制转为整数，均不能构成可信的登录保护 |
| 预期变更 | 删除验证码实现与注册；删除已无其他字段的 `login` Option/Schema/前端数据域；删除重复“登录与安全”主导航、搜索项和失真诊断；登录防护设置只保留现有 `domestic.login_security` 表面 |
| 明确非目标 | 不在本包重写国内登录安全实现，不修复分类链接或图片尺寸两项独立债务，不引入第三方验证码、迁移器、兼容路由、功能开关或兄弟仓库改动 |
| 公共契约 | 模块数从 59 收口为 58；设置 GET/POST 不再包含顶层 `login`；`security` 不再是合法 Admin view，旧查询值按未知 view 回到概览 |
| 预期文件 | Registry/Tier/Autoload、验证码实现、Config Schema/Manager/常量、Diagnostics、React 设置壳/概览/搜索/类型、测试、README/VitePress/功能清单、本 ADR |
| 不得改变 | `domestic.login_security` 字段及运行时、其他工具模块、构建产物、AI 参考快照、用户未跟踪排障文档、兄弟仓库 |
| 必需门禁 | PHPUnit/PHPStan/PHP lint、admin Vitest/TypeScript/lint/build、VitePress build、旧能力残留扫描、`git diff --check`、Local 登录页/后台设置页/新增日志检查 |
| 跨仓矩阵 | 不需要；被删除的设置和运行时全部属于本插件 |
| 回滚计划 | 以一个聚焦提交交付；回滚该提交即可恢复旧实现，不保留双轨兼容 |

### 工作包 6 结果复核

- `login.login_verify` 已从 Registry、Tier、Autoload 和实现文件中删除；注册模块从 59 个收口为 58 个。已无其他字段的顶层 `login` Schema、Option 常量、Config Manager 映射、卸载认知和前端 Option/default/type 同步删除，设置域从 6 个收口为 5 个。
- Diagnostics 不再为验证码加分，也不再输出“可有效防御暴力破解”的健康项、推荐或自动修复。负向测试锁定旧配置不会影响评分、不会重新进入诊断或被浏览器设置契约接受。
- 管理后台当前为 7 个语义 `view`；旧 `view=security` 按未知路由回到概览。概览只按后端真实的 `fail_limit_enabled` 模块加载门槛展示登录保护，安全面板会直接聚焦国内生态的登录安全配置。
- 独立审查发现 `domestic.login_security` 是一个复合模块，但 Registry 只以“账号失败限制”作为统一加载门槛；仅开启用户名枚举等其他开关时运行时不会加载。本包未越界重写该高风险运行时，而是删除 Dashboard 假阳性，并在当前功能清单和 VitePress 中明确依赖关系；拆分激活边界列为下一工作包首项。
- README、功能清单和 VitePress 已删除验证码能力、页面与链接；明确标为历史快照的旧文档保留原始事实，并继续以本 ADR、代码、Schema 和测试作为当前权威。
- 自动化门禁：PHPUnit 309 项测试/3136 个断言，PHPStan 0 error，变更 PHP 语法检查、admin Vitest 14 个文件/90 项测试、TypeScript、严格 ESLint、Vite build、VitePress build、Composer 校验、残留扫描和 `git diff --check` 均通过。
- Local 管理员登录态中，概览与国内生态正常加载，旧安全路由回到概览，`wp-login.php` 只保留 WordPress 原生登录表单，浏览器 console 无 error/warning。开发数据库中的旧 `Magick_ToolBox_Option_Login` 已删除，浏览器配置只剩 5 个当前域；新触发前台、登录页和未登录后台请求分别返回 200/200/302，PHP、PHP-FPM 和 Nginx 错误日志均无增量。
- PHP/安全契约与前端/产品表面分别完成独立复审；审查发现的 Dashboard 假阳性、深链未聚焦、ADR/功能统计漂移均已修正，最终结论均为 Approve，无剩余 P0–P2。

## 工作包 7：登录安全运行时收口变更信封

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `domestic.login_security` 运行时、设置 Schema、管理界面、搜索/概览和当前事实文档 |
| 失败证据 | 旧复合模块只以 `fail_limit_enabled` 激活；锁定过滤器晚于 WordPress 密码校验；锁定请求会延长自身 TTL；可信代理不可配置；自定义登录地址、登录日志和后台 IP 白名单分别存在锁死、写放大和错误保护边界 |
| 预期变更 | 以 clean break 收口为“登录尝试保护”和“限制匿名作者枚举”两项能力；独立激活；使用固定窗口、可验证代理链和紧急恢复边界；同步删除失效字段与产品表面 |
| 保留字段 | `attempt_limit_enabled`、`attempt_limit_count`、`attempt_window_minutes`、`lock_duration_minutes`、`trusted_proxies`、`anonymous_author_guard_enabled` |
| 删除能力 | 自定义登录地址、独立 IP 锁定、登录通知、登录日志、后台 IP 白名单，以及对应字段、搜索项、UI 和当前文档 |
| 明确非目标 | 不实现验证码、双因素认证、纯账号全局锁、纯 IP 全局锁、登录审计系统、WAF/防火墙、兼容映射、迁移器、Cloud/Addon 能力或兄弟仓库改动 |
| 公共契约 | `domestic.login_security` 只接受六个保留字段；登录尝试保护与匿名作者枚举限制可独立启用；`MABOX_DISABLE_LOGIN_PROTECTION` 只紧急绕过登录尝试保护 |
| 预期文件 | 登录安全运行时、Registry/Metadata、Config Schema、React 默认值/类型/UI/搜索/概览、PHP/前端测试、README、功能清单、VitePress 与本 ADR |
| 不得改变 | WordPress 原生登录 URL、其他国内生态模块、其他工具模块、AI 参考快照、历史实施报告、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | 登录行为 PHPUnit、Registry/Schema 契约、PHPStan/PHP lint、admin Vitest/TypeScript/lint/build、VitePress build、链接与旧能力残留扫描、`git diff --check`、Local 登录/恢复/REST/日志烟测 |
| 跨仓矩阵 | 不需要；设置、运行时和全部消费者均在本仓库 |
| 回滚计划 | 以一个聚焦提交交付；回滚该提交恢复工作包 6 基线，不保留旧字段兼容、双轨运行或隐藏 UI |

### 工作包 7 决策

1. 登录尝试保护只按“已存在的 WordPress user ID + 已解析客户端 IP”组合计数，不建立可被远程滥用的纯账号全局锁，也不让共享出口 IP 形成纯 IP 全局锁。
2. 失败计数使用固定统计窗口；达到阈值后使用独立、固定时长的锁定状态。由模块自身返回的锁定错误不得再次累计或延长锁定，成功登录清除对应组合状态。
3. `trusted_proxies` 每行只接受一个精确 IPv4 或 IPv6 地址，不接受 CIDR、域名或通配符。`REMOTE_ADDR` 未命中可信列表时完全忽略 `X-Forwarded-For`；命中时验证完整转发链，并从右向左跳过可信跳点，取第一个非可信 IP。链无效或没有可识别客户端时跳过本次执法，不使用共享哨兵 IP。
4. 匿名作者枚举限制只收紧匿名数字作者查询和匿名 REST 用户读取；具备相应权限的编辑器、管理员和 REST 客户端继续使用 WordPress 原生作者工作流。
5. `MABOX_DISABLE_LOGIN_PROTECTION` 是紧急恢复常量，不是长期功能开关，也不关闭匿名作者枚举限制。日常配置仍以本地设置 Option 为唯一真相。
6. 自定义登录地址、独立 IP 锁定、登录通知、登录日志和后台 IP 白名单不迁移、不隐藏、不移往 Cloud；其代码和产品表面直接删除。

### 工作包 7 验收清单

- [x] 两项保留能力可分别独立激活；全部关闭时模块不注册登录安全 Hook。
- [x] 同一用户使用用户名或邮箱登录时归一到同一 user ID；不同来源 IP 不形成账号全局锁。
- [x] 固定窗口、锁定到期、成功清理和自身锁定错误不续期均有行为测试。
- [x] 非可信来源不能用 XFF 覆盖 `REMOTE_ADDR`；可信多跳代理、伪造左侧地址、IPv4/IPv6、非法链和无法解析场景均有测试。
- [x] 匿名作者查询受限，同时已授权编辑器的 REST 用户读取和作者选择正常。
- [x] 紧急常量和 WP-CLI 恢复步骤在真实 Local 站点验证，恢复后常量可安全移除。
- [x] 删除字段不能从 Schema、设置 GET/POST、浏览器默认值、搜索、概览、文档或构建产物重新进入产品。
- [x] 自动化门禁、VitePress 构建、链接扫描、残留扫描、Local 登录页/后台/REST 和新增日志检查给出真实结果。

### 工作包 7 结果复核

- `domestic.login_security` 已 clean break 收口为六字段、两能力契约；Loader 的 `activation_paths` 使用显式 ANY-OF 语义，非法或自相矛盾的声明失败关闭。旧自定义登录地址、独立 IP 锁、登录通知、登录日志和后台 IP 白名单已从当前运行时、Schema、设置 UI、搜索、概览和文档删除。
- 登录尝试保护在 WordPress 密码哈希前检查锁定，按 user ID 与已解析客户端 IP 的组合使用固定统计窗口和独立固定锁定时长。用户名与邮箱归一、来源隔离、未知账号与无法解析 IP 跳过、成功清理、锁定错误不续期和到期恢复均有行为测试。
- 可信代理列表在服务端保存边界逐行校验并规范化精确 IPv4/IPv6；CIDR、域名、通配符或任一非法行会让整串配置拒绝写入。运行时同样不会让混合非法列表部分生效，并覆盖可信多跳、伪造左侧 XFF、IPv4/IPv6、非法链和无法解析场景。
- 匿名作者保护已对齐 WordPress 对 `author` 参数的清洗语义，`author=1`、`author=x1` 和 `author=+1` 均不能绕过；匿名 `/wp/v2/users*` 被收紧，已登录管理员的原生 REST 用户读取仍返回 200。紧急常量只绕过登录尝试保护，不影响匿名作者保护。
- 独立复审在提交前发现并修正了非法代理“保存成功但运行时忽略”、混合作者参数绕过、`risk.level=none` 空白风险弹窗、Schema 首次加载失败时确认降级、整数参数界面与运行时漂移，以及概览与恢复文案漂移，并为各路径补回归测试。
- 根代理复跑结果：PHPUnit 330 项测试、3323 个断言通过，PHPStan 0 error，全部 PHP 语法检查通过；Admin Vitest 15 个文件、100 项测试通过，TypeScript 与 Vite build 通过，ESLint 0 error、137 个既有 warning；VitePress build 与 `git diff --check` 通过。生产构建仍有约 1.26 MB 的 `tab-dashboard.js` 大块，属于后续前端拆包债务。
- 真实 Local 站点先验证非法代理列表被 REST 保存边界拒绝且数据库零写入，再保存两项能力。临时订阅者连续两次错误密码后，正确密码请求被锁定提示阻止；加入 `MABOX_DISABLE_LOGIN_PROTECTION=true` 后正确密码恢复为 302，同时匿名作者查询仍为 302、匿名 REST 用户端点仍为 404。按文档执行 WP-CLI Option patch 与 cache flush 后移除常量，正确密码继续恢复为 302。
- Local 最终状态已清理：临时用户、测试 Cookie、紧急常量和登录 transient 均不存在；登录安全配置保留六个当前字段并恢复为两开关关闭、`5/15/30` 默认数值和空可信代理，激活模块中不再包含 `domestic.login_security`。PHP 日志只新增两条预期配置更新 INFO，PHP-FPM 与 Nginx 错误日志无增量。
- 已知剩余风险：失败计数仍使用 WordPress transient 的读、加、写序列，极端并发请求可能丢失增量。数据库 transient 与外部对象缓存没有统一 CAS 契约，本包没有用仅覆盖部分后端的 `wp_cache_incr` 或临时 Option 锁伪装成原子实现；如发布定位要求强限流保证，应另立存储与原子后端工作包。

## 工作包 8A：分类链接简化生命周期修复

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `optimize.site.category_link_simplify` 的插件激活、停用和设置更新生命周期 |
| 失败证据 | 旧实现只在前台模块 `run()` 时注册以模块文件为入口的激活/停用 Hook，无法可靠收到主插件生命周期事件；停用时又使用了错误的字符串回调，且没有恢复 WordPress Core 分类 permastruct |
| 预期变更 | 在主插件文件顶层注册生命周期 Hook；启用时按配置应用并刷新规则；停用时移除精确回调、恢复 Core 分类结构并刷新；Optimize Option 只在目标布尔值实际双向变化后刷新 |
| 明确非目标 | 不修改分类 URL 算法、设置 Schema、其他模块、前端、数据库结构或兄弟仓库 |
| 公共契约 | `Magick_ToolBox_Option_Optimize.site.category_link_simplify` 保持布尔字段；只修正该字段与 WordPress rewrite 生命周期的同步语义 |
| 预期文件 | `magick-tool-box.php`、`admin/partials/optimize/site/category_link_simplify.php`、聚焦 PHPUnit 回归测试与本 ADR |
| 不得改变 | 其他 URL/Rewrite 模块、前端构建产物、用户未跟踪排障文档、兄弟仓库 |
| 必需门禁 | 聚焦 PHPUnit、`composer test`、PHPStan、变更 PHP 语法检查、`git diff --check` |
| 跨仓矩阵 | 不需要；生命周期与设置 Option 均由本插件拥有 |
| 回滚计划 | 回滚本工作包局部变更即可恢复旧行为；不新增迁移器、兼容回调或双轨状态 |

### 工作包 8A 决策与结果

1. `register_activation_hook()` 与 `register_deactivation_hook()` 由 `magick-tool-box.php` 在顶层注册，回调统一指向分类链接模块；模块 `run()` 只负责请求期 Hook，不再伪装插件生命周期入口。
2. 插件激活只在已保存值严格为布尔 `true` 时注册自定义回调、应用 `%category%` permastruct 并刷新规则；关闭、缺失或非布尔值均不产生无意义刷新。
3. 停用或设置从 `true` 转为 `false` 时，以完整的静态类回调移除该模块注册的 actions/filters，再调用 Core `category` taxonomy 的 `add_rewrite_rules()` 恢复原生 permastruct，最后刷新规则。
4. 监听 WordPress 成功更新后的动态 `update_option_Magick_ToolBox_Option_Optimize` action；仅当目标字段在严格布尔 `false` 与 `true` 间转换时处理，其他 Optimize 字段变化、同值保存和非布尔漂移均不刷新。
5. 新增 6 项聚焦回归测试、28 个断言，覆盖主入口注册、激活开关、精确停用清理、Core 恢复、双向设置转换和无转换不刷新。聚焦测试通过；全量 PHPUnit 336 项测试/3351 个断言通过，PHPStan 0 error，3 个变更 PHP 文件语法检查通过。
6. Local WordPress 7.0.1 四阶段烟测通过：设置开启后分类链接由 `/category/uncategorized/` 变为 `/uncategorized/` 且直接规则存在；停用插件后恢复 Core 链接并删除直接规则；保留开启配置重新激活后再次应用简化规则；设置关闭后再次恢复 Core。烟测清理脚本末尾因 zsh 保留的只读变量名 `status` 中止自动恢复，随后已显式重建激活模块表并刷新缓存；最终确认插件启用、该设置为 `false`、激活模块仅 `optimize.widgets`，PHP、PHP-FPM 与 Nginx 错误日志均无增量。

## 结果复核

首个垂直切片已完成独立代码审查和浏览器烟测，改进假说成立：

- 数字 Tab 最初由 8 个语义化 `view` 取代；工作包 6 合并重复安全入口后，当前 7 个 `view` 的刷新、返回和未知路由回退行为可用。
- Dashboard 删除了预设市场、拖拽收藏、向导、备份编排、默认 60 分和不可达 `13`。
- 数据库清理、媒体健康、SEO 检查和百度推送不再硬编码 REST 根地址或读取错误 nonce。
- 数据库清理回调改为直接读取 `WP_REST_Request` JSON；百度批推从前后端共同阻止游标停滞。
- “补全 Alt”改为写入 WordPress 的 `_wp_attachment_image_alt`，不再误写附件说明。
- 功能搜索使用可聚焦按钮，并能通过语义路由定位维护工具中的具体设置。
- 已删除孤立向导、预设、拖拽收藏面板和三个 DnD 包。

验收快照：admin Vitest 11 个文件、68 项测试通过；TypeScript、Vite build、PHP 语法、PHPUnit 304 项测试、PHPStan、Composer 校验和 `git diff --check` 通过；ESLint 0 error，仍有 179 个历史 warning。生产依赖 `npm audit --omit=dev` 为 0，开发依赖仍有需要升级工具链才能消除的 advisory。

浏览器烟测先使用 Vite 默认数据验证桌面/移动结构、语义路由、浏览器返回、未知路由、搜索定位和接口失败状态；工作包 4 又在真实 Local WordPress 管理员登录态中验证了 WordPress 管理栏、服务端成功响应和敏感设置闭环。

整个 Pre-GA Reset 尚未完成；AI Provider Runtime 清退已由工作包 2 收口，pnpm/CI 可重复基线已由工作包 3 收口，敏感设置契约已由工作包 4 收口，注册模块与 Loader 的运行时契约已由工作包 5 收口，不可信登录验证码已由工作包 6 清退。工作包 7 已冻结登录安全的最小最终契约，工作包 8A 已修复分类链接简化的生命周期债务。其余主要问题是 `ban_auto_size` 已确认行为债务、重复 manifest/schema，以及 Ant Design/Tailwind 和大 chunk 带来的前端维护与性能成本。
