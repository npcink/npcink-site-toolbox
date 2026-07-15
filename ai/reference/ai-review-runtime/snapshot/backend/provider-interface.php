<?php
/**
 * AI 审核 Provider 抽象接口
 *
 * 所有审核 Provider 必须实现此接口。
 *
 * @since 2.3.0
 */
if (!interface_exists('MaBox_Ai_Provider_Interface')) {
    interface MaBox_Ai_Provider_Interface {

        /**
         * 审核文本内容
         *
         * @param string $text 待审核文本
         * @param array  $config Provider 配置
         * @return array ['is_safe' => bool, 'confidence' => float, 'reason' => string, 'risk_level' => string]
         */
        public function review($text, $config);

        /**
         * 获取 Provider 名称（用于后台显示）
         *
         * @return string
         */
        public function get_name();

        /**
         * 检查 Provider 是否可用（配置完整性验证）
         *
         * @param array $config
         * @return bool
         */
        public function is_available($config);
    }
}
