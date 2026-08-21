<?php
defined('ABSPATH') || exit;
/**
 * 隐私与外部服务说明
 *
 * 列出插件连接的所有外部服务、数据流向、隐私政策。
 *
 * @since 2.4.0
 */
if (!class_exists('Npcink_Toolbox_Privacy')) {
    class Npcink_Toolbox_Privacy
    {
        /**
         * 外部服务列表
         */
        private static $external_services = array(
            array(
                'name'        => '微信 JSSDK',
                'provider'    => '腾讯（微信）',
                'purpose'     => '微信内分享自定义标题、描述、图标',
                'trigger'     => '管理员启用微信 JSSDK、配置 AppID/AppSecret，且访客打开单篇内容时',
                'data_sent'   => '服务器向微信 API 发送 AppID、AppSecret 和后续 access token；访客浏览器从微信加载 JSSDK，并向 SDK 提供页面 URL、标题、摘要和缩略图 URL',
                'data_stored' => 'AppID/AppSecret 存在本站 WordPress 数据库；jsapi_ticket 在本站短期缓存',
                'opt_in'      => true,
                'config_key'  => 'domestic.wechat.jssdk_enabled',
                'service_url' => 'https://developers.weixin.qq.com/doc/offiaccount/OA_Web_Apps/JS-SDK.html',
                'terms_url'   => 'https://weixin.qq.com/cgi-bin/readtemplate?lang=zh_CN&t=weixin_agreement&s=default',
                'privacy_url' => 'https://weixin.qq.com/cgi-bin/readtemplate?lang=zh_CN&t=weixin_agreement&s=privacy',
            ),
            array(
                'name'        => '阿里云对象存储 OSS',
                'provider'    => '阿里云',
                'purpose'     => '验证对象存储写入连接，或将 WordPress 媒体文件上传到管理员选择的对象存储',
                'trigger'     => '管理员选择阿里云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时',
                'data_sent'   => '连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket、阿里云 Endpoint、AccessKey 标识和签名请求头',
                'data_stored' => '已保存的 AccessKey、SecretKey、Bucket、上传目录、Endpoint 与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象',
                'opt_in'      => true,
                'config_key'  => 'performance.oss.enabled',
                'service_url' => 'https://www.aliyun.com/product/oss',
                'terms_url'   => 'https://terms.aliyun.com/legal-agreement/terms/suit_bu1_ali_cloud/suit_bu1_ali_cloud201912232313_55403.html',
                'privacy_url' => 'https://terms.aliyun.com/legal-agreement/terms/suit_bu1_ali_cloud/suit_bu1_ali_cloud202107091605_49213.html',
            ),
            array(
                'name'        => '腾讯云对象存储 COS',
                'provider'    => '腾讯云',
                'purpose'     => '验证对象存储写入连接，或将 WordPress 媒体文件上传到管理员选择的对象存储',
                'trigger'     => '管理员选择腾讯云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时',
                'data_sent'   => '连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket/Region、AccessKey 标识和签名请求头',
                'data_stored' => '已保存的 AccessKey、SecretKey、Bucket、上传目录、Region 与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象',
                'opt_in'      => true,
                'config_key'  => 'performance.oss.enabled',
                'service_url' => 'https://cloud.tencent.com/product/cos',
                'terms_url'   => 'https://cloud.tencent.com/document/product/301/1967',
                'privacy_url' => 'https://cloud.tencent.com/document/product/301/11470',
            ),
            array(
                'name'        => '七牛云对象存储 Kodo',
                'provider'    => '七牛云',
                'purpose'     => '验证对象存储写入连接，或将 WordPress 媒体文件上传到管理员选择的对象存储',
                'trigger'     => '管理员选择七牛云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时',
                'data_sent'   => '连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket、AccessKey 标识和上传凭证',
                'data_stored' => '已保存的 AccessKey、SecretKey、Bucket、上传目录与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象',
                'opt_in'      => true,
                'config_key'  => 'performance.oss.enabled',
                'service_url' => 'https://www.qiniu.com/products/kodo',
                'terms_url'   => 'https://www.qiniu.com/agreements/user-agreement',
                'privacy_url' => 'https://www.qiniu.com/agreements/privacy-right',
            ),
            array(
                'name'        => '百度统计',
                'provider'    => '百度',
                'purpose'     => '在站点前台加载百度统计脚本',
                'trigger'     => '管理员启用百度统计模块并保存非空站点 ID 后，访客打开前台页面时',
                'data_sent'   => '访客浏览器会向百度发送 IP 地址、User-Agent、Referrer、页面 URL 以及百度统计脚本按其政策处理的访问数据',
                'data_stored' => '百度统计站点 ID 存在本站 WordPress 数据库',
                'opt_in'      => true,
                'config_key'  => 'function.auxiliary.baidu_tonji',
                'service_url' => 'https://tongji.baidu.com/',
                'terms_url'   => 'https://tongji.baidu.com/web/help/article?id=314&type=0',
                'privacy_url' => 'https://tongji.baidu.com/web/help/article?id=330&type=0',
            ),
            array(
                'name'        => 'Google Search Console 站点验证',
                'provider'    => 'Google',
                'purpose'     => '输出管理员提供的站点验证 meta 标签',
                'trigger'     => '管理员启用模块并保存非空验证码后',
                'data_sent'   => '无；插件只输出 meta 标签，不主动请求 Google',
                'data_stored' => '站点验证码存在本站 WordPress 数据库',
                'opt_in'      => true,
                'config_key'  => 'function.auxiliary.google_tonji',
                'service_url' => 'https://search.google.com/search-console/about',
                'terms_url'   => 'https://policies.google.com/terms',
                'privacy_url' => 'https://policies.google.com/privacy',
            ),
            array(
                'name'        => 'Bing Webmaster Tools 站点验证',
                'provider'    => 'Microsoft',
                'purpose'     => '输出管理员提供的站点验证 meta 标签',
                'trigger'     => '管理员启用模块并保存非空验证码后',
                'data_sent'   => '无；插件只输出 meta 标签，不主动请求 Microsoft',
                'data_stored' => '站点验证码存在本站 WordPress 数据库',
                'opt_in'      => true,
                'config_key'  => 'function.auxiliary.biying_tonji',
                'service_url' => 'https://www.bing.com/webmasters/about',
                'terms_url'   => 'https://www.microsoft.com/servicesagreement',
                'privacy_url' => 'https://privacy.microsoft.com/privacystatement',
            ),
            array(
                'name'        => 'GitHub 项目 API',
                'provider'    => 'GitHub',
                'purpose'     => '读取作者主动插入的公开仓库摘要、主要语言、Stars、Forks 和归档状态',
                'trigger'     => '作者插入 GitHub 项目区块并填写公开仓库地址，且本地缓存过期时',
                'data_sent'   => '公开的仓库所有者和仓库名称、站点服务器 IP、插件 User-Agent 和正常 HTTP 请求头；不发送文章内容、访客 IP、Token 或插件设置',
                'data_stored' => '成功响应在本站短期缓存 12 小时，失败响应缓存 30 分钟；不保存 GitHub 凭据',
                'opt_in'      => true,
                'config_key'  => 'blocks.github_project',
                'service_url' => 'https://docs.github.com/en/rest/repos/repos#get-a-repository',
                'terms_url'   => 'https://docs.github.com/en/site-policy/github-terms/github-terms-of-service',
                'privacy_url' => 'https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement',
            ),
            array(
                'name'        => 'DeepSeek 诊断服务',
                'provider'    => 'DeepSeek Provider',
                'purpose'     => '在管理员明确启动 AI 诊断后解释受限的站点事实和前后复验结果',
                'trigger'     => 'WordPress 7.0+、已安装并连接 DeepSeek Provider，且管理员审阅白名单快照后主动开始分析或追问时',
                'data_sent'   => '白名单事实、提示词、服务器 IP、正常 HTTP 请求头和由 WordPress Connectors/Provider 管理的 API 凭据；不发送凭据字段、文章内容、用户、评论、路径或请求日志',
                'data_stored' => '问题、快照、基线、追问历史和回答只存在当前浏览器页面；本插件不读取或保存 Provider 凭据',
                'opt_in'      => true,
                'config_key'  => 'about.ai_diagnostics',
                'service_url' => 'https://www.deepseek.com/',
                'terms_url'   => 'https://cdn.deepseek.com/policies/en-US/deepseek-open-platform-terms-of-service.html',
                'privacy_url' => 'https://cdn.deepseek.com/policies/en-US/deepseek-privacy-policy.html',
            ),
        );

        public static function run()
        {
            // 后台显示隐私说明
            add_action('admin_notices', array(__CLASS__, 'admin_notice'));
        }

        /**
         * 获取外部服务列表
         */
        public static function get_services()
        {
            return self::$external_services;
        }

        /**
         * 获取需要 opt-in 的服务
         */
        public static function get_opt_in_services()
        {
            return array_filter(self::$external_services, function ($service) {
                return $service['opt_in'];
            });
        }

        /**
         * 后台通知（仅显示一次）
         */
        public static function admin_notice()
        {
            if (get_option('npcink_site_toolbox_privacy_notice_dismissed')) {
                return;
            }

            $screen = get_current_screen();
            if ($screen && $screen->id !== 'plugins_page_npcink-site-toolbox') {
                return;
            }

            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Npcink Site Toolbox</strong>
                    <?php esc_html_e('隐私提示：本插件只会在管理员明确启用或主动触发相关功能后连接已披露的外部服务。', 'npcink-site-toolbox'); ?>
                    <a href="<?php echo esc_url(admin_url('plugins.php?page=npcink-site-toolbox')); ?>"><?php esc_html_e('查看完整隐私说明 →', 'npcink-site-toolbox'); ?></a>
                </p>
            </div>
            <?php
        }

        /**
         * 渲染隐私说明页面（供 React 前端调用）
         */
        public static function get_privacy_data()
        {
            return array(
                'services'       => self::translate_tree(self::$external_services),
                'opt_in_services' => self::translate_tree(self::get_opt_in_services()),
                'privacy_policy' => array(
                    'data_collection' => __('本插件默认不收集用户个人身份信息。所有配置数据存储在 WordPress 数据库内。', 'npcink-site-toolbox'),
                    'third_party'     => __('部分功能需要连接第三方服务，具体触发条件、数据流向及服务条款见上方服务列表。', 'npcink-site-toolbox'),
                    'user_consent'    => __('所有列出的出站连接都需要管理员明确启用相关功能或主动运行检测；插件默认不向开发者发送遥测。', 'npcink-site-toolbox'),
                    'api_keys'        => __('已保存凭据存储在 WordPress 数据库中，仅在管理员启用对应功能或主动运行对象存储连接测试时用于服务端鉴权请求；未保存的对象存储凭据草稿只用于当次测试。插件不会把凭据发送给开发者。', 'npcink-site-toolbox'),
                    'uninstall'       => __('卸载会清理插件设置、计划任务、临时缓存、评论拦截标记及纯运行态附件标记，但不会删除或改写媒体文件、文章、评论、分类或远端对象。已转换附件的 WebP 恢复记录会保留，避免失去恢复原 JPEG 所需的信息；如需由插件执行恢复，请在卸载前完成。', 'npcink-site-toolbox'),
                ),
            );
        }

        private static function translate_tree($value)
        {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $value[$key] = self::translate_tree($item);
                }
                return $value;
            }
            static $translations = null;
            if ($translations === null) {
                $translations = array(
                    '微信 JSSDK' => __('微信 JSSDK', 'npcink-site-toolbox'),
                    '腾讯（微信）' => __('腾讯（微信）', 'npcink-site-toolbox'),
                    '微信内分享自定义标题、描述、图标' => __('微信内分享自定义标题、描述、图标', 'npcink-site-toolbox'),
                    '管理员启用微信 JSSDK、配置 AppID/AppSecret，且访客打开单篇内容时' => __('管理员启用微信 JSSDK、配置 AppID/AppSecret，且访客打开单篇内容时', 'npcink-site-toolbox'),
                    '服务器向微信 API 发送 AppID、AppSecret 和后续 access token；访客浏览器从微信加载 JSSDK，并向 SDK 提供页面 URL、标题、摘要和缩略图 URL' => __('服务器向微信 API 发送 AppID、AppSecret 和后续 access token；访客浏览器从微信加载 JSSDK，并向 SDK 提供页面 URL、标题、摘要和缩略图 URL', 'npcink-site-toolbox'),
                    'AppID/AppSecret 存在本站 WordPress 数据库；jsapi_ticket 在本站短期缓存' => __('AppID/AppSecret 存在本站 WordPress 数据库；jsapi_ticket 在本站短期缓存', 'npcink-site-toolbox'),
                    '阿里云对象存储 OSS' => __('阿里云对象存储 OSS', 'npcink-site-toolbox'),
                    '阿里云' => __('阿里云', 'npcink-site-toolbox'),
                    '验证对象存储写入连接，或将 WordPress 媒体文件上传到管理员选择的对象存储' => __('验证对象存储写入连接，或将 WordPress 媒体文件上传到管理员选择的对象存储', 'npcink-site-toolbox'),
                    '管理员选择阿里云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时' => __('管理员选择阿里云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时', 'npcink-site-toolbox'),
                    '连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket、阿里云 Endpoint、AccessKey 标识和签名请求头' => __('连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket、阿里云 Endpoint、AccessKey 标识和签名请求头', 'npcink-site-toolbox'),
                    '已保存的 AccessKey、SecretKey、Bucket、上传目录、Endpoint 与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象' => __('已保存的 AccessKey、SecretKey、Bucket、上传目录、Endpoint 与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象', 'npcink-site-toolbox'),
                    '腾讯云对象存储 COS' => __('腾讯云对象存储 COS', 'npcink-site-toolbox'),
                    '腾讯云' => __('腾讯云', 'npcink-site-toolbox'),
                    '管理员选择腾讯云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时' => __('管理员选择腾讯云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时', 'npcink-site-toolbox'),
                    '连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket/Region、AccessKey 标识和签名请求头' => __('连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket/Region、AccessKey 标识和签名请求头', 'npcink-site-toolbox'),
                    '已保存的 AccessKey、SecretKey、Bucket、上传目录、Region 与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象' => __('已保存的 AccessKey、SecretKey、Bucket、上传目录、Region 与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象', 'npcink-site-toolbox'),
                    '七牛云对象存储 Kodo' => __('七牛云对象存储 Kodo', 'npcink-site-toolbox'),
                    '七牛云' => __('七牛云', 'npcink-site-toolbox'),
                    '管理员选择七牛云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时' => __('管理员选择七牛云并主动运行连接测试时，或启用对象存储并在之后上传媒体文件时', 'npcink-site-toolbox'),
                    '连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket、AccessKey 标识和上传凭证' => __('连接测试文本或媒体文件内容、含可选目录前缀的对象路径、Bucket、AccessKey 标识和上传凭证', 'npcink-site-toolbox'),
                    '已保存的 AccessKey、SecretKey、Bucket、上传目录与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象' => __('已保存的 AccessKey、SecretKey、Bucket、上传目录与公开访问地址存在本站 WordPress 数据库；未保存的凭据草稿只用于当次测试。测试对象保留在 Bucket 的可选目录下，重复测试会覆盖同名对象', 'npcink-site-toolbox'),
                    '百度统计' => __('百度统计', 'npcink-site-toolbox'),
                    '百度' => __('百度', 'npcink-site-toolbox'),
                    '在站点前台加载百度统计脚本' => __('在站点前台加载百度统计脚本', 'npcink-site-toolbox'),
                    '管理员启用百度统计模块并保存非空站点 ID 后，访客打开前台页面时' => __('管理员启用百度统计模块并保存非空站点 ID 后，访客打开前台页面时', 'npcink-site-toolbox'),
                    '访客浏览器会向百度发送 IP 地址、User-Agent、Referrer、页面 URL 以及百度统计脚本按其政策处理的访问数据' => __('访客浏览器会向百度发送 IP 地址、User-Agent、Referrer、页面 URL 以及百度统计脚本按其政策处理的访问数据', 'npcink-site-toolbox'),
                    '百度统计站点 ID 存在本站 WordPress 数据库' => __('百度统计站点 ID 存在本站 WordPress 数据库', 'npcink-site-toolbox'),
                    'Google Search Console 站点验证' => __('Google Search Console 站点验证', 'npcink-site-toolbox'),
                    '输出管理员提供的站点验证 meta 标签' => __('输出管理员提供的站点验证 meta 标签', 'npcink-site-toolbox'),
                    '管理员启用模块并保存非空验证码后' => __('管理员启用模块并保存非空验证码后', 'npcink-site-toolbox'),
                    '无；插件只输出 meta 标签，不主动请求 Google' => __('无；插件只输出 meta 标签，不主动请求 Google', 'npcink-site-toolbox'),
                    '站点验证码存在本站 WordPress 数据库' => __('站点验证码存在本站 WordPress 数据库', 'npcink-site-toolbox'),
                    'Bing Webmaster Tools 站点验证' => __('Bing Webmaster Tools 站点验证', 'npcink-site-toolbox'),
                    '无；插件只输出 meta 标签，不主动请求 Microsoft' => __('无；插件只输出 meta 标签，不主动请求 Microsoft', 'npcink-site-toolbox'),
                    'GitHub 项目 API' => __('GitHub 项目 API', 'npcink-site-toolbox'),
                    'GitHub' => __('GitHub', 'npcink-site-toolbox'),
                    '读取作者主动插入的公开仓库摘要、主要语言、Stars、Forks 和归档状态' => __('读取作者主动插入的公开仓库摘要、主要语言、Stars、Forks 和归档状态', 'npcink-site-toolbox'),
                    '作者插入 GitHub 项目区块并填写公开仓库地址，且本地缓存过期时' => __('作者插入 GitHub 项目区块并填写公开仓库地址，且本地缓存过期时', 'npcink-site-toolbox'),
                    '公开的仓库所有者和仓库名称、站点服务器 IP、插件 User-Agent 和正常 HTTP 请求头；不发送文章内容、访客 IP、Token 或插件设置' => __('公开的仓库所有者和仓库名称、站点服务器 IP、插件 User-Agent 和正常 HTTP 请求头；不发送文章内容、访客 IP、Token 或插件设置', 'npcink-site-toolbox'),
                    '成功响应在本站短期缓存 12 小时，失败响应缓存 30 分钟；不保存 GitHub 凭据' => __('成功响应在本站短期缓存 12 小时，失败响应缓存 30 分钟；不保存 GitHub 凭据', 'npcink-site-toolbox'),
                    'DeepSeek 诊断服务' => __('DeepSeek 诊断服务', 'npcink-site-toolbox'),
                    'DeepSeek Provider' => __('DeepSeek Provider', 'npcink-site-toolbox'),
                    '在管理员明确启动 AI 诊断后解释受限的站点事实和前后复验结果' => __('在管理员明确启动 AI 诊断后解释受限的站点事实和前后复验结果', 'npcink-site-toolbox'),
                    'WordPress 7.0+、已安装并连接 DeepSeek Provider，且管理员审阅白名单快照后主动开始分析或追问时' => __('WordPress 7.0+、已安装并连接 DeepSeek Provider，且管理员审阅白名单快照后主动开始分析或追问时', 'npcink-site-toolbox'),
                    '白名单事实、提示词、服务器 IP、正常 HTTP 请求头和由 WordPress Connectors/Provider 管理的 API 凭据；不发送凭据字段、文章内容、用户、评论、路径或请求日志' => __('白名单事实、提示词、服务器 IP、正常 HTTP 请求头和由 WordPress Connectors/Provider 管理的 API 凭据；不发送凭据字段、文章内容、用户、评论、路径或请求日志', 'npcink-site-toolbox'),
                    '问题、快照、基线、追问历史和回答只存在当前浏览器页面；本插件不读取或保存 Provider 凭据' => __('问题、快照、基线、追问历史和回答只存在当前浏览器页面；本插件不读取或保存 Provider 凭据', 'npcink-site-toolbox'),
                    '本插件默认不收集用户个人身份信息。所有配置数据存储在 WordPress 数据库内。' => __('本插件默认不收集用户个人身份信息。所有配置数据存储在 WordPress 数据库内。', 'npcink-site-toolbox'),
                    '部分功能需要连接第三方服务，具体触发条件、数据流向及服务条款见上方服务列表。' => __('部分功能需要连接第三方服务，具体触发条件、数据流向及服务条款见上方服务列表。', 'npcink-site-toolbox'),
                    '所有列出的出站连接都需要管理员明确启用或主动运行检测；插件默认不向开发者发送遥测。' => __('所有列出的出站连接都需要管理员明确启用或主动运行检测；插件默认不向开发者发送遥测。', 'npcink-site-toolbox'),
                    '已保存凭据存储在 WordPress 数据库中，仅在管理员启用对应功能或主动运行对象存储连接测试时用于服务端鉴权请求；未保存的对象存储凭据草稿只用于当次测试。插件不会把凭据发送给开发者。' => __('已保存凭据存储在 WordPress 数据库中，仅在管理员启用对应功能或主动运行对象存储连接测试时用于服务端鉴权请求；未保存的对象存储凭据草稿只用于当次测试。插件不会把凭据发送给开发者。', 'npcink-site-toolbox'),
                    '卸载会清理插件设置、计划任务、临时缓存、评论拦截标记及纯运行态附件标记，但不会删除或改写媒体文件、文章、评论、分类或远端对象。已转换附件的 WebP 恢复记录会保留，避免失去恢复原 JPEG 所需的信息；如需由插件执行恢复，请在卸载前完成。' => __('卸载会清理插件设置、计划任务、临时缓存、评论拦截标记及纯运行态附件标记，但不会删除或改写媒体文件、文章、评论、分类或远端对象。已转换附件的 WebP 恢复记录会保留，避免失去恢复原 JPEG 所需的信息；如需由插件执行恢复，请在卸载前完成。', 'npcink-site-toolbox'),
                );
            }
            return is_string($value) && isset($translations[$value]) ? $translations[$value] : $value;
        }
    }
}
