<?php
/**
 * 功能：给图片添加alt标签
 * 来源：
 */
if (!class_exists('MaBox_Image_Add_Tag')) {
    class MaBox_Image_Add_Tag
    {
        //加载
        public static function run()
        {
            add_filter('the_content', array(__CLASS__, 'image_alt_tag'), 99999);
        }
        //自动给图片添加Alt标签
        public static function image_alt_tag($content)
        {
            //global $post;
            preg_match_all('/<img (.*?)\/>/', $content, $images);
            if (!is_null($images)) {
                foreach ($images[1] as $index => $value) {
                    $new_img = str_replace('<img', '<img alt="' . get_the_title() . ' - ' . get_bloginfo('name') . '"', $images[0][$index]);
                    $content = str_replace($images[0][$index], $new_img, $content);
                }
            }
            return $content;
        }
    }
}
