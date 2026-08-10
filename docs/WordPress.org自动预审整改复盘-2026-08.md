# WordPress.org 自动预审整改复盘

> 日期：2026-08-10
>
> 状态：当前有效
>
> 范围：WordPress.org 提交 ZIP、Plugin Check 与仓库发布门禁

## 一、事件与结论

WordPress.org 对 `npcink-site-toolbox.zip` 的自动预审将提交置为 pending，并指出四类风险：PHP 直接输出 `<script>` 或 `<style>`、请求处理缺少 nonce 或权限检查的可能性、`readme.txt` 中不可直接访问的模板 URL，以及发布包内非 ASCII 文件名。

这不是永久拒绝，而是要求作者修复、重新测试、上传新 ZIP，并在原邮件线程中回复。此次暴露出的主要问题不是现有测试失效，而是本地门禁只证明了功能、结构和部分安全合同，没有完整模拟 WordPress.org 的目录预审规则。

## 二、根因

### 2.1 把 Plugin Check 的零错误当成目录审核通过

Plugin Check 的错误、警告和 WordPress.org 自动预审不是完全相同的规则集。`0 errors` 只能说明该次 Plugin Check 没有错误级结果，不能抵消警告，也不能证明目录团队不会要求整改。

以后必须保存并阅读完整 PCP 输出，逐项判断所有错误和警告。不得只记录错误数量，也不得用 `--ignore-warnings` 隐藏结果。

### 2.2 项目测试保护了“披露存在”，没有验证 URL 可访问

GitHub 外部服务合同测试曾要求 `readme.txt` 包含 API 路径前缀，但 `{owner}/{repository}` 是说明模板，不是可直接访问的公共 URL。WordPress.org 自动检查会尝试请求它，因此得到 404。

外部服务披露应链接到稳定的官方文档或真实公共页面。动态 endpoint 模板只应用普通文字或代码格式说明，不能伪装成可点击链接。

### 2.3 发布校验只检查结构安全，没有检查文件系统兼容性

Vite 会保留导入资源的原始 basename，中文源文件名因此进入构建产物和最终 ZIP。旧发布校验覆盖了单一根目录、路径穿越、符号链接、禁入文件、版本和校验和，但没有检查：

- 路径是否仅使用可移植 ASCII 字符；
- 两个路径是否仅靠字母大小写区分。

最终 ZIP 现在必须拒绝这两种情况。源代码和生成产物中的发布文件均使用英文小写、数字和短横线命名。

### 2.4 代码审查关注了转义，没有统一检查资源加载方式

部分旧模块通过 `wp_head`、`wp_footer`、`admin_head` 或模板直接打印脚本和样式。即使内容固定且已转义，WordPress.org 仍要求通过资源 API 管理依赖、版本和加载时机。

以后处理资源遵守：

- 静态 JS：`wp_register_script()` / `wp_enqueue_script()`；
- 动态内联 JS：先注册或加载 handle，再用 `wp_add_inline_script()`；
- 静态 CSS：`wp_register_style()` / `wp_enqueue_style()`；
- 动态内联 CSS：先注册或加载 handle，再用 `wp_add_inline_style()`；
- 不在 PHP 模板中直接输出 `<script>`、`<style>`、`onclick` 或 `javascript:` URL。

## 三、nonce 与权限判断规则

nonce 防止 CSRF，capability 决定调用者是否有权执行动作，两者不能互相替代。

所有写操作必须同时满足：

1. 从明确字段读取并 `wp_unslash()`；
2. 验证 nonce；
3. 验证与对象匹配的 capability；
4. 验证和清洗输入；
5. 最后才执行写入、删除、远程副作用或敏感读取。

纯只读筛选参数可以不使用 nonce，但必须限制类型、unslash、sanitize，并写明只读理由。收到自动预审的通用 nonce 提醒时，必须搜索全部 `$_GET`、`$_POST`、`$_REQUEST`、AJAX、admin-post 和 REST 写入路径，不能只修邮件点名位置。

## 四、发布前强制门禁

代码或发布内容变化后执行：

```bash
composer links:check
composer test
composer phpstan
pnpm --dir vite test:admin
pnpm --dir vite build
composer release:build
composer release:verify -- npcink-site-toolbox.zip
git diff --check
```

随后必须对最终 `npcink-site-toolbox.zip` 的解压内容运行最新版 Plugin Check。验收记录至少包含：

- PCP 与 WordPress 版本；
- 扫描目标为最终 ZIP，而不是开发仓库；
- error 和 warning 的完整数量；
- 每条剩余结果的文件、规则与判断；
- ZIP SHA-256；
- 激活无致命错误的证据。

如 PCP 存在 error，不得提交。warning 不得自动视为误报；只有给出代码事实、规则依据和人工复核结论后才可保留。

## 五、防回归检查清单

上传前逐项确认：

- PHP 发布文件没有直接输出 `<script>` 或 `<style>`；
- 没有内联事件处理器或 `javascript:` URL；
- 所有写操作均有 nonce 和 capability；
- `readme.txt` 的可点击 URL 都是可公开访问的真实页面；
- ZIP 路径全部为 ASCII，且不存在仅大小写不同的路径；
- `composer release:verify` 已检查上述文件名规则；
- PCP 扫描的是刚构建且 SHA-256 一致的最终 ZIP；
- 完整阅读 PCP 错误和警告，而不是只看汇总数字。

## 六、回滚与维护

本轮整改不改变设置结构、REST 合同或数据存储。若资源加载调整造成显示回归，应只回滚对应模块的 enqueue 改动，不恢复直接输出标签的旧实现；应改用独立静态资源或正确注册的 inline handle 修复。

未来 WordPress.org 再次反馈新规则时，应更新本复盘、发布校验器和对应自动化测试，使一次审核反馈变成永久门禁。

## 七、本轮最终验收证据

2026-08-10 使用仓库唯一发布入口生成最终安装包，并在全新临时 WordPress 环境中安装官方 Plugin Check：

- WordPress：7.0.3；
- Plugin Check：2.0.0；
- 插件激活：成功，无致命错误；
- PCP：`0 errors / 2 warnings`；
- ZIP 条目：228；
- ZIP 大小：1,056,721 bytes；
- SHA-256：`818b4b5a5a96cda1851f4ebcad29a2abc7ec64a3f04e63661f086ef3b43c576d`。

剩余两条 warning 均位于 `admin/partials/performance/media_health/webp_batch.php`，PCP 将插件调用 WordPress 核心过滤器 `wp_generate_attachment_metadata` 和 `intermediate_image_sizes_advanced` 误判为未加插件前缀的自定义 Hook。代码没有注册或发明同名 Hook，因此不通过忽略注释隐藏结果，保留完整证据供复核。

## 八、从历史问题提炼出的开发方法

### 8.1 把发布包当成独立产品，而不是源码目录的副本

开发仓库、构建产物和最终安装包是三个不同对象：

1. 源码门禁证明实现逻辑正确；
2. 构建门禁证明产物可以重现；
3. ZIP 门禁证明用户和 WordPress.org 实际收到的文件完整、可移植、可激活且符合目录规则。

以后所有发布判断都以最终 ZIP 为准。不能因为源码测试、PHPStan 或前端构建通过，就推断安装包也合格；也不能直接对包含测试、文档和依赖的开发仓库运行 PCP 后把结果当成发布证据。

### 8.2 用“三层证据”判断自动扫描结果

面对 PCP 或 WordPress.org 自动预审告警，按以下顺序处理：

1. **规则事实**：扫描器具体命中了什么规则、文件和代码；
2. **运行事实**：相关代码在 WordPress 生命周期中是否真的产生风险或不兼容行为；
3. **目录事实**：即使运行安全，WordPress.org 是否仍明确要求使用特定 API 或文件约定。

只有三层证据都核对后才能判定“真问题”或“误报”。不能因为邮件由 AI 辅助生成就忽略，也不能因为扫描器命中就机械添加 suppress 注释。

### 8.3 修一个实例时建立一类问题的门禁

审核邮件列出的文件只是样例。正确整改不是逐行消除邮件中的十个位置，而是：

- 搜索所有 PHP 直接资源输出；
- 搜索所有请求入口和写操作；
- 扫描整个 ZIP 的文件名；
- 检查全部公开链接；
- 把规则写入测试和发布校验器。

一次反馈至少应产生“实现修复、同类审计、回归测试、维护文档”四项结果。否则下次换一个文件仍会重复犯错。

### 8.4 优先使用 WordPress 生命周期和事实源

资源加载问题的根因不是标签本身，而是绕开了 WordPress 的依赖、版本、加载顺序和条件加载机制。因此修复时应回到正确生命周期：

- 前台资源挂到 `wp_enqueue_scripts`；
- 后台资源挂到 `admin_enqueue_scripts`；
- 独立维护模板先 enqueue，再在文档 head 中打印对应 handle；
- 运行时数据通过 `wp_add_inline_script()` 或 `wp_add_inline_style()` 绑定到已注册 handle；
- 第三方静态脚本直接 enqueue，并声明可审计的版本参数。

同理，外部服务披露以 `readme.txt` 为 WordPress.org 发布事实源，构建文件名以源资源 basename 为事实源，不能只在生成文件上临时改名。

### 8.5 安全审查区分请求类型，不做形式主义

nonce 告警必须结合副作用判断：

- 写入、删除、远程写操作和敏感动作需要 nonce 与 capability；
- REST 写操作需要明确的 `permission_callback` 和资源所有权检查；
- 只读列表筛选通常不需要 nonce，但仍需 unslash、类型限制和 sanitization；
- 返回链接、动态 URL 和外部 endpoint 仍需验证目标和输出上下文。

安全目标是阻止未授权副作用和注入，不是让每一个 `$_GET` 都形式化地带 nonce。

## 九、标准发布工作流

### 9.1 开发阶段

1. 写明变更包、非目标和回滚路径；
2. 修改一个聚焦模块；
3. 添加行为测试和发布合同测试；
4. 运行最窄门禁，快速发现局部回归；
5. 完成后运行全量 PHP、静态分析和相关前端门禁。

### 9.2 打包阶段

1. 从唯一前端工程生成正式产物；
2. 用 `composer release:build` 生成安装包；
3. 用 `composer release:verify` 检查结构、版本、必需文件、禁入路径、ASCII 文件名、大小写冲突和 SHA-256；
4. 确认工作区中的 ZIP 与侧车校验文件一致；
5. 任何发布文件变化后重新生成 ZIP，旧 PCP 结果立即失效。

### 9.3 PCP 阶段

1. 创建干净、一次性的 WordPress 环境；
2. 安装最新版官方 Plugin Check；
3. 解压并激活刚生成的最终 ZIP；
4. 记录 WordPress 与 PCP 版本；
5. 运行完整扫描并保存全部结果；
6. error 必须清零；warning 逐条给出代码事实和处理结论；
7. 扫描后再次核对 ZIP SHA-256，确保提交物未变化；
8. 清理临时容器、数据库、网络和扫描目录。

### 9.4 提交与回复阶段

1. 上传通过验收的同一 ZIP；
2. 回复原审核邮件线程，不新建邮件；
3. 回复保持简短，只说明已完成整改、上传新包及必要澄清；
4. 不向审核员复制长篇变更日志，也不声称“零 warning”等与证据不符的结论；
5. 保存 ZIP 哈希和 PCP 结果，直到本轮审核结束。

## 十、发布决策原则

- **测试通过不是发布完成。** 发布完成必须包含最终产物验证。
- **零错误不是审核批准。** 自动扫描、目录规则和人工复核是互补关系。
- **警告不是噪声。** 能修的修，确认误报的保留事实依据。
- **生成物问题从源头修。** 不直接手工编辑 `dist` 或 ZIP。
- **一次修复形成永久规则。** 反馈应进入测试、脚本、AGENTS 和复盘文档。
- **证据必须绑定具体产物。** 版本、条目数、大小、哈希和 PCP 结果缺一不可。
