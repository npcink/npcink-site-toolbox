<?php

if (!class_exists('MaBox_Comment_Sensitive_Words')) {
    class MaBox_Comment_Sensitive_Words
    {
        private static $option;

        /**
         * 常见敏感词拼音变体映射
         * 用于识别用户通过拼音、缩写等方式规避敏感词的行为
         */
        private static $pinyin_variants = array(
            '微信' => array('vx', 'v信', '威信', '薇信', 'vxin', 'weixin'),
            '支付宝' => array('zfb', '支福宝', '芝麻信用'),
            'QQ' => array('qq', '扣扣', 'Q Q'),
            '淘宝' => array('tb', '掏宝', '桃宝'),
            '京东' => array('jd', '京東', '京dong'),
            '拼多多' => array('pdd', '拼夕夕', 'pin duo duo'),
            '抖音' => array('dy', '斗音', '抖阴'),
            '快手' => array('ks', '快守', '快 shou'),
            '小红书' => array('xhs', '红书', 'hong shu'),
            '微博' => array('wb', '围脖', 'wei bo'),
            '百度' => array('bd', '摆渡', 'bai du'),
            '谷歌' => array('gg', '谷哥', 'google'),
            'facebook' => array('fb', '脸书', 'fb'),
            'twitter' => array('tw', '推特', 'tui te'),
            'instagram' => array('ins', 'IG', 'insta'),
            'whatsapp' => array('wa', 'ws', 'whats app'),
            'telegram' => array('tg', '电报', 'telegram'),
            'youtube' => array('yt', '油管', 'you tube'),
            'netflix' => array('nf', '网飞', 'nai fei'),
        );

        public static function run($config)
        {
            self::$option = $config;
            add_filter('preprocess_comment', array(__CLASS__, 'check_comment'));
        }

        public static function check_comment($commentdata)
        {
            $content = isset($commentdata['comment_content']) ? $commentdata['comment_content'] : '';
            if (empty($content)) {
                return $commentdata;
            }

            $words = self::get_sensitive_words();
            if (empty($words)) {
                return $commentdata;
            }

            $action = MaBox_Admin::get_config(self::$option, 'sensitive_words_action', 'replace');
            $replace_char = MaBox_Admin::get_config(self::$option, 'sensitive_words_replace_char', '***');

            $all_words = self::expand_with_pinyin_variants($words);

            foreach ($all_words as $word) {
                if (empty($word)) {
                    continue;
                }
                if (mb_stripos($content, $word) !== false) {
                    if ($action === 'block') {
                        wp_die(
                            esc_html__('您的评论包含敏感词，请修改后重新提交。'),
                            esc_html__('评论被拦截'),
                            array('back_link' => true)
                        );
                    }
                    $content = str_ireplace($word, $replace_char, $content);
                }
            }

            $commentdata['comment_content'] = $content;
            return $commentdata;
        }

        /**
         * 将敏感词列表扩展为包含拼音变体的完整列表
         */
        private static function expand_with_pinyin_variants($words)
        {
            $expanded = $words;

            foreach ($words as $word) {
                if (isset(self::$pinyin_variants[$word])) {
                    $expanded = array_merge($expanded, self::$pinyin_variants[$word]);
                }

                foreach (self::$pinyin_variants as $original => $variants) {
                    if (in_array(strtolower($word), array_map('strtolower', $variants))) {
                        $expanded[] = $original;
                    }
                }
            }

            return array_unique($expanded);
        }

        private static function get_sensitive_words()
        {
            $raw = MaBox_Admin::get_config(self::$option, 'sensitive_words', '');
            if (empty($raw)) {
                return array();
            }
            $lines = explode("\n", trim($raw));
            return array_filter(array_map('trim', $lines));
        }
    }
}
