# 工作区布局与清洁规则

## 目录职责

```text
admin/                 后台运行时代码和模块实现
includes/              共享 PHP、REST、配置和服务边界
public/                前台运行时代码和样式
blocks/                可读的编辑器区块源码
patterns/              可读的编辑器样板
languages/             发布所需的 POT、PO、MO 和脚本语言包
tests/                 PHPUnit、前端测试和隔离测试夹具
bin/                   构建、国际化、ZIP 和 WordPress.org 验收脚本
vite/                  唯一前端工程；源码不进入安装包，dist 由构建生成
docs-site/             面向用户/开发者的公开文档站点
docs/                  仓库内部治理、证据和架构决策
ai/                    仅保留确有仓库级参考价值的 AI 评测资料
```

## 不应进入 Git 或安装包的内容

- `node_modules/`、`vendor/`、`dist/`、coverage 和 Vite 缓存；
- `.DS_Store`、`.phpunit.result.cache`、临时日志和本地环境文件；
- 根目录历史 ZIP、`.sha256` 侧车和手工下载的 `phpunit.phar`；
- `docs-site/.vitepress/dist`、测试生成结果和 AI 临时输出；
- `vite/*/src`、前端配置和依赖文件（源码通过公开 tag 另行提供，安装包只带构建产物）。

这些内容已经由 `.gitignore` 或 `.distignore` 排除。清理时优先移动到仓库外的可恢复归档目录，不对整个工作区使用递归删除。

## 运行时代码整理纪律

`admin/partials/`、`includes/` 和 `public/` 的路径可能被 Registry、加载器、测试和发布合同直接引用。不得进行全仓库批量移动。整理一个模块时必须同时更新：

1. Registry 和加载路径；
2. 相关 PHPUnit/前端合同；
3. POT 提取路径；
4. ZIP 必需文件和 `.distignore`；
5. 文档链接和回滚说明。

一个模块一个提交，完成后再进入下一个模块。
