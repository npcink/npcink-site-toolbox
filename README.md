# 使用

vite 文件夹下，
admin 是设置框架内容
count 文件夹下，是图表展示内容
均使用 React 构建。

您可以自行修改后分别打包，仅保留 dist 内文件即可。

# 打包

- 移除相关开发用函数

# 修复

- 统一接口
- 小程序接口添加错误提示

# 计划

- 开启严格模式下开发
- 给每篇文章自动都生成一个小程序文章链接

- 集成百度文本审核 https://ai.baidu.com/ai-doc/REFERENCE/Ck3dwjgn3
- 撰写文章类型，模仿日记格式https://www.dratk.com/
- 文章显示最后更新时间 https://www.landafu.com/29563.html

- 添加闭站倒计时功能
- 添加设置选项导入导出功能
- 设置选项内容添加移动端适配
- 隐藏指定分类、标签，登录可见
- 隐藏邮件中的 IP： https://7b2.com/circle/63482.html
- 集成文档在线预览功能 wps 永中等
- 集成文本审核，
- 集成工单系统
- 优化外观
- 登录才能访问，
- 将各种通用功能做成库，方便调用

## 功能

- 去除分类 category https://www.npc.ink/5783.html
- 复制文字跳出弹窗提示 https://www.npc.ink/5032.html https://www.npc.ink/12196.html

- 重复标题文章 https://www.npc.ink/5867.html
- 禁用自动保存等 https://www.npc.ink/5002.html
- 转义邮箱 https://www.npc.ink/11809.html
- 防止打开 F12 https://www.npc.ink/6764.html https://juejin.cn/post/7262175454714626108
- 指定邮箱可注册 https://www.npc.ink/19117.html
- 支持 webp https://www.npc.ink/18850.html
- 支持 exe\app\
- 裁剪图片下侧去水印 https://www.npc.ink/276026.html
- 首行缩进两字符 https://www.npc.ink/4639.html
- 添加顶部统计代码和底部统计代码 https://www.npc.ink/13225.html

- 公告单页模版 https://www.npc.ink/14482.html
- 所有文中链接从新窗口打开 https://www.npc.ink/17846.html
- 用户根据文章数量排序 https://www.npc.ink/17135.html
- 文章添加缩略图展示 https://www.npc.ink/17087.html
- 添加客服按钮 https://www.npc.ink/14571.html

## 外观特效

- 评论框打字特效

- 点击出现数字 https://www.npc.ink/5955.html
- 点击出现文字 https://www.npc.ink/11756.html
- 添加魔方 https://www.npc.ink/12188.html

## 功能特效

- 写了多少字+阅读时间 https://www.npc.ink/6896.html
- 文章底部添加赞赏引导按钮 https://www.npc.ink/6613.html
- 生成海报功能：https://blog.wpjam.com/project/wpjam-modal/

# 更新记录

#### 2023.11.22

- 添加下载指定数据库表功能

### 0.1.7

- 修复 解决启用文章统计导致的语法错误
- 修复 商城统计中的错误文本信息

### 0.1.6

- 添加 生成微信小程序跳转链接功能
- 添加 微信小程序跳转页面模版功能（启用生成跳转链接功能才有此模版）
- 添加 网页变灰功能

- 添加 灯笼效果、全屏飘樱花效果
- 添加 动态标题
- 添加 美化滚动条
- 添加 细线联结特效
- 添加 站内跳转站外添加中转页提示

- 修复 B2 商城统计时间倒叙问题
- 修复 月度统计数据不准的问题

### 0.1.5

- 修复未登录模糊图片报错问题
- 添加 移除文章内链接功能
- 添加 文章末尾添加最后更新时间

### 0.1.3

- 更换自研设置框架
- 重写文章统计图表
- 重写 B2 销售统计图表
- 添加屏幕上的毛

### 0.0.2

- 临时禁用了部分文章统计数据，这些数据会在量大时造成页面卡顿
- 优化了部分代码，解决了报错问题

### 0.0.3

#### 发文统计

- 新增了一些统计信息，优化了性能

#### 销售统计

- 图示中添加数据展示，优化了详情信息
  - 添加月度统计

#### 新增设置选项

### 新增

- 添加表情包功能
- 各种资源按需加载
- 引用的资源统一名称和版本号

# 功能表

| 功能名                                 | 参考地址（项目）                                                                                                                             | 加入时间 |
| -------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| 页面添加烟花粒子                       | https://www.iowen.cn/canvas-click-effect-second-edition/                                                                                     | 22.03.01 |
| 自定义登录页面                         | https://www.iowen.cn/chundaimameihuawordpressmorendengluye/<br>https://www.iotheme.cn/login/                                                 | 22.03.01 |
| 评论区添加 OwO 表情                    | [DIYgod/OwO: Lovely Emoticon and Emoji Keyboard for input (github.com)](https://github.com/DIYgod/OwO)                                       | 22.03.02 |
| 接入腾讯防水墙，给网站登录加上验证功能 | https://www.iowen.cn/wordpress-tencent-waterproof-wall/                                                                                      | 22.03.02 |
| 登录页添加数学验证码                   | [(20 条消息) wordpress 安全防护设置\_wordpress 安全设置\_zzsi 的博客-CSDN 博客](https://blog.csdn.net/qq_39339179/article/details/119183143) | 22.03.03 |
| 登录页添加*随机混合数验证码*           | [(20 条消息) wordpress 安全防护设置\_wordpress 安全设置\_zzsi 的博客-CSDN 博客](https://blog.csdn.net/qq_39339179/article/details/119183143) | 22.03.03 |
| 图片使用数字或 MD5 重命名              | [Wordpress 上传图片自动重命名代码 - Npcink](https://www.npc.ink/25.html)                                                                     | 22.03.03 |
| 禁止网站 title 中的 “-” 被转义         | ……                                                                                                                                           | 22.03.04 |
| 圆角彩色背景标签云                     | ……                                                                                                                                           | 22.03.04 |
| 禁用更新检查                           | https://www.npc.ink/15932.html                                                                                                               | 22.03.04 |
| 给文章关键词自动添加内链               | https://www.npc.ink/15286.html                                                                                                               | 22.03.04 |
| 屏蔽恶意关键词搜索                     |                                                                                                                                              | 22.03.04 |
|                                        |                                                                                                                                              |          |
|                                        |                                                                                                                                              |          |
|                                        |                                                                                                                                              |          |
|                                        |                                                                                                                                              |          |
|                                        |                                                                                                                                              |          |
|                                        |                                                                                                                                              |          |
|                                        |                                                                                                                                              |          |

# 参考

- 表情：https://7b2.com/circle/56687.html

- 表情用项目：https://github.com/DIYgod/OwO
- 下载插件：https://www.xiaomaw.cn/2658.html

## 优化插件

- 外链跳转：https://www.lovestu.com/cp-link-open.html
- 新窗口打开链接：https://www.xiaomaw.cn/3208.html
- 优化：https://www.lovestu.com/wpopt.html
- 文章图片遮罩层水印插件：https://ds17.cn/2711.html

## 其他

- https://7b2.com/circle-people?id=6118

## 统计插件

- https://www.termilk.com/shop/742.html
- https://www.termilk.com/Rose-archive

# 待解决问题

隐藏 wp-admin 防垃圾注册
依据二开文档在适当的位置添加如下代码

add_action(‘login_enqueue_scripts’,’login_protection’);
function login_protection(){
if($\_GET[‘root‘] != ‘admin‘)header(‘Location: https://www.phpfensi.com’);
}

添加上面的脚本就可以做到隐藏https://www.aovon.com/wp-login.php了，将红色的部分修改成自己需要的就可以了，以后就可以使用下面的链接进行登陆了。

https://www.phpfensi.com/wp-login.php?root=admin

这样的话，别人再使用 wp-login.php 访问时就会自动跳转到指定的页面了，确保了登陆入口的隐蔽性。
