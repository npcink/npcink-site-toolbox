<?php

/**
 * 国内 CDN 资源替换
 * 将 WordPress 加载的国外资源替换为国内 CDN 镜像
 * 包括：Google 字体、Gravatar 头像、Google Ajax 等
 */
if (!class_exists('MaBox_CDN_Replace')) {
    class MaBox_CDN_Replace
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('init', array(__CLASS__, 'do_replace'));
        }

        public static function do_replace()
        {
            $gravatar = MaBox_Admin::get_config(self::$option, 'cdn_gravatar', false);
            $google_fonts = MaBox_Admin::get_config(self::$option, 'cdn_google_fonts', false);
            $google_ajax = MaBox_Admin::get_config(self::$option, 'cdn_google_ajax', false);
            $custom_cdn = MaBox_Admin::get_config(self::$option, 'cdn_custom', '');

            // Gravatar 头像替换
            if ($gravatar && $gravatar !== 'false') {
                add_filter('avatar_defaults', array(__CLASS__, 'remove_default_avatar'));
                add_filter('get_avatar', array(__CLASS__, 'replace_gravatar'));
                add_filter('get_avatar_url', array(__CLASS__, 'replace_gravatar_url'));
            }

            // Google Fonts 替换
            if ($google_fonts && $google_fonts !== 'false') {
                add_filter('style_loader_src', array(__CLASS__, 'replace_google_fonts'));
            }

            // Google Ajax 替换
            if ($google_ajax && $google_ajax !== 'false') {
                add_filter('script_loader_src', array(__CLASS__, 'replace_google_ajax'));
            }

            // 自定义 CDN 替换（通用 URL 替换）
            if (!empty($custom_cdn)) {
                $rules = explode("\n", trim($custom_cdn));
                foreach ($rules as $rule) {
                    $rule = trim($rule);
                    if (empty($rule) || strpos($rule, '=>') === false) {
                        continue;
                    }
                    list($from, $to) = explode('=>', $rule, 2);
                    $from = trim($from);
                    $to = trim($to);
                    if (empty($from) || empty($to)) {
                        continue;
                    }
                    add_filter('style_loader_src', function ($src) use ($from, $to) {
                        return str_replace($from, $to, $src);
                    });
                    add_filter('script_loader_src', function ($src) use ($from, $to) {
                        return str_replace($from, $to, $src);
                    });
                    add_filter('content_url', function ($url) use ($from, $to) {
                        return str_replace($from, $to, $url);
                    });
                }
            }
        }

        /**
         * 替换 Gravatar URL
         */
        public static function replace_gravatar($avatar)
        {
            $mirror = self::get_gravatar_mirror();
            $sources = array(
                'www.gravatar.com/avatar/',
                '0.gravatar.com/avatar/',
                '1.gravatar.com/avatar/',
                '2.gravatar.com/avatar/',
                'secure.gravatar.com/avatar/',
                'cn.gravatar.com/avatar/',
            );
            return str_replace($sources, $mirror, $avatar);
        }

        /**
         * 替换 Gravatar URL（用于 get_avatar_url 过滤器）
         */
        public static function replace_gravatar_url($url)
        {
            $mirror = self::get_gravatar_mirror();
            $sources = array(
                'www.gravatar.com/avatar/',
                '0.gravatar.com/avatar/',
                '1.gravatar.com/avatar/',
                '2.gravatar.com/avatar/',
                'secure.gravatar.com/avatar/',
                'cn.gravatar.com/avatar/',
            );
            return str_replace($sources, $mirror, $url);
        }

        /**
         * 删除默认 Gravatar 选项
         */
        public static function remove_default_avatar($avatar_defaults)
        {
            unset($avatar_defaults['mystery']);
            unset($avatar_defaults['blank']);
            return $avatar_defaults;
        }

        /**
         * 替换 Google Fonts URL
         */
        public static function replace_google_fonts($src)
        {
            $mirror = self::get_google_fonts_mirror();
            if (strpos($src, 'fonts.googleapis.com') !== false) {
                $src = str_replace('fonts.googleapis.com', $mirror, $src);
            }
            if (strpos($src, 'fonts.gstatic.com') !== false) {
                $src = str_replace('fonts.gstatic.com', 'gstatic.loli.net', $src);
            }
            return $src;
        }

        /**
         * 替换 Google Ajax URL
         */
        public static function replace_google_ajax($src)
        {
            if (strpos($src, 'ajax.googleapis.com') !== false) {
                $src = str_replace('ajax.googleapis.com', 'ajax.loli.net', $src);
            }
            return $src;
        }

        /**
         * 获取 Gravatar 镜像地址
         */
        private static function get_gravatar_mirror()
        {
            $mirror = MaBox_Admin::get_config(self::$option, 'cdn_gravatar_mirror', 'gravatar.loli.net/avatar/');
            return trim($mirror);
        }

        /**
         * 获取 Google Fonts 镜像地址
         */
        private static function get_google_fonts_mirror()
        {
            $mirror = MaBox_Admin::get_config(self::$option, 'cdn_google_fonts_mirror', 'fonts.loli.net');
            return trim($mirror);
        }
    }
}
