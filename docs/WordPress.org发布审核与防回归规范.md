# Npcink Site Toolbox WordPress.org 发布审核与防回归规范

> 状态：当前有效
>
> 适用范围：WordPress.org 首次提交、重新提交、版本发布和发布门禁维护
>
> 证据台账：[WordPress.org 自动预审整改复盘](WordPress.org自动预审整改复盘-2026-08.md)
>
> 架构决策：[ADR-0005：以精确发布包为 WordPress.org 验收对象](decisions/0005-exact-artifact-wordpress-org-release-gate.md)

## 1. 文档定位

本文是长期执行规范，回答“以后每次发布必须怎么做”。历史邮件、阶段性判断、旧 ZIP 哈希和逐次扫描结果保留在复盘文档中，本文不复制全部原始输出，也不覆盖历史事实。

规则优先级如下：

1. `AGENTS.md` 定义每次开发会话必须遵守的仓库纪律；
2. 本文定义 WordPress.org 发布与审核的长期规范；
3. `docs/构建与发布指南.md` 提供具体构建和交付操作；
4. `docs/WordPress.org自动预审整改复盘-2026-08.md` 保存事件时间线与证据；
5. 某次发布记录只对其明确绑定的版本、提交和 ZIP 哈希有效。

## 2. 历史问题归纳

### 2.1 时间线

| 时间 | 事件 | 暴露的问题 | 形成的长期规则 |
|---|---|---|---|
| 2026-08-08 | WordPress.org 自动预审 | PHP 直接输出资源标签、请求鉴权风险、无效模板 URL、非 ASCII 产物名 | 资源 API、安全入口审计、真实链接合同、ZIP 路径门禁 |
| 2026-08-10 | 第一轮整改与 PCP 2.0.0 | 自动工具结果被过度解释为“可以发布” | PCP 结果只证明该版本扫描器检查到的规则，不等于目录批准 |
| 2026-08-14 | 人工审核 | 压缩资源缺少与提交包对应的公开源码；`dataLocal`、`ets_strings`、`ts_ets_*` 等名称过于通用 | 可读源码必须绑定精确 tag/commit；跨语言全局标识必须进入身份合同 |
| 2026-08-17 | PCP 2.1.0 复验 | 新版 `OffloadedContent` 规则发现 CDN URL 改写和连通性修复表面 | 每次发布安装最新版 PCP；新规则命中不能沿用旧结论 |
| 2026-08-17 | 3.3.1 最终验收 | 精确 ZIP 为 `0 errors / 2 known warnings` | 错误阻断；已审阅 warning 窄允许；其他 warning 阻断 |
| 2026-08-17 | 自动化收口 | 手工 PCP、WP_DEBUG 和清理容易被漏跑 | CI 强制运行 `composer release:wordpress-org-check` |
| 2026-08-21 | 3.3.2 自动复核 | 输出型过滤器、动态 gettext 和 `Tested up to` 目录要求再次暴露覆盖缺口 | 过滤器/字面量 gettext 合同进入测试；PCP header error 也必须阻断；实际运行版本与目录 header 分开记录 |

### 2.2 根因不是“少修了几个文件”

历史问题来自五个方法层面的缺口：

1. **把源码目录当成发布产品。** 开发测试通过，不能证明 ZIP 内实际文件合规。
2. **把某一版扫描器当成永久规则全集。** PCP 2.0.0 的零错误不能覆盖 PCP 2.1.0 或人工审核的新规则。
3. **只修邮件样例，没有先定义问题类别。** 审核点名一个全局变量时，必须同时检查 AJAX action、handle、nonce、Cookie、图片尺寸和其他跨插件标识。
4. **存在说明，但缺少可验证的对应关系。** 仓库首页存在，不代表审核员能找到与 ZIP 相同的源码。
5. **依赖人工记忆。** 没有进入脚本、测试、CI 和仓库规则的经验，下一次仍会被跳过。

## 3. 核心发布模型

一次 WordPress.org 发布包含三个不同对象：

| 对象 | 证明什么 | 不能替代什么 |
|---|---|---|
| 源码提交 | 实现、测试和可读源码的事实 | 不能代替最终 ZIP 验收 |
| 构建产物 | 前端编译和打包过程可重现 | 不能代替干净 WordPress 运行 |
| 最终 ZIP | WordPress.org 和用户实际收到的内容 | 不能由后续重新打包的“相似 ZIP”代替 |

最终发布判断必须绑定四元组：

```text
版本 + Git commit/tag + ZIP SHA-256 + PCP/WP_DEBUG 验收记录
```

任一进入 ZIP 的文件变化，都会产生新的验收对象。旧哈希、旧激活记录和旧 PCP 结果立即失效。

## 4. 开发阶段规范

### 4.1 先写变更信封

涉及 WordPress.org 审核、发布包、外部服务或公共标识时，编辑前必须记录：

- 聚焦模块和目标版本；
- 预期改变与明确非目标；
- 触及的公共合同；
- 预计进入 ZIP 的文件；
- 不得改变的模块和数据边界；
- 必跑测试、真实环境和发布门禁；
- 回滚方式；
- 是否需要更新 tag、公开源码和 WordPress.org readme。

### 4.2 从样例扩展到问题类别

收到审核反馈后，不得只修改邮件中的示例行。必须进行同类扫描：

| 点名问题 | 必须扩展检查的范围 |
|---|---|
| 通用函数或变量名 | PHP 函数/类/常量、Option、Transient、REST namespace、AJAX action、浏览器全局、localized object、handle、nonce、Cookie、图片尺寸 |
| 直接 `<script>/<style>` | 所有发布 PHP、内联事件、`javascript:` URL、动态 CSS/JS 输出 |
| nonce 或权限 | `$_GET`、`$_POST`、`$_REQUEST`、AJAX、admin-post、REST 写入、删除和远程副作用 |
| 无效 URL | README、readme、内置帮助、docs-site、外部服务披露和示例链接 |
| 不可移植文件名 | 整个 ZIP、构建资源 basename、大小写碰撞和非 ASCII 路径 |
| 压缩源码不可读 | 每一个压缩入口、对应源码目录、锁文件、构建命令和精确 tag/commit |
| 远程内容规则 | CDN 改写、脚本镜像、字体镜像、代理、连通性检测和自动替换建议 |

### 4.3 代码规则

#### 资源加载

- 静态 JS/CSS 使用 WordPress enqueue API；
- 动态内容先注册稳定 handle，再使用 `wp_add_inline_script()` 或 `wp_add_inline_style()`；
- 不在发布 PHP 中直接输出资源标签、内联事件或 `javascript:` URL；
- 维护页等独立文档也必须先 enqueue，再在正确位置打印 handle。

#### 请求安全

- nonce 证明请求意图，capability 证明操作权限，两者不可互相替代；
- 写入、删除、远程写操作和敏感读取必须同时验证 nonce、权限、对象所有权和输入；
- 只读筛选可以不使用 nonce，但必须 unslash、限制类型、sanitize，并留下只读理由；
- REST 每个 endpoint 都必须有 `permission_callback`。

#### 公共标识

- 插件自有 PHP 和跨请求标识使用 `npcink_site_toolbox` / `Npcink_Toolbox` / `NPCINK_SITE_TOOLBOX` 家族；
- 浏览器 API 使用 `npcinkSiteToolbox` 家族；
- WordPress 核心函数和核心 hooks 保持官方名称，不为了消除误报而伪造前缀。

#### 外部资源和服务

- WordPress.org 插件不得通过 URL 改写把核心、插件或主题静态资源转发到未被产品边界证明必要的镜像；
- 外部服务必须有明确触发条件、数据流向、存储位置、失败行为和公开条款链接；
- 动态 endpoint 示例不得伪装成可以访问的 Markdown 链接；
- 没有用户和兼容负担时，违反目录边界的能力应直接退役，不保留隐藏入口或休眠代码。

#### 过滤器返回值与国际化

- `the_content`、`the_title`、`the_excerpt` 等输出型过滤器的回调必须按最终 HTML 上下文处理动态值：文本用 `esc_html()`，属性用 `esc_attr()`，URL 用 `esc_url()`；需要保留文章 HTML 时，对最终返回值使用明确的 `wp_kses_post()` 边界。
- 不得依赖 PHPCS 对 `WordPress.WP.I18n.NonSingularStringLiteralText` 的逐行忽略来满足目录规则。gettext 的 msgid、context 和 text domain 必须在源码中可静态发现；固定 Registry/隐私树也必须通过字面量翻译映射实现。
- 每次发布合同测试都要扫描全部输出型过滤器和全部 PHP gettext 调用，而不是只验证邮件列出的示例文件。

## 5. 测试与证据分层

### 5.1 第一层：源码合同

至少覆盖：

- 版本、slug、文本域和公共前缀一致性；
- 所有 REST endpoint 的权限合同；
- 写操作 nonce/capability；
- 发布 PHP 不直接输出资源标签；
- 通用全局名和已退役标识不得重新出现；
- 配置 Schema、Registry、搜索索引和前端生成合同一致；
- 文档链接目标存在。
- 输出型过滤器返回值的上下文转义和最终 HTML 清洗存在源码合同。
- 动态 gettext 参数为零，固定元数据通过字面量翻译映射提取。
- 每个远程服务调用都能在 `readme.txt` 找到用途、触发条件、发送数据、Terms 和 Privacy 对应条目。

### 5.2 第二层：构建合同

- 前端类型、lint、测试和正式构建通过；
- dist 只包含预期入口；
- 翻译 POT、PO、MO、JSON 与最新源码一致；
- 构建不能通过手工改 dist 或 ZIP 修补问题。

### 5.3 第三层：ZIP 合同

执行：

```bash
composer release:build
composer release:verify -- npcink-site-toolbox.zip
```

验证器必须检查：

- 单一插件根目录；
- 必需运行文件和构建产物；
- 禁入源码、测试、依赖和开发文档；
- 版本和 Stable tag 一致；
- 路径为可移植 ASCII；
- 不存在仅大小写不同的路径；
- `readme.txt` 包含与版本匹配的公开源码 tag、构建文件和命令；
- ZIP 与 `.sha256` 侧车一致。

### 5.4 第四层：真实 WordPress.org 门禁

执行：

```bash
composer release:wordpress-org-check
```

该门禁必须：

1. 创建一次性数据库、WordPress、卷和网络；
2. 安装刚构建的精确 ZIP，而不是挂载源码目录；
3. 在激活前开启 `WP_DEBUG`、`WP_DEBUG_LOG`，关闭页面显示；
4. 通过真实 HTTP 完成前台、管理员登录、插件列表和插件设置页请求；
5. 验证后台确实已登录，插件页面确实渲染应用根节点；
6. 要求 debug log 为空；
7. 安装 WordPress.org 官方最新版 Plugin Check；
8. 完整输出 PCP 结果；
9. error 数量大于零立即失败；
10. 任何不在窄允许项中的 warning 立即失败；
11. 扫描前后重新计算 ZIP SHA-256，发生变化立即失败；
12. 无论成功或失败都清理临时资源。

## 6. PCP 结果处理规范

### 6.1 Error

- PCP error 一律阻断提交；
- 不得传入 `--ignore-warnings`，也不得使用 blanket ignore、宽泛 suppress 或降低扫描范围绕过；
- 如果能力本身违反目录边界，应收缩产品面，而不是只改写代码形态。

### 6.2 Warning

每条 warning 必须记录：

- 文件和规则；
- 命中的具体 API 或标识；
- 代码事实；
- WordPress/目录规则事实；
- 修复、接受或退役的决定；
- 允许项的最窄匹配条件。

当前只接受 `admin/partials/performance/media_health/webp_batch.php` 中两个 WordPress Core hook 的前缀误报：

- `wp_generate_attachment_metadata`；
- `intermediate_image_sizes_advanced`。

允许项由文件、规则代码和 hook 名共同约束。行号变化不应误伤正常重构；文件、规则或 hook 变化必须重新复核。已知 warning 消失属于改进，可以通过；新增或未审阅 warning 必须阻断。

## 7. 公开源码与 tag 顺序

发布 `X.Y.Z` 时采用以下顺序：

1. 在 `readme.txt` 中预先写入 `vX.Y.Z` 的源码、构建文件和 checkout 命令；
2. 完成版本提交，并在该提交上生成唯一 ZIP；
3. 运行全部源码、构建、ZIP、WP_DEBUG 和 PCP 门禁；
4. 推送该提交并创建 `vX.Y.Z` tag；
5. 匿名访问 tag、Admin 源码、Count 源码和构建文件，必须全部成功；
6. 再次核对本地 ZIP 哈希，禁止因为 tag 已创建而重新构建；
7. 上传已扫描的同一 ZIP。

如果验证后修改了任何进入 ZIP 的文件，必须创建新提交、重新构建、重新扫描，并根据版本策略决定是否使用新版本号。不得移动已经对外发布且可能被引用的 tag 来掩盖内容变化。

## 8. CI 与人工职责

CI 负责可重复事实：

- PHP/前端质量门禁；
- 构建和 ZIP 合同；
- WP_DEBUG HTTP 冒烟；
- 最新 PCP 扫描；
- 已知 warning 窄允许；
- 哈希不变和环境清理。

发布者仍需人工负责：

- 确认审核邮件是否出现新语义要求；
- 判断新 warning 是否是真问题；
- 确认 tag 和源码链接匿名可访问；
- 从正确提交的 CI run 下载 artifact；
- 上传同一 ZIP；
- 在原审核邮件线程简短回复。

自动化不能证明 WordPress.org 一定批准，也不能替代人工阅读完整输出。

## 9. 标准命令顺序

```bash
git status --short --branch
composer settings-contract:check
composer links:check
composer test
composer phpstan
pnpm --dir vite typecheck
pnpm --dir vite test
pnpm --dir vite build
composer i18n:pot
composer i18n:build
composer release:build
composer release:verify -- npcink-site-toolbox.zip
composer release:wordpress-org-check
git diff --check
shasum -a 256 npcink-site-toolbox.zip
```

只运行与变更相关的窄门禁用于快速反馈；正式 WordPress.org 候选必须完成全部发布门禁。

## 10. 发布证据记录模板

```markdown
## X.Y.Z WordPress.org 发布证据

- Git commit：
- Git tag：
- WordPress：
- PHP：
- Plugin Check：
- 插件激活：成功/失败
- WP_DEBUG HTTP：前台 / 后台 / 插件页
- debug.log：空/非空
- PCP errors：
- PCP warnings：
- 已接受 warning 与理由：
- ZIP 条目：
- ZIP 大小：
- ZIP SHA-256：
- 公开源码链接：已匿名验证/未验证
- 临时资源：已清理/未清理
```

不得只写“测试通过”或“PCP 通过”。缺少版本、计数和哈希的记录不能作为发布证据。

## 11. 常见错误思路

| 错误思路 | 为什么不成立 | 正确做法 |
|---|---|---|
| “源码测试都绿了，ZIP 应该没问题” | 排除规则、构建名和压缩内容只存在于 ZIP | 独立验证精确 ZIP |
| “上次 PCP 是零错误” | PCP 和目录规则会更新 | 发布时安装最新版 PCP |
| “仓库公开就够了” | 默认分支可能落后于提交包 | 链接到精确 tag/commit |
| “warning 都是噪声” | 新 warning 可能是新规则或真实风险 | 已知项窄允许，其他项阻断 |
| “邮件只列了四个文件” | 审核示例通常不是完整清单 | 从实例扩展到问题类别 |
| “为了兼容先把旧功能藏起来” | 休眠代码仍会扩大审核和维护面 | 无用户阶段直接删除违规能力 |
| “出错后手工改 ZIP 最快” | 产物不可重现，源码与 ZIP 漂移 | 从源代码或构建配置修复 |
| “CI 成功就一定批准” | 人工审核还检查产品语义和目录政策 | 保留人工复核与简洁说明 |

## 12. 从本次历史提炼出的开发方法

### 12.1 事实优先

先确认审核命中了哪个文件、规则和实际发布包，再判断问题。不要因为反馈来自自动工具就忽略，也不要因为扫描命中就机械 suppress。

### 12.2 抓主要矛盾

本次主要矛盾不是单个变量名，而是“开发事实与审核事实没有绑定到同一个产物”。建立精确 ZIP 证据链后，命名、源码、路径和 PCP 才能进入同一验收对象。

### 12.3 一次反馈形成永久门禁

每一类审核反馈至少产生四项结果：实现修复、同类扫描、回归合同、维护文档。只有邮件回复而没有门禁，不能算完成。

### 12.4 工具升级是输入变化

PCP 版本升级、WordPress 核心升级或 GitHub Actions 镜像变化都可能暴露新问题。门禁因此失败是有价值的信息，不应为了“恢复绿色”先放宽规则。

### 12.5 兼容性成本必须有服务对象

没有用户、数据或第三方调用者时，为违规功能保留兼容层没有收益。直接清退可以同时减少产品风险、审核面和测试矩阵。

### 12.6 验证工具本身也要被验证

本次自动化在实跑中发现 MariaDB `ping` 早于业务账号可用。最终改为使用真实 WordPress 数据库账号执行 `SELECT 1`。发布工具不能只通过 Shell 语法和字符串合同，必须实际运行成功，并验证失败时能清理资源。

## 13. 收尾检查表

- [ ] 变更信封和非目标已记录
- [ ] 邮件样例已扩展为同类扫描
- [ ] 实现、合同测试和文档同步
- [ ] 公共源码链接绑定目标 tag
- [ ] 完整源码、前端和链接门禁通过
- [ ] 唯一 ZIP 已构建并通过结构/路径/版本校验
- [ ] WP_DEBUG 前台和真实后台请求通过，日志为空
- [ ] 最新官方 PCP errors 为 0
- [ ] 所有 warnings 均为已记录窄允许项
- [ ] 扫描前后 ZIP SHA-256 一致
- [ ] tag 与 commit 对应，公开链接匿名可访问
- [ ] 临时容器、卷、网络和测试数据已清理
- [ ] 暂存、提交、推送和远端状态已核对
