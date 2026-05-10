<?php
/**
 * 文章链接添加来源标识
 *
 * 在文章中的所有内部链接后添加 from=npc 参数，用于流量追踪。
 */
if (!class_exists('MaBox_Page_Link_Source')) {
    class MaBox_Page_Link_Source {

        private static $option;

        public static function run($config) {
            self::$option = $config;
            add_filter('the_content', array(__CLASS__, 'add_source_to_links'));
            add_filter('the_excerpt', array(__CLASS__, 'add_source_to_links'));
        }

        public static function add_source_to_links($content) {
            $source_key = !empty(self::$option['source_key']) ? self::$option['source_key'] : 'npc';
            $source_param = 'from=' . $source_key;

            // 匹配所有 <a> 标签的 href 属性
            $content = preg_replace_callback(
                '/<a\s+([^>]*?)href="([^"]*?)"([^>]*)>/i',
                function ($matches) use ($source_param) {
                    $attrs = $matches[1];
                    $url   = $matches[2];
                    $rest  = $matches[3];

                    // 跳过已包含 from 参数的链接
                    if (strpos($url, 'from=') !== false) {
                        return $matches[0];
                    }

                    // 跳过外部链接
                    $home_url = home_url();
                    if (strpos($url, $home_url) !== 0 && strpos($url, '/') === 0) {
                        $url = $home_url . $url;
                    } elseif (strpos($url, $home_url) !== 0) {
                        return $matches[0];
                    }

                    // 添加来源参数
                    $separator = strpos($url, '?') !== false ? '&' : '?';
                    $url .= $separator . $source_param;

                    return '<a ' . $attrs . 'href="' . esc_url($url) . '"' . $rest . '>';
                },
                $content
            );

            return $content;
        }
    }
}
