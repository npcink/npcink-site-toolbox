# AI 评论审核 Runtime 参考快照

> **REFERENCE ONLY / 仅供参考。禁止从本目录加载代码，禁止直接投产。**

本目录保存 WP Magick Toolbox 曾使用的 AI 评论审核 Provider Runtime、WordPress 集成和管理界面。归档的目的，是让后续其他插件能够研究或有选择地移植其中的接口设计与核心实现；它不是当前 Toolbox 的产品能力，也不是一个需要在本仓维护的第二项目。

## 归档信息

- 归档日期：2026-07-15
- 来源仓库：`wp-magick-toolbox`
- 来源提交：`b42f681fce6bc17b09fde44f64d7c343caefb4d8`
- 来源许可：GPL-2.0；复制或派生代码时应继续遵守仓库根目录 `LICENSE`
- 完整性记录：见 `MANIFEST.md`

归档只复制仓库中的代码和设计事实，没有复制 WordPress 数据库、设置导出、日志样本或任何真实 API Key。以后也不得向本目录写入真实凭据。

## 权威边界

当前插件代码、仓库根目录 `AGENTS.md`、已接受的 ADR 和当前产品文档，全部高于本快照。发生冲突时，以这些当前资料为准。

本快照：

- 不定义 Toolbox 当前产品边界；
- 不定义当前 REST、设置 Schema 或安全契约；
- 不应被 autoload、Composer、Vite、测试或 CI 当作源码；
- 不提供依赖、Bootstrap、构建脚本或可运行入口；
- 可以保留历史实现缺陷，以便理解原始思路，但缺陷不构成推荐方案。

## 内容

```text
ai/reference/ai-review-runtime/
├── README.md
├── DESIGN.md
├── PORTING.md
├── SECURITY.md
├── MANIFEST.md
└── snapshot/
    ├── backend/
    │   ├── provider-interface.php
    │   ├── provider-manager.php
    │   ├── deepseek.php
    │   ├── aliyun.php
    │   ├── custom-api.php
    │   └── local-rules.php
    ├── wordpress-integration/
    │   └── comment-review.php
    └── admin-ui/
        ├── index.tsx
        ├── provider-config.tsx
        └── audit-log.tsx
```

- `DESIGN.md`：解释原实现的组件、数据流和可复用思路。
- `PORTING.md`：说明如何在另一个插件中重新设计并移植，而不是原样复制。
- `SECURITY.md`：列出原实现中必须先解决的风险。
- `MANIFEST.md`：记录每个快照文件的原始路径、用途和 SHA-256。
- `snapshot/`：保留核心实现；除 `audit-log.tsx` 的文件末尾换行标准化外，代码内容与来源提交一致。

## 发布隔离

仓库 `.distignore` 已排除 `ai`，当前 CI 的 ZIP 命令也显式排除 `ai`。这只是双重防线，不是允许接入运行时的理由。每次改变发布脚本后仍应检查产物，确认 ZIP 中不存在 `ai/`。

## 使用规则

1. 先阅读 `SECURITY.md` 和 `PORTING.md`，再决定哪些概念值得迁移。
2. 在目标插件中使用新的命名空间、配置 Schema、数据存储和测试，不要直接 include 本目录文件。
3. 不要复制真实设置导出、Authorization Header、AccessKey、Secret 或评论日志。
4. 不要把本目录重新接入 Toolbox 的 autoload、模块注册表、REST 或管理后台。
5. 手动迁移完成后，可整体移走或删除本参考目录；Toolbox 不依赖它。

原实现包含同步 15 秒外部请求、异常时放行、自定义 URL SSRF、敏感配置回显等已知风险。任何直接复制并启用的做法都不符合生产要求。
