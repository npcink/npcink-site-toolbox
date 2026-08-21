<?php
defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Module_Metadata')) {
    class Npcink_Toolbox_Module_Metadata {

        private static $registry = null;

        public static function get_registry() {
            if (self::$registry === null) {
                self::$registry = require plugin_dir_path(__FILE__) . 'registry.php';
            }
            return self::$registry;
        }

        public static function get_module($id) {
            $registry = self::get_registry();
            return isset($registry[$id]) ? $registry[$id] : null;
        }

        public static function get_ui_metadata() {
            $registry = self::get_registry();
            $ui = array();

            foreach ($registry as $module_id => $meta) {
                $entry = array(
                    'id'        => $module_id,
                    'category'  => $meta['category'],
                    'scope'     => $meta['scope'],
                    'label'     => isset($meta['label']) ? self::translate($meta['label']) : '',
                    'group'     => isset($meta['group']) ? self::translate($meta['group']) : '',
                    'feature_id' => isset($meta['feature_id']) ? $meta['feature_id'] : '',
                    'risk_tags' => isset($meta['risk_tags']) ? self::translate_list($meta['risk_tags']) : array(),
                    'risk'      => isset($meta['risk']) ? $meta['risk'] : array('level' => 'none'),
                    'depends_on' => isset($meta['depends_on']) ? $meta['depends_on'] : array(),
                    'preset_tags' => isset($meta['preset_tags']) ? $meta['preset_tags'] : array(),
                );

                if (!empty($meta['always_load'])) {
                    $entry['always_load'] = true;
                }
                if (!empty($meta['mobile_only'])) {
                    $entry['mobile_only'] = true;
                }

                $ui[$module_id] = $entry;
            }

            return $ui;
        }

        private static function translate($value) {
            switch ((string) $value) {
                case '隐藏顶部工具条':
                    return __('隐藏顶部工具条', 'npcink-site-toolbox');
                case '站点':
                    return __('站点', 'npcink-site-toolbox');
                case '推荐':
                    return __('推荐', 'npcink-site-toolbox');
                case '仅后台':
                    return __('仅后台', 'npcink-site-toolbox');
                case '禁止 Title 转义':
                    return __('禁止 Title 转义', 'npcink-site-toolbox');
                case '移除版本信息':
                    return __('移除版本信息', 'npcink-site-toolbox');
                case '安全':
                    return __('安全', 'npcink-site-toolbox');
                case '分类链接简化':
                    return __('分类链接简化', 'npcink-site-toolbox');
                case 'SEO':
                    return __('SEO', 'npcink-site-toolbox');
                case '搜索链接优化':
                    return __('搜索链接优化', 'npcink-site-toolbox');
                case '移除用户站点地图':
                    return __('移除用户站点地图', 'npcink-site-toolbox');
                case '用户列表展示昵称':
                    return __('用户列表展示昵称', 'npcink-site-toolbox');
                case '隐藏邮件中的 IP':
                    return __('隐藏邮件中的 IP', 'npcink-site-toolbox');
                case '站点小工具':
                    return __('站点小工具', 'npcink-site-toolbox');
                case '图片自动添加 Alt 标签':
                    return __('图片自动添加 Alt 标签', 'npcink-site-toolbox');
                case '禁止缩略图':
                    return __('禁止缩略图', 'npcink-site-toolbox');
                case '谨慎':
                    return __('谨慎', 'npcink-site-toolbox');
                case '需主题兼容':
                    return __('需主题兼容', 'npcink-site-toolbox');
                case '添加 SVG 图标支持':
                    return __('添加 SVG 图标支持', 'npcink-site-toolbox');
                case '进阶':
                    return __('进阶', 'npcink-site-toolbox');
                case 'XSS 风险':
                    return __('XSS 风险', 'npcink-site-toolbox');
                case '上传文件重命名':
                    return __('上传文件重命名', 'npcink-site-toolbox');
                case '新生成图片使用 WebP':
                    return __('新生成图片使用 WebP', 'npcink-site-toolbox');
                case '媒体':
                    return __('媒体', 'npcink-site-toolbox');
                case '性能':
                    return __('性能', 'npcink-site-toolbox');
                case '添加作者筛选项':
                    return __('添加作者筛选项', 'npcink-site-toolbox');
                case '添加时间筛选项':
                    return __('添加时间筛选项', 'npcink-site-toolbox');
                case '各个列表显示链接 ID':
                    return __('各个列表显示链接 ID', 'npcink-site-toolbox');
                case '缩略图切换':
                    return __('缩略图切换', 'npcink-site-toolbox');
                case '阅读进度条':
                    return __('阅读进度条', 'npcink-site-toolbox');
                case '仅前台':
                    return __('仅前台', 'npcink-site-toolbox');
                case '两次评论间隔时间':
                    return __('两次评论间隔时间', 'npcink-site-toolbox');
                case '限制评论字数':
                    return __('限制评论字数', 'npcink-site-toolbox');
                case '禁止纯英文评论':
                    return __('禁止纯英文评论', 'npcink-site-toolbox');
                case '单篇文章仅限评论一次':
                    return __('单篇文章仅限评论一次', 'npcink-site-toolbox');
                case '敏感词过滤':
                    return __('敏感词过滤', 'npcink-site-toolbox');
                case '用户评论自助管理':
                    return __('用户评论自助管理', 'npcink-site-toolbox');
                case '需认证':
                    return __('需认证', 'npcink-site-toolbox');
                case 'REST API':
                    return __('REST API', 'npcink-site-toolbox');
                case '首图作特色图':
                    return __('首图作特色图', 'npcink-site-toolbox');
                case '关键词自动内链':
                    return __('关键词自动内链', 'npcink-site-toolbox');
                case '添加最后更新时间':
                    return __('添加最后更新时间', 'npcink-site-toolbox');
                case '未登录模糊文章内图片':
                    return __('未登录模糊文章内图片', 'npcink-site-toolbox');
                case '维护提示页':
                    return __('维护提示页', 'npcink-site-toolbox');
                case '默认缩略图':
                    return __('默认缩略图', 'npcink-site-toolbox');
                case '搜索频率限制':
                    return __('搜索频率限制', 'npcink-site-toolbox');
                case '仅登录用户可搜索':
                    return __('仅登录用户可搜索', 'npcink-site-toolbox');
                case '隐藏指定分类文章':
                    return __('隐藏指定分类文章', 'npcink-site-toolbox');
                case '隐藏指定标签文章':
                    return __('隐藏指定标签文章', 'npcink-site-toolbox');
                case '隐藏指定页面':
                    return __('隐藏指定页面', 'npcink-site-toolbox');
                case '首页 SEO':
                    return __('首页 SEO', 'npcink-site-toolbox');
                case '文章 SEO':
                    return __('文章 SEO', 'npcink-site-toolbox');
                case '分类 SEO 字段':
                    return __('分类 SEO 字段', 'npcink-site-toolbox');
                case '分类 SEO':
                    return __('分类 SEO', 'npcink-site-toolbox');
                case '标签 SEO':
                    return __('标签 SEO', 'npcink-site-toolbox');
                case '文章访问统计':
                    return __('文章访问统计', 'npcink-site-toolbox');
                case '屏蔽恶意关键词搜索':
                    return __('屏蔽恶意关键词搜索', 'npcink-site-toolbox');
                case '百度统计':
                    return __('百度统计', 'npcink-site-toolbox');
                case 'Google Analytics':
                    return __('Google Analytics', 'npcink-site-toolbox');
                case '必应统计':
                    return __('必应统计', 'npcink-site-toolbox');
                case '分类数据接口':
                    return __('分类数据接口', 'npcink-site-toolbox');
                case '备案与合规':
                    return __('备案与合规', 'npcink-site-toolbox');
                case '微信生态':
                    return __('微信生态', 'npcink-site-toolbox');
                case '评论安全':
                    return __('评论安全', 'npcink-site-toolbox');
                case '登录安全':
                    return __('登录安全', 'npcink-site-toolbox');
                case '对象存储 / OSS':
                    return __('对象存储 / OSS', 'npcink-site-toolbox');
                case 'SEO 检查助手':
                    return __('SEO 检查助手', 'npcink-site-toolbox');
                case '媒体库体检':
                    return __('媒体库体检', 'npcink-site-toolbox');
                case '搜索增强':
                    return __('搜索增强', 'npcink-site-toolbox');
                case '数据库清理优化':
                    return __('数据库清理优化', 'npcink-site-toolbox');
                case '高风险':
                    return __('高风险', 'npcink-site-toolbox');
                case '不可逆':
                    return __('不可逆', 'npcink-site-toolbox');
                default:
                    return $value;
            }
        }

        private static function translate_list($values) {
            if (!is_array($values)) {
                return array();
            }
            return array_map(array(__CLASS__, 'translate'), $values);
        }

        public static function reset_cache() {
            self::$registry = null;
        }
    }
}
