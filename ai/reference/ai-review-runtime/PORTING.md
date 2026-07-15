# 移植指南

> 不要把 `snapshot/` 整体复制到另一个插件后直接启用。它是素材库，不是 SDK。

## 先确定产品策略

移植前必须明确审核失败时的产品语义：

1. **异步标记模式（推荐）**：评论先进入待审核状态，后台任务调用 Provider，得到结果后再放行或交给人工处理。外部服务故障不会阻塞访客请求。
2. **同步硬阻止模式**：只有在业务确实要求提交前决策时使用。必须设置很短的时间预算、明确失败策略，并评估 Provider SLA；不能沿用 15 秒阻塞。
3. **仅本地规则模式**：不需要 Provider Runtime，适合简单、确定性的站点规则。

不要在同一个默认流程中含糊混合“服务错误”“安全通过”和“待人工复核”。

## 推荐的目标边界

建议拆成五层：

```text
WordPress Adapter
  -> Review Application Service
      -> Policy（mark/block/failure handling）
      -> Provider Interface
          -> DeepSeek / Aliyun / allowlisted HTTP adapter
      -> Review Repository
      -> Audit/Event Logger
```

- WordPress Adapter 只处理 Hook、评论 ID 和状态转换。
- Application Service 编排审核，不直接读全局 Option。
- Policy 明确网络错误、超时、解析错误和低置信度的处理。
- Provider 只做请求、响应校验和归一化。
- Repository 使用稳定 UUID/数据库主键，不使用数组下标。
- 管理 UI 只调用最小化 REST 契约，不接触明文密钥。

## 分步移植

### 1. 建立新的契约

在目标插件命名空间下重新定义接口。除审核结果外，至少区分：

- `approved`、`rejected`、`needs_review`；
- `provider_error`、`timeout`、`invalid_response`；
- Provider 请求 ID、耗时、模型或规则版本；
- 可重试与不可重试错误。

不要继续使用全局 `MaBox_*` 类名或单例 Manager。

### 2. 只迁移所需 Provider

从 `snapshot/backend/` 选择必要的算法，不要默认携带四个 Provider。对每个外部 Provider：

- 对照供应商当前官方文档重新确认端点、签名和响应结构；
- 使用固定允许的 HTTPS Host，避免用户提供任意 Endpoint；
- 设置连接与总耗时预算、响应体大小上限和重试边界；
- 严格校验 JSON 类型、必填字段、枚举和置信度范围；
- 不把原始供应商错误或敏感响应直接回显给浏览器。

### 3. 重做凭据存储

- 密钥只在服务器端读取；优先环境变量、常量或专用密钥服务。
- REST 只返回 `configured: true/false` 和必要掩码。
- 更新语义区分“保持原值”“替换”“清除”，空字符串不能意外覆盖。
- 权限要求、nonce、审计日志和错误信息应分别测试。
- 禁止复制 WordPress 设置导出、数据库 Option、真实 Authorization Header 或真实评论日志到本目录或目标仓库。

### 4. 选择 WordPress 集成方式

异步模式建议：

1. 评论进入待审核；
2. 以评论 ID 创建幂等任务；
3. 后台 Worker 调用 Provider；
4. 按稳定记录 ID 更新评论与审核记录；
5. 失败进入可见的待处理队列，而不是自动放行。

如果必须同步，至少要有短超时、并发限制、断路器和明确的 `needs_review` 结果。不要直接复制 `preprocess_comment` 内 15 秒 HTTP 调用。

### 5. 重做日志与人工复核

- 使用稳定 ID，不用分页数组的当前位置。
- 记录评论 ID、判定、Provider、版本、耗时、时间和人工动作。
- 评论正文和邮箱属于个人信息，应有保留期、删除路径和最小权限。
- 人工动作必须幂等，并验证记录仍处于可处理状态。

### 6. 重做管理界面

归档的 React 组件依赖 Ant Design、`DataContext`、`SettingsSection`、`FeatureSwitch` 和原仓 REST 客户端，不能独立使用。目标界面应：

- 只显示密钥是否已配置；
- 让 Provider 测试走服务器端保存配置，不在请求体重复上传整套密钥；
- 对加载、失败、无记录和权限不足提供明确状态；
- 通过稳定审核记录 ID 操作；
- 对清空、拒绝等动作使用能力检查、nonce 和审计记录。

## 自定义 API 的最低约束

如果目标插件确实需要自定义 HTTP Provider，至少应：

- 仅允许 HTTPS；
- 使用管理员配置的 Host allowlist；
- 拒绝 localhost、回环、链路本地、私网、云元数据 IP 和非标准解析结果；
- 对每次重定向重新验证目标；
- 固定允许的请求方法与 Header 名单；
- 禁止浏览器提交任意 Authorization Header 模板；
- 对 DNS rebinding、响应体大小和超时设置防护。

仅调用 `esc_url_raw()` 不足以阻止 SSRF。

## 最小验收清单

- Provider 单元测试覆盖成功、超时、非 2xx、畸形 JSON、缺字段和边界置信度。
- 失败策略测试证明异常不会伪装成安全通过。
- SSRF 测试覆盖 IPv4/IPv6 私网、回环、重定向和 DNS 变化。
- 密钥从未出现在页面源代码、REST GET 响应、日志、异常或构建产物。
- 评论提交延迟有预算和监控；异步任务幂等、可重试、可观察。
- 审核记录使用稳定 ID，并发新增记录不会导致误操作。
- 数据保留、导出和删除策略符合目标插件的隐私说明。
- 目标插件自己的 PHP、静态分析、前端、WordPress 激活和发布 ZIP 门禁全部通过。

完成迁移后，应在目标插件中保存新的 ADR 和测试，而不是继续把本快照当作共享运行库。
