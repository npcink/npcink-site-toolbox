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
            return is_string($value) && $value !== '' && preg_match('/[\x{4e00}-\x{9fff}]/u', $value)
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Values come only from the fixed privacy disclosure tree above and are extracted into the plugin POT.
                ? __($value, 'npcink-site-toolbox')
                : $value;
        }
    }
}
