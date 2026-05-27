<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * AI 审核引擎主模块
 *
 * 功能：
 * - 评论提交时自动审核
 * - 审核日志记录与人工复核
 * - REST API 端点（日志查询、复核、测试）
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Ai_Review')) {
    class MaBox_Ai_Review {

        private static $config;
        private static $log_option_key = 'mabox_ai_review_log';

        public static function run($config) {
            self::$config = $config;


            MaBox_Ai_Provider_Manager::get_instance()->set_config($config);

            add_action('preprocess_comment', array(__CLASS__, 'review_comment'), 1, 1);
            add_action('mabox_register_rest_routes', array(__CLASS__, 'register_rest_routes'));
        }

        public static function review_comment($comment_data) {
            $comment_text = !empty($comment_data['comment_content']) ? $comment_data['comment_content'] : '';
            if (empty($comment_text)) {
                return $comment_data;
            }

            $result = MaBox_Ai_Provider_Manager::get_instance()->review($comment_text);

            self::log_review(array(
                'comment_author'  => !empty($comment_data['comment_author']) ? $comment_data['comment_author'] : 'anonymous',
                'comment_email'   => !empty($comment_data['comment_author_email']) ? $comment_data['comment_author_email'] : '',
                'comment_text'    => mb_substr($comment_text, 0, 200),
                'is_safe'         => $result['is_safe'],
                'confidence'      => $result['confidence'],
                'reason'          => $result['reason'],
                'risk_level'      => $result['risk_level'],
                'provider'        => MaBox_Ai_Provider_Manager::get_instance()->get_active_provider()->get_name(),
                'reviewed_at'     => current_time('mysql'),
                'status'          => 'pending_review',
                'reviewer_action' => '',
            ));

            $mode = !empty(self::$config['mode']) ? self::$config['mode'] : 'mark';

            if (!$result['is_safe']) {
                if ($mode === 'block') {
                    wp_die(
                        esc_html__('您的评论未通过审核：' . $result['reason']),
                        esc_html__('评论审核', 'magick-toolbox'),
                        array('response' => 403)
                    );
                } else {
                    $comment_data['comment_approved'] = 0;
                }
            }

            return $comment_data;
        }

        private static function log_review($entry) {
            if (empty(self::$config['log_enabled'])) {
                return;
            }

            $max_entries = !empty(self::$config['log_max_entries']) ? intval(self::$config['log_max_entries']) : 500;
            $log = get_option(self::$log_option_key, array());

            array_unshift($log, $entry);

            if (count($log) > $max_entries) {
                $log = array_slice($log, 0, $max_entries);
            }

            update_option(self::$log_option_key, $log);
        }

        public static function get_logs($page = 1, $per_page = 20) {
            $log = get_option(self::$log_option_key, array());
            $total = count($log);
            $offset = ($page - 1) * $per_page;
            $items = array_slice($log, $offset, $per_page);

            return array(
                'total' => $total,
                'page'  => $page,
                'per_page' => $per_page,
                'items' => $items,
            );
        }

        public static function review_entry($index, $action) {
            $log = get_option(self::$log_option_key, array());
            if (!isset($log[$index])) {
                return array('success' => false, 'error' => '记录不存在');
            }

            $log[$index]['reviewer_action'] = $action;
            $log[$index]['reviewed_at'] = current_time('mysql');

            if ($action === 'approve' && $log[$index]['status'] === 'pending_review') {
                $log[$index]['status'] = 'approved';
            } elseif ($action === 'reject' && $log[$index]['status'] === 'pending_review') {
                $log[$index]['status'] = 'rejected';
            }

            update_option(self::$log_option_key, $log);
            return array('success' => true);
        }

        public static function clear_logs() {
            delete_option(self::$log_option_key);
            return array('success' => true);
        }

        public static function test_provider() {
            $test_text = '这是一条测试评论，用于验证审核功能是否正常。';
            $result = MaBox_Ai_Provider_Manager::get_instance()->review($test_text);
            $provider = MaBox_Ai_Provider_Manager::get_instance()->get_active_provider();

            return array(
                'success'  => true,
                'provider' => $provider->get_name(),
                'result'   => $result,
                'test_text' => $test_text,
            );
        }

        public static function register_rest_routes() {
            register_rest_route('mabox/v1', '/ai-review/logs', array(
                'methods'             => 'GET',
                'callback'            => array(__CLASS__, 'rest_get_logs'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            ));

            register_rest_route('mabox/v1', '/ai-review/review/(?P<index>\d+)', array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_review_entry'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
                'args'                => array(
                    'index'  => array('required' => true, 'type' => 'integer'),
                    'action' => array('required' => true, 'type' => 'string'),
                ),
            ));

            register_rest_route('mabox/v1', '/ai-review/clear-logs', array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_clear_logs'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            ));

            register_rest_route('mabox/v1', '/ai-review/test', array(
                'methods'             => 'POST',
                'callback'            => array(__CLASS__, 'rest_test_provider'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            ));
        }

        public static function rest_get_logs($request) {
            $page     = $request->get_param('page') ? intval($request->get_param('page')) : 1;
            $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 20;
            return rest_ensure_response(array(
                'success' => true,
                'data'    => self::get_logs($page, $per_page),
            ));
        }

        public static function rest_review_entry($request) {
            $index  = intval($request->get_param('index'));
            $action = sanitize_text_field($request->get_param('action'));
            return rest_ensure_response(self::review_entry($index, $action));
        }

        public static function rest_clear_logs($request) {
            return rest_ensure_response(self::clear_logs());
        }

        public static function rest_test_provider($request) {
            return rest_ensure_response(self::test_provider());
        }

        public static function check_admin_permission() {
            return current_user_can('manage_options');
        }
    }
}
