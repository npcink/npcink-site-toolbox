<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 配置 Schema 定义类
 *
 * 负责：
 * 1. 定义每个模块、子模块、字段的类型、默认值、校验规则
 * 2. 提供 validate() 和 sanitize() 方法供保存时调用
 * 3. 提供 get_defaults() 供新安装或重置使用
 * 4. 提供 get_schema() 供 REST API 返回给前端
 *
 * @since 2.5.0
 */

if (!class_exists('MaBox_Config_Schema')) {
    class MaBox_Config_Schema {

        private static $schema = null;

        private static function string_list_items() {
            return array('type' => 'string');
        }

        private static function number_list_items() {
            return array('type' => 'number', 'finite' => true);
        }

        private static function batch_replace_items() {
            return array(
                'type' => 'object',
                'required' => array('find', 'replace'),
                'additionalProperties' => false,
                'properties' => array(
                    'find' => array('type' => 'string'),
                    'replace' => array('type' => 'string'),
                ),
            );
        }

        private static function build_schema() {
            return array(
                'optimize' => array(
                    '_option_key' => MAGICK_MIXTURE_OPTION_OPTIMIZE,
                    'site' => array(
                        'hide_top_toolbar'       => array('type' => 'boolean', 'default' => false),
                        'no_escape'              => array('type' => 'boolean', 'default' => false),
                        'remove_RSS_version'     => array('type' => 'boolean', 'default' => false),
                        'renew'                 => array('type' => 'boolean', 'default' => false),
                        'category_link_simplify' => array('type' => 'boolean', 'default' => false),
                        'search_link_simplify'   => array('type' => 'boolean', 'default' => false),
                        'remove_sitemap_users'   => array('type' => 'boolean', 'default' => false),
                        'user_list_show_nickname' => array('type' => 'boolean', 'default' => false),
                        'cdn_replace'            => array('type' => 'boolean', 'default' => false),
                        'cdn_gravatar'           => array('type' => 'boolean', 'default' => false),
                        'cdn_gravatar_mirror'    => array('type' => 'string',  'default' => 'gravatar.loli.net/avatar/', 'sanitize' => 'esc_url_raw'),
                        'cdn_google_fonts'       => array('type' => 'boolean', 'default' => false),
                        'cdn_google_fonts_mirror' => array('type' => 'string',  'default' => 'fonts.loli.net', 'sanitize' => 'sanitize_text_field'),
                        'cdn_google_ajax'        => array('type' => 'boolean', 'default' => false),
                        'cdn_custom'             => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'hide_email_ip'          => array('type' => 'boolean', 'default' => false),
                    ),
                    'medium' => array(
                        'img_add_tag'     => array('type' => 'boolean', 'default' => false),
                        'no_auto_size'    => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'low', 'title' => '禁止缩略图', 'warning' => '此功能可能与部分主题不兼容，导致图片显示异常。', 'suggestion' => '开启前请确认主题支持。'), 'feature_id' => 'optimize-medium-no_auto_size', 'label' => '禁止缩略图', 'group' => '媒体'),
                        'medium_add_svg'  => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'low', 'title' => 'SVG 上传支持', 'warning' => 'SVG 文件可能包含恶意脚本，已做安全过滤但仍需注意。', 'suggestion' => '仅允许可信用户上传 SVG 文件。'), 'feature_id' => 'optimize-medium-medium_add_svg', 'label' => 'SVG 上传支持', 'group' => '媒体'),
                        'upload_auto_name' => array('type' => 'string',  'default' => 'false', 'sanitize' => 'sanitize_text_field'),
                    ),
                    'admin' => array(
                        'add_user'            => array('type' => 'boolean', 'default' => false),
                        'add_time'            => array('type' => 'boolean', 'default' => false),
                        'show_id'             => array('type' => 'boolean', 'default' => false),
                        'thumbnail_switcher'  => array('type' => 'boolean', 'default' => false),
                    ),
                ),
                'page' => array(
                    '_option_key' => MAGICK_MIXTURE_OPTION_PAGE,
                    'comment' => array(
                        'interval'                   => array('type' => 'boolean', 'default' => false),
                        'interval_time'              => array('type' => 'number',  'default' => 5, 'min' => 1, 'max' => 3600),
                        'words_number'               => array('type' => 'boolean', 'default' => false),
                        'words_number_min'           => array('type' => 'number',  'default' => 0, 'min' => 0),
                        'words_number_max'           => array('type' => 'number',  'default' => 120, 'min' => 1),
                        'english'                    => array('type' => 'boolean', 'default' => false),
                        'only'                       => array('type' => 'boolean', 'default' => false),
                        'sensitive_words'            => array('type' => 'boolean', 'default' => false),
                        'sensitive_words_list'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'sensitive_words_action'     => array('type' => 'string',  'default' => 'replace', 'enum' => array('replace', 'block')),
                        'sensitive_words_replace_char' => array('type' => 'string',  'default' => '***', 'sanitize' => 'sanitize_text_field'),
                    ),
                    'feature' => array(
                        'reading_progress'         => array('type' => 'boolean', 'default' => false),
                        'reading_progress_color'    => array('type' => 'string',  'default' => '#1677ff', 'sanitize' => 'sanitize_hex_color'),
                        'reading_progress_height'  => array('type' => 'number',  'default' => 3, 'min' => 1, 'max' => 20),
                    ),
                    'function' => array(
                        'first_picture'           => array('type' => 'boolean', 'default' => false),
                        'add_inks'                => array('type' => 'boolean', 'default' => false),
                        'add_last_update'         => array('type' => 'boolean', 'default' => false),
                        'no_login_img'            => array('type' => 'boolean', 'default' => false),
                        'maintenance_tips'        => array('type' => 'string',  'default' => 'false', 'sanitize' => 'sanitize_text_field'),
                        'countdown'               => array('type' => 'array',   'default' => array(), 'items' => self::string_list_items()),
                        'countdown_title'         => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'countdown_image'         => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                        'countdown_content'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'default_thumbnail'       => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                        'search_limit'            => array('type' => 'boolean', 'default' => false),
                        'search_limit_count'      => array('type' => 'number',  'default' => 10, 'min' => 1, 'max' => 100),
                        'batch_replace'           => array('type' => 'boolean', 'default' => false),
                        'batch_replace_pairs'     => array('type' => 'array',   'default' => array(), 'items' => self::batch_replace_items()),
                        'login_search'            => array('type' => 'boolean', 'default' => false),
                    ),
                    'jurisdiction' => array(
                        'category_id'             => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'tag_id'                  => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'page_id'                => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'single_id'              => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'tip_content'             => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                    ),
                ),
                'function' => array(
                    '_option_key' => MAGICK_MIXTURE_OPTION_FUNCTION,
                    'auxiliary' => array(
                        'single_count'       => array('type' => 'boolean', 'default' => false),
                        'no_malice_key'      => array('type' => 'boolean', 'default' => false),
                        'malice_keu_content' => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'baidu_tonji'        => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'google_tonji'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'biying_tonji'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'uniqueKey'         => array('type' => 'number',  'default' => 0),
                    ),

                    'seo' => array(
                        'title'        => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'keywords'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'description'  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'seo_single'   => array('type' => 'boolean', 'default' => false),
                        'seo_category' => array('type' => 'boolean', 'default' => false),
                    ),
                    'config' => array(
                        'pop_tips'      => array('type' => 'boolean', 'default' => false),
                        'tips_time'     => array('type' => 'number',  'default' => 0, 'min' => 0),
                        'tips_content'  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'tips_button'   => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'tips_link'     => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                    ),
                ),
                'login' => array(
                    '_option_key' => MAGICK_MIXTURE_OPTION_LOGIN,
                    'security' => array(
                        'login_code' => array('type' => 'string',  'default' => 'false', 'sanitize' => 'sanitize_text_field'),
                    ),
                ),

                'domestic' => array(
                    '_option_key' => MAGICK_MIXTURE_OPTION_DOMESTIC,
                    'compliance' => array(
                        'icp_enabled'    => array('type' => 'boolean', 'default' => false),
                        'icp_number'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'icp_link'       => array('type' => 'string',  'default' => 'https://beian.miit.gov.cn/', 'sanitize' => 'esc_url_raw'),
                        'police_enabled' => array('type' => 'boolean', 'default' => false),
                        'police_number'  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'police_link'    => array('type' => 'string',  'default' => 'https://www.beian.gov.cn/portal/registerSystemInfo', 'sanitize' => 'esc_url_raw'),
                        'cookie_enabled' => array('type' => 'boolean', 'default' => false),
                        'cookie_style'   => array('type' => 'string',  'default' => 'bottom', 'enum' => array('bottom', 'top', 'center'), 'sanitize' => 'sanitize_text_field'),
                        'cookie_title'   => array('type' => 'string',  'default' => 'Cookie 同意', 'sanitize' => 'sanitize_text_field'),
                        'cookie_content' => array('type' => 'string',  'default' => '本网站使用 Cookie 来改善您的体验。继续浏览即表示您同意我们的 Cookie 政策。', 'sanitize' => 'sanitize_textarea_field'),
                        'cookie_button'  => array('type' => 'string',  'default' => '我知道了', 'sanitize' => 'sanitize_text_field'),
                        'copyright_enabled' => array('type' => 'boolean', 'default' => false),
                        'copyright_html' => array('type' => 'string',  'default' => '', 'sanitize' => 'wp_kses_post'),
                    ),
                    'wechat' => array(
                        'jssdk_enabled'          => array('type' => 'boolean', 'default' => false),
                        'appid'                  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'appsecret'              => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'sensitive' => true),
                        'guide_overlay_enabled'  => array('type' => 'boolean', 'default' => false),
                        'guide_mode'             => array('type' => 'string',  'default' => 'guide', 'sanitize' => 'sanitize_text_field'),
                        'guide_text'             => array('type' => 'string',  'default' => '点击右上角 ··· 在浏览器中打开', 'sanitize' => 'sanitize_text_field'),
                        'guide_qrcode'           => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                    ),
                    'comment_security' => array(
                        'blacklist_enabled'         => array('type' => 'boolean', 'default' => false),
                        'blacklist_words'           => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'blacklist_action'          => array('type' => 'string',  'default' => 'block', 'enum' => array('block', 'mark'), 'sanitize' => 'sanitize_text_field'),
                        'link_limit_enabled'        => array('type' => 'boolean', 'default' => false),
                        'link_limit_count'          => array('type' => 'number',  'default' => 2, 'min' => 0),
                        'nickname_filter_enabled'   => array('type' => 'boolean', 'default' => false),
                        'nickname_filter_words'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'email_domain_enabled'      => array('type' => 'boolean', 'default' => false),
                        'email_domain_blacklist'   => array('type' => 'string',  'default' => '10minutemail.com,guerrillamail.com,temp-mail.org', 'sanitize' => 'sanitize_textarea_field'),
                        'duplicate_enabled'         => array('type' => 'boolean', 'default' => false),
                        'ip_rate_enabled'           => array('type' => 'boolean', 'default' => false),
                        'ip_rate_limit'             => array('type' => 'number',  'default' => 5, 'min' => 1),
                        'ip_rate_window'            => array('type' => 'number',  'default' => 60, 'min' => 1),
                        'log_enabled'               => array('type' => 'boolean', 'default' => false),
                    ),
                    'login_security' => array(
                        'fail_limit_enabled'    => array('type' => 'boolean', 'default' => false),
                        'fail_limit_count'      => array('type' => 'number',  'default' => 5, 'min' => 1),
                        'fail_lock_duration'    => array('type' => 'number',  'default' => 30, 'min' => 1),
                        'ip_lock_enabled'       => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'low', 'title' => 'IP 锁定', 'warning' => 'IP 锁定可能在反向代理环境下误判，导致正常用户被锁定。', 'suggestion' => '如使用 CDN 或反向代理，请配置可信代理 IP。'), 'feature_id' => 'domestic-login_security-ip_lock_enabled', 'label' => 'IP 锁定', 'group' => '登录安全'),
                        'ip_lock_count'         => array('type' => 'number',  'default' => 10, 'min' => 1),
                        'ip_lock_duration'      => array('type' => 'number',  'default' => 60, 'min' => 1),
                        'custom_login_enabled'  => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'high', 'title' => '自定义登录地址', 'warning' => '修改登录地址后，原 wp-login.php 将被重定向，配置错误可能导致无法登录。', 'suggestion' => '记住新的登录地址，避免锁定自己。', 'noDismiss' => true), 'feature_id' => 'domestic-login_security-custom_login_enabled', 'label' => '自定义登录地址', 'group' => '登录安全'),
                        'custom_login_slug'     => array('type' => 'string',  'default' => 'my-login', 'sanitize' => 'sanitize_title'),
                        'ban_enumeration_enabled' => array('type' => 'boolean', 'default' => false),
                        'login_notify_enabled'  => array('type' => 'boolean', 'default' => false),
                        'login_log_enabled'     => array('type' => 'boolean', 'default' => false),
                        'ip_whitelist_enabled'  => array('type' => 'boolean', 'default' => false),
                        'ip_whitelist'          => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                    ),
                ),
                'performance' => array(
                    '_option_key' => MAGICK_MIXTURE_OPTION_PERFORMANCE,
                    'oss' => array(
                        'enabled'      => array('type' => 'boolean', 'default' => false),
                        'provider'     => array('type' => 'string',  'default' => 'aliyun', 'sanitize' => 'sanitize_text_field'),
                        'access_key'   => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'sensitive' => true),
                        'secret_key'   => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'sensitive' => true),
                        'bucket'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'region'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'domain'       => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                        'delete_local' => array('type' => 'boolean', 'default' => false),
                    ),
                    'seo_checker' => array(
                        'enabled' => array('type' => 'boolean', 'default' => false),
                    ),
                    'media_health' => array(
                        'enabled' => array('type' => 'boolean', 'default' => false),
                    ),
                    'search_enhance' => array(
                        'highlight_enabled' => array('type' => 'boolean', 'default' => false),
                        'recommend_enabled' => array('type' => 'boolean', 'default' => false),
                        'hotwords_enabled'  => array('type' => 'boolean', 'default' => false),
                    ),
                    'db_clean' => array(
                        'enabled'             => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'high', 'title' => '数据库清理', 'warning' => '数据库清理操作不可逆，删除的数据无法恢复。', 'suggestion' => '执行前务必先预览影响数量，并做好备份。', 'noDismiss' => true), 'feature_id' => 'performance-db_clean-enabled', 'label' => '数据库清理优化', 'group' => '数据库'),
                        'clean_revisions'    => array('type' => 'boolean', 'default' => false),
                        'clean_drafts'       => array('type' => 'boolean', 'default' => false),
                        'clean_spam_comments' => array('type' => 'boolean', 'default' => false),
                        'clean_transients'   => array('type' => 'boolean', 'default' => false),
                        'auto_clean'         => array('type' => 'boolean', 'default' => false),
                        'auto_clean_schedule' => array('type' => 'string',  'default' => 'weekly', 'enum' => array('daily', 'weekly', 'monthly'), 'sanitize' => 'sanitize_text_field'),
                    ),
                ),
            );
        }

        /**
         * 获取完整 schema
         */
        public static function get_schema() {
            if (self::$schema === null) {
                self::$schema = self::build_schema();
            }
            return self::$schema;
        }

        public static function get_ui_schema() {
            $schema = self::get_schema();
            $ui = array();

            foreach ($schema as $module_key => $module_def) {
                if ($module_key === '_option_key' || $module_key === '_flat') {
                    continue;
                }

                if (!empty($module_def['_flat'])) {
                    foreach ($module_def as $field_key => $field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat') {
                            continue;
                        }
                        if (!is_array($field_def) || !isset($field_def['type'])) {
                            continue;
                        }
                        $ui_field = self::extract_ui_field($field_def, $module_key, '', $field_key);
                        if ($ui_field !== null) {
                            $ui[$module_key . '-' . $field_key] = $ui_field;
                        }
                    }
                } else {
                    foreach ($module_def as $sub_key => $sub_def) {
                        if ($sub_key === '_option_key' || $sub_key === '_flat') {
                            continue;
                        }
                        if (!is_array($sub_def)) {
                            continue;
                        }
                        foreach ($sub_def as $field_key => $field_def) {
                            if ($field_key === '_option_key' || $field_key === '_flat') {
                                continue;
                            }
                            if (!is_array($field_def) || !isset($field_def['type'])) {
                                continue;
                            }
                            $ui_field = self::extract_ui_field($field_def, $module_key, $sub_key, $field_key);
                            if ($ui_field !== null) {
                                $ui[$module_key . '-' . $sub_key . '-' . $field_key] = $ui_field;
                            }
                        }
                    }
                }
            }

            if (class_exists('MaBox_Module_Metadata')) {
                $module_ui = MaBox_Module_Metadata::get_ui_metadata();
                $ui = self::merge_module_metadata($ui, $module_ui);
            }

            return $ui;
        }

        private static function merge_module_metadata($ui, $module_ui) {
            foreach ($module_ui as $module_id => $meta) {
                $matched = false;
                foreach ($ui as $key => &$entry) {
                    if (!empty($meta['feature_id']) && !empty($entry['feature_id']) && $entry['feature_id'] === $meta['feature_id']) {
                        if (isset($meta['risk_tags']) && !empty($meta['risk_tags'])) {
                            $entry['risk_tags'] = $meta['risk_tags'];
                        }
                        if (isset($meta['preset_tags']) && !empty($meta['preset_tags'])) {
                            $entry['preset_tags'] = $meta['preset_tags'];
                        }
                        if (isset($meta['depends_on'])) {
                            $entry['depends_on'] = $meta['depends_on'];
                        }
                        $matched = true;
                        break;
                    }
                }
                unset($entry);

                if (!$matched && !empty($meta['feature_id'])) {
                    $module_entry = array(
                        'path'        => isset($meta['config_path']) ? $meta['config_path'] : $module_id,
                        'type'        => 'module',
                        'label'       => isset($meta['label']) ? $meta['label'] : '',
                        'group'       => isset($meta['group']) ? $meta['group'] : '',
                        'feature_id'  => $meta['feature_id'],
                        'risk_tags'   => isset($meta['risk_tags']) ? $meta['risk_tags'] : array(),
                        'risk'        => isset($meta['risk']) ? $meta['risk'] : array('level' => 'none'),
                        'depends_on'  => isset($meta['depends_on']) ? $meta['depends_on'] : array(),
                        'preset_tags' => isset($meta['preset_tags']) ? $meta['preset_tags'] : array(),
                    );
                    $ui[$meta['feature_id']] = $module_entry;
                }
            }

            return $ui;
        }

        private static function extract_ui_field($field_def, $module_key, $sub_key, $field_key) {
            $has_ui = isset($field_def['label']) || isset($field_def['risk']) ||
                      isset($field_def['feature_id']) || isset($field_def['group']);
            if (!$has_ui) {
                return null;
            }

            $entry = array(
                'path' => $module_key . ($sub_key ? '.' . $sub_key : '') . '.' . $field_key,
                'type' => isset($field_def['type']) ? $field_def['type'] : 'string',
            );

            if (isset($field_def['label'])) {
                $entry['label'] = $field_def['label'];
            }
            if (isset($field_def['group'])) {
                $entry['group'] = $field_def['group'];
            }
            if (isset($field_def['feature_id'])) {
                $entry['feature_id'] = $field_def['feature_id'];
            }
            if (isset($field_def['risk'])) {
                $entry['risk'] = $field_def['risk'];
            }
            if (isset($field_def['depends_on'])) {
                $entry['depends_on'] = $field_def['depends_on'];
            }
            if (isset($field_def['preset_tags'])) {
                $entry['preset_tags'] = $field_def['preset_tags'];
            }
            if (isset($field_def['risk_tags'])) {
                $entry['risk_tags'] = $field_def['risk_tags'];
            } elseif (!empty($entry['risk']) && !empty($entry['risk']['level']) && $entry['risk']['level'] !== 'none') {
                $tag_map = array('low' => '谨慎', 'high' => '安全');
                $level = $entry['risk']['level'];
                if (isset($tag_map[$level])) {
                    $entry['risk_tags'] = array($tag_map[$level]);
                }
            }

            return $entry;
        }

        /**
         * 获取所有模块的默认值
         *
         * @return array
         */
        public static function get_defaults() {
            $schema = self::get_schema();
            $defaults = array();

            foreach ($schema as $module_key => $module_def) {
                if ($module_key === '_option_key' || $module_key === '_flat') {
                    continue;
                }

                if (!empty($module_def['_flat'])) {
                    $defaults[$module_key] = array();
                    foreach ($module_def as $field_key => $field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat') {
                            continue;
                        }
                        $defaults[$module_key][$field_key] = $field_def['default'];
                    }
                } else {
                    $defaults[$module_key] = array();
                    foreach ($module_def as $sub_key => $sub_def) {
                        if ($sub_key === '_option_key' || $sub_key === '_flat') {
                            continue;
                        }
                        $defaults[$module_key][$sub_key] = array();
                        foreach ($sub_def as $field_key => $field_def) {
                            if ($field_key === '_option_key' || $field_key === '_flat') {
                                continue;
                            }
                            $defaults[$module_key][$sub_key][$field_key] = $field_def['default'];
                        }
                    }
                }
            }

            return $defaults;
        }

        /**
         * 严格验证浏览器提交的非敏感设置树。
         *
         * 读取端可以用 defaults 补齐新安装结构，但写入端不得将缺失、
         * 未知键或错误 JSON 类型静默修正后整包落库。敏感字段不属于
         * settings 树，只能通过 secretChanges 提交。
         *
         * @param mixed $settings
         * @return array{valid: bool, errors: array<int, string>}
         */
        public static function validate_browser_settings($settings) {
            if (!is_array($settings)) {
                return array('valid' => false, 'errors' => array('settings 必须为对象'));
            }

            $schema = self::get_schema();
            $expected_modules = array();
            foreach ($schema as $module_key => $module_def) {
                if ($module_key !== '_option_key' && $module_key !== '_flat') {
                    $expected_modules[] = $module_key;
                }
            }

            $errors = array();
            self::validate_exact_keys($settings, $expected_modules, 'settings', $errors);

            foreach ($expected_modules as $module_key) {
                if (!array_key_exists($module_key, $settings) || !is_array($settings[$module_key])) {
                    if (array_key_exists($module_key, $settings)) {
                        $errors[] = "settings.{$module_key} 必须为对象";
                    }
                    continue;
                }

                $module_def = $schema[$module_key];
                if (!empty($module_def['_flat'])) {
                    self::validate_browser_fields($settings[$module_key], $module_def, "settings.{$module_key}", $errors);
                    continue;
                }

                $expected_submodules = array();
                foreach ($module_def as $sub_key => $sub_def) {
                    if ($sub_key !== '_option_key' && $sub_key !== '_flat') {
                        $expected_submodules[] = $sub_key;
                    }
                }
                self::validate_exact_keys($settings[$module_key], $expected_submodules, "settings.{$module_key}", $errors);

                foreach ($expected_submodules as $sub_key) {
                    if (!array_key_exists($sub_key, $settings[$module_key]) || !is_array($settings[$module_key][$sub_key])) {
                        if (array_key_exists($sub_key, $settings[$module_key])) {
                            $errors[] = "settings.{$module_key}.{$sub_key} 必须为对象";
                        }
                        continue;
                    }
                    self::validate_browser_fields(
                        $settings[$module_key][$sub_key],
                        $module_def[$sub_key],
                        "settings.{$module_key}.{$sub_key}",
                        $errors
                    );
                }
            }

            return array('valid' => empty($errors), 'errors' => $errors);
        }

        private static function validate_browser_fields($values, $field_definitions, $path, &$errors) {
            $expected_fields = array();
            foreach ($field_definitions as $field_key => $field_def) {
                if ($field_key === '_option_key' || $field_key === '_flat' || !is_array($field_def)) {
                    continue;
                }
                if (empty($field_def['sensitive'])) {
                    $expected_fields[] = $field_key;
                }
            }
            self::validate_exact_keys($values, $expected_fields, $path, $errors);

            foreach ($expected_fields as $field_key) {
                if (!array_key_exists($field_key, $values)) {
                    continue;
                }
                $field_def = $field_definitions[$field_key];
                $type = isset($field_def['type']) ? $field_def['type'] : 'string';
                if (!self::matches_json_type($values[$field_key], $type)) {
                    $errors[] = "{$path}.{$field_key} 必须为 {$type} 类型";
                    continue;
                }
                if ($type === 'array' && isset($field_def['items']) && is_array($field_def['items'])) {
                    self::validate_array_items(
                        $values[$field_key],
                        $field_def['items'],
                        "{$path}.{$field_key}",
                        $errors
                    );
                }
            }
        }

        private static function validate_array_items($values, $item_contract, $path, &$errors) {
            if (!self::is_list_array($values)) {
                $errors[] = "{$path} 必须为 JSON 列表";
                return;
            }

            foreach ($values as $index => $value) {
                self::validate_array_item($value, $item_contract, "{$path}[{$index}]", $errors);
            }
        }

        private static function validate_array_item($value, $contract, $path, &$errors) {
            $type = isset($contract['type']) ? $contract['type'] : '';
            if ($type !== 'object') {
                if (!self::matches_json_type($value, $type)) {
                    $errors[] = "{$path} 必须为 {$type} 类型";
                }
                return;
            }

            if (!is_array($value)) {
                $errors[] = "{$path} 必须为对象";
                return;
            }

            $properties = isset($contract['properties']) && is_array($contract['properties'])
                ? $contract['properties']
                : array();
            $required = isset($contract['required']) && is_array($contract['required'])
                ? $contract['required']
                : array();

            foreach ($required as $required_key) {
                if (!array_key_exists($required_key, $value)) {
                    $errors[] = "{$path}.{$required_key} 缺失";
                }
            }
            if (isset($contract['additionalProperties']) && $contract['additionalProperties'] === false) {
                foreach (array_diff(array_keys($value), array_keys($properties)) as $unknown_key) {
                    $errors[] = "{$path}.{$unknown_key} 不是已知字段";
                }
            }

            foreach ($properties as $property_key => $property_contract) {
                if (!array_key_exists($property_key, $value)) {
                    continue;
                }
                $property_type = isset($property_contract['type']) ? $property_contract['type'] : '';
                if (!self::matches_json_type($value[$property_key], $property_type)) {
                    $errors[] = "{$path}.{$property_key} 必须为 {$property_type} 类型";
                }
            }
        }

        private static function is_list_array($value) {
            if (empty($value)) {
                return true;
            }
            return array_keys($value) === range(0, count($value) - 1);
        }

        private static function validate_exact_keys($values, $expected_keys, $path, &$errors) {
            foreach (array_diff($expected_keys, array_keys($values)) as $missing_key) {
                $errors[] = "{$path}.{$missing_key} 缺失";
            }
            foreach (array_diff(array_keys($values), $expected_keys) as $unknown_key) {
                $errors[] = "{$path}.{$unknown_key} 不是已知字段";
            }
        }

        private static function matches_json_type($value, $type) {
            switch ($type) {
                case 'boolean':
                    return is_bool($value);
                case 'string':
                    return is_string($value);
                case 'number':
                    return is_int($value) || (is_float($value) && is_finite($value));
                case 'array':
                    return is_array($value);
                default:
                    return false;
            }
        }

        /**
         * 校验并清洗单个字段值
         *
         * @param mixed  $value     原始值
         * @param array  $field_def 字段定义
         * @return array ['valid' => bool, 'value' => mixed, 'error' => string|null]
         */
        private static function sanitize_field($value, $field_def) {
            $type = isset($field_def['type']) ? $field_def['type'] : 'string';

            if ($value === null) {
                return array('valid' => true, 'value' => $field_def['default'], 'error' => null);
            }

            switch ($type) {
                case 'boolean':
                    $sanitized = rest_sanitize_boolean($value);
                    return array('valid' => true, 'value' => $sanitized, 'error' => null);

                case 'number':
                    $sanitized = is_numeric($value) ? floatval($value) : $field_def['default'];
                    if (isset($field_def['min']) && $sanitized < $field_def['min']) {
                        $sanitized = $field_def['min'];
                    }
                    if (isset($field_def['max']) && $sanitized > $field_def['max']) {
                        $sanitized = $field_def['max'];
                    }
                    return array('valid' => true, 'value' => $sanitized, 'error' => null);

                case 'string':
                    if (!is_string($value) && !is_numeric($value)) {
                        return array('valid' => false, 'value' => $field_def['default'], 'error' => 'Expected string');
                    }
                    $sanitized = (string) $value;
                    if (!empty($field_def['enum']) && !in_array($sanitized, $field_def['enum'], true)) {
                        $sanitized = $field_def['default'];
                    }
                    // 凭据是不透明字符串：它们已在 secretChanges 边界严格验证，
                    // 不应再被 sanitize_text_field() 静默改写。
                    if (!empty($field_def['sensitive'])) {
                        return array('valid' => true, 'value' => $sanitized, 'error' => null);
                    }
                    $sanitize_fn = !empty($field_def['sanitize']) ? $field_def['sanitize'] : 'sanitize_text_field';
                    if (is_callable($sanitize_fn)) {
                        $sanitized = call_user_func($sanitize_fn, $sanitized);
                    }
                    return array('valid' => true, 'value' => $sanitized, 'error' => null);

                case 'array':
                    if (!is_array($value)) {
                        return array('valid' => true, 'value' => $field_def['default'], 'error' => null);
                    }
                    return array('valid' => true, 'value' => $value, 'error' => null);

                default:
                    return array('valid' => true, 'value' => $value, 'error' => null);
            }
        }

        /**
         * 校验并清洗整个模块配置
         *
         * @param string $module 模块名
         * @param array  $data   模块数据
         * @return array ['valid' => bool, 'data' => array, 'errors' => array]
         */
        public static function validate_module($module, $data) {
            $schema = self::get_schema();

            if (!isset($schema[$module])) {
                return array('valid' => false, 'data' => array(), 'errors' => array("Unknown module: {$module}"));
            }

            $module_def = $schema[$module];
            $cleaned = array();
            $errors = array();

            if (!empty($module_def['_flat'])) {
                foreach ($module_def as $field_key => $field_def) {
                    if ($field_key === '_option_key' || $field_key === '_flat') {
                        continue;
                    }
                    $raw = isset($data[$field_key]) ? $data[$field_key] : null;
                    $result = self::sanitize_field($raw, $field_def);
                    $cleaned[$field_key] = $result['value'];
                    if ($result['error']) {
                        $errors[$module . '.' . $field_key] = $result['error'];
                    }
                }
            } else {
                foreach ($module_def as $sub_key => $sub_def) {
                    if ($sub_key === '_option_key' || $sub_key === '_flat') {
                        continue;
                    }
                    $cleaned[$sub_key] = array();
                    $sub_data = isset($data[$sub_key]) && is_array($data[$sub_key]) ? $data[$sub_key] : array();
                    foreach ($sub_def as $field_key => $field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat') {
                            continue;
                        }
                        $raw = isset($sub_data[$field_key]) ? $sub_data[$field_key] : null;
                        $result = self::sanitize_field($raw, $field_def);
                        $cleaned[$sub_key][$field_key] = $result['value'];
                        if ($result['error']) {
                            $errors[$module . '.' . $sub_key . '.' . $field_key] = $result['error'];
                        }
                    }
                }
            }

            return array(
                'valid'  => empty($errors),
                'data'   => $cleaned,
                'errors' => $errors,
            );
        }

        /**
         * 校验并清洗完整配置
         *
         * @param array $full_config 完整配置
         * @return array ['valid' => bool, 'data' => array, 'errors' => array]
         */
        public static function validate_full_config($full_config) {
            $schema = self::get_schema();
            $cleaned = array();
            $all_errors = array();

            foreach ($schema as $module_key => $module_def) {
                if ($module_key === '_option_key' || $module_key === '_flat') {
                    continue;
                }
                $module_data = isset($full_config[$module_key]) && is_array($full_config[$module_key]) ? $full_config[$module_key] : array();
                $result = self::validate_module($module_key, $module_data);
                $cleaned[$module_key] = $result['data'];
                if (!empty($result['errors'])) {
                    $all_errors = array_merge($all_errors, $result['errors']);
                }
            }

            return array(
                'valid'  => empty($all_errors),
                'data'   => $cleaned,
                'errors' => $all_errors,
            );
        }
    }
}
