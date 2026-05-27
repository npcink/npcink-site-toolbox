<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * 用户反馈与数据洞察
 *
 * 功能：
 * - 插件内反馈表单（自动附带环境信息）
 * - 匿名使用数据统计（需用户授权）
 * - 数据洞察面板
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Feedback')) {
    class MaBox_Feedback {

        private static $config;
        private static $telemetry_option_key = 'mabox_telemetry_data';

        public static function run($config) {
            self::$config = $config;
            add_action('mabox_register_rest_routes', array(__CLASS__, 'register_rest_routes'));
            add_action('mabox_telemetry_cron', array(__CLASS__, 'send_telemetry'));

            if (!empty($config['telemetry_enabled'])) {
                if (!wp_next_scheduled('mabox_telemetry_cron')) {
                    wp_schedule_event(time(), 'weekly', 'mabox_telemetry_cron');
                }
            }
        }

        public static function register_rest_routes() {
            register_rest_route('mabox/v1', '/feedback/submit', array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_submit_feedback'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            ));

            register_rest_route('mabox/v1', '/feedback/telemetry', array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_report_telemetry'),
                'permission_callback' => '__return_true', // 匿名数据收集，但回调中有启用检查
            ));

            register_rest_route('mabox/v1', '/feedback/insights', array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'rest_get_insights'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'), // 统计数据仅管理员可见
            ));
        }

        public static function rest_submit_feedback($request) {
            if (empty(self::$config['feedback_enabled'])) {
                return array('success' => false, 'error' => '反馈功能未启用');
            }

            $subject  = sanitize_text_field($request->get_param('subject'));
            $content  = sanitize_textarea_field($request->get_param('content'));
            $type     = sanitize_text_field($request->get_param('type'));

            if (empty($content)) {
                return array('success' => false, 'error' => '反馈内容不能为空');
            }

            $env = self::get_environment_info();
            $email = !empty(self::$config['feedback_email']) ? self::$config['feedback_email'] : get_option('admin_email');

            $headers = array('Content-Type: text/html; charset=UTF-8');
            $body = sprintf(
                '<h3>插件反馈</h3><p><b>类型：</b>%s</p><p><b>主题：</b>%s</p><p><b>内容：</b>%s</p><hr/><h4>环境信息</h4><pre>%s</pre>',
                esc_html($type),
                esc_html($subject),
                nl2br(esc_html($content)),
                esc_html(print_r($env, true))
            );

            $sent = wp_mail($email, '[魔法工具箱] 新反馈：' . $subject, $body, $headers);

            if ($sent) {
                self::record_feedback_stat($type);
                return array('success' => true, 'message' => !empty(self::$config['feedback_auto_reply']) ? self::$config['feedback_auto_reply'] : '反馈已提交，感谢您的宝贵意见！');
            }

            return array('success' => false, 'error' => '邮件发送失败，请稍后重试');
        }

        public static function rest_report_telemetry($request) {
            if (empty(self::$config['telemetry_enabled'])) {
                return array('success' => false, 'error' => '数据收集未启用');
            }

            $data = $request->get_json_params();
            if (!$data || !is_array($data)) {
                return array('success' => false, 'error' => '数据格式无效');
            }

            $telemetry = get_option(self::$telemetry_option_key, array());
            $telemetry[] = array(
                'data'      => $data,
                'timestamp' => current_time('mysql'),
                'version'   => MAGICK_MIXTURE_VERSION,
            );

            if (count($telemetry) > 100) {
                $telemetry = array_slice($telemetry, -100);
            }

            update_option(self::$telemetry_option_key, $telemetry);
            return array('success' => true);
        }

        public static function rest_get_insights() {
            $feedback_stats = get_option('mabox_feedback_stats', array());
            $telemetry = get_option(self::$telemetry_option_key, array());

            $total_users = get_option('mabox_telemetry_user_count', 0);
            $feature_popularity = get_option('mabox_feature_popularity', array());

            return array(
                'success' => true,
                'data'    => array(
                    'feedback_stats'     => $feedback_stats,
                    'total_telemetry'    => count($telemetry),
                    'estimated_users'    => $total_users,
                    'feature_popularity' => $feature_popularity,
                ),
            );
        }

        public static function send_telemetry() {
            if (empty(self::$config['telemetry_enabled'])) {
                return;
            }

            $telemetry = get_option(self::$telemetry_option_key, array());
            if (empty($telemetry)) {
                return;
            }

            $payload = array(
                'version'     => MAGICK_MIXTURE_VERSION,
                'php_version' => PHP_VERSION,
                'wp_version'  => get_bloginfo('version'),
                'reports'     => array_slice($telemetry, -10),
            );

            wp_remote_post('https://telemetry.npc.ink/api/report', array(
                'timeout' => 10,
                'body'    => json_encode($payload),
                'headers' => array('Content-Type' => 'application/json'),
            ));
        }

        private static function record_feedback_stat($type) {
            $stats = get_option('mabox_feedback_stats', array());
            if (!isset($stats[$type])) {
                $stats[$type] = 0;
            }
            $stats[$type]++;
            update_option('mabox_feedback_stats', $stats);
        }

        private static function get_environment_info() {
            return array(
                'WordPress' => get_bloginfo('version'),
                'PHP'       => PHP_VERSION,
                '主题'      => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
                '插件版本'  => MAGICK_MIXTURE_VERSION,
                '站点地址'  => home_url(),
            );
        }

        public static function check_admin_permission() {
            return current_user_can('manage_options');
        }
    }
}
