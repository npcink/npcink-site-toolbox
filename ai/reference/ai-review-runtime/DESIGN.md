# 原实现设计说明

> 本文描述归档代码“当时如何工作”，不是当前 Toolbox 架构，也不是生产推荐实现。

## 目标与范围

原模块希望用统一 Provider 接口审核 WordPress 评论，并在没有外部 Provider 配置时降级到本地关键词与正则规则。它同时提供：

- 评论提交时审核；
- DeepSeek、阿里云内容安全、自定义 HTTP API 和本地规则四种实现；
- 标记待审核或直接阻止两种处理方式；
- 选项表中的有限审核日志；
- 管理端测试、日志查询、人工通过/拒绝和清空日志。

## 组件职责

| 组件 | 原职责 | 可迁移价值 |
| --- | --- | --- |
| `provider-interface.php` | 定义 `review`、`get_name`、`is_available` | 统一适配器概念可保留 |
| `provider-manager.php` | 选择 Provider，不可用时回退本地规则 | 路由和能力探测概念可保留 |
| `deepseek.php` | 构造审核提示词并解析 JSON | 提示词/归一化思路可参考 |
| `aliyun.php` | 签名并调用内容安全接口 | 签名流程只可作为历史参考 |
| `custom-api.php` | 用 URL、Header 和 Body 模板调用任意 API | 扩展性思路可参考，当前安全模型不可保留 |
| `local-rules.php` | 关键词和正则匹配 | 可作为低延迟前置规则或离线降级 |
| `comment-review.php` | WordPress Hook、日志、REST 和处置策略 | 展示 WordPress 集成点，但职责过多 |
| `provider-config.tsx` | Provider 与密钥配置、在线测试 | 表单字段清单可参考，密钥数据流不可保留 |
| `audit-log.tsx` | 日志分页、详情和人工处理 | 人工复核工作流可保留 |

## 原始数据流

```text
preprocess_comment
  -> MaBox_Ai_Review::review_comment
  -> Provider Manager 选择配置的 Provider
  -> Provider 同步审核（最长 15 秒）
  -> 统一结果 is_safe/confidence/reason/risk_level
  -> 写入 mabox_ai_review_log option
  -> 安全：继续提交
     不安全 + mark：comment_approved = 0
     不安全 + block：wp_die(403)
```

Provider 不可用时，Manager 会创建本地规则引擎。需要注意：本地规则自身只有在 `local_rules_enabled` 为真时才报告可用，但 Manager 回退时不会再次检查这一条件。

## Provider 契约

所有 Provider 返回下列形状：

```php
array(
    'is_safe'    => true,
    'confidence' => 0.0,
    'reason'     => '',
    'risk_level' => 'safe', // safe | medium | high
)
```

这个“供应商响应归一化”思路值得保留，但目标插件应增加：明确错误类型、Provider 请求 ID、耗时、规则/模型版本、可重试状态和原始响应的安全摘要。传输错误不能伪装成 `is_safe=true`。

## 历史配置字段

| 类别 | 字段 |
| --- | --- |
| 总体 | `enabled`、`provider`、`mode`、`strict_mode` |
| DeepSeek | `deepseek_api_key`、`deepseek_api_url`、`deepseek_model` |
| 阿里云 | `aliyun_access_key`、`aliyun_secret_key`、`aliyun_region` |
| 自定义 API | `custom_api_url`、`custom_api_method`、`custom_api_headers`、`custom_api_body_template` |
| 本地规则 | `local_rules_enabled`、`local_keywords`、`local_regex` |
| 日志 | `log_enabled`、`log_max_entries` |

这里只记录字段名，不保存任何实例值。目标插件不得照搬“整个配置对象送到浏览器”的数据流。

## 历史 REST 表面

原模块在 `mabox/v1` 下注册：

- `GET /ai-review/logs`
- `POST /ai-review/review/{index}`
- `POST /ai-review/clear-logs`
- `POST /ai-review/test`

四个端点使用 `manage_options` 权限检查。日志项通过数组位置而非稳定 ID 操作；并发新增日志后，位置可能变化，因此该契约不应复制。

## 可保留与应重建

可以保留的思路：

- 小而稳定的 Provider 接口；
- 供应商响应归一化；
- 本地规则作为低成本预筛选；
- 明确区分“标记待审核”和“直接阻止”；
- 审核结果进入人工复核与审计界面。

必须重建的部分：

- 同步评论请求中的外部调用；
- 错误或解析失败时默认安全；
- 浏览器持有密钥和任意 Header；
- 任意自定义 URL 请求；
- 用单个 Option 保存所有日志；
- 用数组下标标识审核记录；
- WordPress Hook、Provider、日志、REST 集中在一个类中的职责边界。
