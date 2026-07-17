# 后台 REST 与 Dashboard 排障经验

> 日期：2026-07-15
> 范围：WordPress 后台插件页、`mabox/v1` REST 路由、Dashboard 初始化请求
> 背景：本地打开或刷新插件设置页时，浏览器控制台和页面 toast 连续暴露多个问题。

## 一、问题链路概览

本轮问题不是单个前端异常，而是后台页面初始化时多条 REST 请求依次暴露了三个层面的契约缺口：

1. REST 路由注册依赖没有在真实插件入口加载。
2. REST 参数 `sanitize_callback` 直接使用 PHP 内置函数 `intval`，在 PHP 8 下被 WordPress 多参数调用触发 fatal。
3. 搜索健康接口返回裸数据，和后台前端统一期待的 `{ success, data }` 格式不一致，导致刷新时误弹“未知错误”。

这三个问题都出现在“打开插件页即初始化 Dashboard”的路径上，因此用户看到的是一串浏览器控制台 500、再到页面 toast 的连续失败。

## 二、问题 1：打开插件页时多个 REST 端点 500

### 现象

浏览器控制台出现多条 500：

- `GET /wp-json/mabox/v1/settings`
- `GET /wp-json/mabox/v1/settings/schema`
- `GET /wp-json/mabox/v1/search-health/summary?days=30`
- `GET /wp-json/mabox/v1/diagnostics/summary`

错误体里有：

```text
Uncaught Error: Class "MaBox_Rest_Route_Registry" ...
admin/class-magick-mixture-admin.php line 560
```

### 根因

`MaBox_Admin::register_rest_routes()` 依赖 `MaBox_Rest_Route_Registry`，类映射存在于 `includes/autoload.php`，但真实插件入口 `magick-tool-box.php` 只加载了 `includes/class-magick-mixture.php`。

测试环境没有暴露该问题，因为 `tests/bootstrap.php` 会主动加载 `includes/autoload.php`。也就是说，单元测试路径和真实 WordPress 插件入口路径不一致。

### 修复

在插件主文件里先加载自动加载器，再加载核心类：

```php
require_once plugin_dir_path(__FILE__) . 'includes/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-magick-mixture.php';
```

同时补回归测试，断言主插件文件必须先加载 `includes/autoload.php`，再加载 `includes/class-magick-mixture.php`。

### 经验

- 类映射文件存在不等于运行时已加载。
- `tests/bootstrap.php` 可能掩盖真实入口加载顺序问题。
- 涉及插件入口、REST 注册、hook 初始化的问题，必须核对真实 WordPress 请求路径。

## 三、问题 2：`search-health/summary` 仍然 500

### 现象

修复类加载后，`/wp-json/mabox/v1/search-health/summary?days=30` 仍返回 500：

```text
Uncaught ArgumentCountError: intval() expects ...
wp-includes/rest-api/class-wp-rest-request.php line 850
```

### 根因

REST 路由参数中存在：

```php
'sanitize_callback' => 'intval'
```

WordPress REST 在清洗参数时会向 `sanitize_callback` 传入多个参数。PHP 8 下，内部函数 `intval()` 收到多余参数会抛 `ArgumentCountError`，从而导致 REST 请求 500。

这个风险不只在 `days` 参数上，`admin/class-magick-mixture-admin.php` 中多个 REST 参数都使用了同样写法。

### 修复

新增项目内包装方法：

```php
public static function sanitize_int_arg($value)
{
    return intval($value);
}
```

将 REST 参数里的直接 `intval` 全部替换为：

```php
'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg')
```

并补测试：

- 模拟 WordPress REST 用多个参数调用整数清洗回调。
- 禁止 `admin/class-magick-mixture-admin.php` 再出现 `'sanitize_callback' => 'intval'`。

### 经验

- REST `sanitize_callback` 不要直接传 PHP 内置函数，尤其是只接受固定参数数量的函数。
- 用项目内 wrapper 可以吸收 WordPress 传入的额外参数，避免 PHP 版本差异引发 fatal。
- 搜索到一个 REST 参数回调问题时，应全文件扫同类模式，而不是只修当前报错端点。

## 四、问题 3：刷新页面弹“未知错误”

### 现象

刷新插件页后，页面出现 Ant Design toast：

```text
未知错误
```

此时不一定伴随新的 PHP fatal。

### 根因

前端 `restInstance` 拦截器会把没有 `success: true` 的响应视为失败：

```ts
if (responseData.success) {
  ...
} else {
  message.error(... || '未知错误');
}
```

Dashboard 中 `searchHealthApi.getSummary()` 也按统一格式读取：

```ts
if (res?.success && res?.data) {
  setSearchHealth(res.data);
}
```

但 `MaBox_Search_Health::rest_get_summary()` 原先返回的是裸摘要对象：

```json
{
  "range_days": 30,
  "total_searches": 0
}
```

前端收到 200 响应，却因为缺少 `success` 字段误判为业务失败，于是弹出“未知错误”。

### 修复

让搜索健康 REST 接口和其它后台管理接口保持同一响应格式：

```php
return rest_ensure_response(array(
    'success' => true,
    'data' => self::get_summary($days),
));
```

并补测试，断言 `rest_get_summary()` 返回：

- `success === true`
- 包含 `data`
- `data.range_days` 正确

### 经验

- 后台管理端 REST 响应格式必须统一，不能有的端点返回裸数据、有的端点返回 `{ success, data }`。
- 前端 toast 的“未知错误”通常说明错误体没有被规范化，不一定代表后端真的抛了 unknown error。
- Dashboard 初始化接口要同时验证 HTTP 状态、业务包装格式和前端读取方式。

## 五、排障顺序建议

遇到“打开插件页/刷新页面报错”时，按以下顺序排查：

1. 先看浏览器 Network 中第一个失败的 REST 请求。
2. 展开响应体，优先找 PHP fatal 的 `message`、`file`、`line`。
3. 若是类不存在，核对真实插件入口加载顺序，不只看 autoload 映射。
4. 若是 `ArgumentCountError`，检查 REST `args` 中的 `sanitize_callback` 和 `validate_callback`。
5. 若 HTTP 200 但前端弹错，检查响应 JSON 是否符合前端统一契约。
6. 修一个端点后，用 `rg` 搜全仓同类模式。
7. 补测试时要覆盖真实失败条件，不只测类存在或方法存在。

## 六、建议长期约定

### REST 参数回调

REST 参数的 `sanitize_callback` 优先使用项目 wrapper 或闭包，避免直接使用可能存在参数数量限制的 PHP 内置函数。

### REST 响应格式

后台管理端接口统一返回：

```json
{
  "success": true,
  "data": {}
}
```

失败时使用 `WP_Error`，并提供明确 `status` 与可读 `message`。

### 测试覆盖

入口加载类问题应由入口文件测试覆盖；REST 参数问题应模拟 WordPress REST 的多参数回调；前端契约问题应由 REST 回调结构测试覆盖。

## 七、本轮验证记录

本轮相关修复完成后已执行：

```bash
php -l magick-tool-box.php
php -l admin/class-magick-mixture-admin.php
php -l includes/class-mabox-search-health.php
vendor/bin/phpunit --configuration phpunit.xml.dist tests/unit/RestApiSecurityTest.php tests/unit/SearchHealthTest.php
composer test
```

最终全量结果：

```text
OK (301 tests, 2431 assertions)
```

## 八、关联文件

- `magick-tool-box.php`
- `admin/class-magick-mixture-admin.php`
- `includes/autoload.php`
- `includes/class-mabox-search-health.php`
- `tests/unit/AbspathGuardTest.php`
- `tests/unit/RestApiSecurityTest.php`
- `tests/unit/SearchHealthTest.php`
- `vite/admin/src/axios/public.ts`
- `vite/admin/src/components/dashboard/index.tsx`
