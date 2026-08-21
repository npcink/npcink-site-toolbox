<?php

defined('ABSPATH') || exit;

/**
 * 效果：文章添加更新时间
 * 来源：
 */

if (!class_exists('Npcink_Toolbox_Single_Add_Last_Updated_Date')) {
    class Npcink_Toolbox_Single_Add_Last_Updated_Date implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_filter('the_content', array(__CLASS__, 'add_last_updated_date'));
        }

        //在更新过的文章的页面结尾添加最后更新时间
        public static function add_last_updated_date($content)
        {
            $content = wp_kses_post((string) $content);
            $u_time = get_the_time('U'); //发布时间
            $u_modified_time = get_the_modified_time('U'); //修改时间
            $custom_content = '';
            if ($u_modified_time >= $u_time + 86400) {
                $updated_date = get_the_modified_time('Y-m-d H:i'); //Y-m-d H:i
                $custom_content .= sprintf(
                    /* translators: %s: Post last modified date and time. */
                    '<div class="npcink-last-updated">%1$s<span>%2$s </span></div>',
                    esc_html__('最后编辑于：', 'npcink-site-toolbox'),
                    esc_html($updated_date)
                );
            }
            return wp_kses_post($content . $custom_content);
        }
    }
}
