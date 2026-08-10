<?php

defined('ABSPATH') || exit;

/**
 * 功能：媒体库支持SVG格式（安全模式）
 * 来源：
 *
 * 安全措施：
 * 1. 仅管理员可上传 SVG
 * 2. 上传时清洗 SVG 内容（移除危险标签和属性）
 * 3. 使用 wp_check_filetype_and_ext 验证文件类型
 */
if (!class_exists('Npcink_Toolbox_Medium_Svg_Support')) {
    class Npcink_Toolbox_Medium_Svg_Support implements Npcink_Toolbox_Module_Interface
    {
        //加载
        public static function run($config = array())
        {
            self::run_add_svg();
        }

        //添加媒体库 SVG 支持
        public static function run_add_svg()
        {
            add_filter('upload_mimes', array(__CLASS__, 'salong_mime_types'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_svg_style'));

            // SVG 上传时清洗内容
            add_filter('wp_handle_upload_prefilter', array(__CLASS__, 'sanitize_svg_upload'));
            add_filter('wp_handle_sideload_prefilter', array(__CLASS__, 'sanitize_svg_upload'));
            add_filter('wp_generate_attachment_metadata', array(__CLASS__, 'add_svg_metadata'), 10, 3);
        }

        //添加媒体库 SVG 图标支持
        public static function salong_mime_types($mimes)
        {
            // 仅管理员可上传 SVG
            if (!current_user_can('manage_options')) {
                return $mimes;
            }

            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        }

        //在媒体库显示 SVG 图标
        public static function enqueue_admin_svg_style()
        {
            wp_register_style('npcink-site-toolbox-svg-admin', false, array(), NPCINK_SITE_TOOLBOX_VERSION);
            wp_enqueue_style('npcink-site-toolbox-svg-admin');
            wp_add_inline_style(
                'npcink-site-toolbox-svg-admin',
                "table.media .column-title .media-icon img[src*='.svg']{width:100%;height:auto}"
            );
        }

        /**
         * 清洗 SVG 文件内容
         * 移除危险标签和属性，防止 XSS 攻击
         */
        public static function sanitize_svg_content($content)
        {
            // 移除危险标签
            $dangerous_tags = array(
                'script', 'object', 'embed', 'iframe', 'form', 'input',
                'button', 'select', 'textarea', 'link', 'meta', 'base',
            );

            foreach ($dangerous_tags as $tag) {
                $qualified_tag = '(?:[a-zA-Z_][a-zA-Z0-9_.-]*:)?' . preg_quote($tag, '/');
                // 移除整个标签及其内容
                $content = preg_replace('/<' . $qualified_tag . '\b[^>]*>.*?<\/' . $qualified_tag . '\s*>/is', '', $content);
                $content = preg_replace('/<' . $qualified_tag . '\b[^>]*\/?>/is', '', $content);
            }

            // 移除危险属性
            $dangerous_attrs = array(
                'on\w+',           // 所有 on* 事件处理器
                'javascript:',     // javascript: 协议
                'vbscript:',       // vbscript: 协议
                'expression\(',    // CSS expression
                'url\(',           // CSS url() - 可选，可能误伤
                'import',          // CSS @import
            );

            foreach ($dangerous_attrs as $attr) {
                $content = preg_replace('/\s*' . $attr . '\s*=\s*["\'][^"\']*["\']/i', '', $content);
                $content = preg_replace('/\s*' . $attr . '\s*=\s*\S+/i', '', $content);
            }

            $dangerous_values = array(
                'javascript\s*:',
                'vbscript\s*:',
                'expression\s*\(',
            );
            foreach ($dangerous_values as $pattern) {
                $content = preg_replace('/\s*[\w-]+\s*=\s*["\'][^"\']*' . $pattern . '[^"\']*["\']/i', '', $content);
                $content = preg_replace('/\s*[\w-]+\s*=\s*\S*' . $pattern . '\S*/i', '', $content);
            }

            // XML 字符引用会在浏览器解析属性时解码，必须检查解码后的值。
            $decoded_attribute_result = preg_replace_callback(
                '/\s+([a-zA-Z_:][a-zA-Z0-9_.:-]*)\s*=\s*(["\'])(.*?)\2/s',
                static function ($matches) {
                    $attribute_name = strtolower($matches[1]);
                    $attribute_value = html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $compact_value = preg_replace('/[\x00-\x20\x7f]+/u', '', $attribute_value);

                    if (
                        strpos($attribute_name, 'on') === 0
                        || preg_match('/(?:javascript|vbscript):/i', (string) $compact_value)
                        || preg_match('/expression\s*\(/i', $attribute_value)
                    ) {
                        return '';
                    }

                    return $matches[0];
                },
                $content
            );
            if (is_string($decoded_attribute_result)) {
                $content = $decoded_attribute_result;
            }

            // 移除 XML 外部实体 (XXE)
            $content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $content);
            $content = preg_replace('/<!ENTITY[^>]*>/i', '', $content);

            return $content;
        }

        /**
         * SVG 上传预处理
         */
        public static function sanitize_svg_upload($file)
        {
            // 检查是否为 SVG 文件
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'svg') {
                return $file;
            }

            // 仅管理员可上传 SVG；其他文件不受此功能影响。
            if (!current_user_can('manage_options')) {
                $file['error'] = '仅管理员可上传 SVG 文件';
                return $file;
            }

            // 读取文件内容
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                $file['error'] = '无法读取 SVG 文件内容';
                return $file;
            }

            // 检查是否为有效的 XML
            $xml = @simplexml_load_string($content);
            if ($xml === false) {
                $file['error'] = 'SVG 文件格式无效（无效的 XML）';
                return $file;
            }

            // 清洗内容
            $sanitized = self::sanitize_svg_content($content);

            // 写回文件
            if (file_put_contents($file['tmp_name'], $sanitized) === false) {
                $file['error'] = '无法保存清洗后的 SVG 文件';
                return $file;
            }

            // 记录日志
            if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                Npcink_Toolbox_Audit_Logger::file('SVG 上传已清洗: ' . $file['name'], array(
                    'user_id' => get_current_user_id(),
                ));
            }

            return $file;
        }

        /**
         * Supply dimensions for SVG attachments so REST responses can calculate
         * missing image sizes without reading absent raster metadata keys.
         *
         * @param mixed  $metadata Existing attachment metadata.
         * @param int    $attachment_id Attachment ID.
         * @param string $context Metadata generation context.
         * @return mixed
         */
        public static function add_svg_metadata($metadata, $attachment_id, $context)
        {
            unset($context);

            if ('image/svg+xml' !== get_post_mime_type($attachment_id)) {
                return $metadata;
            }

            $file = get_attached_file($attachment_id);
            if (!is_string($file) || !is_readable($file)) {
                return $metadata;
            }

            $content = file_get_contents($file);
            $dimensions = self::get_svg_dimensions($content);
            if (null === $dimensions) {
                return $metadata;
            }

            $metadata = is_array($metadata) ? $metadata : array();
            $metadata['width'] = $dimensions['width'];
            $metadata['height'] = $dimensions['height'];
            $metadata['sizes'] = isset($metadata['sizes']) && is_array($metadata['sizes'])
                ? $metadata['sizes']
                : array();

            if (empty($metadata['file'])) {
                $relative_file = _wp_relative_upload_path($file);
                if (is_string($relative_file) && '' !== $relative_file) {
                    $metadata['file'] = $relative_file;
                }
            }

            return $metadata;
        }

        /**
         * @param mixed $content SVG XML.
         * @return array{width: int, height: int}|null
         */
        public static function get_svg_dimensions($content)
        {
            if (!is_string($content) || '' === trim($content)) {
                return null;
            }

            $xml = @simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
            if (false === $xml) {
                return null;
            }

            $width = self::parse_svg_length((string) $xml['width']);
            $height = self::parse_svg_length((string) $xml['height']);

            if (null === $width || null === $height) {
                $view_box = preg_split('/[\s,]+/', trim((string) $xml['viewBox']));
                if (is_array($view_box) && 4 === count($view_box)) {
                    $view_box_width = is_numeric($view_box[2]) ? (float) $view_box[2] : 0.0;
                    $view_box_height = is_numeric($view_box[3]) ? (float) $view_box[3] : 0.0;
                    if ($view_box_width > 0 && $view_box_height > 0) {
                        $width = max(1, (int) round($view_box_width));
                        $height = max(1, (int) round($view_box_height));
                    }
                }
            }

            return array(
                'width' => null === $width ? 0 : $width,
                'height' => null === $height ? 0 : $height,
            );
        }

        /**
         * @param string $value SVG length attribute.
         * @return int|null
         */
        private static function parse_svg_length($value)
        {
            if (1 !== preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*(?:px)?\s*$/i', $value, $matches)) {
                return null;
            }

            $length = (float) $matches[1];
            return $length > 0 ? max(1, (int) round($length)) : null;
        }
    }
}
