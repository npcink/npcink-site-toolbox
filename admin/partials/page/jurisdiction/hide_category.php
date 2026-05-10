<?php

/**
 * 未登录隐藏指定分类下的文章
 */

if (!class_exists('MaBox_Page_Hide_Category')) {
    class MaBox_Page_Hide_Category
    {
        private static $id_array; //分类数组
        private static $tip_content; //提示信息
        public static function run($array, $id_tip_content)
        {
            self::$id_array = $array;
            self::$tip_content = $id_tip_content;
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_categories'));
            add_action('wp_footer', array(__CLASS__, 'hide_download_for_restricted_categories'));
        }

        public static function restrict_content_for_specific_categories($content)
        {
            $restricted_category_ids = self::$id_array;

            if (in_category($restricted_category_ids)) {
                if (!MaBox_Helpers::is_logged_in()) {
                    // 首先移除内容中的下载框 HTML（服务端过滤，非仅 CSS 隐藏）
                    $content = self::strip_download_boxes($content);
                    // 然后替换为登录提示
                    $content = self::$tip_content;
                    self::enqueue_assets();
                }
            }
            return $content;
        }

        /**
         * 从内容中移除下载框 HTML（服务端过滤）
         */
        private static function strip_download_boxes($content)
        {
            $patterns = array(
                // 短代码形式的下载框
                '/\[download[^\]]*\].*?\[\/download\]/is',
            );
            $content = preg_replace($patterns, '', $content);

            $content = self::strip_div_by_class($content, 'b2-down-box');
            $content = self::strip_div_by_class($content, 'down-box');
            $content = self::strip_div_by_class($content, 'post-download');
            $content = self::strip_div_by_class($content, 'download-box');

            return $content;
        }

        private static function strip_div_by_class($html, $class_name)
        {
            $pattern = '/<div([^>]*)class="([^"]*' . preg_quote($class_name, '/') . '[^"]*)"([^>]*)>/i';
            while (preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
                $start_pos = $matches[0][1];
                $depth = 1;
                $pos = $start_pos + strlen($matches[0][0]);
                $len = strlen($html);

                while ($pos < $len && $depth > 0) {
                    $next_open = strpos($html, '<div', $pos);
                    $next_close = strpos($html, '</div>', $pos);

                    if ($next_close === false) {
                        break;
                    }

                    if ($next_open !== false && $next_open < $next_close) {
                        $depth++;
                        $pos = $next_open + 4;
                    } else {
                        $depth--;
                        if ($depth === 0) {
                            $end_pos = $next_close + 6;
                            $html = substr($html, 0, $start_pos) . substr($html, $end_pos);
                            break;
                        }
                        $pos = $next_close + 6;
                    }
                }

                if ($depth > 0) {
                    break;
                }
            }

            return $html;
        }

        public static function hide_download_for_restricted_categories()
        {
            if (MaBox_Helpers::is_logged_in()) {
                return;
            }

            if (in_category(self::$id_array)) {
                echo '<style>.b2-down-box, .down-box, .post-download, .download-box, .m-box.down { display: none !important; }</style>';
            }
        }
        public static function enqueue_assets()
        {
            wp_enqueue_script(MAGICK_MIXTURE_NAME . '_hide_category', '', array(), MAGICK_MIXTURE_VERSION, true);
            $tip_content = wp_kses_post(self::$tip_content);
            $js = "const entryContent = document.querySelector('.entry-content'); if (entryContent) { entryContent.innerHTML = '" . $tip_content . "'; }";
            wp_add_inline_script(MAGICK_MIXTURE_NAME . '_hide_category', $js);
        }
    }
}
