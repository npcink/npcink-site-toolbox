# ADR: Pre-GA 管理后台清场式重构

- 状态：已接受；工作包 1-7 已验收，工作包 8A-8B、9A-9C、10A-12 已完成自动化与 Local 验收
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

该目标已由工作包 13 完成；后续不得再新增新的手写事实源。

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
| 多份 manifest/schema/index | 合并 | 工作包 13 已完成 |
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
2. 工作包 8A-8B 狭窄收口 `category_link_simplify` 生命周期和 `ban_auto_size` 过滤器返回契约，不在其上扩大功能范围。
3. 工作包 13 建立单一模块 manifest/config schema，并生成前端类型与搜索索引，已完成。
4. 工作包 14A 将导航、搜索、状态、通知和保存外壳从 Ant Design 解耦，复杂表单继续按需复用，已完成。
5. 工作包 14B 完成版本事实、真实发布 ZIP、远端 CI 与正式发布人工验收。

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

## 工作包 8B：自动图片尺寸过滤器返回契约修复

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `optimize.medium.ban_auto_size` 的 `intermediate_image_sizes_advanced` 过滤器契约 |
| 失败证据 | 旧实现把 filter hook 以 action 注册，回调删除六个尺寸后没有返回 `$sizes`，使调用方得到 `null`，并可能破坏其他主题或插件注册的自定义尺寸 |
| 预期变更 | 以 filter 注册现有回调；删除六个既定 Core 尺寸后返回剩余数组，保留未知和自定义尺寸 |
| 明确非目标 | 不修改模块 scope、设置 Schema、风险文案、其他 `remove_image_size()` 行为、前端或其他媒体模块，也不扩大为删除全部图片尺寸 |
| 公共契约 | `shapeSpace_disable_image_sizes($sizes)` 接受并返回尺寸关联数组，只移除 `thumbnail`、`medium`、`large`、`medium_large`、`1536x1536`、`2048x2048` |
| 预期文件 | `admin/partials/optimize/medium/ban_auto_size.php`、聚焦 PHPUnit 回归测试与本 ADR |
| 不得改变 | WP8A、其他媒体行为、前端构建产物、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | 聚焦 PHPUnit、`composer test`、PHPStan、变更 PHP 语法检查、`git diff --check` |
| 跨仓矩阵 | 不需要；过滤器实现和测试均由本插件拥有 |
| 回滚计划 | 回滚本工作包局部变更即可恢复旧行为，不新增兼容入口或并行实现 |

### 工作包 8B 结果复核

1. `intermediate_image_sizes_advanced` 改为通过 `add_filter()` 注册；`big_image_size_threshold` 和 `init` 上的既有行为保持不变。
2. `shapeSpace_disable_image_sizes()` 只 unset 六个既定 Core 键并明确返回剩余 `$sizes`；主题或插件添加的未知、自定义尺寸及其原始配置保持不变。
3. 新增 2 项行为回归测试、3 个断言，旧实现会分别因错误 hook 类型和 `null` 返回失败。聚焦测试通过；全量 PHPUnit 338 项测试/3354 个断言通过，PHPStan 0 error，2 个变更 PHP 文件语法检查通过。
4. Local WordPress 7.0.1 真实过滤链验收通过：临时开启模块后，以预加载 `WP_ADMIN=true` 的普通 WordPress 引导确认目标回调已注册，过滤结果仍为数组，六个 Core 尺寸被删除，`theme-hero` 自定义尺寸及配置原样保留，既有大图阈值过滤仍返回 `false`。WP-CLI 2.12.0 的 `--context=admin` 会在加载 Core 后台菜单时因缺少全局菜单数组触发 `uksort(null)`，故未将该工具基线错误误判为插件故障。最终已恢复 `no_auto_size=false`、激活模块仅 `optimize.widgets` 且插件保持启用，PHP、PHP-FPM 与 Nginx 错误日志无增量。

## 工作包 9A：WordPress 后台嵌入隔离

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `vite/admin` 的 CSS 选择器与浏览器事件边界 |
| 失败证据 | Tailwind preflight 会重置整个 WordPress 后台文档，utilities 又会向生产 CSS 输出 `.fixed`、`.table`、`.border`、`.filter` 等裸全局类；`App.css` 另有裸 `a`/`h2`、`.ant-btn-primary`、`.ant-form-item` 和 `.menu-header`；PHP 还通过 `wp_add_inline_style()` 注入裸 `#root`/`.ant-*` 响应式旁路，入口加载 `default-passive-events` 后会修改宿主事件监听语义 |
| 预期变更 | 将仅有的三类 Tailwind utility 使用改为 `.mabox-*` 局部 CSS，以 `.mabox-shell` 内最小 normalization 保留应用布局，删除 admin Tailwind/PostCSS 管线和 PHP 内联 CSS 旁路；所有自有规则限定在 `.mabox-*` 命名空间；删除 admin 全局事件补丁；增加 clean-checkout 源契约和 build 后递归产物扫描 |
| 明确非目标 | 不改 UI 结构、Ant Design、业务表单、代码分块、PHP 功能、`count` 子项目仍在使用的事件补丁或兄弟仓库 |
| 公共契约 | WordPress 后台宿主的全局元素样式和事件监听选项不再被 admin bundle 改写；插件内部业务与设置契约不变 |
| 预期文件 | 删除 admin Tailwind/PostCSS 配置；修改 `admin/class-magick-mixture-admin.php`、`src/App.css`、三类 utility 消费者、portal 消费者、`src/main.tsx`、admin manifest、工作区 lockfile、聚焦测试、构建扫描脚本与本 ADR |
| 不得改变 | 其他前端项目、后台 UI 结构、PHP/REST、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | admin Vitest、TypeScript、Vite build、build 后所有 CSS 的 `.mabox-*` 选择器扫描、ESLint 0 error、frozen lockfile 检查、PHP 语法、PHPUnit、PHPStan 和 `git diff --check` |
| 跨仓矩阵 | 不需要；改动只约束本仓库 admin bundle 的宿主嵌入行为 |
| 回滚计划 | 回滚本切片即可恢复旧样式/事件入口；不引入 feature flag 或双轨加载 |

### 工作包 9A 实施事实

1. admin 已删除全部 `@tailwind`/`@apply`、Tailwind/PostCSS 配置和 `tailwindcss`/`autoprefixer`/`postcss` 三个直接开发依赖；`count` 与 `public` 子项目未改动。
2. 原 `pre-meat`、`cursor-pointer font-bold` 和 `w-full` 分别改为 `mabox-preformatted-hint`、`mabox-preview-trigger` 和 `mabox-full-width`，以少量普通 CSS 保留现有视觉与布局。
3. `App.css` 在 `.mabox-shell` 内保留最小 normalization：box sizing、标题/段落/pre、列表和表单控件只影响插件后代；元素 reset 使用低特异性的 `:where()`，不会覆盖后续组件类。Drawer 使用 `.mabox-detail-drawer`，Modal/Popover/Image Preview 与 `Modal.confirm` 使用 `.mabox-admin-modal`，因此挂载到 `body` 的 portal 也进入同一 scoped normalization 和响应式 Ant 覆盖；通用 `.menu-header` 改为 `.mabox-menu-header`。原 PHP `wp_add_inline_style()` 旁路已删除，`#root` 外边距和仍需保留的 Ant 响应式规则均迁入可扫描的 bundle；源契约会读取 PHP 文件阻止旁路复活，并拒绝不以 `.mabox-` 起始的 CSS 选择器。Local 320px 复核确认 SEO 模块网格的 300px 最小列宽会造成横向滚动，移动断点现强制使用 `minmax(0, 1fr)`，并允许模块卡片收缩；未打开和打开 Drawer 时，`body`/document 的 `scrollWidth` 均为 320px。
4. admin 入口不再导入 `default-passive-events`，admin manifest/lock importer 同步删除该直接依赖；`count` 子项目仍有真实入口引用，因此共享 lockfile 中的包快照按需保留。
5. `vite build` 后由 `vite/admin/src/check-admin-css-isolation.mjs` 递归发现 `dist` 下全部 CSS，并以 jsdom/CSSOM 解析普通规则及媒体规则；选择器列表只在顶层逗号处分隔，不会误拆 `:where()`、`:is()`、属性或字符串中的逗号。若构建工具重新生成裸全局类、元素或 reset，构建会直接失败。脚本位于发布 ZIP 已排除的 `src/`，普通 Vitest 不依赖被忽略的 `dist`，可在 clean checkout 独立运行。
6. 自动化门禁：admin Vitest 16 个文件/104 项测试通过；TypeScript 与 Vite build 通过；构建扫描确认 2 个 CSS 文件/286 个选择器全部位于 `.mabox-*` 命名空间，`index.css` 从旧基线约 15.20 kB 收口为 13.63 kB；ESLint 0 error、frozen offline install、PHP 语法、PHPUnit 338 项测试/3354 个断言、PHPStan 0 error 和 `git diff --check` 通过。

## 工作包 9B：保存信任条与差异确认

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `vite/admin` 的全局保存反馈与设置差异确认弹窗 |
| 失败证据 | 保存区只在点击后计算 diff，读取中、读取失败、无改动和待保存缺少持续可见的状态；无改动仍弹 toast；保存组件混入滚动监听和返回顶部按钮；差异弹窗暴露内部 path，并把普通变更后的值误用成功绿色表达 |
| 预期变更 | 用普通设置 diff 与凭据 diff 的真实总数建立稳定保存状态；无改动禁用保存；高风险仍显式确认；差异默认只显示用户标签与前后值；桌面头部和移动粘性底栏采用同一紧凑信任条布局 |
| 明确非目标 | 不改导航、设置表单、Ant Design、REST/敏感设置契约、业务设置、代码分块、前台项目或兄弟仓库 |
| 公共契约 | 保存请求与凭据操作契约不变；新增 `role=status`、`aria-live=polite` 的可访问状态反馈；差异标签从已加载 UI Schema 或静态功能索引解析，未知设置使用安全通用标签，弹窗不再把内部 path 呈现给用户 |
| 预期文件 | `src/tool/save.tsx`、`src/tool/diff.ts`、`src/tool/featureIndex.ts`、`src/components/diff-modal.tsx`、`src/App.tsx`、`src/components/tab.tsx`、`src/App.css`、聚焦 Vitest 与本 ADR |
| 不得改变 | WordPress/PHP 运行时、设置 Schema、其他后台功能、构建分块、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | admin Vitest、TypeScript、Vite build 与 CSS 隔离扫描、ESLint quiet error gate、`git diff --check`、桌面与 320px Local 浏览器验收 |
| 跨仓矩阵 | 不需要；保存状态与确认界面只属于本仓库 admin bundle |
| 回滚计划 | 回滚本工作包局部变更即可恢复旧保存区；不增加 feature flag、双轨保存或兼容层 |

### 工作包 9B 实施事实

1. 保存组件持续组合 `diffConfig()` 与 `diffSecretChanges()`，以真实总数呈现五态：读取中为“正在读取设置…”，读取失败为“设置不可用”，无改动为“已保存”，有改动为“N 项待保存”，提交期间为“正在保存…”；状态使用 polite live region。
2. 只有设置成功读取且真实 diff 非空时，“查看并保存”才可用；读取中、读取失败和无改动均显示禁用的“保存”。无改动不再弹 toast，点击处理仍保留无 diff 的防御性短路。
3. 保存成功、保存失败以及“写入已成功但回读失败”继续使用彼此不同的诚实反馈。凭据 draft 只在写入成功后清空，随后仍强制回读服务端状态。保存组件中的滚动 state/effect、全局 scroll 监听、返回顶部图标和按钮已删除。
4. 差异弹窗删除内部 path，只显示用户标签及格式化后的前后值；普通 after 使用正文中性色，高风险开启仍有红色警告、标签、结果值和危险确认按钮。长值允许任意断行，移动断点将前后值改为上下排列；每组值包含视觉隐藏的“原值：”与“新值：”文本，不依赖箭头或不含值的容器 `aria-label` 传达变化。
5. 主代理首次 Local 320px 验收发现，仅删除 path 副行仍会因 `diffConfig()` 的旧回退规则把 `optimize.site.hide_top_toolbar` 当作 label 展示。标签解析现优先使用显式字段标签，再按已加载 UI Schema 的精确 `path` 匹配，继而按静态功能索引的 feature ID/alias 匹配；未知设置统一显示“设置项”，不再回退内部 path。真实路径现显示“隐藏顶部工具条”。
6. 复审又发现旧 `RISKY_PATHS` 把 PHP UI Schema 中的两个 `low` 路径硬编码为 `high`，同时遗漏真正的 `performance.db_clean.enabled=high`。风险等级现按已缓存 UI Schema 的精确 path/risk.level 解析；Schema 显式给出的 `none`、`low`、`high` 均优先于静态镜像。由于 FeatureSearch 异步加载 Schema 而 Save 可能先渲染，未缓存、缺条目或风险元数据不完整时，以只包含当前 PHP Schema 唯一 high 路径的 `CURRENT_HIGH_PATHS` 最小镜像安全兜底。旧高风险硬编码已删除；该镜像是生成单一 Schema 契约前的明确临时边界。
7. 桌面头部与移动粘性底栏共用紧凑保存信任条；移动底栏用状态与操作两端布局并限制按钮最小宽度，320px 不依赖水平滚动。错误的全局 `message.config({ rtl: true })` 已删除，其他 message 配置保持不变。
8. Save/DiffModal 9 项测试覆盖读取、错误、无改动、普通与凭据真实计数、异步保存、写入失败、回读失败、读屏原值/新值、path 隐藏、中性普通变更和高风险确认；Diff/FeatureIndex 回归另锁定真实路径标签、Schema 标签、未知路径安全回退、Schema high/low 权威值和 Schema 未缓存/不完整时的真实 high 兜底。全量 admin Vitest 17 个文件/116 项测试通过；TypeScript、Vite build、2 个 CSS 文件/308 个选择器隔离扫描、ESLint quiet error gate 和 `git diff --check` 通过。
9. Local 桌面复验确认无改动时稳定显示“已保存”且保存按钮禁用，`body`/document 宽度均为 1280px。320px 下切换“隐藏顶部工具条”后显示“1 项待保存 / 查看并保存”，差异弹窗显示用户标签且内部 path 数量为 0，页面和弹窗均无横向溢出；取消并刷新后开关恢复关闭。数据库清理开启流程先显示即时高风险确认，随后保存弹窗继续显示 1 项高风险、危险确认按钮及“原值/新值”读屏标签，内部 path 数量为 0；取消并刷新后开关恢复关闭，整个验收未写入配置。控制台没有新增 warning/error，只有 WordPress 的 JQMIGRATE 普通日志。

## 工作包 9C：管理后台构建分块与资源路径契约

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `vite/admin` 的生产分块、资源 URL 与 build 后预算门禁 |
| 失败证据 | 对象形式 `manualChunks` 把 Dashboard/Page 及其共享依赖强制提升为首屏 preload：旧产物首次加载 JS 为 1,345,339 B（1,313.81 KiB）/ gzip 432,686 B（422.54 KiB），最大 `tab-dashboard.js` 为 1,258,457 B（1,228.96 KiB）/ gzip 400,324 B（390.94 KiB），另生成 28 B 的空 `vendor.js`；生产 base 又硬编码 `/wp-content/plugins/wp-magick-toolbox/`，与 PHP `plugin_dir_url()` 支持实际安装目录的契约冲突；全部 lazy chunk 使用固定文件名，跨版本更新时旧入口缓存可能请求新旧内容错配的同名文件 |
| 预期变更 | 删除错误手工分块并让 `React.lazy()`/模块图决定异步边界；生产 base 改为 `./`；入口和合并后的 CSS 保持 PHP 可 enqueue 的固定名称，并以插件版本加文件修改时间刷新 URL，其余 JS/图片使用内容 hash；降低 chunk warning；build 后从 HTML module script/modulepreload 和 Vite manifest 递归静态 import，验证首次加载、全图可达、最大 chunk、路径与缓存契约 |
| 明确非目标 | 不拆 Dashboard、不替换 Ant Design、不改业务 UI、REST/Schema、其他 Vite 子项目、锁文件或兄弟仓库 |
| 公共契约 | PHP 仍 enqueue 固定 `dist/index.js` 与 `dist/index.css`，查询版本为插件版本与对应构建文件 mtime 的组合；lazy JS 与图片使用 hash 文件名且相对于实际 dist URL 解析；initial JS raw 不超过 900 KiB、gzip 不超过 300 KiB，最大单个 JS chunk 使用同一上限 |
| 预期文件 | `admin/class-magick-mixture-admin.php`、`vite.config.ts`、`index.html`、`package.json`、`src/bootstrap.ts`、`src/check-admin-build-contract.mjs`、聚焦 Vitest 与本 ADR |
| 不得改变 | PHP/WordPress 运行时、业务源码、其他前端项目、发布包固定入口、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | admin Vitest、TypeScript/Vite build、CSS 隔离与构建契约扫描、ESLint quiet error gate、`git diff --check` |
| 跨仓矩阵 | 不需要；资源构建和全部消费者都在本仓库 |
| 回滚计划 | 回滚本工作包即可恢复旧分块与 base；不保留双轨配置、feature flag 或运行时兼容层 |

### 工作包 9C 自动化实施事实

1. 删除对象形式 `manualChunks` 后，Vite 按现有 7 个 `React.lazy()` 动态入口和真实共享依赖生成模块图；旧 `tab-dashboard.js`/`tab-page.js` 首屏 preload 与 28 B `vendor.js` 均消失，HTML 的 modulepreload 集合从 `{tab-dashboard.js, tab-page.js}` 收口为空。
2. PHP 继续 enqueue 固定 `index.js`；该文件现在只是 41 B 的 bootstrap，只立即导入唯一 hashed app entry，不承载或导出 React/Context/预加载运行时。`cssCodeSplit=false` 将全部 308 个已隔离选择器合并到唯一 `index.css`，固定两文件使用“插件版本-对应文件 mtime”作为查询版本。hashed 图包含 1 个 app entry、7 个路由动态入口、12 个共享 JS chunk 及 4 个图片/SVG；Vite manifest 只服务构建契约，不形成运行时依赖。
3. 生产 `base` 从硬编码插件安装路径改为 `./`。构建后的 HTML 只含 `src="./index.js"` 与 `href="./index.css"`，bootstrap 只含 `void import("./assets/app-hash.js")`，其余动态 import 也全部相对于 hashed 模块；全部产物扫描未发现 `/wp-content/plugins/`，资源从 PHP 实际 enqueue 的 dist URL 解析且无需硬编码安装目录。
4. 新扫描器解析 HTML 的 module script、modulepreload 和 stylesheet，再以 `.vite/manifest.json` 锁定 fixed bootstrap 的唯一 immediate app import，并把 app entry 及其静态 imports 计入真实首次闭包；其余 7 个 route dynamic entries 才作为 lazy。它同时读取 `dist/index.js`，要求产物严格只执行 `void import("./<manifest appEntry>")`，不允许空 bootstrap、错误 hash 或额外逻辑借正确 manifest 过关；随后以 `imports + dynamicImports` 验证全图可达，禁止任何 hashed chunk 反向 import 带 query 的固定入口，并逐文件分别维护 raw 与 gzip 最大 chunk。路径逃逸、缺文件/manifest 键、lazy chunk 进入首屏、孤儿/空 vendor、拆分 CSS、非 hash 资源、硬编码插件路径及预算超限均 fail closed。
5. 当前真实首次加载为 791,127 B（772.58 KiB）/ gzip 257,968 B（251.92 KiB），固定 bootstrap 为 41 B / gzip 61 B，最大 chunk 为 hashed app entry 790,051 B（771.53 KiB）/ gzip 257,307 B（251.28 KiB），均低于 900/300 KiB 双预算；相对旧基线，首次 raw/gzip 分别下降 41.19%/40.38%，最大 chunk raw/gzip 分别下降 37.22%/35.73%。`chunkSizeWarningLimit` 从 1600 KiB 降为 900 KiB。
6. Scanner 10 项聚焦测试覆盖配置源契约、PHP 固定入口 mtime/module 契约、bootstrap→hashed app、空 bootstrap 与错误 hashed app 产物、hash chunk 禁止反向 import fixed bootstrap、initial/dynamic 图、路径与 manifest 缺失、raw/gzip 预算异构反例、hash/孤儿 vendor/硬编码路径、单 CSS 与 HTML 固定入口。全量 admin Vitest 18 个文件/126 项测试、TypeScript/Vite build、1 个 CSS 文件/308 个选择器隔离扫描、构建契约扫描、ESLint quiet error gate 和 `git diff --check` 均通过；PHPUnit 338 项/3354 个断言、PHPStan 0 error 已通过。
7. 首次 Local 验收出现空白页，先后排除了 MIME、`type="module"`、相对 URL、文件 404、`dataLocal` 和固定入口缓存。浏览器最终捕获 React #321 invalid hook call：WP 只给固定入口 URL 加 `?ver=`，而 Vite 旧图让 hashed app/route chunks 反向 import 无 query 的 `../index.js`，浏览器把两种 URL 当作两个模块 identity，导致两份 React dispatcher/Context。显式 hashed app entry 与构建期 bootstrap 隔离现保证固定入口没有共享导出，manifest 和 scanner 同时禁止反向边。
8. 登录态最终 Local 验收使用新鲜页面确认固定入口为 `index.js?ver=2.6.1-1784128230`，React #321 与后续 `removeChild` NotFoundError 均消失。`overview/site/content/seo/china/maintenance/about` 七个语义路由逐一硬刷新，均有一份 shell、状态为“已保存”、内容非空且无设置读取失败；1280px 下无横向溢出，320px 的 maintenance 页面同样保持 shell、已保存状态及 `body/document=320px`。控制台没有新增 warning/error，只有 WordPress JQMIGRATE 普通日志。

## 工作包 10A：最小生成式设置契约

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `MaBox_Config_Schema` 到 `vite/admin` 的浏览器默认值与 UI 风险元数据同步 |
| 失败证据 | PHP Schema 将 `page.function.countdown` 默认值定义为空数组，前端却按浏览器当天动态生成“昨天 9–12 点”；前端另有 `CURRENT_HIGH_PATHS` 和四条风险确认文案镜像，已发生风险等级误判 |
| 预期变更 | 从 PHP Schema 确定性导出去敏浏览器 defaults 与 schema-only UI 风险元数据；前端消费受检生成物；生成物漂移与敏感字段泄漏 fail closed |
| 明确非目标 | 不生成 TypeScript 类型、不改 REST 路由/响应或保存 payload、不做契约 hash 握手、不改 Registry、搜索关键词/别名、业务表单、Ant Design、构建产物或兄弟仓库 |
| 公共契约 | `Option` 类型和设置 GET/POST 不变；新增 `composer settings-contract:generate` 与 `composer settings-contract:check` 开发门禁；tracked JSON 仅作为构建期同步快照 |
| 预期文件 | Config Schema、dev-only PHP exporter、tracked JSON、默认值/UI Schema/风险消费点、聚焦 PHP/Vitest、README 与本 ADR |
| 不得改变 | 插件运行时设置存储与敏感凭据操作、模块 Registry、发布 `dist`、用户未跟踪排障文档、兄弟仓库 |
| 必需门禁 | generator `--check`、聚焦与全量 PHPUnit、PHPStan、admin Vitest/TypeScript/lint/build、`git diff --check` |
| 跨仓矩阵 | 不需要；Schema 及全部生成物消费者均在本仓库 |
| 回滚计划 | 回滚本工作包即可恢复旧手写前端默认值/风险 fallback；不保留双轨兼容层或运行时 feature flag |

### 工作包 10A 实施事实

1. `MaBox_Config_Schema::get_schema_ui_schema()` 现在提供不合并 Registry/模块 metadata 的 schema-only UI 视图；原 `get_ui_schema()` 仍在此基础上可选合并模块 metadata，因此现有 REST 端点用途和搜索增强能力不变。
2. `get_admin_settings_contract()` 从同一 Schema 导出浏览器默认值和 UI 风险元数据。三个敏感字段的键和值均从 defaults 与 UI Schema 排除；回归测试另用 synthetic flat Schema 锁定未来 flat 模块和“敏感字段带 label/risk”场景仍会完整去敏。
3. dev-only PHP CLI 位于发布包已排除的 `tests/support/`，不加载 WordPress 或 PHPUnit bootstrap，自带 PHP 7.4 可用的最小常量环境；递归稳定排序 JSON object、保留 list 顺序、UTF-8/斜线不转义并固定末尾换行。生成模式通过目标同目录临时文件和 `rename()` 原子替换，失败时清理临时文件；`--check` 只比较 tracked JSON，不写文件。
4. 前端 `defaultVarOption` 直接消费生成 defaults 并由 TypeScript 赋值检查，删除约 250 行手写默认树和日期计算；`countdown` 首次值恢复为 Schema 定义的空数组。`Option` 类型、保存 payload 和业务表单保持不变。
5. 生成 UI Schema 作为同步 fallback，风险等级由窄类型与运行时 type guard 共同约束，不再使用双重类型断言；服务端 Schema 获取状态与 generated fallback 是否存在明确分离。若目标不在 generated 且服务端尚未取，首次风险检查会继续请求 `/settings/schema`，再按 Registry-only 风险弹出确认；FeatureIndex 也仍会执行真实请求并合并模块 metadata。`CURRENT_HIGH_PATHS` 与 `riskyFeature.tsx` 的四条风险文案镜像已删除，高风险等级、标题、警告、建议和不可忽略标记均来自 PHP Schema。
6. Node CI/build 不依赖隐含 PHP 环境；漂移门禁由 `composer settings-contract:check` 和 PHPUnit parity 测试放在 PHP 工具链中执行，admin 测试独立验证生成物的默认值、风险 fallback 和 REST 合并行为。
7. 自动化门禁：生成物 `--check`、Composer validate、PHP 语法检查、PHPUnit 341 项测试/3367 个断言、PHPStan 0 error、admin Vitest 19 个文件/130 项测试、TypeScript/Vite build、308 个 CSS 选择器隔离扫描、构建契约扫描、ESLint 0 error 和 `git diff --check` 均通过。独立复审 build 的首次 JS 为 792,862 B / gzip 258,453 B，最大 app chunk 为 791,786 B / gzip 257,792 B，仍低于工作包 9C 的 900/300 KiB 预算；本工作包没有修改或跟踪 `dist`。
8. Local WordPress 7.0.1 真实验收通过，一次性管理员已删除。`overview/site/content/seo/china/maintenance/about` 七个 view 均正常渲染并显示“已保存”，无设置读取失败、可见错误或横向溢出；generated `optimize-medium-no_auto_size` 会弹出“禁止缩略图”风险确认，取消后开关保持 `false`；`performance-db_clean-enabled` 会弹出不可忽略的高风险确认且没有“不再提示”，取消后同样保持 `false`。浏览器 error/warn 均为 0，验收过程未保存设置。

## 工作包 11：前端工程与发布边界收口

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | `vite/` 的依赖与质量工具链、Admin/Count 构建入口，以及 CI、发布 ZIP、当前开发文档和边界测试 |
| 失败证据 | 三个 Vite 子项目重复维护 React/Vite/TypeScript/ESLint/Ant Design 依赖；`vite/public` 没有 PHP enqueue、DOM 挂载或模块 Registry 消费者，却仍参与安装、CI、构建与发布；Count 的 ignored `dist` 被 PHPUnit 当作 fresh-checkout 前置条件 |
| 预期变更 | 收口为一份根 `package.json`、锁文件与 ESLint 工具链；删除无人消费的 `vite/public`；保留 Admin/Count 两份专用 Vite 配置和独立 `dist/index.js`、`dist/index.css`；CI 只构建一次并把受检产物交给 ZIP job |
| 明确非目标 | 不合并 Admin 与 Count 的运行时应用、不改变设置/统计业务、不删除仓库根 `public/`、不改变 Admin 固定 bootstrap/单 CSS/相对路径/预算契约、不引入新的前端框架或兄弟仓库改动 |
| 公共契约 | 根命令为 `dev/typecheck/lint/build` 的 Admin/Count 目标；PHP 继续分别 enqueue `vite/admin/dist/index.{js,css}` 与 `vite/count/dist/index.{js,css}`；两组固定资源均使用“插件版本 + 对应文件 mtime”刷新 URL |
| 预期文件 | `vite/package.json`、lockfile、根 ESLint 配置、Admin/Count 配置与源码、`vite/public/**`、Count PHP consumer、CI/ZIP、README/当前构建/VitePress 文档和聚焦 PHPUnit |
| 不得改变 | Admin/Count hook、handle、DOM mount、`dataLocal` 数据契约、设置 REST/Schema、数据库结构、历史 release wrap-up/阶段总结、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | frozen install、根 typecheck/lint/test/build、Admin CSS/构建扫描、Count 固定产物、PHP lint、聚焦与全量 PHPUnit、PHPStan、VitePress build、当前引用扫描、ZIP 内容检查和 `git diff --check` |
| 跨仓矩阵 | 不需要；构建配置与全部 WordPress 消费者均在本仓库 |
| 回滚计划 | 回滚本工作包即可恢复三项目工具链；不保留双 package、双 lockfile、空 `vite/public` 壳或发布兼容入口 |

本工作包不采用“一个 Vite 配置同时构建两个入口”。Admin 的 tiny bootstrap、`cssCodeSplit=false`、manifest 扫描与资源预算是专用契约；Count 是按独立后台页面加载的简单图表入口。共享依赖和质量工具链、保留两份窄构建配置，可以消除重复维护，同时避免一次构建清理另一个 `outDir` 或把 Admin 规则错误施加给 Count。

### 工作包 11 实施事实

1. `vite/` 只保留一份依赖清单、pnpm lockfile 与 ESLint 配置；Admin/Count 不再是子 package，但仍保留各自的源码、TypeScript/Vite 配置和独立输出目录。
2. 无 WordPress 运行时消费者的 `vite/public` 整体删除；仍由插件 bootstrap 使用的仓库根 `public/` 完整保留，二者边界已在当前文档中明确。
3. Count PHP consumer 使用与 `index.php` 父菜单匹配的 `dashboard_page_magick-census-single` 页面 hook，并保持 `_census_css`/`_census_js` handle、`#mabox_census_count` 与 `dataLocal.countData` 不变；CSS 和 JS 各自改用文件 mtime 形成缓存版本，缺少构建文件时安全回退插件版本。
4. CI 的前端 job 在根目录一次安装并依次执行共享 typecheck、lint、coverage 和 build；受检的 Admin/Count `dist` 作为 artifact 直接交给 ZIP job，避免发布阶段再次安装和构建。ZIP 门禁明确要求四个固定文件存在，只允许两份 `dist` 进入 `vite/` 发布边界，并拒绝 `vite/public`、源码或配置文件。
5. PHPUnit 不再要求 gitignore 的 Count `dist` 预先存在，而是锁定源码入口与 PHP consumer 契约；真实构建文件存在性由前端 build 与 ZIP 两层门禁负责。
6. README、当前构建指南和 VitePress 已统一为“一套前端工程、两个独立产物”；早期开发手册和按需加载规范只增加权威边界提示，历史 release wrap-up 与阶段总结保持原文。
7. 自动化门禁通过：frozen install、根 typecheck、ESLint 0 error（Admin 133/Count 4 个既有 warning）、Admin Vitest 与 coverage 23 个文件/136 项测试；Admin CSS 319 个选择器隔离和构建图扫描通过，首次 JS 774,809 B / gzip 252,139 B，tiny bootstrap 41 B；Count 构建扫描通过，`index.js` 621,299 B / gzip 206,360 B，`index.css` 826 B / gzip 370 B。PHP 语法、3 项聚焦 PHPUnit/21 个断言、全量 PHPUnit 343 项/3386 个断言、PHPStan、VitePress build、CI YAML 解析、ZIP 内容模拟和 `git diff --check` 均通过。

## 工作包 12：REST 与产品表面收口

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | REST Registry、分类数据接口，以及文章批量替换、数据库表导出、文章评分和微信解锁的失配残留 |
| 失败证据 | Registry 中 4 条路由指向不存在的类；文章批量替换保留 3 条手动路由、模块、Schema 和客户端 API，却没有配置或操作界面消费者；唯一真实使用的 `/tools/categories` 又混用了 AJAX nonce、`wp_send_json_*` 与不匹配的响应包络 |
| 预期变更 | 删除无实现或无消费者的路由、模块、配置和客户端代码；保留 `/tools/categories` 路径并改成管理员权限、标准 REST 响应和受检前端数据形状；建立精确路由表及 callback 可调用性门禁 |
| 明确非目标 | 不新增替代功能、不保留自动批量替换子集、不建立迁移器或兼容端点、不改后台视觉、Count、其他前台模块、AI 参考快照或兄弟仓库 |
| 公共契约 | 删除 `/page/batch-replace*`、`/tools/tables`、`/tools/table-data`、`/public/rating`、`/public/wx-unlock/verify`；`/tools/categories` 从公开权限改为 `manage_options`，成功响应固定为 `{success:true,data:{categorys,tags,pages}}` |
| 预期文件 | REST 注册、模块 Registry/Tier/Autoload、批量替换模块、Config Schema/生成物、Admin API/类型/消费者、回归测试、当前功能清单/文档站/变更日志及本 ADR |
| 不得改变 | 历史架构快照、早期实施报告与规划原文、数据库结构、用户未跟踪排障文档、无关功能和兄弟仓库 |
| 必需门禁 | callback 失败复现、精确路由表、聚焦与全量 PHPUnit、PHPStan/PHP lint、设置契约生成检查、Admin coverage/typecheck/lint/build、Count build、VitePress build、ZIP 边界、Local 登录态分类数据烟测和 `git diff --check` |
| 跨仓矩阵 | 不需要；路由、消费者和发布边界均在本仓库 |
| 回滚计划 | 回滚本工作包即可恢复旧表面；不保留 feature flag、双路由或历史配置兼容层 |

### 工作包 12 实施事实

1. 先新增 callback 可调用性门禁并在旧基线稳定复现 4 个失败：`MaBox_Download_SQL_Table` 两条、`MaBox_Page_Article_Rating` 一条、`MaBox_ShortCode_Wx_Unlock` 一条。删除残留后，15 条当前路由路径、16 个 endpoint callback 全部可调用；测试从模糊的“至少 19 条”改成精确产品表面。
2. 删除 4 条无实现路由、3 条无消费者的批量替换手动路由，以及对应前端包装。文章批量替换本身没有配置或操作界面，继续保留保存时 Hook 只会形成不可管理的隐藏行为，因此模块文件、Registry/Tier/Autoload、Schema 两字段、前端类型/校验、生成契约和当前功能文档整体清退，不建立自动替换子集。
3. 读取端 Schema 会忽略旧开发 Option 中的 `batch_replace` 与 `batch_replace_pairs`，严格写入契约拒绝未知字段，下一次正常保存自然覆盖旧键；项目没有用户或兼容承诺，因此不增加一次性迁移器、数据库扫描或双轨字段。
4. `/tools/categories` 继续服务“隐藏指定内容”设置，但 permission 改为 `manage_options`；callback 接受 WordPress REST 请求，不再读取 AJAX nonce 或调用 `wp_send_json_*`。分类、标签或页面读取失败统一返回不泄露内部细节且带 `status:500` 的 `WP_Error`，空站点则合法返回三个空列表；选项 ID 固定为整数。
5. Admin 客户端只保留 `getCategoryData()`，对成功包络和三个 option 数组做运行时形状验证；权限设置组件直接消费共享 `CategoryData`，畸形响应不会进入 Ant Design Select。数据库表导出和 batch API、CSV 浏览器下载函数及无消费者类型全部删除。
6. 当前事实文档、文档站导航和功能计数同步删除批量替换；2.3/2.4 历史架构、稳定性计划、实施报告与 AI 建议保留原文，并继续由顶部权威警告与当前 ADR 隔离，避免把历史改写成当前事实。
7. 自动化门禁通过：PHPUnit 343 项/3348 个断言、PHPStan 0 error、全仓 PHP lint、设置契约生成检查；Admin coverage 24 个文件/141 项测试，语句覆盖率 46.85%，TypeScript 和 ESLint 0 error（Admin 129/Count 3 个既有 warning）；Admin 构建首次 JS 774,457 B / gzip 252,024 B，Count JS 621,637 B / gzip 206,439 B，CSS/构建契约、VitePress build、189 项 ZIP 边界模拟和 `git diff --check` 均通过。首次并发运行 coverage 时，既有登录安全组件测试在多门禁争用资源下超过 5 秒；单测隔离、隔离 coverage 及前端独占全量 coverage 均稳定通过，未修改生产实现或放宽超时。
8. Local WordPress 7.0.1 管理员登录态验收通过且未保存设置：“内容与页面 → 权限”能加载分类 `Uncategorized` 和页面 `Sample Page`，空标签合法显示“暂无数据”；浏览器 console error/warn 均为 0。匿名 `/tools/categories` 返回 401，已删除 `/page/batch-replace` 返回 404，REST namespace 索引只暴露当前 15 条产品路由。

## 工作包 13：单一 Manifest 与生成式前端契约

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | 模块 Registry/Metadata，以及 `MaBox_Config_Schema` 到 Admin 设置类型、敏感路径和功能搜索的构建期契约 |
| 失败证据 | 57 个模块之外仍有 4 份 `.meta.php` 在每次加载时递归扫描并覆盖 Registry；33 条搜索记录、`Option` 设置树和 3 条敏感字段路径继续由 TypeScript 手写，新增设置需同步 Registry、Schema、类型和搜索多处事实源 |
| 预期变更 | 将 sidecar 元数据折叠进唯一 Registry；删除运行时扫描/merge；由 PHP Schema 确定性生成 JSON defaults/UI/search 与 TypeScript 设置类型/敏感路径；前端删除手写镜像并只消费生成契约 |
| 明确非目标 | 不改 REST 路由或响应结构、设置 GET/POST 与存储、业务表单、Admin 视觉、Count、Ant Design、Vite bootstrap、数据库、版本号、AI 参考快照或兄弟仓库 |
| 公共契约 | 57 个模块 ID/顺序/Loader 激活行为不变；33 条搜索项的 ID、标签、关键词、tags、aliases、顺序和语义 view 不变；`Option`、19 个设置子类型与 3 条 `SecretPath` 不变；公开 Schema 不暴露构建期 `search` |
| 预期文件 | Registry/Metadata 与 sidecar、Config Schema、dev-only exporter、两个 tracked 生成物、Admin 类型/搜索消费者及测试、README、当前开发指南与本 ADR |
| 不得改变 | REST 注册与成功/错误包络、敏感值边界、设置保存 payload、业务组件、Count、发布 `dist` 合同、历史阶段文档、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | 旧合同一次性全对象 parity、生成器成功/漂移/中途失败回滚、聚焦与全量 PHPUnit、PHPStan/PHP lint、Admin coverage/typecheck/lint/build、Count build、VitePress build、ZIP 边界、Local 七视图/搜索/风险烟测和 `git diff --check` |
| 跨仓矩阵 | 不需要；Manifest、Schema、生成器和全部消费者均在本仓库 |
| 回滚计划 | 回滚本工作包即可整体恢复旧 sidecar、手写类型和静态搜索；不保留运行时双轨、兼容扫描或 feature flag |

### 工作包 13 实施事实

1. `admin/modules/registry.php` 继续保持 57 个模块及原顺序，完整吸收 4 份 sidecar 的 label/group/feature/risk/dependency/preset/config 元数据；`MaBox_Module_Metadata` 从 132 行缩为直接缓存 Registry 的 56 行实现，递归目录扫描、文件名猜测、merge 和四份 `.meta.php` 全部删除。Loader 全量 Registry parity 与登录安全 ANY-OF 激活路径由测试锁定。
2. 搜索展示元数据与对应设置字段共同定义在私有 Schema definition 中；公开 `get_schema()` 在返回前递归剥离 build-only `search`，因此 REST、defaults、validation 和 UI Schema 结构不变。`get_admin_search_index()` 验证必填文本、字符串列表、唯一 ID 与 5 个合法语义 view，排除敏感字段并拒绝畸形元数据。
3. 原 33 条 TypeScript 静态索引已逐对象、逐顺序迁入 Schema，一次性对比为 33/33、0 个语义差异；`featureIndexData.ts` 与前端模块/view、preset 映射、运行时 Schema 搜索 merge 一并删除。搜索组件直接消费生成索引，标签仍可优先使用已缓存 UI Schema，风险等级解析保持原合同。
4. dev-only PHP exporter 同时生成 `settings-contract.json` 和 `settings-types.ts`；类型映射只接受 boolean/string/number/带 items 的 array，未知类型 fail closed。双目标先准备同目录临时文件并备份旧目标，任一可捕获的后续替换失败会删除本轮新文件、恢复全部旧文件并清理 temp/backup；回归测试已复现并阻止“第一文件半更新、第二文件失败”。该开发期生成器不宣称在 `SIGKILL` 或断电场景下具备 crash-safe 事务语义，生成物缺失或漂移仍由 `--check` fail closed。
5. 生成 TypeScript 保留原 `Option` 索引签名、19 个设置子类型、`FunctionTips` 命名以及 `SECRET_PATHS`/`SecretPath`；三个敏感字段只以路径常量出现在生成 TS 中，不进入 JSON defaults/UI/search 或 `Option` 子类型。`interface.tsx` 从 424 行缩为 226 行的生成类型出口与手写运行时/诊断合同。
6. 根 README 与 VitePress 当前开发指南改为“Registry + Schema + 生成命令 + UI”流程；2.3/早期阶段的历史手册和实施总结继续由顶部权威警告隔离，未改写原文。独立交叉审查发现并修复旧搜索副本、无意义 Promise 更新和双生成物失败回滚三个问题，修复均有聚焦回归。
7. 自动化门禁通过：设置契约 `--check`、Composer validate、129 个 PHP 文件语法检查、PHPUnit 345 项测试/3711 个断言、PHPStan 0 error；Admin coverage 24 个文件/133 项测试，语句覆盖率 46.19%，Admin/Count TypeScript 和 ESLint 0 error（Admin 129/Count 3 个既有 warning）；Admin 构建首次 JS 773,205 B / gzip 251,972 B，Count JS 621,637 B / gzip 206,439 B，CSS/构建契约、VitePress build、185 项 ZIP 边界模拟、生成器确定性与 `git diff --check` 均通过。
8. Local WordPress 7.0.1 登录态烟测覆盖 overview/site/content/seo/china/maintenance/about 七个语义视图；“数据库清理”搜索只返回生成索引中的匹配项并正确定位 maintenance，风险开关弹窗取消后仍为关闭且保存按钮未激活；320 px 视口无横向溢出，控制台无 warning/error，最终返回 overview 且未保存任何设置。

## 工作包 14A：轻量 Admin 外壳与按需 Ant 边界

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | Admin 导航外壳的搜索、加载/错误状态、通知和保存确认加载链，以及对应构建预算 |
| 失败证据 | 工作包 13 的真实首次 JS 为 773,205 B / gzip 251,972 B；虽然 7 个业务视图已经 `React.lazy()`，`App/Tab/FeatureSearch/Save/Axios/Favorites` 仍把 Ant message、Input/List/Tag/Button/Alert/Spin/Modal 及共享 rc 依赖静态带入首屏 |
| 预期变更 | 原生化首屏搜索、状态和保存按钮；用单一轻量通知替换 Ant message；保存确认只在点击后安全加载；复杂表单、Drawer、Table 和 Modal 继续按需复用 Ant |
| 明确非目标 | 不全量清退 Ant Design、不重写业务表单/Drawer/Table/风险 Modal、不改 REST、Schema、设置 payload、路由、Count、依赖版本、插件版本或数据库 |
| 公共契约 | 7 个语义视图、33 条搜索、收藏、设置读取失败禁用、差异确认、敏感设置、风险确认及通知文案不变；Ant 中文 locale 继续由根 `ConfigProvider` 提供 |
| 预期文件 | Admin 外壳/搜索/保存/通知与测试、6 个 message 消费者、构建扫描/配置、README、当前开发指南与本 ADR |
| 不得改变 | 业务设置控件、REST/权限/nonce、生成式设置合同、Count、发布 `dist` 跟踪边界、历史文档、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | 聚焦与全量 Admin 测试/coverage、TypeScript、ESLint、Admin/Count build、CSS 隔离、首屏前后对比、PHPUnit/PHPStan/设置生成检查、Local 七视图/搜索/保存取消/移动端烟测和 `git diff --check` |
| 跨仓矩阵 | 不需要；外壳、消费者、构建合同和验证均在本仓库 |
| 回滚计划 | 单独回滚工作包 14A 即恢复 Ant 外壳；不保留双通知、双搜索或运行时 feature flag |

### 工作包 14A 实施事实

1. `FeatureSearch` 改为原生 search/list/button 与 Dashicons，不新增依赖；继续消费 33 条生成索引并显示前 20 项，保留收藏、跳转、清空和无结果。Escape、上下/Home/End、失焦关闭、展开 ARIA 与“关闭后第一次方向键”提交竞态均有回归测试，收藏状态色对白底对比度为 6.79:1。
2. 新 `notice` 统一接管 Axios、收藏、保存和 6 个业务视图的 24 个 Ant message 调用，保留原文、2 秒和最多 3 条合同；可见通知使用 `textContent`，稳定的隐藏 polite/assertive live region 在挂载后更新，避免 HTML 注入、漏报或双重播报。生产源码不再存在 Ant message 双轨。
3. `Tab` 的读取/懒加载/错误状态改为原生语义结构，仍以 `role=status/alert` 告知状态并在读取失败时禁止保存；搜索定位遵循 reduced-motion。原生保存按钮继续显示可信状态与真实差异数，两个无限 spinner 在 reduced-motion 下停止动画。
4. 保存确认由可测试的显式 dynamic import 加载：慢加载时显示可见准备状态，chunk 失败只提示并保留待保存内容且允许重试；首次加载后 Modal 保持挂载并只切 `visible`，取消恢复触发按钮焦点，确认则在关闭提交后的下一帧聚焦保存状态区。复杂 Ant Modal 本身没有重写。
5. 根 `ConfigProvider + zhCN` 与复杂业务表单继续保留 Ant；默认 overview 不再静态加载 Ant message、搜索列表、Alert/Spin 或保存 Modal。生产 manifest 仍有 7 个视图动态入口，并新增独立保存确认入口；没有使用 `manualChunks` 伪装收益。
6. 最终首次 JS 为 317,060 B / gzip 109,117 B，较工作包 13 基线减少 456,145 B（58.99%）/ 142,855 B（56.70%）；固定 bootstrap 仍为 41 B / gzip 61 B，modulepreload 为空。构建强制预算与 Vite warning 同步从 900/300 KiB 收紧为 400/140 KiB；单 CSS 为 31.05 kB / gzip 6.20 kB，390 个选择器通过宿主隔离。
7. 独立审查曾阻断消息配置漂移、两套通知、lazy chunk rejection、Modal 直接卸载丢焦点、搜索展开状态/首次方向键竞态、live region 漏报和 reduced-motion 七类问题；均按根因修复并新增回归，最终复审无 P0-P2。
8. 自动化门禁：Admin coverage 25 个文件/147 项测试，语句覆盖率 47.97%；Admin/Count TypeScript 与 ESLint 0 error（Admin 129/Count 3 个既有 warning）；Admin/Count build、CSS/构建扫描、PHPUnit 345 项测试/3711 个断言、PHPStan 0 error、设置契约检查、Composer strict validate 和 `git diff --check` 通过。
9. Local WordPress 7.0.1 登录态烟测覆盖七个语义视图且均无错误状态或横向溢出；搜索框的展开/关闭、Escape 后首次 ArrowDown、数据库清理跳转与目标高亮通过。风险确认和保存差异 Modal 均只取消：前者保持开关关闭，后者在关闭动画后恢复“查看并保存”焦点；测试用关键词高亮开关与收藏状态均已还原，最终 overview 为“已保存”且保存禁用。320 px 下 document/wpcontent/shell 均无溢出，移动导航与搜索可见，console warning/error 为 0，未写入任何设置。

## 工作包 14B：单一发布候选包与 3.0.0 版本事实

| 项目 | 决定 |
| --- | --- |
| 目标仓库 | `/Users/muze/gitee/wp-magick-toolbox` |
| 聚焦模块 | 发布 ZIP 的本地/CI 单一构建合同、候选包验证、3.0.0 版本与当前产品事实 |
| 失败证据 | 旧 CI 在仓库根目录直接 `zip -r`，没有固定 `wp-magick-toolbox/` 插件根；排除和校验规则内联在 CI，本地无法复用；Header/常量/Stable tag 仍是 2.6.1，发布指南又误称创建 GitHub Release 会自动生成 ZIP |
| 预期变更 | 新增唯一 Composer 构建/验证入口；强制单根、路径安全、必需文件、前端产物边界、禁止项、三处版本一致和 SHA-256；CI 只调用同一合同；同步 3.0.0 当前文档 |
| 明确非目标 | 不改插件业务、设置/REST/数据库、Admin/Count 源码或依赖；不推送、不打标签、不创建 Release、不上传 WordPress.org |
| 公共契约 | `composer release:build` 生成根目录 `wp-magick-toolbox.zip` 与同名 `.sha256`；`composer release:verify -- <zip>` 可独立复验；ZIP 只有 `wp-magick-toolbox/` 单根，`vite/` 只允许 Admin/Count 的 `dist` |
| 预期文件 | 两个发布脚本、`.distignore`/`.gitignore`、Composer、聚焦 PHPUnit、CI、版本/发布/当前功能文档与本 ADR |
| 不得改变 | 生产运行时代码、生成式设置契约、前端构建图、历史发布记录、用户未跟踪排障文档和兄弟仓库 |
| 必需门禁 | Shell 语法、合成 ZIP 正/反例、真实 ZIP/校验文件/解压 PHP 语法、Composer validate、设置契约、PHPUnit/PHPStan/全仓 PHP lint、前端 typecheck/lint/coverage/build、VitePress、CI YAML、独立审查和 `git diff --check` |
| 跨仓矩阵 | 不需要；发布源码、产物和 CI 消费者都在本仓库 |
| 回滚计划 | 回滚本工作包即恢复 2.6.1 与旧 CI 打包；不保留两套发布脚本或双排除清单 |

### 工作包 14B 实施事实

1. `composer release:build` 使用 `.distignore` 与 `rsync` 建立临时 staging，看到 Admin/Count 四个固定 JS/CSS 后才生成 `wp-magick-toolbox/` 单根 ZIP。临时产物自验通过后才成对安装 ZIP 与 `.sha256`；同步失败会恢复旧产物，可捕获 HUP/INT/TERM 在短暂提交窗口内不会留下持久的半提交状态。
2. `composer release:verify -- <zip>` 验证完整性、重复/特殊条目、单根与路径穿越；解压后再拒绝符号链接，强制主入口、核心启动链、REST Registry、四个前端固定文件，并拒绝 tests/docs/vendor/node_modules/源码、`.env*`、`.DS_Store`、PHPUnit 缓存和 Vite `.vite` 元数据。
3. Header、`MAGICK_MIXTURE_VERSION`、Stable tag 必须唯一且一致；sidecar 若存在，必须恰好一行 `<64hex><两个空格><ZIP basename>`，支持文件名空格并拒绝错文件名或额外行。macOS 使用 `shasum -a 256`，Linux 使用 `sha256sum`。
4. 8 项发布契约测试/988 个断言包含含空格正例、敏感 dotfile/Vite 元数据、版本/checksum 漂移、必需文件、第二根、`../` 穿越，以及在第 3 次 `mv`——新 ZIP 已安装而 sidecar 尚未安装——时真实注入 TERM 的事务反例。
5. CI 不再维护内联 `EXCLUDE` 和弱 unzip 检查；前端 job 一次生成受检 Admin/Count `dist`，发布 job 下载后调用同一 Composer 合同，并一起上传 ZIP 和 SHA-256。工作流仍只由 push/PR 触发，没有虚构 Release 自动化。
6. 主文件 Header、运行时常量、Stable tag、README 和当前功能清单统一到 3.0.0，`Tested up to` 依据已验收的 Local WordPress 7.0.1 写为 7.0。readme 删除已清退功能宣传，并明确本地搜索词/IP/审计诊断与显式第三方请求的隐私边界。
7. 当前功能清单以 60 个用户可理解的能力/任务入口为口径，并用内部 provider、合并/拆分能力与两个非 Registry 用户任务对账 57 个运行模块（19/18/11/4/5），不再把过期的 56 与 Registry 混为同一统计。
8. 真实候选包为 189 个条目、896,690 bytes、版本 3.0.0；根目录只有运行文件和两份 `dist`，`.vite` manifest 不入包。ZIP/sidecar 校验通过，解压后 88 个 PHP 文件语法通过，四个中文图片/SVG 文件名原样恢复。最终独立复审 P0–P3 均清零并 Approve。

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

整个 Pre-GA Reset 的代码与本地候选包已收口：工作包 2–13 依次清退 AI Runtime 正式表面、固化设置/模块/安全/构建/REST/Manifest 契约，工作包 14A 把 Admin 首屏 Ant 成本降低约 57%–59%，工作包 14B 又统一了 3.0.0 版本事实和发布 ZIP/CI 合同。下一阶段不再横向重构：提交/推送后只核对该提交 SHA 的 Linux CI artifact，再做一次从 ZIP 全新安装并激活 WordPress 的人工验收；两关都通过才进入 tag/Release。SIGKILL/断电不在 shell 可捕获事务保证内，也不建议为了“纯粹去 Ant”继续重写稳定业务控件。
