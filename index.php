<?php //沉默是金

//http://magick.plugin/wp-json/carbon-fields/v1/posts/2278
add_action('rest_api_init', function () {
    register_rest_route('carbon-fields/v1', 'posts/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'mytheme_get_post_info',
        'permission_callback' => '__return_true',
    ));
});

function mytheme_get_post_info($request)
{
    $post_id = $request->get_param('id');
    $post = get_post($post_id);

    $post_title = get_the_title($post_id);
    $post_excerpt = $post->post_excerpt; // 获取文章描述
    $post_date = get_the_date('Y-m-d H:i:s', $post_id);

    $post_categories = get_the_category($post_id);
    $cat_array = array();
    foreach ($post_categories as $cat) {
        $cat_array[] = array(
            'id' => $cat->cat_ID,
            'name' => $cat->name,
        );
    }
    $categories = $cat_array;

    $featured_image = array();
    if (has_post_thumbnail($post_id)) {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $featured_image['url'] = wp_get_attachment_url($thumbnail_id);
        $featured_image['caption'] = get_post($thumbnail_id)->post_excerpt;
        $featured_image['details'] = wp_get_attachment_metadata($thumbnail_id);
    }
    $post_content = apply_filters('the_content', $post->post_content); // 获取文章正文内容
    $response = array(
        'id' => $post_id,
        'date' => $post_date,
        'title' => $post_title,
        'excerpt' => $post_excerpt,
        'image' => $featured_image,
        'category' => $categories,
        'content' => $post_content,
    );

    return $response;
}
