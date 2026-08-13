<?php

defined('ABSPATH') || exit;

$npcink_toolbox_api_base = esc_url_raw(rest_url('npcink-site-toolbox/v1'));
$npcink_toolbox_profile_url = admin_url('profile.php#application-passwords-section');
?>
<div class="wrap npcink-comment-rest-help">
    <h1><?php echo esc_html__('用户评论 REST 接口', 'npcink-site-toolbox'); ?></h1>
    <p class="npcink-comment-rest-help__lead">
        <?php echo esc_html__('使用 WordPress 应用程序密码，外部客户端可以查看、发布、修改和删除当前认证用户自己的评论。', 'npcink-site-toolbox'); ?>
    </p>
    <p><?php echo esc_html__('适用版本：Npcink Site Toolbox 3.3.0 及以上；该功能默认关闭，必须由管理员显式启用。', 'npcink-site-toolbox'); ?></p>

    <div class="notice notice-warning inline">
        <p><strong><?php echo esc_html__('正式环境必须使用 HTTPS。', 'npcink-site-toolbox'); ?></strong>
            <?php echo esc_html__('HTTP 仅适用于 Local、WordPress Playground 等隔离的本地开发环境。', 'npcink-site-toolbox'); ?>
        </p>
    </div>

    <section class="card">
        <h2><?php echo esc_html__('1. 开启功能', 'npcink-site-toolbox'); ?></h2>
        <ol>
            <li><?php echo esc_html__('打开“Npcink 站点工具箱 → 内容与页面 → 评论”。', 'npcink-site-toolbox'); ?></li>
            <li><?php echo esc_html__('开启“用户评论 REST 接口”并保存设置。', 'npcink-site-toolbox'); ?></li>
            <li><?php echo esc_html__('“显示‘我的评论’后台页面”是独立的兜底界面开关，关闭后 REST 仍可使用。', 'npcink-site-toolbox'); ?></li>
        </ol>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('2. 创建应用程序密码', 'npcink-site-toolbox'); ?></h2>
        <ol>
            <li><?php echo esc_html__('使用需要管理评论的 WordPress 用户登录。', 'npcink-site-toolbox'); ?></li>
            <li><a href="<?php echo esc_url($npcink_toolbox_profile_url); ?>"><?php echo esc_html__('打开“用户 → 个人资料 → 应用程序密码”', 'npcink-site-toolbox'); ?></a>。</li>
            <li><?php echo esc_html__('填写客户端名称，创建并立即复制密码。密码离开页面后不会再次显示。', 'npcink-site-toolbox'); ?></li>
        </ol>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('3. 准备 curl 变量', 'npcink-site-toolbox'); ?></h2>
        <pre><code><?php echo esc_html("API_BASE='" . $npcink_toolbox_api_base . "'\nWP_USER='your-login-name'\nAPP_PASSWORD='xxxx xxxx xxxx xxxx xxxx xxxx'"); ?></code></pre>
        <p><?php echo esc_html__('WordPress 显示的密码空格仅用于阅读，curl 可以直接使用完整密码。', 'npcink-site-toolbox'); ?></p>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('4. 查看自己的评论', 'npcink-site-toolbox'); ?></h2>
        <pre><code><?php echo esc_html('curl --user "$WP_USER:$APP_PASSWORD" \\\n  "$API_BASE/me/comments?page=1&pageSize=20&status=all"'); ?></code></pre>
        <p><?php echo esc_html__('status 支持 all、approved、pending 和 trash。列表响应中的 expectedHash 用于修改时的并发校验。', 'npcink-site-toolbox'); ?></p>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('5. 发表评论', 'npcink-site-toolbox'); ?></h2>
        <pre><code><?php echo esc_html('curl --user "$WP_USER:$APP_PASSWORD" \\\n  --header \'Content-Type: application/json\' \\\n  --request POST \\\n  --data \'{"postId":123,"content":"这是一条评论"}\' \\\n  "$API_BASE/me/comments"'); ?></code></pre>
        <p><?php echo esc_html__('成功返回 HTTP 201 和新评论对象。评论必须发布到已公开且允许评论的文章。', 'npcink-site-toolbox'); ?></p>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('6. 修改评论', 'npcink-site-toolbox'); ?></h2>
        <pre><code><?php echo esc_html('curl --user "$WP_USER:$APP_PASSWORD" \\\n  --header \'Content-Type: application/json\' \\\n  --request PATCH \\\n  --data \'{"content":"修改后的评论","expectedHash":"从最新响应复制的哈希"}\' \\\n  "$API_BASE/me/comments/456"'); ?></code></pre>
        <p><?php echo esc_html__('成功返回 HTTP 200，评论重新进入待审核状态。哈希不一致时返回 409，应重新读取后再修改。', 'npcink-site-toolbox'); ?></p>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('7. 删除评论', 'npcink-site-toolbox'); ?></h2>
        <pre><code><?php echo esc_html('curl --user "$WP_USER:$APP_PASSWORD" \\\n  --request DELETE \\\n  "$API_BASE/me/comments/456"'); ?></code></pre>
        <p><?php echo esc_html__('删除只会移入 WordPress 回收站。首次成功返回 deleted，重复删除返回 alreadyDeleted。', 'npcink-site-toolbox'); ?></p>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('8. 批量删除', 'npcink-site-toolbox'); ?></h2>
        <pre><code><?php echo esc_html('curl --user "$WP_USER:$APP_PASSWORD" \\\n  --header \'Content-Type: application/json\' \\\n  --request POST \\\n  --data \'{"commentIds":[456,457,458]}\' \\\n  "$API_BASE/me/comments/batch-delete"'); ?></code></pre>
        <p><?php echo esc_html__('每次最多 20 个评论 ID。系统逐条处理，部分失败不会回滚已经成功的结果。', 'npcink-site-toolbox'); ?></p>
    </section>

    <section class="card">
        <h2><?php echo esc_html__('常见状态码', 'npcink-site-toolbox'); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php echo esc_html__('状态码', 'npcink-site-toolbox'); ?></th><th><?php echo esc_html__('含义', 'npcink-site-toolbox'); ?></th></tr></thead>
            <tbody>
                <tr><td><code>401</code></td><td><?php echo esc_html__('用户名或应用程序密码错误。', 'npcink-site-toolbox'); ?></td></tr>
                <tr><td><code>404</code></td><td><?php echo esc_html__('功能未开启、评论不存在或不属于当前用户。', 'npcink-site-toolbox'); ?></td></tr>
                <tr><td><code>409</code></td><td><?php echo esc_html__('评论已变化、已有回复或文章已关闭评论。', 'npcink-site-toolbox'); ?></td></tr>
            </tbody>
        </table>
    </section>
</div>
