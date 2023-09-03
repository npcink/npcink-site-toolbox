<?php //沉默是金


/**
 * WordPress外链新窗口打开并使用php页面go跳转
 * https://www.dujin.org/12762.html
 */
function the_content_nofollowss($content)
{
    preg_match_all('/<a(.*?)href="(.*?)"(.*?)>/', $content, $matches);
    if ($matches) {
        foreach ($matches[2] as $val) {
            if (strpos($val, '://') !== false && strpos($val, home_url()) === false && !preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i', $val)) {
                $content = str_replace("href=\"$val\"", "href=\"" . home_url() . "/golink/?url=$val\" ", $content);
            }
        }
    }
    return $content;
}
add_filter('the_content', 'the_content_nofollowss', 999);





function custom_plugin_rewrite_rules()
{
    add_rewrite_rule(
        '^too/', // 设置你的链接格式，例如 /too/
        'index.php?pagename=page-go&custom_id=$matches[1]', // 指向你的自定义模板文件的路径
        'top'
    );

    add_rewrite_rule(
        '^my-page/?$',
        plugins_url('/page-go.php', __FILE__),
        'top'
    );
}

add_action('init', 'custom_plugin_rewrite_rules');
