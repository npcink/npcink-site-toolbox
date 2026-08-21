# 用户评论 REST 接口

> 适用版本：Npcink Site Toolbox 3.3.0 及以上。

::: warning 默认关闭
该功能允许外部客户端代表登录用户发布、修改和删除评论。请仅在站点启用 HTTPS 且确实需要评论客户端时开启。
:::

## 功能说明

开启后，登录用户可以使用 WordPress 原生应用程序密码，通过 JSON REST API 管理自己的评论。接口只允许操作当前认证用户的评论，不会授予全站评论审核权限。

支持的操作包括：

- 分页查看自己的评论；
- 发表顶级评论；
- 修改没有有效回复的评论；
- 将没有有效回复的评论移入回收站；
- 每次批量删除最多 20 条评论。

## 开启功能

1. 使用站点管理员账号进入 WordPress 后台；
2. 打开 **Npcink 站点工具箱 → 内容与页面 → 评论**；
3. 开启 **用户评论 REST 接口**；
4. 根据需要设置 **显示“我的评论”后台页面**；
5. 保存设置。

两个开关的区别：

| 开关 | 作用 |
| --- | --- |
| 用户评论 REST 接口 | 总开关；关闭后接口和“我的评论”菜单都不会注册 |
| 显示“我的评论”后台页面 | 浏览器内兜底界面；关闭后仍可只使用 REST API |

## 创建应用程序密码

应用程序密码由 WordPress 原生功能管理，本插件不会读取或保存密码明文。

1. 使用需要管理评论的用户登录 WordPress；
2. 打开 **用户 → 个人资料**；
3. 找到 **应用程序密码**；
4. 输入客户端名称，例如“Npcink 评论客户端”；
5. 点击 **添加新的应用程序密码**；
6. 立即复制生成的密码。

应用程序密码离开页面后不能再次查看。如果丢失，请撤销旧密码并重新创建。建议每个客户端单独创建一个密码，停用客户端时及时撤销。

## 准备连接信息

下面示例使用 curl。请替换站点地址、WordPress 登录名和应用程序密码：

```bash
API_BASE='https://example.com/wp-json/npcink-site-toolbox/v1'
WP_USER='your-login-name'
APP_PASSWORD='xxxx xxxx xxxx xxxx xxxx xxxx'
```

WordPress 显示的密码空格仅用于方便阅读，curl 可以直接使用完整密码。

::: danger 正式环境必须使用 HTTPS
只有在 Local、WordPress Playground 等隔离的本地开发环境中，才可以使用 HTTP 做接口联调。不要在公网或正式站点上通过 HTTP 发送应用程序密码。
:::

## 查看自己的评论

```bash
curl --user "$WP_USER:$APP_PASSWORD" \
  "$API_BASE/me/comments?page=1&pageSize=20&status=all"
```

可用状态：

- `all`：已通过、待审核和回收站；
- `approved`：已通过；
- `pending`：待审核；
- `trash`：回收站。

响应示例：

```json
{
  "data": [
    {
      "id": 456,
      "postId": 123,
      "postTitle": "示例文章",
      "postUrl": "https://example.com/example-post/",
      "content": "这是一条评论",
      "status": "approved",
      "date": "2026-08-06T08:00:00",
      "expectedHash": "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef",
      "hasReplies": false,
      "canEdit": true,
      "canDelete": true
    }
  ],
  "pagination": {
    "page": 1,
    "pageSize": 20,
    "totalItems": 1,
    "totalPages": 1
  }
}
```

## 发表评论

```bash
curl --user "$WP_USER:$APP_PASSWORD" \
  --header 'Content-Type: application/json' \
  --request POST \
  --data '{"postId":123,"content":"这是一条评论"}' \
  "$API_BASE/me/comments"
```

评论必须发布到已公开且允许评论的文章。评论状态由站点现有审核规则决定。

成功时返回 HTTP `201` 和新评论：

```json
{
  "id": 456,
  "postId": 123,
  "postTitle": "示例文章",
  "postUrl": "https://example.com/example-post/",
  "content": "这是一条评论",
  "status": "approved",
  "date": "2026-08-06T08:00:00",
  "expectedHash": "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef",
  "hasReplies": false,
  "canEdit": true,
  "canDelete": true
}
```

## 修改评论

先通过列表接口读取最新的 `expectedHash`，然后提交修改：

```bash
curl --user "$WP_USER:$APP_PASSWORD" \
  --header 'Content-Type: application/json' \
  --request PATCH \
  --data '{"content":"修改后的评论","expectedHash":"0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef"}' \
  "$API_BASE/me/comments/456"
```

修改成功后，评论会重新进入待审核状态。如果评论已经变化，接口返回 `409`，客户端应重新读取后再操作。

成功响应为 HTTP `200`：

```json
{
  "id": 456,
  "postId": 123,
  "postTitle": "示例文章",
  "postUrl": "https://example.com/example-post/",
  "content": "修改后的评论",
  "status": "pending",
  "date": "2026-08-06T08:00:00",
  "expectedHash": "abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789",
  "hasReplies": false,
  "canEdit": true,
  "canDelete": true
}
```

请保存响应中新的 `expectedHash` 供下次修改使用。

## 删除单条评论

```bash
curl --user "$WP_USER:$APP_PASSWORD" \
  --request DELETE \
  "$API_BASE/me/comments/456"
```

删除只会将评论移入 WordPress 回收站，不会永久删除数据库记录。已有有效回复的评论暂不支持删除。

首次删除成功返回 HTTP `200`：

```json
{"id":456,"status":"deleted"}
```

重复删除已在回收站的评论时，仍返回 HTTP `200`，但状态为 `alreadyDeleted`。

## 批量删除评论

```bash
curl --user "$WP_USER:$APP_PASSWORD" \
  --header 'Content-Type: application/json' \
  --request POST \
  --data '{"commentIds":[456,457,458]}' \
  "$API_BASE/me/comments/batch-delete"
```

每次最多提交 20 个评论 ID。系统逐条检查归属和状态，部分评论失败不会回滚已经成功的评论。

响应为 HTTP `200`，`summary` 是汇总，`results` 是逐条结果：

```json
{
  "summary": {
    "requested": 3,
    "deleted": 3,
    "failed": 0
  },
  "results": [
    {"id": 456, "status": "deleted"},
    {"id": 457, "status": "deleted"},
    {"id": 458, "status": "alreadyDeleted"}
  ]
}
```

`summary.deleted` 表示成功处理的数量，包含状态为 `alreadyDeleted` 的幂等结果。

## 常见错误

| 状态码 | 含义 | 处理方式 |
| --- | --- | --- |
| `401` | 用户名或应用程序密码错误 | 核对登录名、密码和 HTTPS 配置 |
| `404` | 功能未开启、评论不存在或不属于当前用户 | 检查总开关和评论 ID |
| `409` | 评论已有回复、已经变化或文章关闭评论 | 刷新评论状态并根据提示处理 |

## 安全建议

- 只通过 HTTPS 使用应用程序密码；
- 不要把密码写入前端网页、公开仓库或日志；
- 每个应用或设备使用独立密码；
- 客户端停用或设备丢失后立即撤销密码；
- 普通用户不需要 `moderate_comments`、`edit_posts` 或 `manage_options` 权限；
- 如果只使用外部客户端，可以关闭“我的评论”后台兜底页面。
