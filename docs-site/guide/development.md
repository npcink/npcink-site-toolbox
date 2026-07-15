# 开发指南

## 添加新功能

### 1. 创建 PHP 后端

在 `admin/partials/[category]/` 下创建功能文件：

```php
<?php
/**
 * 功能：新功能说明
 */
if (!class_exists('MaBox_Page_New_Feature')) {
    class MaBox_Page_New_Feature {
        private static $option;

        public static function run($config) {
            self::$option = $config;
            // 仅在功能开启时注册 Hook
            $enabled = MaBox_Helpers::get_config('page', 'new_feature_enabled', false);
            if ($enabled) {
                add_action('wp_footer', array(__CLASS__, 'display'));
            }
        }

        public static function display() {
            $text = MaBox_Helpers::get_config('page', 'new_feature_text', 'Hello');
            echo esc_html($text);
        }
    }
}
```

### 2. 注册模块

在 `admin/modules/registry.php` 中添加：

```php
'page.new_feature' => array(
    'class'     => 'MaBox_Page_New_Feature',
    'file'      => 'page/new_feature.php',
    'option_key'=> 'page.function.new_feature_enabled',
    'category'  => 'page',
    'scope'     => 'frontend',  // admin / frontend / both
    'config_path' => 'page.function',
    'risk_tags' => array('推荐'),
),
```

**注册表字段说明：**

| 字段 | 说明 | 必填 |
|------|------|------|
| `class` | 模块类名 | ✅ |
| `file` | 文件路径（相对于 `admin/partials/`） | ✅ |
| `option_key` | 配置键名 | ✅ |
| `category` | 所属分类 | ✅ |
| `scope` | 加载范围 | ✅ |
| `config_path` | 配置路径（用于传递配置给模块） | - |
| `risk_tags` | 风险标签（推荐/谨慎/安全/SEO 等） | - |
| `always_load` | 是否始终加载（忽略配置开关） | - |
| `theme_requirement` | 依赖的主题名称 | - |
| `mobile_only` | 仅移动端加载 | - |

### 3. 添加前端类型定义

在 `vite/admin/src/tool/interface.tsx` 中添加：

```typescript
export type PageFunction = {
    // ... existing fields
    new_feature_enabled: boolean;
    new_feature_text?: string;
};
```

### 4. 添加默认值

在 `vite/admin/src/tool/defaultVar.tsx` 中添加：

```typescript
const PageFunction = {
    // ... existing fields
    new_feature_enabled: false,
    new_feature_text: 'Hello World',
};
```

### 5. 添加 UI 控件

在对应 Tab 组件中添加表单控件：

```tsx
<Form.Item label="新功能开关" name="new_feature_enabled" valuePropName="checked">
    <FeatureSwitch featureId="new-feature-enabled" />
</Form.Item>

<Form.Item label="自定义文本" name="new_feature_text">
    <Input placeholder="输入文本" />
</Form.Item>
```

## 安全规范

| 场景 | 要求 | 示例 |
|------|------|------|
| SQL 查询 | 必须使用 `$wpdb->prepare()` | `$wpdb->prepare("SELECT * FROM t WHERE id = %d", $id)` |
| 输出到 HTML | 使用转义函数 | `esc_html()`, `esc_url()`, `esc_attr()` |
| 富文本输出 | 使用 `wp_kses_post()` | `wp_kses_post($content)` |
| AJAX 端点 | nonce 验证 + 权限检查 | `check_ajax_referer()` + `current_user_can()` |
| REST 端点 | `permission_callback` 验证 | `permission_callback => fn() => current_user_can('manage_options')` |
| 用户输入 | 清洗数据 | `sanitize_text_field()`, `sanitize_email()` |

## 前端开发

### 项目结构

```
vite/
├── package.json          # workspace 根配置
├── pnpm-workspace.yaml   # workspace 成员
├── admin/                # 后台设置界面
├── count/                # 统计图表组件
└── public/               # 前端展示组件
```

### 开发服务器

使用 pnpm workspaces 统一管理：

```bash
# 在仓库根目录启用 Corepack，并安装整个 workspace
corepack enable
cd vite
pnpm install --frozen-lockfile

# 启动单个项目
pnpm dev:admin
pnpm dev:count
pnpm dev:public
```

代理配置在 `vite.config.ts` 中，将 `target` 替换为本地 WordPress 地址。

### 构建

```bash
# 构建所有项目
pnpm build

# 构建单个项目
pnpm build:admin
```

构建产物在 `dist/` 目录，仅保留此目录即可部署。

### 类型安全

所有前端项目使用 `global.d.ts` 扩展 `Window` 接口：

```typescript
// vite/admin/src/global.d.ts
import type { DataLocal } from "./tool/interface";

declare global {
  interface Window {
    dataLocal: DataLocal | "";
  }
}

export {};
```

避免使用 `as any`，确保类型安全。

## 模块接口契约

所有模块应实现 `MaBox_Module_Interface`：

```php
class MaBox_My_Module implements MaBox_Module_Interface {
    public static function run($config = array()) {
        // 初始化逻辑
    }
}
```

当前为过渡期，未实现接口的模块会记录警告日志。

## 测试

```bash
# 运行 PHPUnit 测试
composer test

# 运行 Vitest 测试
pnpm test

# 运行覆盖率测试
pnpm test:coverage
```

## CI/CD

GitHub Actions 自动运行：
- PHP 7.4 ~ 8.3 多版本语法检查
- PHPUnit 单元测试
- TypeScript 类型检查
- ESLint 代码检查
- Vite 构建验证
- 自动打包插件 ZIP

## 代码规范

### PHP
- 使用 4 空格缩进
- 类名使用 `MaBox_` 前缀
- 静态类方法，不使用实例化
- 所有字符串使用 `__()` 国际化

### TypeScript
- 使用 2 空格缩进
- 不使用 `as any`
- 组件使用函数式写法
- Props 使用 interface 定义

### Git
- 提交信息格式：`type: description`
- type: feat / fix / refactor / test / docs / chore
- 每次提交一个逻辑变更
