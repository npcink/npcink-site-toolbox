# 技术架构

## 整体架构

```
WP Magick Toolbox
├── PHP 后端（WordPress 插件标准架构）
│   ├── magick-tool-box.php          # 插件入口
│   ├── includes/                     # 核心类
│   │   ├── class-magick-mixtrue-admin.php   # 管理端
│   │   └── class-magick-config-manager.php  # 配置管理
│   ├── admin/                        # 后台功能模块
│   │   ├── modules/                  # 模块注册表与加载器
│   │   │   ├── registry.php          # 功能注册表
│   │   │   └── loader.php            # 统一加载器
│   │   └── partials/                 # 各功能实现
│   │       ├── domestic/             # 国内生态
│   │       ├── performance/          # 性能优化
│   │       └── ...                   # 其他功能模块
│   └── public/                       # 前端资源
├── React 前端（单一 pnpm workspace，含 3 个 Vite 项目）
│   ├── vite/admin/                   # 后台设置界面
│   ├── vite/count/                   # 图表展示
│   └── vite/public/                  # 前端展示
└── docs-site/                        # VitePress 文档站
```

## 配置管理

### 存储结构

配置按模块拆分为多个 `wp_options` 键，避免单键 JSON 膨胀：

| Option 键 | 存储内容 |
|-----------|----------|
| `Magick_ToolBox_Option_Optimize` | 站点、媒体和后台优化 |
| `Magick_ToolBox_Option_Page` | 页面功能、外观和评论 |
| `Magick_ToolBox_Option_Function` | SEO、统计与辅助功能 |
| `Magick_ToolBox_Option_Login` | 登录安全配置 |
| `Magick_ToolBox_Option_Domestic` | 国内生态配置 |
| `Magick_ToolBox_Option_Performance` | OSS 和维护工具配置 |

### 数据流

```
PHP 端                                  React 端
  │                                       │
  ├─ REST 基址 + nonce ──────────────────►│ window.dataLocal
  │                                       │
  ├─ GET /mabox/v1/settings ─────────────►│ 非敏感设置 + secretStatus
  │                                       │
  │◄─ POST { settings, secretChanges } ───┤ 显式保存
  │                                       │
  ├─ Schema 校验 + 凭据合并 + 原子写入 ──►│ wp_options
```

## 模块加载机制

```
registry.php（模块注册表）
    │
    ▼
loader.php（统一加载器）
    │
    ├─ 检查功能开关
    ├─ 检查依赖关系
    ├─ 检查主题要求
    └─ require_once + ::run()
```

未启用的功能不会执行任何代码，不注册 Hook，不占用内存。

## REST API

配置读写、性能检查和公开端点统一使用 REST API；少量独立后台交互可直接使用 WordPress AJAX：

| 端点前缀 | 用途 |
|----------|------|
| `/mabox/v1/settings` | 非敏感配置读取与显式保存 |
| `/mabox/v1/performance/*` | 性能检查与修复 |
| `/mabox/v1/domestic/*` | 国内生态功能 |
| `/mabox/v1/public/*` | 公开端点（前端交互） |
| `/mabox/v1/tools/*` | 工具类功能 |

## 安全层

```
用户输入
    │
    ├─ sanitize_text_field()    # 输入清洗
    ├─ current_user_can()       # 权限检查
    ├─ check_ajax_referer       # CSRF 验证
    ├─ $wpdb->prepare()         # SQL 防注入
    └─ esc_html() / esc_url()   # 输出转义
```

保存的微信与 OSS 凭据只在服务端运行时读取。设置 GET、页面启动数据、错误信息和浏览器存储不得包含原值；管理端通过 `secretStatus` 显示配置状态，并以 `replace` 或 `clear` 表达变更。
