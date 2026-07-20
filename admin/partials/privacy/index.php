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
                'name'        => 'Loli.net 国内资源镜像',
                'provider'    => 'u.sb / Loli.net',
                'purpose'     => '把 Gravatar、Google Fonts 和 Google Hosted Libraries 资源 URL 替换为 gravatar.loli.net、fonts.loli.net、gstatic.loli.net 或 ajax.loli.net；也支持管理员自定义目标',
                'trigger'     => '管理员启用 CDN 替换总开关及对应子项后，访客页面需要相关资源时',
                'data_sent'   => '访客浏览器向镜像发送请求资源 URL、IP 地址及标准 HTTP 请求头；Gravatar 请求还包含头像哈希',
                'data_stored' => '镜像地址和自定义替换规则存在本站 WordPress 数据库',
                'opt_in'      => true,
                'config_key'  => 'optimize.site.cdn_replace',
                'service_url' => 'https://u.sb/css-cdn/',
                'terms_url'   => 'https://u.sb/terms/',
                'privacy_url' => 'https://u.sb/privacy/',
            ),
            array(
                'name'        => '国内访问连通性检测',
                'provider'    => 'Google、Automattic（Gravatar）与 WordPress.org',
                'purpose'     => '检测服务器能否访问 Google Fonts、Google Hosted Libraries、Gravatar 和 WordPress.org API',
                'trigger'     => '具有管理权限的管理员在维护工具中主动运行检测时',
                'data_sent'   => '不发送站点内容或插件配置；各服务可收到服务器 IP、目标 URL 和标准 HTTP 请求头',
                'data_stored' => '检测可达性与延迟结果在本站缓存一小时',
                'opt_in'      => true,
                'config_key'  => 'manual.domestic_environment_check',
                'service_url' => 'https://developers.google.com/fonts/faq/privacy',
                'terms_url'   => 'https://policies.google.com/terms',
                'privacy_url' => 'https://policies.google.com/privacy',
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
                    <strong>Npcink Site Toolbox</strong> 隐私提示：本插件只会在管理员明确启用或主动触发相关功能后连接已披露的外部服务。
                    <a href="<?php echo esc_url(admin_url('plugins.php?page=npcink-site-toolbox')); ?>">查看完整隐私说明 →</a>
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
                'services'       => self::$external_services,
                'opt_in_services' => self::get_opt_in_services(),
                'privacy_policy' => array(
                    'data_collection' => '本插件默认不收集用户个人身份信息。所有配置数据存储在 WordPress 数据库内。',
                    'third_party'     => '部分功能需要连接第三方服务，具体触发条件、数据流向及服务条款见上方服务列表。',
                    'user_consent'    => '所有列出的出站连接都需要管理员明确启用相关功能或主动运行检测；插件默认不向开发者发送遥测。',
                    'api_keys'        => '已保存凭据存储在 WordPress 数据库中，仅在管理员启用对应功能或主动运行对象存储连接测试时用于服务端鉴权请求；未保存的对象存储凭据草稿只用于当次测试。插件不会把凭据发送给开发者。',
                ),
            );
        }
    }
}
