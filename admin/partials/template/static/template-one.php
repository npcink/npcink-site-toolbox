<?php
get_header();
// Custom template code goes here

?>
<?php
// 获取当前页面的ID
$post_id = get_the_ID();

// 获取自定义字段的值
$custom_field_1_value = get_post_meta($post_id, 'custom_field_1', true);

// 如果自定义字段的值为空，则显示提示信息并退出
if (empty($custom_field_1_value)) {
    echo '暂未设置';
    return get_footer(); // 获取页脚;
}

// 构建查询参数
$args = array(
    's' => $custom_field_1_value, // 搜索标题中包含指定值的文章
    'post_type' => 'post', // 文章类型为post（可根据需要修改）
    'posts_per_page' => -1, // 显示所有符合条件的文章
);

// 查询文章列表
$query = new WP_Query($args);

// 输出文章列表
if ($query->have_posts()) :
    while ($query->have_posts()) : $query->the_post();
?>
        <div>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <!-- 这里可以添加文章其他信息的显示，如作者、日期等 -->
        </div>
    <?php
    endwhile;
    wp_reset_postdata(); // 重置文章查询
else :
    ?>
    <p>没有找到符合条件的文章。</p>
<?php
endif;
?>


<?php

get_footer(); // 获取页脚
?>