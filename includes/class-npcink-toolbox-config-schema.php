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

if (!class_exists('Npcink_Toolbox_Config_Schema')) {
    class Npcink_Toolbox_Config_Schema {

        private static $schema = null;

        private static function string_list_items() {
            return array('type' => 'string');
        }

        private static function number_list_items() {
            return array('type' => 'number', 'finite' => true);
        }

        /**
         * Build the narrow metadata used by the generated admin search index.
         *
         * The semantic view is the source of truth; the established tabKey
         * contract name is derived only in the generated artifact.
         */
        private static function search_metadata($id, $label, $view, $tab_label, $section, $keywords, $tags = array(), $aliases = array()) {
            $metadata = array(
                'id'       => $id,
                'label'    => $label,
                'view'     => $view,
                'tabLabel' => $tab_label,
                'section'  => $section,
                'keywords' => $keywords,
            );

            if (!empty($tags)) {
                $metadata['tags'] = $tags;
            }
            if (!empty($aliases)) {
                $metadata['aliases'] = $aliases;
            }

            return $metadata;
        }

        private static function build_schema() {
            return array(
                'optimize' => array(
                    '_option_key' => NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE,
                    'site' => array(
                        'hide_top_toolbar'       => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-site-hide_top_toolbar', '隐藏顶部工具条', 'site', '站点与媒体', '站点', array('toolbar', '顶部', '工具栏'), array('推荐', '仅后台'))),
                        'no_escape'              => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-site-no_escape', '禁止 Title 转义', 'site', '站点与媒体', '站点', array('title', '转义'), array('推荐'))),
                        'remove_RSS_version'     => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-site-remove_RSS_version', '移除 WP 版本号', 'site', '站点与媒体', '站点', array('version', '版本', 'rss'), array('推荐', '安全'))),
                        'category_link_simplify' => array('type' => 'boolean', 'default' => false),
                        'search_link_simplify'   => array('type' => 'boolean', 'default' => false),
                        'remove_sitemap_users'   => array('type' => 'boolean', 'default' => false),
                        'user_list_show_nickname' => array('type' => 'boolean', 'default' => false),
                        'cdn_replace'            => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-site-cdn_replace', '国内 CDN 替换', 'site', '站点与媒体', '站点', array('cdn', '加速'), array('性能'))),
                        'cdn_gravatar'           => array('type' => 'boolean', 'default' => false),
                        'cdn_gravatar_mirror'    => array('type' => 'string',  'default' => 'gravatar.loli.net/avatar/', 'sanitize' => 'esc_url_raw'),
                        'cdn_google_fonts'       => array('type' => 'boolean', 'default' => false),
                        'cdn_google_fonts_mirror' => array('type' => 'string',  'default' => 'fonts.loli.net', 'sanitize' => 'sanitize_text_field'),
                        'cdn_google_ajax'        => array('type' => 'boolean', 'default' => false),
                        'cdn_custom'             => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'hide_email_ip'          => array('type' => 'boolean', 'default' => false),
                    ),
                    'medium' => array(
                        'img_add_tag'     => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-medium-img_add_tag', '图片自动添加 Alt', 'site', '站点与媒体', '媒体', array('alt', '图片', 'seo'), array('推荐', 'SEO'))),
                        'no_auto_size'    => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'low', 'title' => '禁止缩略图', 'warning' => '此功能可能与部分主题不兼容，导致图片显示异常。', 'suggestion' => '开启前请确认主题支持。'), 'feature_id' => 'optimize-medium-no_auto_size', 'label' => '禁止缩略图', 'group' => '媒体', 'search' => self::search_metadata('optimize-medium-no_auto_size', '禁止缩略图', 'site', '站点与媒体', '媒体', array('thumbnail', '缩略图'), array('谨慎', '需主题兼容'))),
                        'medium_add_svg'  => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'low', 'title' => 'SVG 上传支持', 'warning' => 'SVG 文件可能包含恶意脚本，已做安全过滤但仍需注意。', 'suggestion' => '仅允许可信用户上传 SVG 文件。'), 'feature_id' => 'optimize-medium-medium_add_svg', 'label' => 'SVG 上传支持', 'group' => '媒体'),
                        'upload_auto_name' => array('type' => 'string',  'default' => 'false', 'sanitize' => 'sanitize_text_field', 'search' => self::search_metadata('optimize-medium-upload_auto_name', '上传文件重命名', 'site', '站点与媒体', '媒体', array('rename', '重命名', '上传'), array('推荐'))),
                    ),
                    'admin' => array(
                        'add_user'            => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-admin-add_user', '文章作者筛选', 'site', '站点与媒体', '后台', array('author', '作者', '筛选'))),
                        'add_time'            => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-admin-add_time', '文章日期筛选', 'site', '站点与媒体', '后台', array('date', '日期', '筛选'))),
                        'show_id'             => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-admin-show_id', '列表显示 ID 列', 'site', '站点与媒体', '后台', array('id', '列表'))),
                        'thumbnail_switcher'  => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('optimize-admin-thumbnail_switcher', '缩略图切换', 'site', '站点与媒体', '后台', array('thumbnail', '缩略图'))),
                    ),
                ),
                'page' => array(
                    '_option_key' => NPCINK_SITE_TOOLBOX_OPTION_PAGE,
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
                        'maintenance_tips'        => array('type' => 'string',  'default' => 'false', 'sanitize' => 'sanitize_text_field', 'search' => self::search_metadata('page-function-maintenance_tips', '维护提示页', 'content', '内容与页面', '功能', array('maintenance', '维护', '闭站'), array('谨慎'), array('page-feature-maintenance_tips'))),
                        'countdown'               => array('type' => 'array',   'default' => array(), 'items' => self::string_list_items()),
                        'countdown_title'         => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'countdown_image'         => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                        'countdown_content'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'default_thumbnail'       => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                        'search_limit'            => array('type' => 'boolean', 'default' => false),
                        'search_limit_count'      => array('type' => 'number',  'default' => 10, 'min' => 1, 'max' => 100),
                        'login_search'            => array('type' => 'boolean', 'default' => false),
                    ),
                    'jurisdiction' => array(
                        'category_id'             => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'tag_id'                  => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'page_id'                => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'single_id'              => array('type' => 'array',   'default' => array(), 'items' => self::number_list_items()),
                        'tip_content'             => array('type' => 'string',  'default' => '', 'sanitize' => 'wp_kses_post'),
                    ),
                ),
                'function' => array(
                    '_option_key' => NPCINK_SITE_TOOLBOX_OPTION_FUNCTION,
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
                        'title'        => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'search' => self::search_metadata('function-seo-seo_home', '首页 TDK', 'seo', 'SEO 与增强', 'SEO', array('tdk', '首页', 'seo', '标题', '描述'), array('推荐', 'SEO'))),
                        'keywords'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'description'  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'seo_single'   => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('function-seo-seo_single', '文章 SEO', 'seo', 'SEO 与增强', 'SEO', array('seo', '文章', '关键词'), array('推荐', 'SEO'))),
                        'seo_category' => array('type' => 'boolean', 'default' => false),
                    ),
                ),
                'domestic' => array(
                    '_option_key' => NPCINK_SITE_TOOLBOX_OPTION_DOMESTIC,
                    'compliance' => array(
                        'icp_enabled'    => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-compliance-icp', 'ICP 备案号', 'china', '国内生态', '合规', array('icp', '备案', '合规'), array('推荐'))),
                        'icp_number'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'icp_link'       => array('type' => 'string',  'default' => 'https://beian.miit.gov.cn/', 'sanitize' => 'esc_url_raw'),
                        'police_enabled' => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-compliance-police_enabled', '公安网备号', 'china', '国内生态', '合规', array('公安', '网备', '备案'), array('推荐'), array('domestic-compliance-police'))),
                        'police_number'  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'police_link'    => array('type' => 'string',  'default' => 'https://www.beian.gov.cn/portal/registerSystemInfo', 'sanitize' => 'esc_url_raw'),
                        'cookie_enabled' => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-compliance-cookie_enabled', 'Cookie 同意弹窗', 'china', '国内生态', '合规', array('cookie', '隐私', '弹窗'), array(), array('domestic-compliance-cookie'))),
                        'cookie_style'   => array('type' => 'string',  'default' => 'bottom', 'enum' => array('bottom', 'top', 'center'), 'sanitize' => 'sanitize_text_field'),
                        'cookie_title'   => array('type' => 'string',  'default' => 'Cookie 同意', 'sanitize' => 'sanitize_text_field'),
                        'cookie_content' => array('type' => 'string',  'default' => '本网站使用 Cookie 来改善您的体验。继续浏览即表示您同意我们的 Cookie 政策。', 'sanitize' => 'sanitize_textarea_field'),
                        'cookie_button'  => array('type' => 'string',  'default' => '我知道了', 'sanitize' => 'sanitize_text_field'),
                        'copyright_enabled' => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-compliance-copyright_enabled', '版权信息', 'china', '国内生态', '合规', array('copyright', '版权'), array(), array('domestic-compliance-copyright'))),
                        'copyright_html' => array('type' => 'string',  'default' => '', 'sanitize' => 'wp_kses_post'),
                    ),
                    'wechat' => array(
                        'jssdk_enabled'          => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-wechat-jssdk', '微信 JSSDK 分享', 'china', '国内生态', '微信生态', array('wechat', '微信', '分享', 'jssdk'))),
                        'appid'                  => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'appsecret'              => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'sensitive' => true),
                        'guide_overlay_enabled'  => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-wechat-guide', '微信打开引导', 'china', '国内生态', '微信生态', array('wechat', '微信', '引导', '遮层'))),
                        'guide_mode'             => array('type' => 'string',  'default' => 'guide', 'sanitize' => 'sanitize_text_field'),
                        'guide_text'             => array('type' => 'string',  'default' => '点击右上角 ··· 在浏览器中打开', 'sanitize' => 'sanitize_text_field'),
                        'guide_qrcode'           => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                    ),
                    'comment_security' => array(
                        'blacklist_enabled'         => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-comment-blacklist', '评论敏感词过滤', 'china', '国内生态', '评论安全', array('comment', '评论', '敏感词', '黑名单'), array('推荐', '安全'), array('domestic-comment_security-blacklist_enabled'))),
                        'blacklist_words'           => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'blacklist_action'          => array('type' => 'string',  'default' => 'block', 'enum' => array('block', 'mark'), 'sanitize' => 'sanitize_text_field'),
                        'link_limit_enabled'        => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-comment-link-limit', '评论链接限制', 'china', '国内生态', '评论安全', array('comment', '评论', '链接', '垃圾'), array(), array('domestic-comment_security-link_limit'))),
                        'link_limit_count'          => array('type' => 'number',  'default' => 2, 'min' => 0),
                        'nickname_filter_enabled'   => array('type' => 'boolean', 'default' => false),
                        'nickname_filter_words'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_textarea_field'),
                        'email_domain_enabled'      => array('type' => 'boolean', 'default' => false),
                        'email_domain_blacklist'   => array('type' => 'string',  'default' => '10minutemail.com,guerrillamail.com,temp-mail.org', 'sanitize' => 'sanitize_textarea_field'),
                        'duplicate_enabled'         => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-comment_security-duplicate_enabled', '重复评论拦截', 'china', '国内生态', '评论安全', array('comment', '评论', '重复', '拦截'))),
                        'ip_rate_enabled'           => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-comment-ip-rate', '评论 IP 频率限制', 'china', '国内生态', '评论安全', array('comment', '评论', 'ip', '频率'), array(), array('domestic-comment_security-ip_rate_limit'))),
                        'ip_rate_limit'             => array('type' => 'number',  'default' => 5, 'min' => 1),
                        'ip_rate_window'            => array('type' => 'number',  'default' => 60, 'min' => 1),
                        'log_enabled'               => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('domestic-comment_security-log_enabled', '记录拦截日志', 'china', '国内生态', '评论安全', array('comment', '评论', '日志', '拦截'))),
                    ),
                    'login_security' => array(
                        'attempt_limit_enabled' => array(
                            'type'       => 'boolean',
                            'default'    => false,
                            'feature_id' => 'domestic-login_security-attempt_limit_enabled',
                            'label'      => '登录尝试保护',
                            'group'      => '登录安全',
                            'risk'       => array(
                                'level'      => 'low',
                                'title'      => '登录尝试保护',
                                'warning'    => '可信代理配置错误可能让多个访客共享同一来源 IP，造成账号误锁。',
                                'suggestion' => '确认开启后请在保存前核对可信代理；如发生误锁，可在 wp-config.php 中将 NPCINK_SITE_TOOLBOX_DISABLE_LOGIN_PROTECTION 定义为 true 后恢复。',
                            ),
                            'search'     => self::search_metadata('domestic-login_security-attempt_limit_enabled', '登录尝试保护', 'china', '国内生态', '登录安全', array('login', '登录', '失败', '限制', '锁定', '代理'), array('推荐', '安全')),
                        ),
                        'attempt_limit_count' => array('type' => 'number', 'default' => 5, 'min' => 2, 'max' => 20, 'integer' => true),
                        'attempt_window_minutes' => array('type' => 'number', 'default' => 15, 'min' => 1, 'max' => 1440, 'integer' => true),
                        'lock_duration_minutes' => array('type' => 'number', 'default' => 30, 'min' => 1, 'max' => 1440, 'integer' => true),
                        'trusted_proxies' => array('type' => 'string', 'default' => '', 'format' => 'ip_list'),
                        'anonymous_author_guard_enabled' => array(
                            'type'       => 'boolean',
                            'default'    => false,
                            'feature_id' => 'domestic-login_security-anonymous_author_guard_enabled',
                            'label'      => '限制匿名作者枚举',
                            'group'      => '登录安全',
                            'risk'       => array('level' => 'none'),
                            'search'     => self::search_metadata('domestic-login_security-anonymous_author_guard_enabled', '限制匿名作者枚举', 'china', '国内生态', '登录安全', array('login', '登录', '枚举', '作者', '用户名', 'REST'), array('安全')),
                        ),
                    ),
                ),
                'performance' => array(
                    '_option_key' => NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE,
                    'oss' => array(
                        'enabled'      => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('performance-oss-enabled', '对象存储 / OSS', 'maintenance', '存储与维护', '云存储', array('oss', 'cos', '云存储', '阿里云', '腾讯云'), array('性能'))),
                        'provider'     => array('type' => 'string',  'default' => 'aliyun', 'sanitize' => 'sanitize_text_field'),
                        'access_key'   => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'sensitive' => true),
                        'secret_key'   => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field', 'sensitive' => true),
                        'bucket'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'path'         => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'endpoint'     => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'region'       => array('type' => 'string',  'default' => '', 'sanitize' => 'sanitize_text_field'),
                        'domain'       => array('type' => 'string',  'default' => '', 'sanitize' => 'esc_url_raw'),
                    ),
                    'seo_checker' => array(
                        'enabled' => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('performance-seo_checker-enabled', 'SEO 检查助手', 'maintenance', '存储与维护', 'SEO', array('seo', '检查', 'alt', '健康度'), array('SEO'))),
                    ),
                    'media_health' => array(
                        'enabled' => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('performance-media_health-enabled', '媒体库体检', 'maintenance', '存储与维护', '媒体', array('media', '媒体', '图片', 'alt', '体检'))),
                    ),
                    'search_enhance' => array(
                        'highlight_enabled' => array('type' => 'boolean', 'default' => false, 'search' => self::search_metadata('performance-search_enhance-highlight_enabled', '搜索关键词高亮', 'maintenance', '存储与维护', '搜索', array('search', '搜索', '高亮', '关键词'))),
                        'recommend_enabled' => array('type' => 'boolean', 'default' => false),
                        'hotwords_enabled'  => array('type' => 'boolean', 'default' => false),
                    ),
                    'db_clean' => array(
                        'enabled'             => array('type' => 'boolean', 'default' => false, 'risk' => array('level' => 'high', 'title' => '数据库清理', 'warning' => '数据库清理操作不可逆，删除的数据无法恢复。', 'suggestion' => '执行前务必先预览影响数量，并做好备份。', 'noDismiss' => true), 'feature_id' => 'performance-db_clean-enabled', 'label' => '数据库清理优化', 'group' => '数据库', 'search' => self::search_metadata('performance-db_clean-enabled', '数据库清理优化', 'maintenance', '存储与维护', '数据库', array('db', '数据库', '清理', '优化', '修订版本'), array('推荐', '性能'))),
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
         * Get the private Schema definition, including build-only metadata.
         */
        private static function get_schema_definition() {
            if (self::$schema === null) {
                self::$schema = self::build_schema();
            }
            return self::$schema;
        }

        /**
         * 获取运行时公开 schema。
         *
         * 构建期搜索元数据不属于 REST、默认值或校验契约，因此必须在
         * 公开前剥离；敏感标记及其余既有 Schema 结构保持不变。
         */
        public static function get_schema() {
            $schema = self::get_schema_definition();

            foreach ($schema as $module_key => &$module_def) {
                if ($module_key === '_option_key' || $module_key === '_flat' || !is_array($module_def)) {
                    continue;
                }

                if (!empty($module_def['_flat'])) {
                    foreach ($module_def as $field_key => &$field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat' || !is_array($field_def)) {
                            continue;
                        }
                        unset($field_def['search']);
                    }
                    unset($field_def);
                    continue;
                }

                foreach ($module_def as $sub_key => &$sub_def) {
                    if ($sub_key === '_option_key' || $sub_key === '_flat' || !is_array($sub_def)) {
                        continue;
                    }
                    foreach ($sub_def as $field_key => &$field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat' || !is_array($field_def)) {
                            continue;
                        }
                        unset($field_def['search']);
                    }
                    unset($field_def);
                }
                unset($sub_def);
            }
            unset($module_def);

            return $schema;
        }

        /**
         * Get the deterministic search index defined by field-level Schema metadata.
         *
         * Sensitive fields are excluded before their metadata is inspected. Search
         * IDs and semantic views are validated here so malformed generated routes
         * fail closed during export rather than becoming unreachable UI entries.
         *
         * @return array<int, array<string, mixed>>
         */
        public static function get_admin_search_index() {
            $schema = self::get_schema_definition();
            $search_index = array();
            $seen_ids = array();

            foreach ($schema as $module_key => $module_def) {
                if ($module_key === '_option_key' || $module_key === '_flat' || !is_array($module_def)) {
                    continue;
                }

                if (!empty($module_def['_flat'])) {
                    foreach ($module_def as $field_key => $field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat' || !is_array($field_def)) {
                            continue;
                        }
                        self::append_search_item(
                            $search_index,
                            $seen_ids,
                            $field_def,
                            $module_key . '.' . $field_key
                        );
                    }
                    continue;
                }

                foreach ($module_def as $sub_key => $sub_def) {
                    if ($sub_key === '_option_key' || $sub_key === '_flat' || !is_array($sub_def)) {
                        continue;
                    }
                    foreach ($sub_def as $field_key => $field_def) {
                        if ($field_key === '_option_key' || $field_key === '_flat' || !is_array($field_def)) {
                            continue;
                        }
                        self::append_search_item(
                            $search_index,
                            $seen_ids,
                            $field_def,
                            $module_key . '.' . $sub_key . '.' . $field_key
                        );
                    }
                }
            }

            return $search_index;
        }

        private static function append_search_item(&$search_index, &$seen_ids, $field_def, $path) {
            if (!empty($field_def['sensitive']) || !isset($field_def['search'])) {
                return;
            }
            if (!is_array($field_def['search'])) {
                throw new UnexpectedValueException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                    sprintf('Search metadata for %s must be an array', $path)
                );
            }

            $search = $field_def['search'];
            foreach (array('id', 'label', 'view', 'tabLabel', 'section') as $required_key) {
                if (!isset($search[$required_key]) || !is_string($search[$required_key]) || $search[$required_key] === '') {
                    throw new UnexpectedValueException(
                        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                        sprintf('Search metadata for %1$s has an invalid %2$s', $path, $required_key)
                    );
                }
            }
            if (!in_array($search['view'], array('site', 'content', 'seo', 'china', 'maintenance'), true)) {
                throw new UnexpectedValueException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                    sprintf('Search metadata for %s has an invalid view', $path)
                );
            }
            if (isset($seen_ids[$search['id']])) {
                throw new UnexpectedValueException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                    sprintf('Duplicate search ID: %s', $search['id'])
                );
            }

            self::assert_search_string_list($search, 'keywords', $path, false);
            self::assert_search_string_list($search, 'tags', $path, true);
            self::assert_search_string_list($search, 'aliases', $path, true);

            $item = array(
                'id'       => $search['id'],
                'label'    => $search['label'],
                'tabKey'   => $search['view'],
                'tabLabel' => $search['tabLabel'],
                'section'  => $search['section'],
                'keywords' => $search['keywords'],
            );
            if (isset($search['tags'])) {
                $item['tags'] = $search['tags'];
            }
            if (isset($search['aliases'])) {
                $item['aliases'] = $search['aliases'];
            }

            $seen_ids[$search['id']] = true;
            $search_index[] = $item;
        }

        private static function assert_search_string_list($search, $key, $path, $optional) {
            if (!array_key_exists($key, $search)) {
                if ($optional) {
                    return;
                }
                throw new UnexpectedValueException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                    sprintf('Search metadata for %1$s is missing %2$s', $path, $key)
                );
            }
            if (!is_array($search[$key]) || !self::is_list_array($search[$key])) {
                throw new UnexpectedValueException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                    sprintf('Search metadata for %1$s has an invalid %2$s list', $path, $key)
                );
            }
            foreach ($search[$key] as $value) {
                if (!is_string($value) || $value === '') {
                    throw new UnexpectedValueException(
                        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing diagnostic; escape only if an HTML renderer displays it.
                        sprintf('Search metadata for %1$s has an invalid %2$s value', $path, $key)
                    );
                }
            }
        }

        /**
         * 获取仅由配置 Schema 定义的 UI 元数据。
         *
         * 此视图不合并模块 Registry/metadata，供确定性前端契约导出使用。
         */
        public static function get_schema_ui_schema() {
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

            return $ui;
        }

        public static function get_ui_schema() {
            $ui = self::get_schema_ui_schema();

            if (class_exists('Npcink_Toolbox_Module_Metadata')) {
                $module_ui = Npcink_Toolbox_Module_Metadata::get_ui_metadata();
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
         * 获取前端构建使用的最小设置契约。
         *
         * 敏感字段的键和值都不会进入生成物；运行时 REST 契约仍由
         * Npcink_Toolbox_Config_Manager 独立负责，不读取此构建期快照。
         */
        public static function get_admin_settings_contract() {
            $schema = self::get_schema();
            $defaults = self::get_defaults();
            $ui_schema = self::get_schema_ui_schema();

            foreach ($schema as $module_key => $module_def) {
                if (!is_array($module_def) || !isset($defaults[$module_key])) {
                    continue;
                }

                if (!empty($module_def['_flat'])) {
                    foreach ($module_def as $field_key => $field_def) {
                        if (is_array($field_def) && !empty($field_def['sensitive'])) {
                            unset($defaults[$module_key][$field_key]);
                            unset($ui_schema[$module_key . '-' . $field_key]);
                        }
                    }
                    continue;
                }

                foreach ($module_def as $sub_key => $sub_def) {
                    if (!is_array($sub_def) || !isset($defaults[$module_key][$sub_key])) {
                        continue;
                    }
                    foreach ($sub_def as $field_key => $field_def) {
                        if (is_array($field_def) && !empty($field_def['sensitive'])) {
                            unset($defaults[$module_key][$sub_key][$field_key]);
                            unset($ui_schema[$module_key . '-' . $sub_key . '-' . $field_key]);
                        }
                    }
                }
            }

            return array(
                'defaults' => $defaults,
                'searchIndex' => self::get_admin_search_index(),
                'uiSchema' => $ui_schema,
            );
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
                if ($type === 'number' && !empty($field_def['integer'])) {
                    if (!is_int($values[$field_key])) {
                        $errors[] = "{$path}.{$field_key} 必须为整数";
                        continue;
                    }
                    if (isset($field_def['min']) && $values[$field_key] < $field_def['min']) {
                        $errors[] = "{$path}.{$field_key} 不能小于 {$field_def['min']}";
                        continue;
                    }
                    if (isset($field_def['max']) && $values[$field_key] > $field_def['max']) {
                        $errors[] = "{$path}.{$field_key} 不能大于 {$field_def['max']}";
                        continue;
                    }
                }
                if ($type === 'string' && isset($field_def['format']) && $field_def['format'] === 'ip_list') {
                    $ip_list = self::sanitize_ip_list($values[$field_key]);
                    if (!$ip_list['valid']) {
                        $errors[] = "{$path}.{$field_key} {$ip_list['error']}";
                        continue;
                    }
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
                    if (!empty($field_def['integer'])) {
                        if (!is_int($value)) {
                            return array('valid' => false, 'value' => $field_def['default'], 'error' => 'Expected integer');
                        }
                        if (
                            (isset($field_def['min']) && $value < $field_def['min'])
                            || (isset($field_def['max']) && $value > $field_def['max'])
                        ) {
                            return array('valid' => false, 'value' => $field_def['default'], 'error' => 'Integer out of range');
                        }
                        return array('valid' => true, 'value' => $value, 'error' => null);
                    }
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
                    if (isset($field_def['format']) && $field_def['format'] === 'ip_list') {
                        return self::sanitize_ip_list($sanitized);
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
         * 校验并规范化逐行精确 IP 列表。
         *
         * 任一非空行无效时整串失败，避免有效行与无效行混合后形成
         * 看似已保存、实际只部分生效的可信代理配置。
         */
        private static function sanitize_ip_list($value) {
            if (!is_string($value)) {
                return array('valid' => false, 'value' => '', 'error' => '必须为字符串');
            }

            if (trim($value) === '') {
                return array('valid' => true, 'value' => '', 'error' => null);
            }

            $lines = preg_split('/\r\n|\r|\n/', $value);
            if (!is_array($lines)) {
                return array('valid' => false, 'value' => '', 'error' => '格式无效');
            }

            $normalized = array();
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (filter_var($line, FILTER_VALIDATE_IP) === false) {
                    return array('valid' => false, 'value' => '', 'error' => '每个非空行必须是精确 IPv4 或 IPv6 地址');
                }

                $packed = @inet_pton($line);
                $canonical = $packed !== false ? @inet_ntop($packed) : false;
                if (!is_string($canonical)) {
                    return array('valid' => false, 'value' => '', 'error' => '每个非空行必须是精确 IPv4 或 IPv6 地址');
                }

                $normalized[strtolower($canonical)] = true;
            }

            return array(
                'valid' => true,
                'value' => implode("\n", array_keys($normalized)),
                'error' => null,
            );
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
