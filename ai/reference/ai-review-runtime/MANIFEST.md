# 快照清单

## 元数据

- 归档日期：2026-07-15
- 来源仓库：`wp-magick-toolbox`
- 来源提交：`b42f681fce6bc17b09fde44f64d7c343caefb4d8`
- 许可：GPL-2.0，详见仓库根目录 `LICENSE`
- 状态：REFERENCE ONLY，不参与加载、构建、测试或发布
- 敏感数据：未归档真实设置值、API Key、Secret、Header 或评论日志

SHA-256 用于确认快照内容和来源，不代表代码已通过安全审核。

## 文件映射与校验

| 原始相对路径 | 快照相对路径 | 用途 | 来源 SHA-256 | 快照 SHA-256 | 状态 |
| --- | --- | --- | --- | --- | --- |
| `admin/partials/ai_review/provider/interface.php` | `snapshot/backend/provider-interface.php` | Provider 接口 | `f41fc7d7da1ee8f58083a26c2443a9906ed69ade85a4cc3beeeac55f48123609` | `f41fc7d7da1ee8f58083a26c2443a9906ed69ade85a4cc3beeeac55f48123609` | 原样 |
| `admin/partials/ai_review/provider_manager.php` | `snapshot/backend/provider-manager.php` | Provider 选择与本地回退 | `239a5a8b98ad641bfb82d9ea0d87efdf24cc7162cc28cc0b2321e9043cd8e076` | `239a5a8b98ad641bfb82d9ea0d87efdf24cc7162cc28cc0b2321e9043cd8e076` | 原样 |
| `admin/partials/ai_review/provider/deepseek.php` | `snapshot/backend/deepseek.php` | DeepSeek 适配器 | `b9ec0c00b7d08304aea59a5199254d554530442a25e7168b62f3133a721bb2cf` | `b9ec0c00b7d08304aea59a5199254d554530442a25e7168b62f3133a721bb2cf` | 原样 |
| `admin/partials/ai_review/provider/aliyun.php` | `snapshot/backend/aliyun.php` | 阿里云适配器与签名 | `3b1b6a267871e5c7dad4c017181a5a8dfe2e5453ebb18b05d9c3a3b5569cddde` | `3b1b6a267871e5c7dad4c017181a5a8dfe2e5453ebb18b05d9c3a3b5569cddde` | 原样 |
| `admin/partials/ai_review/provider/custom_api.php` | `snapshot/backend/custom-api.php` | 自定义 HTTP Provider | `723b4ef0fcbf82c27e279f4f3a6fd8f309f881759e865eaa113f7a1d8a29fe9e` | `723b4ef0fcbf82c27e279f4f3a6fd8f309f881759e865eaa113f7a1d8a29fe9e` | 原样 |
| `admin/partials/ai_review/provider/local_rules.php` | `snapshot/backend/local-rules.php` | 本地关键词和正则规则 | `333771ad16c5cca4710ea276ca1e2de24ff3adf373584b22d96e56b5805a0b4b` | `333771ad16c5cca4710ea276ca1e2de24ff3adf373584b22d96e56b5805a0b4b` | 原样 |
| `admin/partials/ai_review/index.php` | `snapshot/wordpress-integration/comment-review.php` | 评论 Hook、日志和 REST | `8e183ec522b646463bf6e064b11587909ce2a71655123bbaab6c59385d69f9de` | `8e183ec522b646463bf6e064b11587909ce2a71655123bbaab6c59385d69f9de` | 原样 |
| `vite/admin/src/components/ai_review/index.tsx` | `snapshot/admin-ui/index.tsx` | 配置/日志页切换 | `6a2a9a32d1ae8e78293aba4b53da4e30b9d068482ed960481d9e48a419b937e5` | `6a2a9a32d1ae8e78293aba4b53da4e30b9d068482ed960481d9e48a419b937e5` | 原样 |
| `vite/admin/src/components/ai_review/provider_config.tsx` | `snapshot/admin-ui/provider-config.tsx` | Provider 配置与测试 UI | `d92d86aaa5c7d27e5167162796a69b0a78fc91a245cf8d55debf274ee6e2bfea` | `d92d86aaa5c7d27e5167162796a69b0a78fc91a245cf8d55debf274ee6e2bfea` | 原样 |
| `vite/admin/src/components/ai_review/audit_log.tsx` | `snapshot/admin-ui/audit-log.tsx` | 日志和人工复核 UI | `07cb88834b5dae7798081d1e23071f98ebc67b8d38ebda265fd5247b73f49dbe` | `dd3461284267cdbd14ed4e9d6b82f2c8fc7be95466c3b9905ebfac3836cb5131` | 仅补文件末尾换行 |

## 有意不复制的内容

- 配置 Option 的真实值、设置导出和数据库备份；
- 审核日志及其中的评论、姓名、邮箱；
- `autoload.php`、模块 registry、tiers 和全局配置 Schema 的整文件副本；
- 公共 REST 客户端中与其他 Toolbox 功能混合的整文件；
- `node_modules`、构建产物、锁文件和任何运行依赖；
- 真实 API Key、AccessKey、Secret、自定义 Authorization Header。

这些集成事实已在 `DESIGN.md` 和 `PORTING.md` 中描述。没有复制外围注册代码，是为了避免本目录成为可运行的第二项目。

## 本地校验

在仓库根目录运行：

```bash
shasum -a 256 \
  ai/reference/ai-review-runtime/snapshot/backend/*.php \
  ai/reference/ai-review-runtime/snapshot/wordpress-integration/*.php \
  ai/reference/ai-review-runtime/snapshot/admin-ui/*.tsx
```

输出应与表中的“快照 SHA-256”一致。若文件有意修改，应把它视为新的参考版本，并同时更新来源说明、状态和风险文档；不要静默覆盖历史校验值。
