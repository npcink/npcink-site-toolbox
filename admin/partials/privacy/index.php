<?php
/**
 * 隐私与外部服务说明
 *
 * 列出插件连接的所有外部服务、数据流向、隐私政策。
 *
 * @since 2.4.0
 */
if (!class_exists('MaBox_Privacy')) {
    class MaBox_Privacy
    {
        /**
         * 外部服务列表
         */
        private static $external_services = array(
            array(
                'name'        => '微信 JSSDK',
                'provider'    => '腾讯（微信）',
                'purpose'     => '微信内分享自定义标题、描述、图标',
                'data_sent'   => '当前页面 URL',
                'data_stored' => '无',
                'opt_in'      => false,
                'config_key'  => 'domestic.wechat',
            ),
            array(
                'name'        => '对象存储 OSS',
                'provider'    => '阿里云/腾讯云/七牛',
                'purpose'     => '将媒体文件存储至云端，减轻服务器压力',
                'data_sent'   => '媒体文件（图片、文档等）',
                'data_stored' => '用户配置的 AccessKey、Bucket 信息',
                'opt_in'      => false,
                'config_key'  => 'performance.oss',
            ),
            array(
                'name'        => '百度统计',
                'provider'    => '百度',
                'purpose'     => '网站流量统计分析',
                'data_sent'   => '访客访问数据（匿名）',
                'data_stored' => '用户配置的统计 ID',
                'opt_in'      => true,
                'config_key'  => 'stats.baidu',
            ),
            array(
                'name'        => 'Google Analytics',
                'provider'    => 'Google',
                'purpose'     => '网站流量统计分析',
                'data_sent'   => '访客访问数据（匿名）',
                'data_stored' => '用户配置的 Tracking ID',
                'opt_in'      => true,
                'config_key'  => 'stats.google',
            ),
            array(
                'name'        => '必应统计',
                'provider'    => 'Microsoft',
                'purpose'     => '网站流量统计分析',
                'data_sent'   => '访客访问数据（匿名）',
                'data_stored' => '用户配置的 Tracking ID',
                'opt_in'      => true,
                'config_key'  => 'stats.bing',
            ),
            array(
                'name'        => '用户反馈',
                'provider'    => '插件开发者',
                'purpose'     => '收集用户反馈以改进插件',
                'data_sent'   => '用户提交的反馈内容（需用户同意）',
                'data_stored' => '反馈内容、联系方式（如用户提供）',
                'opt_in'      => true,
                'config_key'  => 'feedback',
            ),
            array(
                'name'        => '增值服务',
                'provider'    => '插件开发者',
                'purpose'     => '提供付费增值服务入口',
                'data_sent'   => '无（仅跳转链接）',
                'data_stored' => '无',
                'opt_in'      => true,
                'config_key'  => 'services',
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
            if (get_option('mabox_privacy_notice_dismissed')) {
                return;
            }

            $screen = get_current_screen();
            if ($screen && $screen->id !== 'plugins_page_MaBox_config') {
                return;
            }

            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>WP Magick Toolbox</strong> 隐私提示：本插件可能连接外部服务（百度、腾讯、阿里云等）。
                    <a href="<?php echo esc_url(admin_url('plugins.php?page=MaBox_config')); ?>">查看完整隐私说明 →</a>
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
                    'third_party'     => '部分功能需要连接第三方服务，具体数据流向见上方服务列表。',
                    'user_consent'    => '标记为 "需同意" 的服务仅在用户主动开启后才会连接外部服务。',
                    'api_keys'        => '用户配置的 API Key 等敏感信息存储在数据库选项中，不会出站传输。',
                ),
            );
        }
    }
}
