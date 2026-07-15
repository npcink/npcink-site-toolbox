<?php
/**
 * 本地规则引擎（降级方案）
 *
 * 当无 AI API 配置时自动启用，基于关键词+正则匹配。
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Ai_Provider_Local_Rules')) {
    class MaBox_Ai_Provider_Local_Rules implements MaBox_Ai_Provider_Interface {

        public function get_name() {
            return '本地规则引擎';
        }

        public function is_available($config) {
            return !empty($config['local_rules_enabled']);
        }

        public function review($text, $config) {
            $result = array(
                'is_safe'    => true,
                'confidence' => 0.5,
                'reason'     => '',
                'risk_level' => 'safe',
            );

            $keywords = !empty($config['local_keywords']) ? explode("\n", trim($config['local_keywords'])) : array();
            $keywords = array_filter(array_map('trim', $keywords));

            $regex_patterns = !empty($config['local_regex']) ? explode("\n", trim($config['local_regex'])) : array();
            $regex_patterns = array_filter(array_map('trim', $regex_patterns));

            $strict = !empty($config['strict_mode']);
            $matched = array();

            foreach ($keywords as $keyword) {
                if (mb_stripos($text, $keyword) !== false) {
                    $matched[] = $keyword;
                }
            }

            foreach ($regex_patterns as $pattern) {
                if (@preg_match($pattern, $text)) {
                    $matched[] = '[正则] ' . $pattern;
                }
            }

            if (!empty($matched)) {
                $result['is_safe']    = false;
                $result['reason']     = '命中规则：' . implode('、', $matched);
                $result['risk_level'] = $strict ? 'high' : 'medium';
                $result['confidence'] = $strict ? 0.9 : 0.7;
            }

            return $result;
        }
    }
}
