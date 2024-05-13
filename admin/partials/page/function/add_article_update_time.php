<?php

/**
 * 效果：文章添加更新时间
 * 来源：
 */

if (!class_exists('Npcink_Single_Add_Last_Updated_Date')) {
    class Npcink_Single_Add_Last_Updated_Date
    {
        public static function run()
        {
            add_filter('the_content', array(__CLASS__, 'add_last_updated_date'));
        }

        //在更新过的文章的页面结尾添加最后更新时间
        public static function add_last_updated_date($content)
        {
            $u_time = get_the_time('U'); //发布时间
            $u_modified_time = get_the_modified_time('U'); //修改时间
            $custom_content = '';
            if ($u_modified_time >= $u_time + 86400) {
                $updated_date = get_the_modified_time('Y-m-d H:i'); //Y-m-d H:i
                $custom_content .= '<div class="npcink-last-updated">最后编辑于：<span>' . $updated_date . ' </span></div>';
            }
            $content .= $custom_content;
            return $content;
        }
    }
}
