<?php
defined('ABSPATH') || exit;

/**
 * 当前登录用户的评论管理页面与 REST 接口。
 */
final class Npcink_Toolbox_My_Comments implements Npcink_Toolbox_Module_Interface
{
    const PAGE_SLUG = 'npcink-site-toolbox-my-comments';
    const BATCH_LIMIT = 20;

    public static function run($config = array())
    {
        if (!self::is_enabled($config) || !self::is_admin_page_enabled($config)) {
            return;
        }
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_menu()
    {
        add_menu_page(
            __('我的评论', 'npcink-site-toolbox'),
            __('我的评论', 'npcink-site-toolbox'),
            'read',
            self::PAGE_SLUG,
            array(__CLASS__, 'render_page'),
            'dashicons-admin-comments',
            26
        );
    }

    public static function render_page()
    {
        if (!current_user_can('read')) {
            wp_die(esc_html__('您没有权限访问此页面。', 'npcink-site-toolbox'));
        }
        ?>
        <div class="wrap npcink-my-comments">
            <h1><?php echo esc_html__('我的评论', 'npcink-site-toolbox'); ?></h1>
            <p><?php echo esc_html__('在这里发布、修改或将自己的评论移入回收站。已有回复的评论暂不支持修改或删除。', 'npcink-site-toolbox'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('profile.php#application-passwords-section')); ?>">
                    <?php echo esc_html__('管理应用程序密码', 'npcink-site-toolbox'); ?>
                </a>
                <span class="description"><?php echo esc_html__('应用程序密码仅用于 HTTPS 外部客户端，创建后只显示一次。', 'npcink-site-toolbox'); ?></span>
            </p>

            <section class="npcink-my-comments__composer" aria-labelledby="npcink-my-comments-compose-title">
                <h2 id="npcink-my-comments-compose-title"><?php echo esc_html__('发表评论', 'npcink-site-toolbox'); ?></h2>
                <form id="npcink-my-comments-compose-form">
                    <label for="npcink-my-comments-post-id"><?php echo esc_html__('文章 ID', 'npcink-site-toolbox'); ?></label>
                    <input id="npcink-my-comments-post-id" name="postId" type="number" min="1" required>
                    <label for="npcink-my-comments-content"><?php echo esc_html__('评论内容', 'npcink-site-toolbox'); ?></label>
                    <textarea id="npcink-my-comments-content" name="content" rows="4" required></textarea>
                    <button class="button button-primary" type="submit"><?php echo esc_html__('发布评论', 'npcink-site-toolbox'); ?></button>
                </form>
            </section>

            <div class="npcink-my-comments__toolbar">
                <label for="npcink-my-comments-status"><?php echo esc_html__('状态', 'npcink-site-toolbox'); ?></label>
                <select id="npcink-my-comments-status">
                    <option value="all"><?php echo esc_html__('全部', 'npcink-site-toolbox'); ?></option>
                    <option value="approved"><?php echo esc_html__('已通过', 'npcink-site-toolbox'); ?></option>
                    <option value="pending"><?php echo esc_html__('待审核', 'npcink-site-toolbox'); ?></option>
                    <option value="trash"><?php echo esc_html__('回收站', 'npcink-site-toolbox'); ?></option>
                </select>
                <button id="npcink-my-comments-refresh" class="button" type="button"><?php echo esc_html__('刷新', 'npcink-site-toolbox'); ?></button>
                <button id="npcink-my-comments-batch-delete" class="button" type="button" disabled><?php echo esc_html__('批量移入回收站', 'npcink-site-toolbox'); ?></button>
            </div>

            <div id="npcink-my-comments-feedback" class="notice inline" role="status" aria-live="polite" hidden></div>
            <div id="npcink-my-comments-list" aria-live="polite"></div>
            <div class="npcink-my-comments__pagination">
                <button id="npcink-my-comments-prev" class="button" type="button" disabled><?php echo esc_html__('上一页', 'npcink-site-toolbox'); ?></button>
                <span id="npcink-my-comments-page"></span>
                <button id="npcink-my-comments-next" class="button" type="button" disabled><?php echo esc_html__('下一页', 'npcink-site-toolbox'); ?></button>
            </div>
        </div>
        <?php
    }

    public static function enqueue_assets($hook)
    {
        if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }

        $style_path = plugin_dir_path(__DIR__) . 'admin/css/my-comments.css';
        $script_path = plugin_dir_path(__DIR__) . 'admin/js/my-comments.js';
        $style_version = is_file($style_path) ? NPCINK_SITE_TOOLBOX_VERSION . '-' . filemtime($style_path) : NPCINK_SITE_TOOLBOX_VERSION;
        $script_version = is_file($script_path) ? NPCINK_SITE_TOOLBOX_VERSION . '-' . filemtime($script_path) : NPCINK_SITE_TOOLBOX_VERSION;

        wp_enqueue_style(
            'npcink-site-toolbox-my-comments',
            plugin_dir_url(__DIR__) . 'admin/css/my-comments.css',
            array(),
            $style_version
        );
        wp_enqueue_script(
            'npcink-site-toolbox-my-comments',
            plugin_dir_url(__DIR__) . 'admin/js/my-comments.js',
            array(),
            $script_version,
            true
        );
        wp_localize_script('npcink-site-toolbox-my-comments', 'npcinkMyComments', array(
            'apiBase' => esc_url_raw(rest_url('npcink-site-toolbox/v1/me/comments')),
            'nonce'   => wp_create_nonce('wp_rest'),
            'pageSize'=> 20,
        ));
    }

    public static function register_routes()
    {
        if (!self::is_enabled()) {
            return;
        }
        $permission = array(__CLASS__, 'permission_check');

        Npcink_Toolbox_Rest_Route_Registry::add('/me/comments', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'list_comments'),
                'permission_callback' => $permission,
                'args'                => self::list_args(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'create_comment'),
                'permission_callback' => $permission,
                'args'                => self::create_args(),
            ),
        ), 'my-comments');

        Npcink_Toolbox_Rest_Route_Registry::add('/me/comments/(?P<id>\\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array(__CLASS__, 'update_comment'),
                'permission_callback' => $permission,
                'args'                => self::update_args(),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array(__CLASS__, 'delete_comment'),
                'permission_callback' => $permission,
                'args'                => array(
                    'id' => array('required' => true, 'type' => 'integer', 'minimum' => 1),
                ),
            ),
        ), 'my-comments');

        Npcink_Toolbox_Rest_Route_Registry::add('/me/comments/batch-delete', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array(__CLASS__, 'batch_delete_comments'),
            'permission_callback' => $permission,
            'args'                => array(
                'commentIds' => array(
                    'required'          => true,
                    'type'              => 'array',
                    'items'             => array('type' => 'integer', 'minimum' => 1),
                    'minItems'          => 1,
                    'maxItems'          => self::BATCH_LIMIT,
                    'sanitize_callback' => array(__CLASS__, 'sanitize_comment_ids'),
                    'validate_callback' => array(__CLASS__, 'validate_comment_ids'),
                ),
            ),
        ), 'my-comments');
    }

    private static function list_args()
    {
        return array(
            'page' => array('type' => 'integer', 'minimum' => 1, 'default' => 1),
            'pageSize' => array('type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20),
            'status' => array(
                'type' => 'string',
                'enum' => array('all', 'approved', 'pending', 'trash'),
                'default' => 'all',
                'sanitize_callback' => 'sanitize_key',
            ),
        );
    }

    private static function create_args()
    {
        return array(
            'postId' => array('required' => true, 'type' => 'integer', 'minimum' => 1),
            'content' => array(
                'required' => true,
                'type' => 'string',
                'minLength' => 1,
                'sanitize_callback' => array(__CLASS__, 'sanitize_comment_content'),
            ),
        );
    }

    private static function update_args()
    {
        return array(
            'id' => array('required' => true, 'type' => 'integer', 'minimum' => 1),
            'content' => array(
                'required' => true,
                'type' => 'string',
                'minLength' => 1,
                'sanitize_callback' => array(__CLASS__, 'sanitize_comment_content'),
            ),
            'expectedHash' => array(
                'required' => true,
                'type' => 'string',
                'pattern' => '^[a-f0-9]{64}$',
                'sanitize_callback' => 'sanitize_key',
            ),
        );
    }

    public static function permission_check()
    {
        return get_current_user_id() > 0 && current_user_can('read');
    }

    public static function is_enabled($config = null)
    {
        if (is_array($config) && array_key_exists('self_service_enabled', $config)) {
            return true === $config['self_service_enabled'];
        }

        $page = get_option(NPCINK_SITE_TOOLBOX_OPTION_PAGE, array());
        return is_array($page)
            && !empty($page['comment'])
            && is_array($page['comment'])
            && !empty($page['comment']['self_service_enabled']);
    }

    public static function is_admin_page_enabled($config = null)
    {
        if (is_array($config) && array_key_exists('self_service_admin_page_enabled', $config)) {
            return true === $config['self_service_admin_page_enabled'];
        }

        $page = get_option(NPCINK_SITE_TOOLBOX_OPTION_PAGE, array());
        return is_array($page)
            && !empty($page['comment'])
            && is_array($page['comment'])
            && !empty($page['comment']['self_service_admin_page_enabled']);
    }

    public static function sanitize_comment_content($value)
    {
        return is_string($value) ? trim(wp_kses_data($value)) : '';
    }

    public static function sanitize_comment_ids($value)
    {
        if (!is_array($value)) {
            return array();
        }
        return array_values(array_unique(array_filter(array_map('absint', $value))));
    }

    public static function validate_comment_ids($value)
    {
        if (!is_array($value) || empty($value) || count($value) > self::BATCH_LIMIT) {
            return false;
        }
        foreach ($value as $id) {
            if (!is_numeric($id) || (int) $id < 1) {
                return false;
            }
        }
        return true;
    }

    public static function list_comments($request)
    {
        $user_id = get_current_user_id();
        $page = max(1, (int) $request->get_param('page'));
        $page_size = min(50, max(1, (int) $request->get_param('pageSize')));
        $status = self::query_status((string) $request->get_param('status'));
        $query = array(
            'user_id' => $user_id,
            'status' => $status,
            'number' => $page_size,
            'offset' => ($page - 1) * $page_size,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
        );
        $comments = get_comments($query);
        $reply_map = self::get_reply_map($comments);
        $total_query = $query;
        unset($total_query['number'], $total_query['offset'], $total_query['orderby'], $total_query['order']);
        $total_query['count'] = true;
        $total = (int) get_comments($total_query);

        return rest_ensure_response(array(
            'data' => array_map(function ($comment) use ($reply_map) {
                $comment_id = (int) $comment->comment_ID;
                return self::prepare_comment($comment, !empty($reply_map[$comment_id]));
            }, $comments),
            'pagination' => array(
                'page' => $page,
                'pageSize' => $page_size,
                'totalItems' => $total,
                'totalPages' => $total > 0 ? (int) ceil($total / $page_size) : 0,
            ),
        ));
    }

    public static function create_comment($request)
    {
        $post_id = (int) $request->get_param('postId');
        $content = self::sanitize_comment_content($request->get_param('content'));
        $post = get_post($post_id);
        if (!$post || 'publish' !== $post->post_status) {
            return new WP_Error('npcink_comment_post_not_found', __('文章不存在或不可评论。', 'npcink-site-toolbox'), array('status' => 404));
        }
        if (!comments_open($post_id)) {
            return new WP_Error('npcink_comments_closed', __('该文章已关闭评论。', 'npcink-site-toolbox'), array('status' => 409));
        }
        if ('' === $content) {
            return new WP_Error('npcink_comment_empty', __('评论内容不能为空。', 'npcink-site-toolbox'), array('status' => 400));
        }

        $user = wp_get_current_user();
        $comment_id = wp_new_comment(array(
            'comment_post_ID' => $post_id,
            'comment_content' => $content,
            'comment_parent' => 0,
            'user_id' => (int) $user->ID,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url' => $user->user_url,
        ), true);
        if (is_wp_error($comment_id)) {
            return $comment_id;
        }

        return new WP_REST_Response(self::prepare_comment(get_comment($comment_id)), 201);
    }

    public static function update_comment($request)
    {
        $comment = self::get_owned_comment((int) $request->get_param('id'));
        if (is_wp_error($comment)) {
            return $comment;
        }
        if (self::is_trashed($comment)) {
            return new WP_Error('npcink_comment_already_deleted', __('评论已经进入回收站。', 'npcink-site-toolbox'), array('status' => 409));
        }
        if (self::has_effective_replies((int) $comment->comment_ID)) {
            return new WP_Error('npcink_comment_has_replies', __('该评论已有回复，暂不支持修改。', 'npcink-site-toolbox'), array('status' => 409));
        }
        if (!hash_equals(self::comment_hash($comment), (string) $request->get_param('expectedHash'))) {
            return new WP_Error('npcink_comment_conflict', __('评论已经发生变化，请刷新后重试。', 'npcink-site-toolbox'), array('status' => 409));
        }
        $content = self::sanitize_comment_content($request->get_param('content'));
        if ('' === $content) {
            return new WP_Error('npcink_comment_empty', __('评论内容不能为空。', 'npcink-site-toolbox'), array('status' => 400));
        }

        $updated = wp_update_comment(array(
            'comment_ID' => (int) $comment->comment_ID,
            'comment_content' => $content,
            'comment_approved' => 0,
        ), true);
        if (is_wp_error($updated)) {
            return $updated;
        }
        if (!$updated) {
            return new WP_Error('npcink_comment_update_failed', __('评论修改失败。', 'npcink-site-toolbox'), array('status' => 500));
        }

        clean_comment_cache((int) $comment->comment_ID);
        return rest_ensure_response(self::prepare_comment(get_comment((int) $comment->comment_ID)));
    }

    public static function delete_comment($request)
    {
        $result = self::delete_owned_comment((int) $request->get_param('id'));
        return is_wp_error($result) ? $result : rest_ensure_response($result);
    }

    public static function batch_delete_comments($request)
    {
        $ids = self::sanitize_comment_ids($request->get_param('commentIds'));
        $results = array();
        $deleted = 0;
        foreach ($ids as $id) {
            $result = self::delete_owned_comment($id);
            if (is_wp_error($result)) {
                $results[] = array(
                    'id' => $id,
                    'status' => 'rejected',
                    'code' => $result->get_error_code(),
                    'message' => $result->get_error_message(),
                );
                continue;
            }
            $deleted++;
            $results[] = $result;
        }

        return rest_ensure_response(array(
            'summary' => array(
                'requested' => count($ids),
                'deleted' => $deleted,
                'failed' => count($ids) - $deleted,
            ),
            'results' => $results,
        ));
    }

    private static function delete_owned_comment($comment_id)
    {
        $comment = self::get_owned_comment($comment_id);
        if (is_wp_error($comment)) {
            return $comment;
        }
        if (self::is_trashed($comment)) {
            return array('id' => $comment_id, 'status' => 'alreadyDeleted');
        }
        if (self::has_effective_replies($comment_id)) {
            return new WP_Error('npcink_comment_has_replies', __('该评论已有回复，暂不支持删除。', 'npcink-site-toolbox'), array('status' => 409));
        }
        if (!wp_trash_comment($comment_id)) {
            return new WP_Error('npcink_comment_delete_failed', __('评论未能移入回收站。', 'npcink-site-toolbox'), array('status' => 500));
        }
        return array('id' => $comment_id, 'status' => 'deleted');
    }

    private static function get_owned_comment($comment_id)
    {
        $comment = get_comment($comment_id);
        if (!$comment || (int) $comment->user_id !== get_current_user_id()) {
            return new WP_Error('npcink_comment_not_found', __('评论不存在。', 'npcink-site-toolbox'), array('status' => 404));
        }
        return $comment;
    }

    private static function has_effective_replies($comment_id)
    {
        return (int) get_comments(array(
            'parent' => $comment_id,
            'status' => array('approve', 'hold'),
            'count' => true,
        )) > 0;
    }

    private static function is_trashed($comment)
    {
        return 'trash' === wp_get_comment_status($comment);
    }

    private static function query_status($status)
    {
        $map = array(
            'approved' => 'approve',
            'pending' => 'hold',
            'trash' => 'trash',
            'all' => array('approve', 'hold', 'trash'),
        );
        return isset($map[$status]) ? $map[$status] : $map['all'];
    }

    public static function prepare_comment($comment, $has_replies = null)
    {
        $status = wp_get_comment_status($comment);
        $status_map = array('approved' => 'approved', 'unapproved' => 'pending', 'trash' => 'trash');
        if (null === $has_replies) {
            $has_replies = self::has_effective_replies((int) $comment->comment_ID);
        }
        return array(
            'id' => (int) $comment->comment_ID,
            'postId' => (int) $comment->comment_post_ID,
            'postTitle' => get_the_title((int) $comment->comment_post_ID),
            'postUrl' => esc_url_raw(get_permalink((int) $comment->comment_post_ID)),
            'content' => (string) $comment->comment_content,
            'status' => isset($status_map[$status]) ? $status_map[$status] : $status,
            'date' => mysql_to_rfc3339($comment->comment_date_gmt),
            'expectedHash' => self::comment_hash($comment),
            'hasReplies' => $has_replies,
            'canEdit' => !self::is_trashed($comment) && !$has_replies,
            'canDelete' => !self::is_trashed($comment) && !$has_replies,
        );
    }

    private static function get_reply_map($comments)
    {
        $parent_ids = array_map(function ($comment) {
            return (int) $comment->comment_ID;
        }, $comments);
        if (empty($parent_ids)) {
            return array();
        }

        $replies = get_comments(array(
            'parent__in' => $parent_ids,
            'status' => array('approve', 'hold'),
            'number' => 0,
        ));
        $reply_map = array();
        foreach ($replies as $reply) {
            $reply_map[(int) $reply->comment_parent] = true;
        }
        return $reply_map;
    }

    private static function comment_hash($comment)
    {
        return hash('sha256', implode('|', array(
            (string) $comment->comment_ID,
            (string) $comment->comment_content,
            (string) $comment->comment_approved,
        )));
    }
}
