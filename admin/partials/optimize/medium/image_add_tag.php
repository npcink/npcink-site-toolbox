<?php

defined('ABSPATH') || exit;

/**
 * 功能：给图片添加alt标签
 * 来源：
 */
if (!class_exists('Npcink_Toolbox_Image_Add_Tag')) {
    class Npcink_Toolbox_Image_Add_Tag implements Npcink_Toolbox_Module_Interface
    {
        //加载
        public static function run($config = array())
        {
            add_filter('the_content', array(__CLASS__, 'image_alt_tag'), 99999);
        }
        //自动给图片添加Alt标签
        public static function image_alt_tag($content)
        {
            $content = wp_kses_post((string) $content);
            $alt_parts = array_filter(array(
                trim(wp_strip_all_tags((string) get_the_title())),
                trim(wp_strip_all_tags((string) get_bloginfo('name'))),
            ));
            $alt_text = implode(' - ', $alt_parts);
            if ($alt_text === '') {
                return $content;
            }

            $processor = new WP_HTML_Tag_Processor($content);
            while ($processor->next_tag('IMG')) {
                $existing_alt = $processor->get_attribute('alt');
                if ($existing_alt !== null && trim((string) $existing_alt) !== '') {
                    continue;
                }

                $processor->set_attribute('alt', $alt_text);
            }

            return wp_kses_post($processor->get_updated_html());
        }
    }
}
