<?php //沉默是金






// 替换评论作者提供的网址链接属性
function replace_comment_link_attributes($content)
{
    $pattern = '/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/';
    $replacement = '<a$1href="$2"$3 rel="external nofollow" target="_blank">$4</a>';
    $content = preg_replace($pattern, $replacement, $content);
    return $content;
}



//add_filter('get_comment_text', 'replace_comment_link_attributes');

// 允许任何来源的跨域请求
function allow_cors()
{
    header("Access-Control-Allow-Origin: *");
}
//add_action('init', 'allow_cors');